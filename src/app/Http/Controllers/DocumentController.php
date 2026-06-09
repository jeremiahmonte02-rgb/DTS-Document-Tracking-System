<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class DocumentController extends Controller
{
    public function create()
    {
        $documentTypes = DB::table('document_types')->orderBy('name')->get();
        $departments = DB::table('departments')->orderBy('name')->get();

        $user = auth()->user();

        $existingDocuments = DB::table('documents')
            ->select('id', 'document_number', 'title', 'document_type_id', 'sender_department_id', 'description')
            ->where('sender_department_id', $user->department_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($doc) {
                $doc->routes = DB::table('document_routes')
                    ->join('departments', 'document_routes.department_id', '=', 'departments.id')
                    ->where('document_id', $doc->id)
                    ->orderBy('route_order', 'asc')
                    ->select('departments.id as department_id', 'departments.name as department_name', 'departments.code as department_code', 'document_routes.route_order')
                    ->get()
                    ->toArray();
                return $doc;
            });

        return view('upload', compact('documentTypes', 'departments', 'existingDocuments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'documentType' => 'required|exists:document_types,id',
            'department' => 'required|exists:departments,id',
            'description' => 'nullable|string|max:1000',
            'fileUpload' => 'required|file|mimes:pdf,docx,doc,xls,xlsx,jpg,png|max:20480',
            'routes' => 'required|string',
        ]);

        $user = auth()->user();

        $documentId = (string) Str::orderedUuid();
        $yearToken = Carbon::now()->year;
        $randomHash = strtoupper(Str::random(4));
        $documentNumber = "DTS-{$yearToken}-{$randomHash}";

        DB::transaction(function () use ($request, $user, $documentId, $documentNumber) {

            DB::table('documents')->insert([
                'id' => $documentId,
                'document_number' => $documentNumber,
                'title' => $request->title,
                'document_type_id' => $request->documentType,
                'sender_department_id' => $request->department,
                'uploaded_by_user_id' => $user->id,
                'current_department_id' => $user->department_id,
                'status' => 'pending_transfer',
                'description' => $request->description,
                'uploaded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $file = $request->file('fileUpload');
            $originalFilename = $file->getClientOriginalName();
            $storedFilename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('private/documents', $storedFilename);

            DB::table('document_files')->insert([
                'document_id' => $documentId,
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by_user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('document_events')->insert([
                'document_id' => $documentId,
                'user_id' => $user->id,
                'department_id' => $user->department_id,
                'event_type' => 'creation',
                'event_label' => 'Document Registered & Uploaded',
                'old_status' => null,
                'new_status' => 'pending_transfer',
                'note' => $request->description ?? 'Initial entry verification trail logging.',
                'metadata' => json_encode(['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]),
                'created_at' => now(),
            ]);

            $rawRoutesString = $request->input('routes');
            $routeSteps = is_string($rawRoutesString) ? json_decode($rawRoutesString, true) : $rawRoutesString;

            if (!empty($routeSteps) && is_array($routeSteps)) {
                foreach ($routeSteps as $index => $step) {
                    $targetDepartmentId = isset($step['department_id']) ? (int)$step['department_id'] : null;
                    $orderSequence = isset($step['route_order']) ? (int)$step['route_order'] : ($index + 1);
                    $initialStepStatus = ($orderSequence === 1) ? 'current' : 'pending';

                    if ($targetDepartmentId) {
                        DB::table('document_routes')->insert([
                            'document_id' => $documentId,
                            'department_id' => $targetDepartmentId,
                            'route_order' => $orderSequence,
                            'status' => $initialStepStatus,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Document reference ticket {$documentNumber} registered successfully!",
            'document_number' => $documentNumber
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
                'message' => 'Tracking parameters missing or invalid.'
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
                'message' => 'Document reference ticket not found.'
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
            'success' => true,
            'document' => $document,
            'routes' => $routes,
            'events' => $events
        ]);
    }

    public function showDocumentDetails($document_number)
    {
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
            return response()->json(['success' => false, 'message' => 'Target document parameter missing.'], 400);
        }

        try {
            $result = DB::transaction(function () use ($docNumber, $note, $user) {

                $document = DB::table('documents')
                    ->where('document_number', $docNumber)
                    ->first();

                if (!$document) {
                    throw new Exception('Document tracking record not found in system archives.', 404);
                }

                $currentRouteStep = DB::table('document_routes')
                    ->where('document_id', $document->id)
                    ->where('status', 'current')
                    ->first();

                if (!$currentRouteStep) {
                    throw new Exception('No active routing checkpoint currently assigned to this document.', 422);
                }

                if (intval($currentRouteStep->department_id) !== intval($user->department_id)) {
                    throw new Exception('Access Denied: Your assigned department does not match the active route destination.', 403);
                }

                DB::table('document_routes')
                    ->where('id', $currentRouteStep->id)
                    ->update(['status' => 'received']);

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
                    'status' => $newDocumentStatus,
                    'current_department_id' => $nextDepartmentId,
                    'updated_at' => now()
                ];

                if ($newDocumentStatus === 'received') {
                    $updatePayload['completed_at'] = now();
                }

                DB::table('documents')
                    ->where('id', $document->id)
                    ->update($updatePayload);

                DB::table('document_events')->insert([
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'department_id' => $user->department_id,
                    'event_type' => 'status_change',
                    'event_label' => 'received',
                    'note' => $note ?: 'Document acknowledged and received safely.',
                    'new_status' => $newDocumentStatus,
                    'created_at' => now()
                ]);

                return [
                    'message' => $newDocumentStatus === 'received'
                        ? 'Document workflow path completed successfully!'
                        : 'Document successfully received and queued for next hop routing.'
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);

        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() <= 500) ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function confirmReceipt(Request $request)
    {
        $documentId = $request->input('document_id');
        $userId = auth()->id();

        $document = DB::table('documents')->where('id', $documentId)->first();
        if (!$document) {
            return response()->json(['success' => false, 'message' => 'Document record tracking sequence not found.'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('document_routes')
                ->where('document_id', $documentId)
                ->where('status', 'current')
                ->update(['status' => 'received', 'updated_at' => now()]);

            $nextRoute = DB::table('document_routes')
                ->where('document_id', $documentId)
                ->where(DB::raw('LOWER(status)'), 'pending')
                ->orderBy('route_order', 'asc')
                ->first();

            if ($nextRoute) {
                DB::table('document_routes')
                    ->where('id', $nextRoute->id)
                    ->update(['status' => 'current', 'updated_at' => now()]);

                DB::table('documents')->where('id', $documentId)->update([
                    'status' => 'in_transit',
                    'updated_at' => now()
                ]);
            } else {
                DB::table('documents')->where('id', $documentId)->update([
                    'status' => 'received',
                    'updated_at' => now()
                ]);
            }

            DB::table('document_events')->insert([
                'document_id' => $documentId,
                'user_id' => $userId,
                'department_id' => auth()->user()->department_id ?? 2,
                'event_type' => 'status_change',
                'event_label' => 'Document Received',
                'new_status' => $nextRoute ? 'in_transit' : 'received',
                'note' => 'The document receipt loop has been formally verified and logged by station operator.',
                'created_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Transaction successfully logged.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Database storage failure: ' . $e->getMessage()], 500);
        }
    }
}
