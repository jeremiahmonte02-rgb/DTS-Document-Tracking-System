<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class DocumentController extends Controller
{
    use AuthorizesRequests;
    private const EVENT_LABELS = [
        'creation'   => 'Document registered — tracking ticket issued',
        'receipt'    => 'Document received at department',
        'completion' => 'Document workflow completed',
        'rejection'  => 'Document rejected at routing checkpoint',
        'cancellation' => 'Document workflow cancelled',
        'issue'      => 'Issue reported on document',
    ];

    public function create()
    {
        $this->authorize('create', Document::class);

        $user = auth()->user();

        $documentTypes = DB::table('document_types')->orderBy('name')->get();
        $departments = DB::table('departments')->orderBy('name')->get();

        $existingDocuments = Document::with(['routes.department'])
            ->where('sender_department_id', $user->department_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                return [
                    'id'                    => $doc->id,
                    'document_number'       => $doc->document_number,
                    'title'                 => $doc->title,
                    'document_type_id'      => $doc->document_type_id,
                    'sender_department_id'  => $doc->sender_department_id,
                    'description'           => $doc->description,
                    'routes'                => $doc->routes->sortBy('route_order')->map(function ($route) {
                        return [
                            'department_id'   => $route->department_id,
                            'department_name' => $route->department->name,
                            'department_code' => $route->department->code,
                            'route_order'     => $route->route_order,
                        ];
                    }),
                ];
            });

        return view('upload', compact('documentTypes', 'departments', 'existingDocuments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'documentType' => ['required', 'integer', 'exists:document_types,id'],
            'department'   => ['required', 'integer', 'exists:departments,id'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'fileUpload'   => ['required', 'file', 'mimes:pdf,docx,doc,xls,xlsx,jpg,png', 'max:20480'],
            'routes'       => ['required', 'string', 'json'],
        ]);

        $this->authorize('create', Document::class);

        $user = auth()->user();

        $routeSteps = json_decode($validated['routes'], true);
        if (!is_array($routeSteps) || count($routeSteps) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one routing destination department is required.',
            ], 422);
        }

        foreach ($routeSteps as $step) {
            if (!isset($step['department_id']) || !isset($step['route_order'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Malformed route step structure. Each step requires department_id and route_order.',
                ], 422);
            }
        }

        $documentId = (string) Str::orderedUuid();
        $documentNumber = $this->generateDocumentNumber();

        DB::transaction(function () use ($request, $user, $documentId, $documentNumber, $validated, $routeSteps) {
            DB::table('documents')->insert([
                'id'                    => $documentId,
                'document_number'       => $documentNumber,
                'title'                 => $validated['title'],
                'document_type_id'      => $validated['documentType'],
                'sender_department_id'  => $validated['department'],
                'uploaded_by_user_id'   => $user->id,
                'current_department_id' => $user->department_id,
                'status'                => 'pending_transfer',
                'description'           => $validated['description'],
                'uploaded_at'           => Carbon::now('Asia/Manila'),
                'created_at'            => Carbon::now('Asia/Manila'),
                'updated_at'            => Carbon::now('Asia/Manila'),
            ]);

            $file = $request->file('fileUpload');
            $originalFilename = $file->getClientOriginalName();
            $storedFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('private/documents', $storedFilename);

            DB::table('document_files')->insert([
                'document_id'          => $documentId,
                'original_filename'    => $originalFilename,
                'stored_filename'      => $storedFilename,
                'file_path'            => $filePath,
                'mime_type'            => $file->getMimeType(),
                'file_size'            => $file->getSize(),
                'uploaded_by_user_id'  => $user->id,
                'created_at'           => Carbon::now('Asia/Manila'),
                'updated_at'           => Carbon::now('Asia/Manila'),
            ]);

            DB::table('document_events')->insert([
                'document_id'   => $documentId,
                'user_id'       => $user->id,
                'department_id' => $user->department_id,
                'event_type'    => 'creation',
                'event_label'   => self::EVENT_LABELS['creation'],
                'old_status'    => null,
                'new_status'    => 'pending_transfer',
                'note'          => "Tracking identifier {$documentNumber} assigned to document \"" . $validated['title'] . '"',
                'metadata'      => json_encode([
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                    'tracking_id' => $documentNumber,
                ]),
                'created_at'    => Carbon::now('Asia/Manila'),
            ]);

            foreach ($routeSteps as $index => $step) {
                $targetDepartmentId = (int) $step['department_id'];
                $orderSequence = (int) $step['route_order'];
                $initialStepStatus = ($orderSequence === 1) ? 'current' : 'pending';

                if ($targetDepartmentId) {
                    DB::table('document_routes')->insert([
                        'document_id'   => $documentId,
                        'department_id' => $targetDepartmentId,
                        'route_order'   => $orderSequence,
                        'status'        => $initialStepStatus,
                        'created_at'    => Carbon::now('Asia/Manila'),
                        'updated_at'    => Carbon::now('Asia/Manila'),
                    ]);

                    DB::table('document_events')->insert([
                        'document_id'   => $documentId,
                        'user_id'       => $user->id,
                        'department_id' => $user->department_id,
                        'event_type'    => 'route_defined',
                        'event_label'   => 'Route step ' . $orderSequence . ' added',
                        'new_status'    => 'pending_transfer',
                        'note'          => 'Step ' . $orderSequence . ' routed to department ID ' . $targetDepartmentId,
                        'created_at'    => Carbon::now('Asia/Manila'),
                    ]);
                }
            }
        });

        return response()->json([
            'success'         => true,
            'message'         => "Document reference ticket {$documentNumber} registered successfully!",
            'document_number' => $documentNumber,
        ]);
    }

    public function showScanPage()
    {
        return view('scan');
    }

    public function lookupDocument(Request $request)
    {
        $docNumber = $request->query('document_number');

        if (!$docNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Tracking parameters missing or invalid.',
            ], 400);
        }

        $document = DB::table('documents')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments', 'documents.sender_department_id', '=', 'departments.id')
            ->where('documents.document_number', $docNumber)
            ->select(
                'documents.id',
                'documents.document_number',
                'documents.title',
                'documents.description',
                'documents.status',
                'documents.created_at',
                'document_types.name as type_name',
                'departments.name as origin_department'
            )
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document reference ticket not found.',
            ], 404);
        }

        $routes = DB::table('document_routes')
            ->join('departments', 'document_routes.department_id', '=', 'departments.id')
            ->where('document_id', $document->id)
            ->orderBy('route_order', 'asc')
            ->select(
                'departments.name as department_name',
                'document_routes.department_id',
                'document_routes.status',
                'document_routes.route_order'
            )
            ->get();

        $events = DB::table('document_events')
            ->join('users', 'document_events.user_id', '=', 'users.id')
            ->join('departments', 'document_events.department_id', '=', 'departments.id')
            ->where('document_id', $document->id)
            ->orderBy('created_at', 'asc')
            ->select(
                'document_events.event_label',
                'document_events.note',
                'document_events.new_status',
                'document_events.created_at',
                'users.name as processed_by_user',
                'departments.name as execution_department'
            )
            ->get()
            ->map(function ($event) {
                $event->formatted_date = Carbon::parse($event->created_at)->format('M d, Y h:i A');
                return $event;
            });

        return response()->json([
            'success'  => true,
            'document' => $document,
            'routes'   => $routes,
            'events'   => $events,
        ]);
    }

    public function showDocumentDetails($document_number)
    {
        $documentModel = Document::where('document_number', $document_number)->first();
        if ($documentModel && auth()->user()->cannot('view', $documentModel)) {
            $deptName = auth()->user()->department->name ?? 'Not Assigned';
            abort(403, 'Your department (' . $deptName . ') is not authorized to view the lifecycle operations of this file.');
        }

        $document = DB::table('documents')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments as sender_dept', 'documents.sender_department_id', '=', 'sender_dept.id')
            ->leftJoin('departments as current_dept', 'documents.current_department_id', '=', 'current_dept.id')
            ->leftJoin('users', 'documents.uploaded_by_user_id', '=', 'users.id')
            ->where('documents.document_number', $document_number)
            ->select(
                'documents.id',
                'documents.document_number',
                'documents.title',
                'documents.description',
                'documents.status',
                'documents.created_at as upload_date',
                'documents.completed_at',
                'document_types.name as type_name',
                'sender_dept.name as origin_department',
                'current_dept.id as current_department_id',
                'current_dept.name as current_department',
                'users.name as uploaded_by_user'
            )
            ->first();

        if (!$document) {
            abort(404, 'The requested tracking identifier sequence does not exist in the master database registries.');
        }

        $routes = DB::table('document_routes')
            ->join('departments', 'document_routes.department_id', '=', 'departments.id')
            ->where('document_id', $document->id)
            ->orderBy('route_order', 'asc')
            ->select(
                'departments.name as department_name',
                'departments.id as department_id',
                'document_routes.status',
                'document_routes.route_order'
            )
            ->get();

        $events = DB::table('document_events')
            ->join('users', 'document_events.user_id', '=', 'users.id')
            ->join('departments', 'document_events.department_id', '=', 'departments.id')
            ->where('document_id', $document->id)
            ->orderBy('created_at', 'desc')
            ->select(
                'document_events.event_label',
                'document_events.note',
                'document_events.new_status',
                'document_events.created_at',
                'users.name as processed_by_user',
                'departments.name as execution_department'
            )
            ->get()
            ->map(function ($event) {
                $event->formatted_date = Carbon::parse($event->created_at)->format('M d, Y h:i A');
                return $event;
            });

        return view('document-details', compact('document', 'routes', 'events'));
    }

    public function receiveDocument(Request $request)
    {
        $docNumber = $request->input('document_number');
        $note = $request->input('note');
        $user = auth()->user();

        if (!$docNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Target document parameter missing.',
            ], 400);
        }

        try {
            $result = DB::transaction(function () use ($docNumber, $note, $user) {
                $document = DB::table('documents')
                    ->where('document_number', $docNumber)
                    ->first();

                if (!$document) {
                    throw new Exception('Document tracking record not found in system archives.', 404);
                }

                $documentModel = Document::find($document->id);
                if ($documentModel) {
                    $this->authorize('receive', $documentModel);
                }

                $currentRouteStep = DB::table('document_routes')
                    ->where('document_id', $document->id)
                    ->where('status', 'current')
                    ->first();

                if (!$currentRouteStep) {
                    throw new Exception('No active routing checkpoint currently assigned to this document.', 422);
                }

                if ((int) $currentRouteStep->department_id !== (int) $user->department_id) {
                    throw new Exception('Access Denied: Your assigned department does not match the active route destination.', 403);
                }

                DB::table('document_routes')
                    ->where('id', $currentRouteStep->id)
                    ->update([
                        'status'              => 'received',
                        'received_at'         => Carbon::now('Asia/Manila'),
                        'received_by_user_id' => $user->id,
                        'updated_at'          => Carbon::now('Asia/Manila'),
                    ]);

                $nextRouteStep = DB::table('document_routes')
                    ->where('document_id', $document->id)
                    ->where('route_order', $currentRouteStep->route_order + 1)
                    ->first();

                $newDocumentStatus = 'in_transit';
                $nextDepartmentId = $user->department_id;

                if ($nextRouteStep) {
                    DB::table('document_routes')
                        ->where('id', $nextRouteStep->id)
                        ->update(['status' => 'current']);

                    $nextDepartmentId = $nextRouteStep->department_id;
                } else {
                    $newDocumentStatus = 'received';
                }

                $updatePayload = [
                    'status'               => $newDocumentStatus,
                    'current_department_id' => $nextDepartmentId,
                    'updated_at'           => Carbon::now('Asia/Manila'),
                ];

                if ($newDocumentStatus === 'received') {
                    $updatePayload['completed_at'] = Carbon::now('Asia/Manila');
                }

                DB::table('documents')
                    ->where('id', $document->id)
                    ->update($updatePayload);

                DB::table('document_events')->insert([
                    'document_id'   => $document->id,
                    'user_id'       => $user->id,
                    'department_id' => $user->department_id,
                    'event_type'    => 'receipt',
                    'event_label'   => self::EVENT_LABELS['receipt'],
                    'old_status'    => $document->status,
                    'new_status'    => $newDocumentStatus,
                    'note'          => $note ?: 'Document acknowledged and received.',
                    'created_at'    => Carbon::now('Asia/Manila'),
                ]);

                return [
                    'message' => $newDocumentStatus === 'received'
                        ? 'Document workflow path completed successfully.'
                        : 'Document successfully received and queued for next hop routing.',
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function inbox()
    {
        $documentTypes = \DB::table('document_types')->where('is_active', 1)->select('id', 'name')->get();
        return view('inbox', compact('documentTypes'));
    }

    public function getInboxData(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: department mapping required.',
            ], 401);
        }

        $userDeptId = $user->department_id;

        $query = DB::table('documents')
            ->join('document_routes as my_route', 'documents.id', '=', 'my_route.document_id')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments as sender_dept', 'documents.sender_department_id', '=', 'sender_dept.id')
            ->join('departments as current_dept', 'documents.current_department_id', '=', 'current_dept.id')
            ->select(
                'documents.id as doc_id',
                'documents.document_number',
                'documents.title',
                'document_types.name as type_name',
                'sender_dept.name as sender_name',
                'current_dept.name as current_department',
                'documents.created_at as date_uploaded',
                'documents.status as step_status'
            )
            ->where('my_route.department_id', $userDeptId)
            ->whereNotNull('my_route.received_at')
            ->whereNotExists(function ($subQuery) use ($userDeptId) {
                $subQuery->select(DB::raw(1))
                    ->from('document_routes as next_route')
                    ->whereColumn('next_route.document_id', 'documents.id')
                    ->where('next_route.route_order', '>', function ($seqQuery) use ($userDeptId) {
                        $seqQuery->select('route_order')
                            ->from('document_routes')
                            ->whereColumn('document_id', 'documents.id')
                            ->where('department_id', $userDeptId)
                            ->limit(1);
                    })
                    ->whereNotNull('next_route.received_at');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('documents.title', 'like', '%' . $search . '%')
                  ->orWhere('documents.document_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('documents.document_type_id', $request->type);
        }

        if ($request->filled('date')) {
            $query->whereDate('documents.created_at', $request->date);
        }

        if ($request->filled('status')) {
            $statusValue = str_replace(' ', '_', strtolower($request->status));
            $query->where('documents.status', $statusValue);
        }

        $paginatedData = $query->orderBy('documents.updated_at', 'desc')
            ->paginate(10);

        return response()->json($paginatedData);
    }

    public function outbox()
    {
        $documentTypes = \DB::table('document_types')->where('is_active', 1)->select('id', 'name')->get();
        return view('outbox', compact('documentTypes'));
    }

    public function getOutboxData(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: department mapping required.',
            ], 401);
        }

        $userDeptId = $user->department_id;

        $query = DB::table('documents')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->join('departments as current_dept', 'documents.current_department_id', '=', 'current_dept.id')
            ->leftJoin('document_routes as next_route', function ($join) {
                $join->on('next_route.document_id', '=', 'documents.id')
                     ->whereRaw('next_route.route_order = (SELECT MIN(r2.route_order) FROM document_routes r2 WHERE r2.document_id = documents.id AND r2.status = "current" AND r2.route_order > (SELECT COALESCE(MAX(r3.route_order), 0) FROM document_routes r3 WHERE r3.document_id = documents.id AND r3.status = "received"))');
            })
            ->select(
                'documents.id as doc_id',
                'documents.document_number',
                'documents.title',
                'document_types.name as type_name',
                'current_dept.name as current_department',
                'current_dept.name as current_location',
                'documents.created_at as date_uploaded',
                'documents.status as computed_status'
            )
            ->where('documents.sender_department_id', $userDeptId)
            ->whereIn('documents.status', ['pending_transfer', 'in_transit', 'received', 'rejected', 'cancelled']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('documents.title', 'like', '%' . $search . '%')
                  ->orWhere('documents.document_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('documents.document_type_id', $request->type);
        }

        if ($request->filled('status')) {
            $statusValue = str_replace(' ', '_', strtolower($request->status));
            $query->where('documents.status', $statusValue);
        }

        if ($request->filled('date')) {
            $query->whereDate('documents.created_at', $request->date);
        }

        $paginatedData = $query->orderBy('documents.updated_at', 'desc')
            ->paginate(10);

        $paginatedData->getCollection()->transform(function ($doc) {
            $doc->date_sent_formatted = \Carbon\Carbon::parse($doc->date_uploaded)->format('M d, Y');
            $doc->computed_status = ucwords(str_replace('_', ' ', $doc->computed_status));
            return $doc;
        });

        return response()->json($paginatedData);
    }

    public function completeDocument(Request $request, $documentNumber)
    {
        $user = auth()->user();

        try {
            DB::transaction(function () use ($documentNumber, $user) {
                $document = DB::table('documents')
                    ->where('document_number', $documentNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$document) {
                    throw new Exception('Document not found.', 404);
                }

                $documentModel = Document::find($document->id);
                if ($documentModel) {
                    $this->authorize('complete', $documentModel);
                }

                $routes = DB::table('document_routes')
                    ->where('document_id', $document->id)
                    ->orderBy('route_order', 'asc')
                    ->get();

                $lastStep = $routes->last();

                if (!$lastStep || (int) $lastStep->department_id !== (int) $user->department_id) {
                    throw new Exception('Unauthorized. Only the final department can mark this document as complete.', 403);
                }

                DB::table('documents')
                    ->where('id', $document->id)
                    ->update([
                        'status'       => 'completed',
                        'completed_at' => Carbon::now('Asia/Manila'),
                        'updated_at'   => Carbon::now('Asia/Manila'),
                    ]);

                DB::table('document_events')->insert([
                    'document_id'   => $document->id,
                    'user_id'       => $user->id,
                    'department_id' => $user->department_id,
                    'event_type'    => 'completion',
                    'event_label'   => self::EVENT_LABELS['completion'],
                    'old_status'    => $document->status,
                    'new_status'    => 'completed',
                    'note'          => 'Document finalized by the terminal routing department.',
                    'created_at'    => Carbon::now('Asia/Manila'),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Document marked as completed.',
            ]);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function rejectDocument(Request $request)
    {
        $docNumber = $request->input('document_number');
        $reason = $request->input('reason', 'Document rejected at routing checkpoint');
        $user = auth()->user();

        if (!$docNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Target document parameter missing.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($docNumber, $reason, $user, $request) {
                $document = DB::table('documents')
                    ->where('document_number', $docNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$document) {
                    throw new Exception('Document not found.', 404);
                }

                $documentModel = Document::find($document->id);
                if ($documentModel) {
                    $this->authorize('reject', $documentModel);
                }

                $currentRouteStep = DB::table('document_routes')
                    ->where('document_id', $document->id)
                    ->where('status', 'current')
                    ->first();

                if ($currentRouteStep) {
                    DB::table('document_routes')
                        ->where('id', $currentRouteStep->id)
                        ->update([
                            'status'     => 'rejected',
                            'remarks'    => $reason,
                            'updated_at' => Carbon::now('Asia/Manila'),
                        ]);
                }

                DB::table('documents')
                    ->where('id', $document->id)
                    ->update([
                        'status'       => 'rejected',
                        'updated_at'   => Carbon::now('Asia/Manila'),
                    ]);

                DB::table('document_events')->insert([
                    'document_id'   => $document->id,
                    'user_id'       => $user->id,
                    'department_id' => $user->department_id,
                    'event_type'    => 'rejection',
                    'event_label'   => self::EVENT_LABELS['rejection'],
                    'old_status'    => $document->status,
                    'new_status'    => 'rejected',
                    'note'          => $reason,
                    'metadata'      => json_encode([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]),
                    'created_at'    => Carbon::now('Asia/Manila'),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Document rejected and returned.',
            ]);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function cancelDocument(Request $request)
    {
        $docNumber = $request->input('document_number');
        $reason = $request->input('reason', 'Document workflow cancelled');
        $user = auth()->user();

        if (!$docNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Target document parameter missing.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($docNumber, $reason, $user, $request) {
                $document = DB::table('documents')
                    ->where('document_number', $docNumber)
                    ->lockForUpdate()
                    ->first();

                if (!$document) {
                    throw new Exception('Document not found.', 404);
                }

                $documentModel = Document::find($document->id);
                if ($documentModel) {
                    $this->authorize('cancel', $documentModel);
                }

                DB::table('documents')
                    ->where('id', $document->id)
                    ->update([
                        'status'       => 'cancelled',
                        'updated_at'   => Carbon::now('Asia/Manila'),
                    ]);

                DB::table('document_events')->insert([
                    'document_id'   => $document->id,
                    'user_id'       => $user->id,
                    'department_id' => $user->department_id,
                    'event_type'    => 'cancellation',
                    'event_label'   => self::EVENT_LABELS['cancellation'],
                    'old_status'    => $document->status,
                    'new_status'    => 'cancelled',
                    'note'          => $reason,
                    'metadata'      => json_encode([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]),
                    'created_at'    => Carbon::now('Asia/Manila'),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Document workflow cancelled.',
            ]);
        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function reportIssue(Request $request)
    {
        $validated = $request->validate([
            'document_number' => ['required', 'string'],
            'description'     => ['required', 'string', 'max:2000'],
            'department'      => ['nullable', 'string', 'max:255'],
        ]);

        $document = DB::table('documents')
            ->where('document_number', $validated['document_number'])
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        $documentModel = Document::find($document->id);
        if ($documentModel) {
            $this->authorize('reportIssue', $documentModel);
        }

        DB::transaction(function () use ($document, $validated, $request) {
            $departmentId = null;
            if (!empty($validated['department'])) {
                $dept = DB::table('departments')
                    ->where('name', $validated['department'])
                    ->first();
                $departmentId = $dept?->id;
            }

            DB::table('document_issues')->insert([
                'document_id'            => $document->id,
                'reported_by_user_id'    => auth()->id(),
                'assigned_department_id' => $departmentId,
                'description'            => $validated['description'],
                'priority'               => 'medium',
                'status'                 => 'open',
                'created_at'             => Carbon::now('Asia/Manila'),
                'updated_at'             => Carbon::now('Asia/Manila'),
            ]);

            DB::table('document_events')->insert([
                'document_id'   => $document->id,
                'user_id'       => auth()->id(),
                'department_id' => auth()->user()->department_id,
                'event_type'    => 'issue',
                'event_label'   => self::EVENT_LABELS['issue'],
                'new_status'    => $document->status,
                'note'          => substr($validated['description'], 0, 255),
                'metadata'      => json_encode([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
                'created_at'    => Carbon::now('Asia/Manila'),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Issue reported successfully.',
        ]);
    }

    private function generateDocumentNumber(): string
    {
        $year = Carbon::now('Asia/Manila')->year;

        return DB::transaction(function () use ($year) {
            $seq = DB::table('document_number_sequences')
                ->where('year_year', $year)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::table('document_number_sequences')->insert([
                    'year_year'     => $year,
                    'last_sequence' => 1,
                    'created_at'    => Carbon::now('Asia/Manila'),
                    'updated_at'    => Carbon::now('Asia/Manila'),
                ]);
                $nextVal = 1;
            } else {
                $nextVal = $seq->last_sequence + 1;
                DB::table('document_number_sequences')
                    ->where('id', $seq->id)
                    ->update(['last_sequence' => $nextVal]);
            }

            return sprintf('DTS-%s-%04d', $year, $nextVal);
        });
    }
}
