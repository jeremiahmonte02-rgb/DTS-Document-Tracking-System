<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditorController extends Controller
{
    /**
     * Render the welcome dashboard view.
     */
    public function dashboard()
    {
        if (!auth()->user() || !auth()->user()->isAuditor()) {
            abort(403, 'Unauthorized access to the Audit Portal.');
        }

        return view('audit.dashboard');
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

        $query = DB::table('documents')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments as sender_dept', 'documents.sender_department_id', '=', 'sender_dept.id')
            ->leftJoin('departments as current_dept', 'documents.current_department_id', '=', 'current_dept.id')
            ->select([
                'documents.document_number',
                'documents.title',
                'documents.status',
                'documents.created_at as date_created',
                DB::raw('document_types.name as document_type_name'),
                DB::raw('sender_dept.name as origin_department'),
                DB::raw('current_dept.name as current_department')
            ]);

        // Reusable filter application used for both row data and counts
        $applyFilters = function ($q) use ($request) {
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
        };

        $applyFilters($query);

        $documents = $query->orderBy('documents.created_at', 'desc')->get();

        // Aggregated counts scoped to the same filter context
        $countQuery = DB::table('documents');
        $applyFilters($countQuery);

        $counts = [
            'total'       => (clone $countQuery)->count(),
            'pending'     => (clone $countQuery)->where('status', 'pending_transfer')->count(),
            'in_transit'  => (clone $countQuery)->where('status', 'in_transit')->count(),
            'completed'   => (clone $countQuery)->whereIn('status', ['received', 'completed'])->count(),
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

        return view('audit.department-details', compact('department'));
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
                DB::raw("COUNT(CASE WHEN status IN ('pending_transfer','in_transit') AND created_at < NOW() - INTERVAL 48 HOUR THEN 1 END) as overdue"),
                DB::raw("COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as added_today"),
            ])
            ->where('current_department_id', $id)
            ->first();

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
                'overdue' => (int) ($aggregates->overdue ?? 0),
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
        ]);
    }
}
