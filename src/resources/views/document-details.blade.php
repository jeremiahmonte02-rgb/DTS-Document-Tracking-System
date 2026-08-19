@extends('layouts.app')

@section('title', 'Document Details - Document Tracking System')



@section('pageTitle', 'Document Details')

<style>
    @media print {
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .print-page-break {
            page-break-before: always !important;
            break-before: page !important;
        }
    }
</style>

@section('content')
            <div class="mb-3 no-print d-print-none">
                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <div class="row g-4">
                <!-- Document Information -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-text"></i> Document Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Document ID:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docId" class="font-mono text-primary fw-bold">{{ $document->document_number }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Title:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docTitle">{{ $document->title }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Document Type:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docType">{{ $document->document_type_name }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Sender Department:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docSender">{{ $document->origin_department }}</span>
                                </div>
                            </div>
                            @php
                                // Find the last step in the routing chain that has been marked received or completed
                                $lastReceivedStep = collect($routes)
                                    ->whereIn('status', ['received', 'completed'])
                                    ->sortByDesc('route_order')
                                    ->first();

                                // Determine display name: use the last receiving department, or fall back to the sender department
                                $currentLocationName = $lastReceivedStep
                                    ? $lastReceivedStep->department_name
                                    : ($document->origin_department ?? 'Originating Office');
                            @endphp

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Current Location:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docReceiver" class="fw-bold">{{ $currentLocationName }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Current Status:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docStatus"><span class="badge text-uppercase px-3 py-1 
                                        {{ $document->status === 'received' ? 'bg-success' : ($document->status === 'in_transit' ? 'bg-info text-white' : ($document->status === 'pending_transfer' ? 'bg-warning text-dark' : ($document->status === 'rejected' ? 'bg-danger' : ($document->status === 'completed' ? 'bg-success text-white' : 'bg-secondary text-white')))) }}">{{ $document->status }}</span></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Upload Date:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docUploadDate" class="tabular-nums">{{ \Carbon\Carbon::parse($document->upload_date)->format('M d, Y h:i A') }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Uploaded By:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docUploadedBy">{{ $document->uploaded_by_user ?? 'System' }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Completed Date:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docReceivedDate" class="tabular-nums">{{ $document->completed_at ? \Carbon\Carbon::parse($document->completed_at)->format('M d, Y h:i A') : '-' }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Routing Steps:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docReceivedBy">{{ count($routes) }} department{{ count($routes) !== 1 ? 's' : '' }} in routing chain</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong class="text-muted">Description:</strong>
                                </div>
                                <div class="col-md-8">
                                    <p id="docDescription" class="mb-0">{{ $document->description ?? 'No description provided.' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white no-print d-print-none">
                            @php
                                $lastStep = collect($routes)->sortBy('route_order')->last();
                                $isFinalDepartment = $lastStep && auth()->user() && $lastStep->department_id === auth()->user()->department_id;
                                $isLastStepCustody = $lastStep && strtolower($lastStep->status) === 'current';
                                $isDocumentCompleted = isset($document->status) && strtolower($document->status) === 'completed';
                            @endphp
                            <div class="d-flex flex-column flex-md-row gap-2 w-100">
                                <button class="btn btn-primary w-100 w-md-auto" onclick="window.print()">
                                    <i class="bi bi-printer"></i> Print Details
                                </button>
                                <button class="btn btn-success w-100 w-md-auto" onclick="downloadDocument()">
                                    <i class="bi bi-download"></i> Download Document
                                </button>
                                <button class="btn btn-info w-100 w-md-auto" onclick="shareDocument()">
                                    <i class="bi bi-share"></i> Share
                                </button>
                                @if($isFinalDepartment && $isLastStepCustody && !$isDocumentCompleted)
                                <button class="btn btn-dark w-100 w-md-auto" id="markAsCompleteBtn" onclick="markDocumentAsComplete('{{ $document->document_number }}')">
                                    <i class="bi bi-check-all"></i> Mark as Complete
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail -->
                    <div class="card mb-4 print-page-break">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> Audit Trail & Document History
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline" id="auditTrail">
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Loading timeline...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Code and Quick Actions -->
                <div class="col-lg-4">
                    <div class="card mb-4 print-page-break">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="bi bi-qr-code"></i> QR Code
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div id="qrCodePrintContainer" class="text-center p-3 border rounded bg-white shadow-3xs mb-4">
                                <div class="qr-code-wrapper d-inline-block p-2 bg-light rounded border border-secondary-subtle">
                                     <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($document->document_number) }}&ecc=M"
                                          alt="Document QR Code ({{ $document->document_number }})"
                                          class="img-fluid qr-code-img">
                                </div>
                                <div class="mt-2 font-mono text-xs text-muted fw-semibold">
                                    {{ $document->document_number }}
                                </div>
                            </div>
                            <button type="button" id="detailsPrintQrBtn" class="btn btn-primary btn-sm w-100 no-print d-print-none">
                                <i class="bi bi-printer"></i> Print QR Code
                            </button>
                        </div>
                    </div>

                    <div class="card mb-4 shadow-3xs">
                        <div class="card-header bg-transparent border-bottom-0 pt-3 pb-1 d-flex justify-content-between align-items-center">
                            <h6 class="card-title text-muted text-uppercase text-xs fw-bold tracking-wider m-0">
                                <i class="bi bi-diagram-3 me-1"></i> Scheduled Routing Path
                            </h6>
                            @if(auth()->user()->department_id == $document->sender_department_id && !$isImmutable)
                            <button type="button" id="editRoutingPathBtn" class="btn btn-light btn-sm p-0 flex-shrink-0 d-inline-flex align-items-center justify-content-center border" style="width: 32px; height: 32px;" title="Edit Routing Path">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil text-dark" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 1.586L10.5 1.914 1.354 11.063 2.646 12.354zm-1.2 1.2L9.207 3.5 1.5 11.207V13.5h2.293zm1.5-1.5H1.5v2.293h2.293zm-1.5-1.5H1.5v2.293h2.293z"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                        <div class="card-body pt-1">
                            <div class="d-flex flex-column gap-2">
                                @php $currentReceiverDepartmentId = 'null'; @endphp
                                @php $userIsNext = false; @endphp
                                @foreach($routes as $route)
                                    @php
                                        $isCurrent = strtolower($route->status) === 'current';
                                        $isNext = strtolower($route->status) === 'next';
                                        if ($isCurrent) {
                                            $currentReceiverDepartmentId = $route->department_id;
                                        }
                                        if ($isNext && auth()->user() && (int) $route->department_id === (int) auth()->user()->department_id) {
                                            $userIsNext = true;
                                        }

                                        $badgeStyle = 'bg-secondary text-white';
                                        $rowModifier = 'border-light-subtle bg-white';

                                        if ($isCurrent) {
                                            $badgeStyle = 'bg-warning text-dark fw-bold';
                                            $rowModifier = 'border-warning bg-warning-subtle bg-opacity-10';
                                        } elseif (in_array(strtolower($route->status), ['received', 'completed'])) {
                                            $badgeStyle = 'bg-success text-white';
                                            $rowModifier = 'border-success-subtle';
                                        } elseif ($isNext) {
                                            $badgeStyle = 'bg-info text-white';
                                            $rowModifier = 'border-info-subtle';
                                        }
                                    @endphp

                                    <div class="p-2 border rounded d-flex align-items-center justify-content-between {{ $rowModifier }}" data-dept-id="{{ $route->department_id }}" data-dept-name="{{ $route->department_name }}">
                                        <div class="d-flex align-items-center">
                                            <span class="badge {{ $badgeStyle }} rounded-circle me-2 font-mono d-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">
                                                {{ $route->route_order }}
                                            </span>
                                            <span class="fw-semibold text-xs text-dark">{{ $route->department_name }}</span>
                                        </div>
                                        <span class="badge text-uppercase text-xxs px-2 py-1 {{ $badgeStyle }}">
                                            {{ $route->status }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 no-print d-print-none">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="bi bi-lightning"></i> Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($userIsNext)
                                <button type="button" id="markAsReceivedBtn" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-check-circle"></i> Mark as Received
                                </button>
                                @endif
                                <button type="button" id="requestAccessBtn" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Request Access
                                </button>
                                <button type="button" id="reportIssueBtn" class="btn btn-outline-danger btn-sm" onclick="reportIssue()">
                                    <i class="bi bi-exclamation-triangle"></i> Report Issue
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="bi bi-shield-check"></i> Security Info
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="small mb-2">
                                <i class="bi bi-lock text-success"></i>
                                <strong>Access Level:</strong> Authorized
                            </p>
                            <p class="small mb-2">
                                <i class="bi bi-eye text-info"></i>
                                <strong>Views:</strong> N/A
                            </p>
                            <p class="small mb-0">
                                <i class="bi bi-shield-check text-primary"></i>
                                <strong>Audit Trail:</strong> Complete
                            </p>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Report Issue Modal -->
    <div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="reportIssueLabel">
                        <i class="bi bi-exclamation-triangle"></i> Report Document Issue
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reportIssueForm" onsubmit="handleReportIssueSubmit(event)">
                    <div class="modal-body">
                        <!-- Document ID (Read-only) -->
                        <div class="mb-3">
                            <label for="issueDocId" class="form-label">
                                <i class="bi bi-file-earmark-text"></i> Document ID
                            </label>
                            <input type="text" class="form-control" id="issueDocId" value="{{ $document->document_number }}" readonly style="background-color: #f8f9fa;">
                        </div>

                        <!-- Issue Description -->
                        <div class="mb-3">
                            <label for="issueDescription" class="form-label">
                                <i class="bi bi-chat-left-text"></i> Issue Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="issueDescription" rows="4" placeholder="Describe the issue in detail..." style="resize: vertical;"></textarea>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle"></i> Please provide as much detail as possible about the issue
                            </small>
                        </div>

                        <!-- Issue Type -->
                        <div class="mb-3">
                            <label for="issueType" class="form-label">
                                <i class="bi bi-exclamation-triangle"></i> Issue Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="issueType" required>
                                <option value="">-- Select Issue Type --</option>
                                <option value="Document Error">Document Error</option>
                                <option value="Processing Delay">Processing Delay</option>
                                <option value="Others">Others</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle"></i> Select the type of issue you are reporting
                            </small>
                        </div>

                        <!-- Responsible Department -->
                        <div class="mb-3">
                            <label for="issueDept" class="form-label">
                                <i class="bi bi-building"></i> Responsible Department
                            </label>
                            <select class="form-select" id="issueDept">
                                <option value="">-- Select Department --</option>
                                @foreach($routes->unique('department_id') as $route)
                                    <option value="{{ $route->department_id }}">{{ $route->department_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle"></i> Select the department responsible for this issue
                            </small>
                        </div>

                        <!-- Priority (Optional) -->
                        <div class="mb-3">
                            <label for="issuePriority" class="form-label">
                                <i class="bi bi-exclamation-square"></i> Priority Level
                            </label>
                            <select class="form-select" id="issuePriority">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-exclamation-triangle"></i> Report Issue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Routing Path Modal -->
    <div class="modal fade" id="editRoutingModal" tabindex="-1" aria-labelledby="editRoutingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('documents.update-routing', $document->document_number) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoutingModalLabel">
                            <i class="bi bi-diagram-3 me-1"></i> Edit Routing Path
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="w-100 route-builder-container">
                            <div class="row g-4 align-items-center">

                                <div class="col-md-5">
                                    <label class="form-label text-secondary fw-semibold mb-2" style="font-size: 0.875rem;">Receiver Departments (ordered)</label>
                                    <div class="route-builder-pool">
                                        <div class="route-list-inner">

                                            <ul class="list-group list-group-flush" id="edit-visual-dept-pool">
                                                @foreach($departments ?? [] as $dept)
                                                    <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark transition-colors"
                                                        data-value="{{ $dept->id ?? $dept }}" style="letter-spacing: -0.01em;">
                                                        {{ $dept->name ?? $dept }}
                                                    </li>
                                                @endforeach
                                            </ul>

                                            <div id="edit-immutablePolicyNotice" class="d-none my-auto p-4 text-center d-flex flex-column align-items-center justify-content-center w-100 h-100">
                                                <i class="bi bi-shield-lock text-danger mb-2" style="font-size: 1.75rem;"></i>
                                                <h6 class="fw-bold text-dark mb-1" style="font-family: 'Satoshi', sans-serif;">Enforced Routing Policy</h6>
                                                <p class="text-muted small mb-0 px-2" style="font-size: 0.85rem;">
                                                    The workflow pathway for this document type has been strictly locked by the system Auditor to ensure organizational compliance.
                                                </p>
                                            </div>

                                        </div>
                                    </div>

                                    <select id="edit-receiverDepartments" name="edit_receiver_departments[]" class="d-none" multiple>
                                        @foreach($departments ?? [] as $dept)
                                            <option value="{{ $dept->id ?? $dept }}">{{ $dept->name ?? $dept }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 d-flex flex-column gap-2.5 px-1 pt-4">
                                    <button id="edit-addToRouteBtn" type="button" class="btn btn-custom-academic btn-sm w-100 fw-bold border-2 text-center text-nowrap py-2" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 8px;">
                                        ADD SELECTED
                                    </button>
                                    <button id="edit-clearRouteBtn" type="button" class="btn btn-custom-clear btn-sm w-100 fw-bold border-2 text-center text-nowrap py-2" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 8px;">
                                        CLEAR
                                    </button>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label text-secondary fw-semibold mb-2" style="font-size: 0.875rem;">Route (ordered)</label>
                                    <div class="route-builder-route">

                                        <div id="edit-route-placeholder" class="d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                                            <svg class="mb-2" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                            </svg>
                                            <p class="mb-0" style="font-size: 0.75rem;">Selected departments will appear here in sequence</p>
                                        </div>

                                        <ul id="edit-routeList" class="list-group list-group-flush w-100 h-100 overflow-y-auto custom-scrollbar d-none route-list-inner"></ul>
                                    </div>
                                </div>

                            </div>

                            <input type="hidden" id="edit-routesInput" name="edit_routes">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                        <button type="submit" id="edit-saveRouteBtn" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('partials.access-denied-modal')
@endsection

@section('scripts')
    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <!-- Page Modules -->
    <script src="{{ asset('js/modules/timeline-renderer.js') }}"></script>
    <script src="{{ asset('js/modules/document-details.js') }}"></script>

    <!-- Utility Functions -->
    <script>
        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function renumberEditRouteModal() {
            const badges = document.querySelectorAll('#edit-routeList .index-counter-badge');
            badges.forEach((badge, index) => {
                badge.textContent = index + 1;
            });
        }

        function synchronizeEditRoutesInput() {
            const listItems = document.querySelectorAll('#edit-routeList li');
            const data = [];
            listItems.forEach(function(li, index) {
                const deptId = li.getAttribute('data-dept-id');
                if (deptId) {
                    data.push({
                        department_id: parseInt(deptId),
                        route_order: index + 1
                    });
                }
            });
            const input = document.getElementById('edit-routesInput');
            if (input) input.value = JSON.stringify(data);
        }

        function appendStepToModalRouteList(id, name) {
            const routeListContainer = document.getElementById('edit-routeList');
            if (!routeListContainer) return;

            const existing = routeListContainer.querySelector(`li[data-dept-id="${id}"]`);
            if (existing) return;

            document.getElementById('edit-route-placeholder')?.classList.add('d-none');
            routeListContainer.classList.remove('d-none');

            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center text-xs p-2 bg-light shadow-2xs mb-1 rounded border';
            li.setAttribute('data-dept-id', id);
            li.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary index-counter-badge me-2">0</span>
                    <span class="text-dark font-medium font-mono">${escapeHtml(name)}</span>
                </div>
                <div class="d-flex gap-1" role="group">
                    <button type="button" class="btn btn-white btn-sm move-up-btn" title="Move Up"><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-white btn-sm move-down-btn" title="Move Down"><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-danger btn-sm remove-step-btn" title="Remove"><i class="bi bi-trash"></i></button>
                </div>
            `;
            routeListContainer.appendChild(li);
        }

        // --- Document Action Functions ---

        function markDocumentAsComplete(documentNumber) {
            if (!confirm('Are you sure you want to officially mark this document as complete? This will finalize its routing record.')) return;

            const token = "{{ csrf_token() }}";

            fetch(`/documents/${documentNumber}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert("Backend Error: " + (data.message || 'An error occurred.'));
                }
            })
            .catch(error => {
                console.error("Network Fetch Failure:", error);
                alert("Network Error: Check browser console for network stream logs.");
            });
        }

        function downloadDocument() {
            showToast('Download feature coming soon', 'info');
        }

        function shareDocument() {
            const docId = document.getElementById('docId').textContent;
            const shareUrl = window.location.href;

            if (navigator.share) {
                navigator.share({
                    title: 'Document Details',
                    text: `Check out document ${docId}`,
                    url: shareUrl
                });
            } else {
                navigator.clipboard.writeText(shareUrl);
                showToast('Link copied to clipboard!', 'success');
            }
        }

        function reportIssue() {
            document.getElementById('reportIssueForm').reset();
            document.getElementById('issueType').value = '';
            document.getElementById('issueDept').value = '';
            document.getElementById('issuePriority').value = 'Medium';

            const desc = document.getElementById('issueDescription');
            desc.required = false;

            const modal = new bootstrap.Modal(document.getElementById('reportIssueModal'));
            modal.show();
        }

        function handleReportIssueSubmit(event) {
            event.preventDefault();

            const form = event.target;
            const docId = document.getElementById('issueDocId').value;
            const description = document.getElementById('issueDescription').value;
            const issueType = document.getElementById('issueType').value;
            const assignedDepartmentId = document.getElementById('issueDept').value;

            if (!issueType || (issueType === 'Others' && !description.trim())) {
                showToast('Please fill in all required fields', 'warning');
                return;
            }

            showSpinner();

            const modal = bootstrap.Modal.getInstance(document.getElementById('reportIssueModal'));

            const payload = {
                document_number: docId,
                description: description,
                issue_type: issueType
            };

            if (assignedDepartmentId) {
                payload.assigned_department_id = assignedDepartmentId;
            }

            fetch('/api/issues', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json().catch(() => ({})))
            .then(data => {
                hideSpinner();
                modal.hide();
                form.reset();
                showToast(data.message || 'Issue reported successfully!', 'success');
                refreshDocumentState(docId);
            })
            .catch(err => {
                hideSpinner();
                modal.hide();
                form.reset();
                showToast(err.message || 'Failed to submit issue report.', 'danger');
            });
        }

        function refreshDocumentState(docNumber) {
            var lookupUrl = '/scan/lookup?document_number=' + encodeURIComponent(docNumber);

            fetch(lookupUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                if (data.events && typeof window.renderTimeline === 'function') {
                    window.renderTimeline('auditTrail', data.events, { reverseOrder: true });
                }

                if (data.routes) {
                    var routeStepDivs = [];
                    document.querySelectorAll('.card').forEach(function (card) {
                        if (card.textContent.indexOf('Scheduled Routing Path') !== -1) {
                            routeStepDivs = card.querySelectorAll('[data-dept-id]');
                        }
                    });

                    routeStepDivs.forEach(function (div) {
                        var deptId = div.getAttribute('data-dept-id');
                        var route = data.routes.find(function (r) { return String(r.department_id) === String(deptId); });
                        if (!route) return;

                        var badge = div.querySelector('.badge:last-child');
                        if (badge) {
                            badge.textContent = route.status.toUpperCase();

                            badge.classList.remove('bg-secondary', 'text-white', 'bg-warning', 'text-dark', 'fw-bold', 'bg-success', 'bg-info');
                            if (route.status === 'current') {
                                badge.classList.add('bg-warning', 'text-dark', 'fw-bold');
                            } else if (route.status === 'received' || route.status === 'completed') {
                                badge.classList.add('bg-success', 'text-white');
                            } else if (route.status === 'next') {
                                badge.classList.add('bg-info', 'text-white');
                            } else {
                                badge.classList.add('bg-secondary', 'text-white');
                            }
                        }

                        var numberCircle = div.querySelector('span.badge.rounded-circle');
                        if (numberCircle) {
                            numberCircle.classList.remove('bg-secondary', 'text-white', 'bg-warning', 'text-dark', 'fw-bold', 'bg-success', 'bg-info');
                            if (route.status === 'current') {
                                numberCircle.classList.add('bg-warning', 'text-dark', 'fw-bold');
                            } else if (route.status === 'received' || route.status === 'completed') {
                                numberCircle.classList.add('bg-success', 'text-white');
                            } else if (route.status === 'next') {
                                numberCircle.classList.add('bg-info', 'text-white');
                            } else {
                                numberCircle.classList.add('bg-secondary', 'text-white');
                            }
                        }

                        div.classList.remove('border-warning', 'bg-warning-subtle', 'bg-opacity-10', 'border-success-subtle', 'border-info-subtle', 'border-light-subtle', 'bg-white');
                        if (route.status === 'current') {
                            div.classList.add('border-warning', 'bg-warning-subtle', 'bg-opacity-10');
                        } else if (route.status === 'received' || route.status === 'completed') {
                            div.classList.add('border-success-subtle');
                        } else if (route.status === 'next') {
                            div.classList.add('border-info-subtle');
                        } else {
                            div.classList.add('border-light-subtle', 'bg-white');
                        }
                    });
                }

                var docStatusBadge = document.querySelector('#docStatus .badge');
                if (docStatusBadge && data.document) {
                    var newStatus = data.document.status || '';
                    docStatusBadge.textContent = newStatus.toUpperCase();
                    docStatusBadge.classList.remove('bg-success', 'bg-info', 'text-white', 'bg-warning', 'text-dark', 'bg-danger', 'bg-secondary');
                    if (newStatus === 'received' || newStatus === 'completed') {
                        docStatusBadge.classList.add('bg-success', 'text-white');
                    } else if (newStatus === 'in_transit') {
                        docStatusBadge.classList.add('bg-info', 'text-white');
                    } else if (newStatus === 'pending_transfer') {
                        docStatusBadge.classList.add('bg-warning', 'text-dark');
                    } else if (newStatus === 'rejected') {
                        docStatusBadge.classList.add('bg-danger', 'text-white');
                    } else {
                        docStatusBadge.classList.add('bg-secondary', 'text-white');
                    }
                }
            })
            .catch(function (err) {
                console.error('[refreshDocumentState] Failed to refresh:', err);
            });
        }

        document.getElementById('issueType')?.addEventListener('change', function () {
            const desc = document.getElementById('issueDescription');
            desc.required = this.value === 'Others';
        });
    </script>

    <!-- DOM Ready Handlers -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- QR CODE PRINT ---
            const printQrBtn = document.getElementById('detailsPrintQrBtn');

            if (printQrBtn) {
                printQrBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    const qrContainer = document.getElementById('qrCodePrintContainer');
                    if (!qrContainer) {
                        console.error("Target print structural container (#qrCodePrintContainer) missing from DOM.");
                        return;
                    }

                    const printWindow = window.open('', '_blank', 'width=600,height=600');
                    if (!printWindow) {
                        alert("Please enable popups to launch the printing utility.");
                        return;
                    }

                    printWindow.document.write(`
                        <html>
                        <head>
                            <title>Print Document QR Code</title>
                            <style>
                                body { 
                                    display: flex; 
                                    flex-direction: column; 
                                    align-items: center; 
                                    justify-content: center; 
                                    height: 100vh; 
                                    margin: 0; 
                                    font-family: Arial, sans-serif;
                                }
                                img { max-width: 250px; height: auto; margin-bottom: 15px; image-rendering: crisp-edges; }
                                .font-mono { font-size: 20px; font-weight: bold; color: #333; letter-spacing: 0.5px; }
                            </style>
                        </head>
                        <body>
                            ${qrContainer.innerHTML}
                            <script>
                                window.onload = function() { 
                                    window.print(); 
                                    window.close(); 
                                };
                            <\/script>
                        </body>
                        </html>
                    `);
                    printWindow.document.close();
                });
            }

            // --- MARK AS RECEIVED VALIDATION ---
            const markAsReceivedBtn = document.getElementById('markAsReceivedBtn');

            if (markAsReceivedBtn) {
                markAsReceivedBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    const userDepartmentId = window.DTS_AUTH_CONTEXT && window.DTS_AUTH_CONTEXT.departmentId
                        ? window.DTS_AUTH_CONTEXT.departmentId.toString().trim()
                        : null;

                    const activeReceiverDepartmentId = '{{ $currentReceiverDepartmentId }}'.trim();

                    if (!userDepartmentId || !activeReceiverDepartmentId || userDepartmentId !== activeReceiverDepartmentId) {
                        const accessModalElement = document.getElementById('routedDocumentErrorModal');
                        if (accessModalElement && typeof bootstrap !== 'undefined') {
                            const modalInstance = bootstrap.Modal.getOrCreateInstance(accessModalElement);
                            modalInstance.show();
                        } else {
                            alert("Access Denied: Your department is not authorized to receive this tracking file at this step.");
                        }
                        return;
                    }

                    markAsReceivedBtn.disabled = true;
                    const originalBtnContent = markAsReceivedBtn.innerHTML;
                    markAsReceivedBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...`;

                    const documentNumber = '{{ $document->document_number }}';
                    const targetEndpoint = `/documents/${documentNumber}/receive`;

                    fetch(targetEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            document_number: documentNumber
                        })
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(data.message || `Server returned response status error code: ${response.status}`);
                        }
                        return data;
                    })
                    .then(data => {
                        window.location.reload();
                    })
                    .catch(error => {
                        console.error("Workflow transmission transaction breakdown error:", error);
                        alert(`Failed to complete transaction check: ${error.message}`);

                        markAsReceivedBtn.disabled = false;
                        markAsReceivedBtn.innerHTML = originalBtnContent;
                    });
                });
            }

            // --- REQUEST ACCESS BUTTON ---
            const requestAccessBtn = document.getElementById('requestAccessBtn');
            const errorModalElement = document.getElementById('routedDocumentErrorModal');

            if (requestAccessBtn && errorModalElement) {
                requestAccessBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const modalInstance = bootstrap.Modal.getOrCreateInstance(errorModalElement);
                    modalInstance.show();
                });
            }

            // --- EDIT ROUTING PATH MODAL ---
            const editRoutingBtn = document.getElementById('editRoutingPathBtn');
            const editModalEl = document.getElementById('editRoutingModal');
            if (editRoutingBtn && editModalEl) {
                editRoutingBtn.addEventListener('click', function() {
                    const routeListContainer = document.getElementById('edit-routeList');
                    routeListContainer.innerHTML = '';
                    routeListContainer.classList.add('d-none');
                    const placeholder = document.getElementById('edit-route-placeholder');
                    if (placeholder) placeholder.classList.remove('d-none');

                    const sidebarCard = document.querySelector('.card.mb-4.shadow-3xs');
                    const activeSteps = sidebarCard ? sidebarCard.querySelectorAll('[data-dept-id]') : document.querySelectorAll('[data-dept-id]');
                    activeSteps.forEach(function(step, index) {
                        const id = step.getAttribute('data-dept-id');
                        const name = step.getAttribute('data-dept-name');
                        if (id && name) {
                            appendStepToModalRouteList(id, name);
                        }
                    });

                    renumberEditRouteModal();
                    synchronizeEditRoutesInput();

                    bootstrap.Modal.getOrCreateInstance(editModalEl).show();
                });

                const editRouteListEl = document.getElementById('edit-routeList');
                if (editRouteListEl) {
                    editRouteListEl.addEventListener('click', function(e) {
                        const btn = e.target.closest('button');
                        if (!btn) return;
                        const li = btn.closest('li');
                        if (!li) return;

                        if (btn.classList.contains('remove-step-btn')) {
                            li.remove();
                            renumberEditRouteModal();
                            synchronizeEditRoutesInput();
                        } else if (btn.classList.contains('move-up-btn')) {
                            const prev = li.previousElementSibling;
                            if (prev) li.parentNode.insertBefore(li, prev);
                            renumberEditRouteModal();
                            synchronizeEditRoutesInput();
                        } else if (btn.classList.contains('move-down-btn')) {
                            const next = li.nextElementSibling;
                            if (next) li.parentNode.insertBefore(li, next.nextElementSibling);
                            renumberEditRouteModal();
                            synchronizeEditRoutesInput();
                        }
                    });
                }

                const editDeptPool = document.getElementById('edit-visual-dept-pool');
                if (editDeptPool) {
                    editDeptPool.addEventListener('click', function(e) {
                        const item = e.target.closest('.list-group-item');
                        if (!item) return;

                        const val = item.getAttribute('data-value');
                        const hiddenSelect = document.getElementById('edit-receiverDepartments');
                        const option = hiddenSelect ? hiddenSelect.querySelector(`option[value="${val}"]`) : null;

                        if (option) {
                            option.selected = !option.selected;
                            item.classList.toggle('bg-success');
                            item.classList.toggle('bg-opacity-10');
                            item.classList.toggle('text-success');
                            item.classList.toggle('fw-semibold');
                            hiddenSelect.dispatchEvent(new Event('change'));
                        }
                    });
                }

                document.getElementById('edit-addToRouteBtn')?.addEventListener('click', function() {
                    const deptSelect = document.getElementById('edit-receiverDepartments');
                    if (!deptSelect) return;
                    Array.from(deptSelect.selectedOptions).forEach(function(opt) {
                        appendStepToModalRouteList(opt.value, opt.text);
                        const poolItem = editDeptPool?.querySelector(`li[data-value="${CSS.escape(opt.value)}"]`);
                        if (poolItem) {
                            poolItem.classList.remove('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
                        }
                        opt.selected = false;
                    });
                    renumberEditRouteModal();
                    synchronizeEditRoutesInput();
                });

                document.getElementById('edit-clearRouteBtn')?.addEventListener('click', function() {
                    const rl = document.getElementById('edit-routeList');
                    if (rl) {
                        rl.innerHTML = '';
                        rl.classList.add('d-none');
                    }
                    const ph = document.getElementById('edit-route-placeholder');
                    if (ph) ph.classList.remove('d-none');

                    if (editDeptPool) {
                        editDeptPool.querySelectorAll('.list-group-item').forEach(function(item) {
                            item.classList.remove('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
                        });
                    }
                    const hiddenSelect = document.getElementById('edit-receiverDepartments');
                    if (hiddenSelect) {
                        Array.from(hiddenSelect.options).forEach(function(opt) { opt.selected = false; });
                    }
                    synchronizeEditRoutesInput();
                });

                const editRouteListObserve = document.getElementById('edit-routeList');
                const editPlaceholder = document.getElementById('edit-route-placeholder');
                if (editRouteListObserve && editPlaceholder) {
                    new MutationObserver(function() {
                        if (editRouteListObserve.children.length > 0) {
                            editRouteListObserve.classList.remove('d-none');
                            editPlaceholder.classList.add('d-none');
                        } else {
                            editRouteListObserve.classList.add('d-none');
                            editPlaceholder.classList.remove('d-none');
                        }
                    }).observe(editRouteListObserve, { childList: true });
                }
            }
        });
    </script>
@endsection
