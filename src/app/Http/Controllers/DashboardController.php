<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userDeptId = $user?->department_id;
        $canViewAll = $user->hasPermission('users.manage');

        // Month filtering — admins can browse historical months; standard users locked to current
        $canFilterHistorical = $canViewAll;
        try {
            $monthParam = ($canFilterHistorical && $request->filled('month'))
                ? $request->input('month')
                : now()->format('Y-m');
            $startOfMonth = Carbon::parse($monthParam . '-01', 'Asia/Manila')->startOfMonth();
        } catch (\Exception $e) {
            $startOfMonth = now('Asia/Manila')->startOfMonth();
        }
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        // Shared scope: user sees documents they sent OR currently hold
        $deptScope = function ($query) use ($userDeptId) {
            if ($userDeptId) {
                $query->where(function ($q) use ($userDeptId) {
                    $q->where('sender_department_id', $userDeptId)
                      ->orWhere('current_department_id', $userDeptId);
                });
            }
        };

        // Unified base query — single source of truth for KPI cards + status chart
        $baseQuery = DB::table('documents');
        $deptScope($baseQuery);
        $baseQuery->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        // 1. KPI Metric Cards
        $totalDocuments = (clone $baseQuery)->count();
        $pendingDocuments = (clone $baseQuery)->where('status', 'pending_transfer')->count();
        $inTransitDocuments = (clone $baseQuery)->where('status', 'in_transit')->count();
        $receivedInMonth = DB::table('document_events')
            ->where('event_type', 'receipt')
            ->where('department_id', $userDeptId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // 2. Status Distribution Doughnut — dynamically extracted, no hardcoded keys
        $statusMetrics = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 3. Department Bar Distribution — scoped to selected month via withCount callback
        $departmentDistribution = Department::query()
            ->where('is_active', true)
            ->withCount(['currentDocuments as count' => function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('documents.created_at', [$startOfMonth, $endOfMonth]);
            }])
            ->orderBy('name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();

        // 4. Recent Activity Stream — scoped to user's department + selected month
        $activityQuery = DB::table('document_events')
            ->join('documents', 'document_events.document_id', '=', 'documents.id')
            ->join('users', 'document_events.user_id', '=', 'users.id')
            ->where('event_type', '!=', 'route_defined')
            ->whereBetween('document_events.created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'document_events.event_label',
                'document_events.note',
                'document_events.created_at',
                'documents.title',
                'documents.document_number',
                'users.name as user_name'
            );
        if ($userDeptId) {
            $activityQuery->where('document_events.department_id', $userDeptId);
        }
        $activityFeed = $activityQuery
            ->latest('document_events.created_at')
            ->take(10)
            ->get();

        // 5. Velocity Analytics — per-hop dwell time, scoped to selected month
        $avgDwellHours = DB::table('document_routes')
            ->join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->whereNotNull('document_routes.received_at')
            ->whereBetween('document_routes.created_at', [$startOfMonth, $endOfMonth])
            ->when(!$canViewAll && $userDeptId, fn ($q) => $q->where('document_routes.department_id', $userDeptId))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, document_routes.created_at, document_routes.received_at)) as avg_hours'))
            ->value('avg_hours');
        $avgDwellHours = $avgDwellHours ? round((float) $avgDwellHours, 1) : 0;

        // 6. Overdue Detection — live operational state, intentionally UNscoped by month
        $activeRoutes = \App\Models\DocumentRoute::join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->select('document_routes.*', 'documents.document_type_id')
            ->where('document_routes.status', 'current')
            ->whereNotIn('documents.status', ['completed', 'cancelled', 'rejected'])
            ->whereBetween('document_routes.created_at', [$startOfMonth, $endOfMonth])
            ->when(!$canViewAll && $userDeptId, fn ($q) => $q->where('document_routes.department_id', $userDeptId))
            ->get();

        $departmentSlas = \App\Models\DepartmentDocumentSla::get()
            ->groupBy('department_id')
            ->map(fn($items) => $items->keyBy('document_type_id'));

        $documentTypes = \App\Models\DocumentType::get()->keyBy('id');

        $dynamicOverdueCount = 0;
        $now = Carbon::now('Asia/Manila');

        foreach ($activeRoutes as $route) {
            $deptId = $route->department_id;
            $docTypeId = $route->document_type_id;
            $allowedMinutes = 1440;

            if (isset($departmentSlas[$deptId][$docTypeId])) {
                $allowedMinutes = $departmentSlas[$deptId][$docTypeId]->processing_time_minutes;
            } elseif (isset($documentTypes[$docTypeId]) && !is_null($documentTypes[$docTypeId]->default_processing_time)) {
                $allowedMinutes = $documentTypes[$docTypeId]->default_processing_time;
            }

            $routeArrival = Carbon::parse($route->created_at, 'Asia/Manila');
            $deadline = $routeArrival->addMinutes($allowedMinutes);

            if ($now->greaterThan($deadline)) {
                $dynamicOverdueCount++;
            }
        }

        $overdueCount = $dynamicOverdueCount;

        // 7. Average completion time — scoped by completed_at within selected month
        $avgCompletionHours = DB::table('documents')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('uploaded_at')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->when(!$canViewAll && $userDeptId, fn ($q) => $q->where(function ($sub) use ($userDeptId) {
                $sub->where('sender_department_id', $userDeptId)
                    ->orWhere('current_department_id', $userDeptId);
            }))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, uploaded_at, completed_at)) as avg_hours'))
            ->value('avg_hours');
        $avgCompletionHours = $avgCompletionHours ? round((float) $avgCompletionHours, 1) : 0;

        // 8. Unread notifications count from the notifications table
        $unreadNotificationsCount = DB::table('notifications')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return view('dashboard', compact(
            'totalDocuments', 'pendingDocuments', 'inTransitDocuments', 'receivedInMonth',
            'statusMetrics', 'departmentDistribution', 'activityFeed',
            'avgDwellHours', 'overdueCount', 'avgCompletionHours', 'unreadNotificationsCount',
            'startOfMonth'
        ));
    }
}
