<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userDeptId = $user?->department_id;
        $canViewAll = $user->hasPermission('users.manage');

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

        // 1. KPI Metric Cards
        $totalDocuments = (clone $baseQuery)->count();
        $pendingDocuments = (clone $baseQuery)->where('status', 'pending_transfer')->count();
        $inTransitDocuments = (clone $baseQuery)->where('status', 'in_transit')->count();
        $receivedToday = DB::table('document_events')
            ->where('event_type', 'receipt')
            ->where('department_id', $userDeptId)
            ->whereDate('created_at', Carbon::today('Asia/Manila'))
            ->count();

        // 2. Status Distribution Doughnut — dynamically extracted, no hardcoded keys
        //    Σ values($statusMetrics) === $totalDocuments (Δ1 fixed)
        $statusMetrics = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 3. Department Bar Distribution — global workload with LEFT JOIN semantics
        //    Uses Eloquent withCount on the Department model so every active department
        //    appears on the X-axis regardless of document count (Δ2, Δ3 fixed)
        $departmentDistribution = Department::query()
            ->where('is_active', true)
            ->withCount(['currentDocuments as count'])
            ->orderBy('name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();

        // 4. Recent Activity Stream — scoped to user's department
        $activityQuery = DB::table('document_events')
            ->join('documents', 'document_events.document_id', '=', 'documents.id')
            ->join('users', 'document_events.user_id', '=', 'users.id')
            ->where('event_type', '!=', 'route_defined')
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

        // 5. Velocity Analytics — per-hop dwell time
        $avgDwellHours = DB::table('document_routes')
            ->join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->whereNotNull('document_routes.received_at')
            ->when(!$canViewAll && $userDeptId, fn ($q) => $q->where('document_routes.department_id', $userDeptId))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, document_routes.created_at, document_routes.received_at)) as avg_hours'))
            ->value('avg_hours');
        $avgDwellHours = $avgDwellHours ? round((float) $avgDwellHours, 1) : 0;

        // 6. Overdue Detection — current route steps pending > 48 hours
        $overdueThreshold = Carbon::now('Asia/Manila')->subHours(48);
        $overdueCount = DB::table('document_routes')
            ->join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->where('document_routes.status', 'current')
            ->where('document_routes.created_at', '<', $overdueThreshold)
            ->whereNotIn('documents.status', ['completed', 'cancelled', 'rejected'])
            ->when(!$canViewAll && $userDeptId, fn ($q) => $q->where('document_routes.department_id', $userDeptId))
            ->count();

        // 7. Average completion time (recently completed docs)
        $avgCompletionHours = DB::table('documents')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('uploaded_at')
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
            'totalDocuments', 'pendingDocuments', 'inTransitDocuments', 'receivedToday',
            'statusMetrics', 'departmentDistribution', 'activityFeed',
            'avgDwellHours', 'overdueCount', 'avgCompletionHours', 'unreadNotificationsCount'
        ));
    }
}
