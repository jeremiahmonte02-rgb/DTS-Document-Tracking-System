<?php

namespace App\Http\Controllers;

use App\Exports\DashboardSummaryExport;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $userDeptId = $user?->department_id;
        $canViewAll = $user->hasPermission('users.manage');

        // Month filtering — admins can browse historical months; standard users locked to current
        [$startOfMonth, $endOfMonth] = $this->resolveMonthRange($request, $canViewAll);

        $stats = $this->buildDashboardStats($userDeptId, $canViewAll, $startOfMonth, $endOfMonth);

        // Unread notification count is injected globally by NotificationServiceProvider
        return view('dashboard', array_merge($stats, [
            'startOfMonth' => $startOfMonth,
        ]));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $user = auth()->user();
        $userDeptId = $user?->department_id;
        $canViewAll = $user->hasPermission('users.manage');

        // Same admin-only historical-month restriction as index(): non-admins
        // silently get the current month even if ?month= is supplied.
        [$startOfMonth, $endOfMonth] = $this->resolveMonthRange($request, $canViewAll);

        $stats = $this->buildDashboardStats($userDeptId, $canViewAll, $startOfMonth, $endOfMonth);

        $departmentName = $userDeptId
            ? (Department::where('id', $userDeptId)->value('name') ?? 'N/A')
            : 'N/A';

        return Excel::download(
            new DashboardSummaryExport($stats, [
                'month' => $startOfMonth->format('F Y'),
                'scope' => $departmentName,
            ]),
            'dashboard-summary-' . $startOfMonth->format('Y-m') . '.xlsx'
        );
    }

    private function resolveMonthRange(Request $request, bool $canFilterHistorical): array
    {
        try {
            $monthParam = ($canFilterHistorical && $request->filled('month'))
                ? $request->input('month')
                : now()->format('Y-m');
            $startOfMonth = Carbon::parse($monthParam . '-01', 'Asia/Manila')->startOfMonth();
        } catch (\Exception $e) {
            $startOfMonth = now('Asia/Manila')->startOfMonth();
        }
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        return [$startOfMonth, $endOfMonth];
    }

    private function buildDashboardStats(?int $userDeptId, bool $canViewAll, Carbon $startOfMonth, Carbon $endOfMonth): array
    {

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
            ->select(
                'document_routes.*',
                'documents.document_type_id',
                'documents.document_number',
                'documents.title',
                'documents.sender_department_id',
                'documents.current_department_id',
                'documents.status as document_status',
                'documents.created_at as document_created_at'
            )
            ->where('document_routes.status', 'current')
            ->whereNotIn('documents.status', ['completed', 'cancelled', 'rejected'])
            ->whereBetween('document_routes.created_at', [$startOfMonth, $endOfMonth])
            ->when(!$canViewAll && $userDeptId, fn ($q) => $q->where('document_routes.department_id', $userDeptId))
            ->get();

        $departmentSlas = \App\Models\DepartmentDocumentSla::get()
            ->groupBy('department_id')
            ->map(fn($items) => $items->keyBy('document_type_id'));

        $documentTypes = \App\Models\DocumentType::get()->keyBy('id');

        $departmentsMap = Department::pluck('name', 'id');

        $dynamicOverdueCount = 0;
        $overdueDocuments = [];
        $now = Carbon::now('Asia/Manila');

        foreach ($activeRoutes as $route) {
            $deptId = $route->department_id;
            $docTypeId = $route->document_type_id;
            $allowedMinutes = \App\Services\SlaResolver::DEFAULT_FALLBACK_MINUTES;

            if (isset($departmentSlas[$deptId][$docTypeId])) {
                $allowedMinutes = $departmentSlas[$deptId][$docTypeId]->processing_time_minutes;
            } elseif (isset($documentTypes[$docTypeId]) && !is_null($documentTypes[$docTypeId]->default_processing_time)) {
                $allowedMinutes = $documentTypes[$docTypeId]->default_processing_time;
            }

            $routeArrival = Carbon::parse($route->created_at, 'Asia/Manila');
            $deadline = $routeArrival->addMinutes($allowedMinutes);

            if ($now->greaterThan($deadline)) {
                $dynamicOverdueCount++;
                $overdueDocuments[] = [
                    'document_number' => $route->document_number,
                    'title' => $route->title,
                    'document_type' => $documentTypes[$docTypeId]->name ?? 'N/A',
                    'sender_department' => $departmentsMap[$route->sender_department_id] ?? 'N/A',
                    'current_department' => $departmentsMap[$route->current_department_id] ?? ($departmentsMap[$deptId] ?? 'N/A'),
                    'status' => ucwords(str_replace('_', ' ', $route->document_status)),
                    'time_at_step' => $routeArrival->diffForHumans($now, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2),
                    'uploaded_at' => Carbon::parse($route->document_created_at, 'Asia/Manila')->format('Y-m-d H:i'),
                ];
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

        return [
            'totalDocuments' => $totalDocuments,
            'pendingDocuments' => $pendingDocuments,
            'inTransitDocuments' => $inTransitDocuments,
            'receivedInMonth' => $receivedInMonth,
            'statusMetrics' => $statusMetrics,
            'departmentDistribution' => $departmentDistribution,
            'activityFeed' => $activityFeed,
            'avgDwellHours' => $avgDwellHours,
            'overdueCount' => $overdueCount,
            'overdueDocuments' => $overdueDocuments,
            'avgCompletionHours' => $avgCompletionHours,
        ];
    }
}
