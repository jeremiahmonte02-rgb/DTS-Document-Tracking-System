<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentRoute;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Enums\ActivityCode;
use App\Services\ActivityLogger;
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
        'route_returned' => 'Document returned to department',
    ];

    /**
     * Render the document upload form for the authenticated sender's department.
     *
     * @return \Illuminate\View\View
     */
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

    /**
     * Register a new tracking document together with its initial routing sequence.
     *
     * Validates the submission, enforces immutable routing policies when configured,
     * persists the document, its file, route steps and lifecycle events inside a single
     * transaction, then issues a new tracking number to the sender.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException When the submitted route sequence violates the enforced routing policy.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'documentType' => ['required', 'integer', 'exists:document_types,id'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'fileUpload'   => ['required', 'file', 'mimes:pdf,docx,doc,xls,xlsx,jpg,png', 'max:20480'],
            'routes'       => ['required', 'string', 'json'],
        ]);

        $policy = \App\Models\DocumentRoutingPolicy::where('document_type_id', $validated['documentType'])->first();

        if ($policy && $policy->is_immutable) {
            $submittedRoutes = json_decode($validated['routes'], true) ?? [];
            $predefinedRoutes = $policy->predefined_route ?? [];

            if (count($submittedRoutes) !== count($predefinedRoutes)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'routes' => ['The provided routing sequence length does not match the enforced policy.']
                ]);
            }

            foreach ($predefinedRoutes as $index => $expectedStep) {
                $submittedStep = $submittedRoutes[$index] ?? null;

                if (!$submittedStep || (int)$submittedStep['department_id'] !== (int)$expectedStep['department_id']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'routes' => ["Step " . ($index + 1) . " must be routed to the designated policy department."]
                    ]);
                }
            }

            }

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
                'sender_department_id'  => $user->department_id,
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
                $initialStepStatus = ($orderSequence === 1) ? 'next' : 'pending';

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

            \App\Models\Notification::broadcastToDepartment(
                (int) $user->department_id,
                'uploaded',
                'Document Uploaded',
                "Tracking ticket {$documentNumber} \"" . $validated['title'] . '" was registered and is awaiting transfer.',
                $documentId
            );
        });

        ActivityLogger::log(ActivityCode::DOC_CREATED, "Uploaded document {$documentNumber}");

        return response()->json([
            'success'         => true,
            'message'         => "Document reference ticket {$documentNumber} registered successfully!",
            'document_number' => $documentNumber,
        ]);
    }

    /**
     * Render the QR scanner view used to acknowledge receipt of routed documents.
     *
     * @return \Illuminate\View\View
     */
    public function showScanPage()
    {
        return view('scan');
    }

    /**
     * Resolve the full tracking trail (routes, events and SLA status) of a document by number.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                'documents.document_type_id',
                'document_types.name as document_type_name',
                'departments.name as sender_department_name'
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
            ->where('event_type', '!=', 'route_defined')
            ->orderBy('created_at', 'asc')
            ->select(
                'document_events.event_label',
                'document_events.event_type',
                'document_events.note',
                'document_events.new_status',
                'document_events.created_at',
                'document_events.department_id',
                'document_events.route_order',
                'users.name as processed_by_user',
                'departments.name as execution_department'
            )
            ->get()
            ->map(function ($event) {
                $event->formatted_date = Carbon::parse($event->created_at)->format('M d, Y h:i A');
                $event->is_sla_breached = false;
                return $event;
            });

        $allDepartmentSlas = \App\Models\DepartmentDocumentSla::get()
            ->groupBy('department_id')
            ->map(fn($items) => $items->keyBy('document_type_id'));

        $allDocumentTypes = \App\Models\DocumentType::get()->keyBy('id');

        $targetDocTypeId = $document->document_type_id;

        $policy = \App\Models\DocumentRoutingPolicy::where('document_type_id', $document->document_type_id)->first();
        $predefinedRoute = $policy->predefined_route ?? null;

        $custodyTypes = ['receipt', 'completion', 'rejection', 'cancellation', 'route_returned'];

        $lastCustodyIndex = null;
        for ($i = count($events) - 1; $i >= 0; $i--) {
            if (in_array($events[$i]->event_type, $custodyTypes)) {
                $lastCustodyIndex = $i;
                break;
            }
        }

        foreach ($events as $i => $event) {
            $isInformational = !in_array($event->event_type, $custodyTypes);

            if (isset($events[$i + 1])) {
                $currentEventTime = Carbon::parse($event->created_at);
                $nextEventTime = Carbon::parse($events[$i + 1]->created_at);

                $elapsedMinutes = $currentEventTime->diffInMinutes($nextEventTime);

                $event->processing_time = $nextEventTime->diffForHumans($currentEventTime, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2);

                if (!$isInformational) {
                    $deptId = $event->department_id;
                    $allowedMinutes = \App\Services\SlaResolver::resolve($deptId, $targetDocTypeId, $event->route_order ?? null, $predefinedRoute);

                    if ($elapsedMinutes > $allowedMinutes) {
                        $event->is_sla_breached = true;
                    }
                }
            } else {
                $documentStatus = $document->status;
                if (!in_array($documentStatus, ['completed', 'cancelled', 'rejected']) && !$isInformational) {
                    $now = Carbon::now('Asia/Manila');
                    $currentEventTime = Carbon::parse($event->created_at);

                    $elapsedMinutes = $currentEventTime->diffInMinutes($now);

                    $event->processing_time = $now->diffForHumans($currentEventTime, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2) . ' (Active Step)';

                    $deptId = $event->department_id;
                    $allowedMinutes = \App\Services\SlaResolver::resolve($deptId, $targetDocTypeId, $event->route_order ?? null, $predefinedRoute);

                    if ($elapsedMinutes > $allowedMinutes) {
                        $event->is_sla_breached = true;
                    }
                } else {
                    $event->processing_time = null;
                }
            }
        }

        if (!in_array($document->status, ['completed', 'cancelled', 'rejected']) && $lastCustodyIndex !== null) {
            $activeEvent = $events[$lastCustodyIndex];
            $now = Carbon::now('Asia/Manila');
            $activeEventTime = Carbon::parse($activeEvent->created_at);

            $activeEvent->processing_time = $now->diffForHumans($activeEventTime, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2) . ' (Active Step)';

            $deptId = $activeEvent->department_id;
            $allowedMinutes = \App\Services\SlaResolver::resolve($deptId, $targetDocTypeId, $activeEvent->route_order ?? null, $predefinedRoute);

            if ($activeEventTime->diffInMinutes($now) > $allowedMinutes) {
                $activeEvent->is_sla_breached = true;
            }
        }

        return response()->json([
            'success'  => true,
            'document' => $document,
            'routes'   => $routes,
            'events'   => $events,
        ]);
    }

    /**
     * Render the document lifecycle detail page with per-step SLA breach analysis.
     *
     * @param string $document_number The tracking number of the document to inspect.
     * @return \Illuminate\View\View
     */
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
                'documents.sender_department_id',
                'documents.document_type_id',
                'document_types.name as document_type_name',
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
            ->where('event_type', '!=', 'route_defined')
            ->orderBy('created_at', 'asc')
            ->select(
                'document_events.event_label',
                'document_events.event_type',
                'document_events.note',
                'document_events.new_status',
                'document_events.created_at',
                'document_events.department_id',
                'document_events.route_order',
                'users.name as processed_by_user',
                'departments.name as execution_department'
            )
            ->get();

        $allDepartmentSlas = \App\Models\DepartmentDocumentSla::get()
            ->groupBy('department_id')
            ->map(fn($items) => $items->keyBy('document_type_id'));

        $allDocumentTypes = \App\Models\DocumentType::get()->keyBy('id');

        $targetDocTypeId = $document->document_type_id;

        $policy = \App\Models\DocumentRoutingPolicy::where('document_type_id', $document->document_type_id)->first();
        $predefinedRoute = $policy->predefined_route ?? null;

        $custodyTypes = ['receipt', 'completion', 'rejection', 'cancellation', 'route_returned'];

        $lastCustodyIndex = null;
        for ($i = count($events) - 1; $i >= 0; $i--) {
            if (in_array($events[$i]->event_type, $custodyTypes)) {
                $lastCustodyIndex = $i;
                break;
            }
        }

        $events->transform(function ($event, $i) use ($events, $targetDocTypeId, $custodyTypes, $document, $predefinedRoute) {
            $event->formatted_date = Carbon::parse($event->created_at)->format('M d, Y h:i A');
            $event->is_sla_breached = false;

            $isInformational = !in_array($event->event_type, $custodyTypes);

            if (isset($events[$i + 1])) {
                $currentEventTime = Carbon::parse($event->created_at);
                $nextEventTime = Carbon::parse($events[$i + 1]->created_at);

                $elapsedMinutes = $currentEventTime->diffInMinutes($nextEventTime);

                $event->processing_time = $nextEventTime->diffForHumans($currentEventTime, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2);

                if (!$isInformational) {
                    $deptId = $event->department_id;
                    $allowedMinutes = \App\Services\SlaResolver::resolve($deptId, $targetDocTypeId, $event->route_order ?? null, $predefinedRoute);

                    if ($elapsedMinutes > $allowedMinutes) {
                        $event->is_sla_breached = true;
                    }
                }
            } else {
                $documentStatus = $document->status;
                if (!in_array($documentStatus, ['completed', 'cancelled', 'rejected']) && !$isInformational) {
                    $now = Carbon::now('Asia/Manila');
                    $currentEventTime = Carbon::parse($event->created_at);

                    $elapsedMinutes = $currentEventTime->diffInMinutes($now);

                    $event->processing_time = $now->diffForHumans($currentEventTime, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2) . ' (Active Step)';

                    $deptId = $event->department_id;
                    $allowedMinutes = \App\Services\SlaResolver::resolve($deptId, $targetDocTypeId, $event->route_order ?? null, $predefinedRoute);

                    if ($elapsedMinutes > $allowedMinutes) {
                        $event->is_sla_breached = true;
                    }
                } else {
                    $event->processing_time = null;
                }
            }

            return $event;
        });

        if (!in_array($document->status, ['completed', 'cancelled', 'rejected']) && $lastCustodyIndex !== null) {
            $activeEvent = $events[$lastCustodyIndex];
            $now = Carbon::now('Asia/Manila');
            $activeEventTime = Carbon::parse($activeEvent->created_at);

            $activeEvent->processing_time = $now->diffForHumans($activeEventTime, \Carbon\CarbonInterface::DIFF_ABSOLUTE, true, 2) . ' (Active Step)';

            $deptId = $activeEvent->department_id;
            $allowedMinutes = \App\Services\SlaResolver::resolve($deptId, $targetDocTypeId, $activeEvent->route_order ?? null, $predefinedRoute);

            if ($activeEventTime->diffInMinutes($now) > $allowedMinutes) {
                $activeEvent->is_sla_breached = true;
            }
        }

        $events = $events->reverse();

        $departments = DB::table('departments')->orderBy('name')->get();

        $isImmutable = DB::table('document_routing_policies')
            ->where('document_type_id', $document->document_type_id)
            ->value('is_immutable') ?? false;

        return view('document-details', compact('document', 'routes', 'events', 'departments', 'isImmutable'));
    }

    /**
     * Acknowledge custody of a routed document at the receiving department.
     *
     * Transitions the active 'current' step to 'received', promotes the next-in-line
     * step to 'current', records a receipt lifecycle event and advances the document
     * status to 'in_transit' within a single transaction.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                    ->lockForUpdate()
                    ->first();

                if (!$document) {
                    throw new Exception('Document tracking record not found in system archives.', 404);
                }

                $documentModel = Document::find($document->id);
                if ($documentModel) {
                    $this->authorize('receive', $documentModel);
                }

                $allRoutes = DB::table('document_routes')
                    ->where('document_id', $document->id)
                    ->orderBy('route_order', 'asc')
                    ->get();

                if ($allRoutes->isEmpty()) {
                    throw new Exception('No routing path defined for this document.', 422);
                }

                // Step 1: Transition any existing 'current' step to 'received'
                $existingCurrent = $allRoutes->firstWhere('status', 'current');
                if ($existingCurrent) {
                    DB::table('document_routes')
                        ->where('id', $existingCurrent->id)
                        ->update([
                            'status'     => 'received',
                            'updated_at' => Carbon::now('Asia/Manila'),
                        ]);
                }

                // Step 2: Find scanning user's department step — must be 'next'
                $receiverStep = $allRoutes->first(function ($route) use ($user) {
                    return $route->status === 'next' && (int) $route->department_id === (int) $user->department_id;
                });

                if (!$receiverStep) {
                    throw new Exception('Access Denied: Your department is not the active next-in-line recipient for this document.', 403);
                }

                // Step 3: Transition receiver's step from 'next' to 'current' (active custody)
                DB::table('document_routes')
                    ->where('id', $receiverStep->id)
                    ->update([
                        'status'              => 'current',
                        'received_at'         => Carbon::now('Asia/Manila'),
                        'received_by_user_id' => $user->id,
                        'updated_at'          => Carbon::now('Asia/Manila'),
                    ]);

                // Step 4: Find the subsequent step and set it to 'next'
                $subsequentStep = $allRoutes->first(function ($route) use ($receiverStep) {
                    return (int) $route->route_order === (int) $receiverStep->route_order + 1;
                });

                if ($subsequentStep) {
                    DB::table('document_routes')
                        ->where('id', $subsequentStep->id)
                        ->update([
                            'status'     => 'next',
                            'updated_at' => Carbon::now('Asia/Manila'),
                        ]);
                }

                // Step 5: Update the documents table
                DB::table('documents')
                    ->where('id', $document->id)
                    ->update([
                        'status'               => 'in_transit',
                        'current_department_id' => $user->department_id,
                        'updated_at'           => Carbon::now('Asia/Manila'),
                    ]);

                // Step 6: Log audit event
                DB::table('document_events')->insert([
                    'document_id'   => $document->id,
                    'user_id'       => $user->id,
                    'department_id' => $user->department_id,
                    'route_order'   => $receiverStep->route_order,
                    'event_type'    => 'receipt',
                    'event_label'   => self::EVENT_LABELS['receipt'],
                    'old_status'    => $document->status,
                    'new_status'    => 'in_transit',
                    'note'          => $note ?: 'Document acknowledged and received.',
                    'created_at'    => Carbon::now('Asia/Manila'),
                ]);

                $hasMoreSteps = $subsequentStep && $allRoutes->where('route_order', '>', $subsequentStep->route_order)->isNotEmpty();

                \App\Models\Notification::broadcastToDepartment(
                    (int) $user->department_id,
                    'received',
                    'Document Received',
                    "Document {$document->document_number} was received by {$user->department->name} and is now in custody.",
                    $document->id
                );

                return [
                    'document' => $documentModel,
                    'message'  => $hasMoreSteps
                        ? 'Document received. Custody acquired — ready for downstream routing.'
                        : 'Document received. This is the final routing step.',
                ];
            });

            ActivityLogger::log(ActivityCode::DOC_RECEIVED, "Received custody of document {$result['document']->document_number}", $result['document']);

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

    /**
     * Render the department inbox listing documents currently in its custody.
     *
     * @return \Illuminate\View\View
     */
    public function inbox()
    {
        $documentTypes = \DB::table('document_types')->where('is_active', 1)->select('id', 'name')->get();
        return view('inbox', compact('documentTypes'));
    }

    /**
     * Return paginated inbox records scoped to the authenticated department's custody.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                'document_types.name as document_type_name',
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
            })
            ->whereNotIn('documents.status', ['completed', 'rejected', 'cancelled']);

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

    /**
     * Render the department outbox listing documents it originated or has transferred.
     *
     * @return \Illuminate\View\View
     */
    public function outbox()
    {
        $documentTypes = \DB::table('document_types')->where('is_active', 1)->select('id', 'name')->get();
        return view('outbox', compact('documentTypes'));
    }

    /**
     * Return paginated outbox records for documents sent by the authenticated department.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                     ->whereRaw('next_route.route_order = (SELECT MIN(r2.route_order) FROM document_routes r2 WHERE r2.document_id = documents.id AND r2.status = "current" AND r2.route_order > (SELECT COALESCE(MAX(r3.route_order), 0) FROM document_routes r3 WHERE r3.document_id = documents.id AND r3.status = "next"))');
            })
            ->select(
                'documents.id as doc_id',
                'documents.document_number',
                'documents.title',
                'document_types.name as document_type_name',
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

    /**
     * Finalize a document after the terminal routing department completes its step.
     *
     * Verifies that the authenticated department holds active custody of the final
     * step, then marks the document 'completed' and stamps the completion timestamp.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $documentNumber The tracking number of the document to complete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeDocument(Request $request, $documentNumber)
    {
        $user = auth()->user();

        try {
            $completedDocument = DB::transaction(function () use ($documentNumber, $user) {
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

                if (strtolower($lastStep->status) !== 'current') {
                    throw new Exception('The final routing step must hold active custody before the document can be marked complete.', 422);
                }

                DB::table('document_routes')
                    ->where('id', $lastStep->id)
                    ->update([
                        'status'              => 'received',
                        'received_at'         => Carbon::now('Asia/Manila'),
                        'received_by_user_id' => $user->id,
                        'updated_at'          => Carbon::now('Asia/Manila'),
                    ]);

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

                return $documentModel;
            });

            ActivityLogger::log(
                ActivityCode::DOC_COMPLETED,
                "Marked document {$completedDocument->document_number} completed",
                $completedDocument
            );

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

    /**
     * Reject a document at routing checkpoints and return it to the originating department.
     *
     * Marks the active route step as 'rejected' with the given reason and records a
     * rejection lifecycle event for the audit trail.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
            $rejectedDocument = DB::transaction(function () use ($docNumber, $reason, $user, $request) {
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

                return $documentModel;
            });

            ActivityLogger::log(ActivityCode::DOC_REJECTED, "Rejected transfer for document {$rejectedDocument->document_number}", $rejectedDocument);

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

    /**
     * Cancel the entire workflow of a document, preserving its lifecycle audit trail.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
            $cancelledDocument = DB::transaction(function () use ($docNumber, $reason, $user, $request) {
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

                return $documentModel;
            });

            ActivityLogger::log(ActivityCode::DOC_CANCELLED, "Cancelled document workflow {$cancelledDocument->document_number}", $cancelledDocument);

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

    /**
     * Record a reported issue and optionally reroute the document to the assigned department.
     *
     * For processing or document errors with an assigned department, downstream steps are
     * reset, the target step is auto-received and the immediate next step is activated.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportIssue(Request $request)
    {
        $validated = $request->validate([
            'document_number'        => ['required', 'string'],
            'description'            => ['required', 'string', 'max:2000'],
            'assigned_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'issue_type'             => ['nullable', 'string'],
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

        $isReroute = false;
        $nextDepartmentId = $document->current_department_id;

        DB::transaction(function () use ($document, $documentModel, $validated, $request, &$isReroute, &$nextDepartmentId) {
            $issueType = $validated['issue_type'] ?? null;
            $assignedDeptId = $validated['assigned_department_id'] ?? null;

            $isProcessingError = in_array($issueType, ['Processing Error', 'Document Error'], true);

            if ($isProcessingError && $assignedDeptId) {
                $targetRoute = DocumentRoute::where('document_id', $document->id)
                    ->where('department_id', $assignedDeptId)
                    ->orderByDesc('route_order')
                    ->first();

                if ($targetRoute) {
                    $isReroute = true;

                    // Reset all downstream steps (route_order > target) to 'pending'
                    DocumentRoute::where('document_id', $document->id)
                        ->where('route_order', '>', $targetRoute->route_order)
                        ->update(['status' => 'pending']);

                    // Auto-receive the target department step
                    $targetRoute->status = 'received';
                    $targetRoute->received_at = Carbon::now('Asia/Manila');
                    $targetRoute->received_by_user_id = auth()->id();
                    $targetRoute->save();

                    // Find and activate the immediate next step as 'current'
                    $nextRouteStep = DocumentRoute::where('document_id', $document->id)
                        ->where('route_order', $targetRoute->route_order + 1)
                        ->first();

                    if ($nextRouteStep) {
                        $nextRouteStep->status = 'current';
                        $nextRouteStep->save();
                        $nextDepartmentId = $nextRouteStep->department_id;
                    } else {
                        $nextDepartmentId = $assignedDeptId;
                    }

                    DB::table('documents')
                        ->where('id', $document->id)
                        ->update([
                            'current_department_id' => $nextDepartmentId,
                            'status'                => 'in_transit',
                        ]);
                }
            }

            DB::table('document_issues')->insert([
                'document_id'            => $document->id,
                'reported_by_user_id'    => auth()->id(),
                'assigned_department_id' => $assignedDeptId,
                'description'            => $validated['description'],
                'type'                   => $issueType,
                'priority'               => 'medium',
                'status'                 => 'open',
                'created_at'             => Carbon::now('Asia/Manila'),
                'updated_at'             => Carbon::now('Asia/Manila'),
            ]);

            $targetDeptName = null;
            if ($isReroute && $assignedDeptId) {
                $targetDeptName = DB::table('departments')
                    ->where('id', $assignedDeptId)
                    ->value('name') ?? 'Unknown';
            }

            $eventLabel = $isReroute && $targetDeptName
                ? "Issue Reported — Document Rerouted to {$targetDeptName}"
                : self::EVENT_LABELS['issue'];

            $newStatus = $isReroute ? 'in_transit' : $document->status;

            DB::table('document_events')->insert([
                'document_id'   => $document->id,
                'user_id'       => auth()->id(),
                'department_id' => auth()->user()->department_id,
                'route_order'   => $targetRoute->route_order ?? null,
                'event_type'    => 'issue',
                'event_label'   => $eventLabel,
                'old_status'    => $document->status,
                'new_status'    => $newStatus,
                'note'          => substr($validated['description'], 0, 255),
                'metadata'      => json_encode([
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
                'created_at'    => Carbon::now('Asia/Manila'),
            ]);

            if ($isReroute && $assignedDeptId) {
                DB::table('document_events')->insert([
                    'document_id'   => $document->id,
                    'user_id'       => auth()->id(),
                    'department_id' => $assignedDeptId,
                    'route_order'   => $targetRoute->route_order ?? null,
                    'event_type'    => 'route_returned',
                    'event_label'   => self::EVENT_LABELS['route_returned'],
                    'old_status'    => $document->status,
                    'new_status'    => 'in_transit',
                    'note'          => 'Document returned to ' . $targetDeptName . ' due to reported error.',
                    'created_at'    => Carbon::now('Asia/Manila'),
                ]);
            }

            return $documentModel;
        });

        $message = $isReroute
            ? 'Issue reported and document rerouted successfully.'
            : 'Issue reported successfully.';

        ActivityLogger::log(
            ActivityCode::DOC_ISSUE_REPORTED,
            "Reported issue on document {$documentModel->document_number}",
            $documentModel
        );

        return response()->json([
            'success' => true,
            'message' => $message,
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

    /**
     * Replace the routing sequence of a document owned by the authenticated department.
     *
     * Rebuilds the route steps and re-emits 'route_defined' events for the audit trail,
     * unless the document type enforces an immutable routing policy.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $document_number The tracking number of the document to update.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRoutingPath(Request $request, $document_number)
    {
        $document = DB::table('documents')->where('document_number', $document_number)->first();
        if (!$document) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        $documentModel = Document::find($document->id);

        $isImmutable = DB::table('document_routing_policies')
            ->where('document_type_id', $document->document_type_id)
            ->value('is_immutable') ?? false;

        if (auth()->user()->department_id != $document->sender_department_id || $isImmutable) {
            return redirect()->back()->with('error', 'Unauthorized modification request.');
        }

        $routes = json_decode($request->input('edit_routes'), true);
        if (!is_array($routes) || count($routes) < 1) {
            return redirect()->back()->with('error', 'At least one routing destination department is required.');
        }

        $user = auth()->user();

        DB::transaction(function () use ($document, $routes, $user) {
            DB::table('document_routes')->where('document_id', $document->id)->delete();

            foreach ($routes as $index => $step) {
                $targetDepartmentId = (int) $step['department_id'];
                $orderSequence = (int) $step['route_order'];
                $initialStepStatus = $orderSequence === 1 ? 'next' : 'pending';

                if ($targetDepartmentId) {
                    DB::table('document_routes')->insert([
                        'document_id'   => $document->id,
                        'department_id' => $targetDepartmentId,
                        'route_order'   => $orderSequence,
                        'status'        => $initialStepStatus,
                        'created_at'    => Carbon::now('Asia/Manila'),
                        'updated_at'    => Carbon::now('Asia/Manila'),
                    ]);

                    DB::table('document_events')->insert([
                        'document_id'   => $document->id,
                        'user_id'       => $user->id,
                        'department_id' => $user->department_id,
                        'event_type'    => 'route_defined',
                        'event_label'   => 'Route step ' . $orderSequence . ' updated',
                        'new_status'    => $document->status,
                        'note'          => 'Step ' . $orderSequence . ' re-routed to department ID ' . $targetDepartmentId,
                        'created_at'    => Carbon::now('Asia/Manila'),
                    ]);
                }
            }
        });

        ActivityLogger::log(
            ActivityCode::DOC_ROUTE_UPDATED,
            "Updated routing path for document {$documentModel->document_number}",
            $documentModel
        );

        return redirect()->back()->with('success', 'Document routing path updated successfully.');
    }
}
