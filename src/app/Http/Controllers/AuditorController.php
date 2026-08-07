<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentEvent;
use App\Models\DocumentRoutingPolicy;
use App\Models\DocumentType;
use App\Models\DepartmentDocumentSla;
use App\Models\DocumentIssue;
use App\Enums\ActivityCode;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditorController extends Controller
{
    /**
     * Render the analytical audit dashboard with global metrics.
     */
    public function dashboard(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        $now = \Carbon\Carbon::now('Asia/Manila');

        // Month filtering — auditors can always browse historical months
        try {
            $monthParam = $request->filled('month')
                ? $request->input('month')
                : now()->format('Y-m');
            $startOfMonth = \Carbon\Carbon::parse($monthParam . '-01', 'Asia/Manila')->startOfMonth();
        } catch (\Exception $e) {
            $startOfMonth = now('Asia/Manila')->startOfMonth();
        }
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        // 1. Global KPI cards — scoped to selected month
        $totalDocuments = DB::table('documents')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
        $pendingDocuments = DB::table('documents')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'pending_transfer')
            ->count();
        $inTransitDocuments = DB::table('documents')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'in_transit')
            ->count();
        $completedDocuments = DB::table('documents')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['received', 'completed'])
            ->count();
        $rejectedDocuments = DB::table('documents')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'rejected')
            ->count();

        // 2. SLA-overdue count — live operational state, intentionally UNscoped by month
        $activeRoutes = \App\Models\DocumentRoute::join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->select('document_routes.*', 'documents.document_type_id')
            ->where('document_routes.status', 'current')
            ->whereNotIn('documents.status', ['completed', 'cancelled', 'rejected'])
            ->whereBetween('document_routes.created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $departmentSlas = \App\Models\DepartmentDocumentSla::get()
            ->groupBy('department_id')
            ->map(fn($items) => $items->keyBy('document_type_id'));

        $documentTypes = \App\Models\DocumentType::get()->keyBy('id');

        $policies = \App\Models\DocumentRoutingPolicy::whereIn('document_type_id', $activeRoutes->pluck('document_type_id')->unique())
            ->get()->keyBy('document_type_id');

        $overdueCount = 0;
        foreach ($activeRoutes as $route) {
            $predefinedRoute = isset($policies[$route->document_type_id]) ? $policies[$route->document_type_id]->predefined_route : null;
            $allowedMinutes = \App\Services\SlaResolver::resolve($route->department_id, $route->document_type_id, $route->route_order ?? null, $predefinedRoute);
            $deadline = \Carbon\Carbon::parse($route->updated_at, 'Asia/Manila')->copy()->addMinutes($allowedMinutes);
            if ($now->greaterThan($deadline)) {
                $overdueCount++;
            }
        }

        // 3. Status distribution — scoped to selected month
        $statusMetrics = DB::table('documents')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 4. Department workload — scoped to selected month via withCount callback
        $departmentDistribution = Department::query()
            ->where('is_active', true)
            ->withCount(['currentDocuments as count' => function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('documents.created_at', [$startOfMonth, $endOfMonth]);
            }])
            ->orderBy('name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();

        // 5. Issue analytics — scoped to selected month
        $totalIssues = DocumentIssue::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $openIssues = DocumentIssue::whereBetween('created_at', [$startOfMonth, $endOfMonth])->where('status', 'open')->count();
        $resolvedIssues = DocumentIssue::whereBetween('created_at', [$startOfMonth, $endOfMonth])->where('status', 'resolved')->count();

        // SLA bottleneck distribution — overdue steps grouped by department (scoped to selected month)
        $departmentsMap = \App\Models\Department::pluck('name', 'id');

        $bottleneckRoutes = \App\Models\DocumentRoute::join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->select('document_routes.*', 'documents.document_type_id')
            ->whereIn('document_routes.status', ['current', 'received'])
            ->whereNotIn('documents.status', ['cancelled', 'rejected', 'completed'])
            ->whereBetween('document_routes.created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $routePolicies = \App\Models\DocumentRoutingPolicy::whereIn('document_type_id', $bottleneckRoutes->pluck('document_type_id')->unique())
            ->get()->keyBy('document_type_id');

        $slaBottlenecksByDept = [];

        foreach ($bottleneckRoutes as $route) {
            $predefinedRoute = isset($routePolicies[$route->document_type_id]) ? $routePolicies[$route->document_type_id]->predefined_route : null;
            $allowedMinutes = \App\Services\SlaResolver::resolve($route->department_id, $route->document_type_id, $route->route_order ?? null, $predefinedRoute);

            $referenceTime = ($route->status === 'received' && $route->received_at)
                ? \Carbon\Carbon::parse($route->received_at, 'Asia/Manila')
                : \Carbon\Carbon::parse($route->updated_at, 'Asia/Manila');

            $elapsed = abs($now->diffInMinutes($referenceTime));

            if ($elapsed > $allowedMinutes) {
                $deptName = $departmentsMap[$route->department_id] ?? 'Unknown';
                $slaBottlenecksByDept[$deptName] = ($slaBottlenecksByDept[$deptName] ?? 0) + 1;
            }
        }

        arsort($slaBottlenecksByDept); // Sort highest bottlenecks first

        // Top departments by reported issues — scoped to selected month
        $issuesByDepartment = DocumentIssue::join('departments', 'document_issues.assigned_department_id', '=', 'departments.id')
            ->whereBetween('document_issues.created_at', [$startOfMonth, $endOfMonth])
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.name')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'name')
            ->toArray();

        // 6. Average dwell time — scoped to selected month
        // Redefined: The average time a document spends in the "current" status (from receipt to release)
        // We calculate this by looking at steps that transitioned FROM 'current' TO 'received' (or terminal status)
        // Since we don't have a 'released_at' timestamp on the route row, we use the timestamp
        // of the *subsequent* step's received_at, or the document's completed_at/rejected_at.
        // For simplicity and accuracy without complex joins, we will calculate the average time
        // between a document's uploaded_at and completed_at, DIVIDED by the number of completed route steps.

        $completedDocumentsData = DB::table('documents')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('uploaded_at')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalDwellHours = 0;
        $totalCompletedSteps = 0;

        foreach ($completedDocumentsData as $doc) {
            $lifecycleHours = \Carbon\Carbon::parse($doc->uploaded_at)->diffInHours(\Carbon\Carbon::parse($doc->completed_at));

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

        // 7. Average completion time — scoped by completed_at within selected month
        $avgCompletionHours = DB::table('documents')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('uploaded_at')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, uploaded_at, completed_at)) as avg_hours'))
            ->value('avg_hours');
        $avgCompletionHours = $avgCompletionHours ? round((float) $avgCompletionHours, 1) : 0;

        // 8. Recent activity feed — scoped to selected month
        $activityFeed = DB::table('document_events')
            ->join('documents', 'document_events.document_id', '=', 'documents.id')
            ->join('users', 'document_events.user_id', '=', 'users.id')
            ->leftJoin('departments', 'document_events.department_id', '=', 'departments.id')
            ->where('document_events.event_type', '!=', 'route_defined')
            ->whereBetween('document_events.created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'document_events.event_type',
                'document_events.note',
                'document_events.created_at',
                'documents.title',
                'documents.document_number',
                'users.name as user_name',
                'departments.name as department_name'
            )
            ->latest('document_events.created_at')
            ->take(12)
            ->get();

        return view('audit.dashboard', compact(
            'totalDocuments', 'pendingDocuments', 'inTransitDocuments',
            'completedDocuments', 'rejectedDocuments', 'overdueCount',
            'statusMetrics', 'departmentDistribution',
            'totalIssues', 'openIssues', 'resolvedIssues',
            'slaBottlenecksByDept', 'issuesByDepartment',
            'avgDwellHours', 'avgCompletionHours', 'activityFeed',
            'startOfMonth'
        ));
    }

    /**
     * Render the static HTML view frame shell.
     */
    public function documents()
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        // Fetch document types strictly to populate the dropdown filter options
        $documentTypes = DB::table('document_types')->select('id', 'name')->get();

        return view('audit.documents', compact('documentTypes'));
    }

    /**
     * Pure JSON API endpoint handling asynchronous document queries.
     */
    public function getDocumentData(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Pre-calculate overdue document IDs across ALL route steps (current + received)
        $now = \Carbon\Carbon::now('Asia/Manila');
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $allRoutes = \App\Models\DocumentRoute::join('documents', 'document_routes.document_id', '=', 'documents.id')
            ->select('document_routes.*', 'documents.document_type_id', 'documents.created_at as document_created_at')
            ->whereIn('document_routes.status', ['current', 'received'])
            ->whereNotIn('documents.status', ['cancelled', 'rejected'])
            ->get();

        $policies = \App\Models\DocumentRoutingPolicy::whereIn('document_type_id', $allRoutes->pluck('document_type_id')->unique())
            ->get()->keyBy('document_type_id');

        $overdueDocumentIds = [];

        foreach ($allRoutes as $route) {
            $predefinedRoute = isset($policies[$route->document_type_id]) ? $policies[$route->document_type_id]->predefined_route : null;
            $allowedMinutes = \App\Services\SlaResolver::resolve($route->department_id, $route->document_type_id, $route->route_order ?? null, $predefinedRoute);

            if ($route->status === 'received') {
                $referenceTime = $route->received_at ? \Carbon\Carbon::parse($route->received_at, 'Asia/Manila') : \Carbon\Carbon::parse($route->updated_at, 'Asia/Manila');
                $elapsed = abs($now->diffInMinutes($referenceTime));
            } else {
                $elapsed = abs($now->diffInMinutes(\Carbon\Carbon::parse($route->updated_at, 'Asia/Manila')));
            }

            \Log::info('Overdue Debug', [
                'doc_id'     => $route->document_id,
                'status'     => $route->status,
                'route_order'=> $route->route_order,
                'elapsed'    => $elapsed,
                'allowed'    => $allowedMinutes,
                'is_overdue' => $elapsed > $allowedMinutes,
            ]);

            if ($elapsed > $allowedMinutes) {
                $overdueDocumentIds[] = $route->document_id;
            }
        }

        $overdueDocumentIds = array_unique($overdueDocumentIds);

        $monthlyOverdueCount = DB::table('documents')
            ->whereIn('id', $overdueDocumentIds)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        $query = DB::table('documents')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments as sender_dept', 'documents.sender_department_id', '=', 'sender_dept.id')
            ->leftJoin('departments as current_dept', 'documents.current_department_id', '=', 'current_dept.id')
            ->select([
                'documents.id',
                'documents.document_number',
                'documents.title',
                'documents.status',
                'documents.created_at as date_created',
                DB::raw('document_types.name as document_type_name'),
                DB::raw('sender_dept.name as origin_department'),
                DB::raw('current_dept.name as current_department')
            ]);

        // Reusable filter application used for both row data and counts
        $applyFilters = function ($q) use ($request, $overdueDocumentIds) {
            if ($request->filled('search')) {
                $search = $request->search;
                $q->where(function ($sq) use ($search) {
                    $sq->where('documents.title', 'like', '%' . $search . '%')
                       ->orWhere('documents.document_number', 'like', '%' . $search . '%');
                });
            }
            if ($request->filled('type')) {
                $q->where('documents.document_type_id', $request->type);
            }
            if ($request->filled('date')) {
                $q->whereDate('documents.created_at', $request->date);
            }
            if ($request->filled('status')) {
                $q->where('documents.status', str_replace(' ', '_', strtolower($request->status)));
            }
            if ($request->filled('overdue') && $request->overdue == '1') {
                $q->whereIn('documents.id', $overdueDocumentIds);
            }
        };

        $applyFilters($query);

        $documents = $query->orderBy('documents.created_at', 'desc')->get();

        // Inject is_overdue flag into each document row
        $documents->transform(function ($doc) use ($overdueDocumentIds) {
            $doc->is_overdue = in_array($doc->id, $overdueDocumentIds);
            return $doc;
        });

        // Aggregated counts scoped to the same filter context
        $countQuery = DB::table('documents');
        $applyFilters($countQuery);

        $counts = [
            'total'          => (clone $countQuery)->count(),
            'pending'        => (clone $countQuery)->where('status', 'pending_transfer')->count(),
            'in_transit'     => (clone $countQuery)->where('status', 'in_transit')->count(),
            'completed'      => (clone $countQuery)->whereIn('status', ['received', 'completed'])->count(),
            'monthly_overdue' => $monthlyOverdueCount,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $documents,
            'counts' => $counts
        ]);
    }

    /**
     * Render the Department Management view shell.
     */
    public function departments()
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        return view('audit.departments');
    }

    /**
     * Pure JSON API endpoint for department data queries.
     */
    public function getDepartmentsData(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Department::select('id', 'name', 'code', 'description', 'is_active', 'created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $departments = $query->orderBy('name', 'asc')->paginate(10);

        $total = Department::count();
        $activeCount = Department::where('is_active', true)->count();
        $inactiveCount = Department::where('is_active', false)->count();

        return response()->json([
            'status' => 'success',
            'data' => $departments->items(),
            'pagination' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
            'counts' => [
                'total' => $total,
                'active' => $activeCount,
                'inactive' => $inactiveCount,
            ]
        ]);
    }

    /**
     * Create a new department record.
     */
    public function storeDepartment(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:departments,code',
            'description' => 'nullable|string',
        ]);

        $department = Department::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        ActivityLogger::log(ActivityCode::DEPT_CREATED, "Created department {$department->name}", $department);

        return response()->json([
            'status' => 'success',
            'message' => "Department {$department->name} created successfully.",
            'data' => $department,
        ]);
    }

    /**
     * Update an existing department record.
     */
    public function updateDepartment(Request $request, $id)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
        ]);

        $department->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'description' => $validated['description'] ?? $department->description,
        ]);

        ActivityLogger::log(ActivityCode::DEPT_UPDATED, "Updated department {$department->name}", $department);

        return response()->json([
            'status' => 'success',
            'message' => "Department {$department->name} updated successfully.",
            'data' => $department->fresh(),
        ]);
    }

    /**
     * Toggle the is_active flag on a department.
     */
    public function toggleDepartment($id)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::findOrFail($id);
        $department->is_active = !$department->is_active;
        $department->save();

        $status = $department->is_active ? 'activated' : 'deactivated';

        ActivityLogger::log(ActivityCode::DEPT_TOGGLED, "Toggled status for department {$department->name}", $department);

        return response()->json([
            'status' => 'success',
            'message' => "Department {$department->name} {$status} successfully.",
            'data' => $department->fresh(),
        ]);
    }

    /**
     * Render the Department Details & Analytics view shell.
     */
    public function departmentDetails($id)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        $department = Department::findOrFail($id);

        $documentTypes = DocumentType::where('is_active', true)->orderBy('name')->get();

        return view('audit.department-details', compact('department', 'documentTypes'));
    }

    /**
     * Pure JSON API endpoint for department-level analytics data.
     */
    public function getDepartmentDetailsData($id)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $department = Department::findOrFail($id);

        // Aggregate counts for documents currently in this department
        $aggregates = DB::table('documents')
            ->select([
                DB::raw('COUNT(*) as total_current'),
                DB::raw("COUNT(CASE WHEN status = 'pending_transfer' THEN 1 END) as pending"),
                DB::raw("COUNT(CASE WHEN status = 'in_transit' THEN 1 END) as in_transit"),
                DB::raw("COUNT(CASE WHEN status = 'received' THEN 1 END) as received"),
                DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed"),
                DB::raw("COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected"),
                DB::raw("COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled"),
                DB::raw("COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as added_today"),
            ])
            ->where('current_department_id', $id)
            ->first();

        // Dynamic SLA-based overdue calculation
        $currentDocuments = Document::where('current_department_id', $id)
            ->whereIn('status', ['pending_transfer', 'in_transit'])
            ->with('documentType')
            ->get();

        $departmentSlas = DepartmentDocumentSla::where('department_id', $id)
            ->get()
            ->keyBy('document_type_id');

        $policies = \App\Models\DocumentRoutingPolicy::whereIn('document_type_id', $currentDocuments->pluck('document_type_id')->unique())
            ->get()->keyBy('document_type_id');

        $dynamicOverdueCount = 0;
        $now = \Carbon\Carbon::now();

        foreach ($currentDocuments as $document) {
            $predefinedRoute = isset($policies[$document->document_type_id]) ? $policies[$document->document_type_id]->predefined_route : null;
            $allowedMinutes = \App\Services\SlaResolver::resolve($document->current_department_id, $document->document_type_id, null, $predefinedRoute);

            $deadline = $document->updated_at->copy()->addMinutes($allowedMinutes);

            if ($now->greaterThan($deadline)) {
                $dynamicOverdueCount++;
            }
        }

        // Received today from document_events
        $receivedToday = DB::table('document_events')
            ->where('department_id', $id)
            ->where('event_type', 'receipt')
            ->whereDate('created_at', DB::raw('CURDATE()'))
            ->count();

        // Total originated from this department
        $totalOriginated = DB::table('documents')
            ->where('sender_department_id', $id)
            ->count();

        // Average dwell time for non-completed documents currently in department (in hours)
        $avgDwellHours = DB::table('documents')
            ->where('current_department_id', $id)
            ->whereNotNull('current_department_id')
            ->whereNotIn('status', ['completed', 'rejected', 'cancelled'])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_hours'))
            ->value('avg_hours');

        // Current documents in department (paginated)
        $documents = Document::with(['documentType', 'senderDepartment'])
            ->where('current_department_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Active SLA overrides for this department
        $slas = DepartmentDocumentSla::where('department_id', $id)
            ->get()
            ->keyBy('document_type_id');

        // Recent events for this department (last 20)
        $recentEvents = DocumentEvent::with(['document', 'department'])
            ->where('department_id', $id)
            ->where('event_type', '!=', 'route_defined')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => 'success',
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'description' => $department->description,
                'is_active' => $department->is_active,
            ],
            'counts' => [
                'total_current' => (int) ($aggregates->total_current ?? 0),
                'pending' => (int) ($aggregates->pending ?? 0),
                'in_transit' => (int) ($aggregates->in_transit ?? 0),
                'received' => (int) ($aggregates->received ?? 0),
                'completed' => (int) ($aggregates->completed ?? 0),
                'rejected' => (int) ($aggregates->rejected ?? 0),
                'cancelled' => (int) ($aggregates->cancelled ?? 0),
                'overdue' => (int) $dynamicOverdueCount,
                'added_today' => (int) ($aggregates->added_today ?? 0),
                'received_today' => (int) $receivedToday,
                'total_originated' => (int) $totalOriginated,
                'avg_dwell_hours' => $avgDwellHours ? round((float) $avgDwellHours, 1) : null,
            ],
            'documents' => [
                'data' => $documents->items(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
            'recent_events' => $recentEvents->map(function ($event) {
                return [
                    'id' => $event->id,
                    'document_number' => $event->document->document_number ?? 'N/A',
                    'document_title' => $event->document->title ?? 'N/A',
                    'action' => $event->event_type,
                    'department_name' => $event->department->name ?? null,
                    'note' => $event->note,
                    'created_at' => $event->created_at->toDateTimeString(),
                ];
            }),
            'slas' => $slas->map(function ($sla) {
                return [
                    'document_type_id' => $sla->document_type_id,
                    'processing_time_minutes' => $sla->processing_time_minutes,
                ];
            }),
        ]);
    }

    public function updateDepartmentSlas(Request $request, $id)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'sla' => 'array|nullable',
            'sla.*.document_type_id' => 'required|exists:document_types,id',
            'sla.*.value' => 'nullable|numeric|min:1',
            'sla.*.unit' => 'required|in:minutes,hours,days',
        ]);

        DB::transaction(function () use ($request, $id) {
            if (!$request->has('sla')) return;

            foreach ($request->input('sla') as $row) {
                if (empty($row['value'])) {
                    DepartmentDocumentSla::where('department_id', $id)
                        ->where('document_type_id', $row['document_type_id'])
                        ->delete();
                    continue;
                }

                $minutes = (int)$row['value'];
                if ($row['unit'] === 'hours') {
                    $minutes *= 60;
                } elseif ($row['unit'] === 'days') {
                    $minutes *= 1440;
                }

                DepartmentDocumentSla::updateOrCreate(
                    [
                        'department_id' => $id,
                        'document_type_id' => $row['document_type_id']
                    ],
                    [
                        'processing_time_minutes' => $minutes
                    ]
                );
            }
        });

        $department = Department::find($id);
        ActivityLogger::log(ActivityCode::DEPT_SLA_UPDATED, "Updated SLA configuration for department {$department->name}", $department);

        return redirect()->back()->with('success', 'Department processing time SLAs updated successfully.');
    }

    public function indexPolicies()
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        $documentTypes = DocumentType::with('routingPolicy')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('audit.document-type-policies', compact('documentTypes', 'departments'));
    }

    public function issues()
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        $issues = DocumentIssue::with(['document', 'reportedBy', 'assignedDepartment'])
            ->latest()
            ->paginate(25);

        return view('audit.issues', compact('issues'));
    }

    public function storePolicy(Request $request)
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        $validated = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'is_immutable'     => ['required', 'boolean'],
            'predefined_route' => ['nullable', 'string', 'json'],
        ]);

        $routeArray = $validated['predefined_route'] ? json_decode($validated['predefined_route'], true) : null;

        if ($routeArray) {
            foreach ($routeArray as $index => $step) {
                if (isset($step['sla_minutes']) && (!is_numeric($step['sla_minutes']) || $step['sla_minutes'] < 1)) {
                    return redirect()->back()->withErrors(['predefined_route' => "Step " . ($index + 1) . " SLA must be a positive number."]);
                }
            }

            if ($request->filled('total_lifecycle_sla')) {
                $totalSlaLimit = (int) $request->input('total_lifecycle_sla');
                $currentSum = 0;
                foreach ($routeArray as $step) {
                    $currentSum += (int) ($step['sla_minutes'] ?? 0);
                }
                if ($currentSum > $totalSlaLimit) {
                    return redirect()->back()->withErrors(['predefined_route' => "The sum of individual step SLAs ($currentSum mins) cannot exceed the Total Lifecycle SLA ($totalSlaLimit mins)."]);
                }
            }
        }

        $policy = DocumentRoutingPolicy::updateOrCreate(
            ['document_type_id' => $validated['document_type_id']],
            [
                'is_immutable'     => $validated['is_immutable'],
                'predefined_route' => $routeArray,
            ]
        );

        ActivityLogger::log(ActivityCode::POLICY_UPDATED, "Updated document routing policy", $policy);

        return redirect()->back()->with('success', 'Routing policy updated successfully.');
    }
}
