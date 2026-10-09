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

        // 1. KPI Metric Cards — each card's document_number list is built ONCE
        // here (single source of truth); the displayed number is derived from
        // the list length so a card number and its "Document Lists" export
        // column can never differ. Filters match the pre-existing card
        // definitions exactly (same status, same created_at month boundary in
        // Asia/Manila, same viewer-department scope).
        $totalDocumentNumbers = (clone $baseQuery)->pluck('documents.document_number')->all();
        sort($totalDocumentNumbers, SORT_STRING);
        $pendingDocumentNumbers = (clone $baseQuery)->where('status', 'pending_transfer')->pluck('documents.document_number')->all();
        sort($pendingDocumentNumbers, SORT_STRING);
        $inTransitDocumentNumbers = (clone $baseQuery)->where('status', 'in_transit')->pluck('documents.document_number')->all();
        sort($inTransitDocumentNumbers, SORT_STRING);
        // RECEIVED IN MONTH counts DISTINCT documents with a receipt event in
        // the month (same event_type/department/created_at filters as before;
        // documents joined only to read document_number, no extra filters).
        // A document created in an earlier month still counts when received
        // in the selected month; a document received twice counts once.
        $receivedDocumentNumbers = DB::table('document_events')
            ->join('documents', 'document_events.document_id', '=', 'documents.id')
            ->where('document_events.event_type', 'receipt')
            ->where('document_events.department_id', $userDeptId)
            ->whereBetween('document_events.created_at', [$startOfMonth, $endOfMonth])
            ->distinct()
            ->pluck('documents.document_number')
            ->all();
        sort($receivedDocumentNumbers, SORT_STRING);

        $totalDocuments = count($totalDocumentNumbers);
        $pendingDocuments = count($pendingDocumentNumbers);
        $inTransitDocuments = count($inTransitDocumentNumbers);
        $receivedInMonth = count($receivedDocumentNumbers);

        // 2. Status Distribution Doughnut — dynamically extracted, no hardcoded keys
        $statusMetrics = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 3. Department Bar Distribution — organization-wide (all active
        // departments), intentionally NOT restricted to the viewer's own
        // department. Deliberate single-chart exception: every other card on
        // this dashboard remains viewer-department-scoped.
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

        // 5. Velocity Analytics — apportioned lifecycle-length, scoped to the
        // viewer's own department (no admin org-wide exception). Same formula
        // as the Auditor dashboard: average time between a document's
        // uploaded_at and completed_at, DIVIDED by the number of completed
        // route steps. Since we don't have a 'released_at' timestamp on the
        // route row, per-step dwell is apportioned from the whole lifecycle.
        $completedDocumentsData = DB::table('documents')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('uploaded_at')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->when($userDeptId, fn ($q) => $q->where(function ($sub) use ($userDeptId) {
                $sub->where('sender_department_id', $userDeptId)
                    ->orWhere('current_department_id', $userDeptId);
            }))
            ->get();

        $totalDwellHours = 0;
        $totalCompletedSteps = 0;

        foreach ($completedDocumentsData as $doc) {
            $lifecycleHours = Carbon::parse($doc->uploaded_at)->diffInHours(Carbon::parse($doc->completed_at));

            // How many steps did this document go through?
            $stepCount = DB::table('document_routes')
                ->where('document_id', $doc->id)
                ->whereNotNull('received_at') // Count steps that were actually processed
                ->count();

            if ($stepCount > 0) {
                // If a document took 100 hours to complete across 4 steps, the average dwell per department is 25 hours.
                $totalDwellHours += ($lifecycleHours / $stepCount);
                $totalCompletedSteps++;
            }
        }

        $avgDwellHours = $totalCompletedSteps > 0 ? round($totalDwellHours / $totalCompletedSteps, 1) : 0;

        // 6. Overdue Detection — live operational state, intentionally UNscoped
        // by month (a genuinely-overdue-right-now document counts regardless
        // of when its route row was created) and scoped to the viewer's own
        // department (no admin org-wide exception)
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
            ->when($userDeptId, fn ($q) => $q->where('document_routes.department_id', $userDeptId))
            ->get();

        $documentTypes = \App\Models\DocumentType::get()->keyBy('id');

        $departmentsMap = Department::pluck('name', 'id');

        $policies = \App\Models\DocumentRoutingPolicy::whereIn('document_type_id', $activeRoutes->pluck('document_type_id')->unique())
            ->get()->keyBy('document_type_id');

        $overdueDocuments = [];
        $now = Carbon::now('Asia/Manila');

        foreach ($activeRoutes as $route) {
            $deptId = $route->department_id;
            $docTypeId = $route->document_type_id;
            $predefinedRoute = isset($policies[$docTypeId]) ? ($policies[$docTypeId]->predefined_route ?? null) : null;
            $allowedMinutes = \App\Services\SlaResolver::resolve((int) $deptId, (int) $docTypeId, $route->route_order ?? null, $predefinedRoute);

            $routeArrival = Carbon::parse($route->created_at, 'Asia/Manila');
            $deadline = $routeArrival->addMinutes($allowedMinutes);

            if ($now->greaterThan($deadline)) {
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

        // Overdue document_number list is the single source of truth for both
        // the card number and the export column: reuse this loop's values,
        // deduplicated (one row per document even if it ever holds two
        // 'current' steps), sorted ascending.
        $overdueDocumentNumbers = array_values(array_unique(array_column($overdueDocuments, 'document_number')));
        sort($overdueDocumentNumbers, SORT_STRING);

        $overdueCount = count($overdueDocumentNumbers);

        // 7. Average completion time — scoped by completed_at within selected
        // month and to the viewer's own department (no admin org-wide exception)
        $avgCompletionHours = DB::table('documents')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('uploaded_at')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->when($userDeptId, fn ($q) => $q->where(function ($sub) use ($userDeptId) {
                $sub->where('sender_department_id', $userDeptId)
                    ->orWhere('current_department_id', $userDeptId);
            }))
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, uploaded_at, completed_at)) as avg_hours'))
            ->value('avg_hours');
        $avgCompletionHours = $avgCompletionHours ? round((float) $avgCompletionHours, 1) : 0;

        // Column-per-card source for the "Document Lists" export sheet.
        // NEW key only — every pre-existing key above is untouched.
        $documentLists = [
            'TOTAL DOCUMENT' => $totalDocumentNumbers,
            'PENDING TRANSFER' => $pendingDocumentNumbers,
            'RECEIVED IN MONTH' => $receivedDocumentNumbers,
            'IN-TRANSIT' => $inTransitDocumentNumbers,
            'OVERDUED' => $overdueDocumentNumbers,
        ];

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
            'documentLists' => $documentLists,
        ];
    }
}
