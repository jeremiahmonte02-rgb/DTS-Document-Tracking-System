<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Details - Document Tracking System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-file-earmark-text"></i> DTS</h4>
            <small class="text-white-50">Document Tracking</small>
        </div>
        <ul class="sidebar-nav nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="/dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/upload">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Upload Document</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/scan">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Scan QR Code</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/inbox">
                    <i class="bi bi-inbox"></i>
                    <span>Inbox</span>
                    <span class="badge bg-danger ms-auto">3</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/outbox">
                    <i class="bi bi-send"></i>
                    <span>Outbox</span>
                </a>
            </li>
            @if(auth()->user()->role_id === 1)
            <li class="nav-item">
                <a class="nav-link" href="/users">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
            </li>
            @endif
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0">Document Details</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <button class="btn btn-link position-relative">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">3</span>
                    </button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <h6 class="profile-name mb-1">{{ auth()->user()->name }}</h6>
                            <p class="profile-department small text-muted mb-1">{{ auth()->user()->department->name ?? 'No Department Assigned' }}</p>
                            <span class="profile-role-badge badge bg-primary">{{ auth()->user()->role->name ?? 'Standard User' }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Document Details Content -->
        <div class="container-fluid p-4">
            <div class="mb-3">
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
                                    <span id="docType">{{ $document->type_name }}</span>
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
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Current Department:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docReceiver">{{ $document->current_department ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Current Status:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docStatus"><span class="badge text-uppercase px-3 py-1 {{ $document->status === 'received' ? 'bg-success' : ($document->status === 'in_transit' ? 'bg-warning text-dark' : 'bg-info text-dark') }}">{{ $document->status }}</span></span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong class="text-muted">Upload Date:</strong>
                                </div>
                                <div class="col-md-8">
                                    <span id="docUploadDate">{{ \Carbon\Carbon::parse($document->upload_date)->format('M d, Y h:i A') }}</span>
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
                                    <span id="docReceivedDate">{{ $document->completed_at ? \Carbon\Carbon::parse($document->completed_at)->format('M d, Y h:i A') : '-' }}</span>
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
                        <div class="card-footer bg-white">
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary" onclick="window.print()">
                                    <i class="bi bi-printer"></i> Print Details
                                </button>
                                <button class="btn btn-success" onclick="downloadDocument()">
                                    <i class="bi bi-download"></i> Download Document
                                </button>
                                <button class="btn btn-info" onclick="shareDocument()">
                                    <i class="bi bi-share"></i> Share
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> Audit Trail & Document History
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline" id="auditTrail">
                                @forelse($events as $event)
                                <div class="timeline-item border-start ps-3 pb-3 position-relative">
                                    <span class="position-absolute start-0 top-0 translate-middle-x badge rounded-circle bg-primary p-1" style="margin-left:-1px; margin-top:4px;"><span class="visually-hidden">.</span></span>
                                    <div class="text-xxs text-muted font-mono">{{ $event->formatted_date }}</div>
                                    <div class="text-xs font-semibold text-dark mt-0.5">{{ $event->event_label }} - <span class="text-primary font-normal">{{ $event->execution_department }}</span></div>
                                    <p class="text-muted text-xxs mb-0 mt-0.5 bg-light p-1 rounded border">Note: {{ $event->note ?? 'No transaction notes added.' }} <br><span class="text-dark font-medium">By: {{ $event->processed_by_user }}</span></p>
                                </div>
                                @empty
                                <div class="text-center text-muted text-xs py-3">
                                    <i class="bi bi-inbox"></i> No transactional logging history logs discovered.
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Code and Quick Actions -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="bi bi-qr-code"></i> QR Code
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="text-center p-3 border rounded bg-white shadow-3xs mb-4">
                                <div class="qr-code-wrapper d-inline-block p-2 bg-light rounded border border-secondary-subtle">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($document->document_number) }}&ecc=M"
                                         alt="Document QR Code ({{ $document->document_number }})"
                                         class="img-fluid"
                                         style="width: 180px; height: 180px; image-rendering: crisp-edges;">
                                </div>
                                <div class="mt-2 font-mono text-xs text-muted fw-semibold">
                                    {{ $document->document_number }}
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm w-100" onclick="printQRCode()">
                                <i class="bi bi-printer"></i> Print QR Code
                            </button>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="bi bi-lightning"></i> Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="markAsReceived()">
                                    <i class="bi bi-check-circle"></i> Mark as Received
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="requestUpdate()">
                                    <i class="bi bi-pencil"></i> Request Update
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="reportIssue()">
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
                                <strong>Views:</strong> 12 times
                            </p>
                            <p class="small mb-0">
                                <i class="bi bi-shield-check text-primary"></i>
                                <strong>Audit Trail:</strong> Complete
                            </p>
                        </div>
                    </div>
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
                            <input type="text" class="form-control" id="issueDocId" readonly style="background-color: #f8f9fa;">
                        </div>

                        <!-- Issue Description -->
                        <div class="mb-3">
                            <label for="issueDescription" class="form-label">
                                <i class="bi bi-chat-left-text"></i> Issue Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="issueDescription" rows="4" placeholder="Describe the issue in detail..." required style="resize: vertical;"></textarea>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle"></i> Please provide as much detail as possible about the issue
                            </small>
                        </div>

                        <!-- Department Selection -->
                        <div class="mb-3">
                            <label for="issueDepartment" class="form-label">
                                <i class="bi bi-building"></i> Route to Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="issueDepartment" required>
                                <option value="">-- Select Department --</option>
                                <option value="IT Department">IT Department</option>
                                <option value="Operations">Operations</option>
                                <option value="Legal Department">Legal Department</option>
                                <option value="HR Department">HR Department</option>
                                <option value="Finance Department">Finance Department</option>
                                <option value="Executive Office">Executive Office</option>
                                <option value="Customer Service">Customer Service</option>
                                <option value="Marketing Department">Marketing Department</option>
                                <option value="Other">Other</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle"></i> Select the department responsible for resolving this issue
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <!-- Custom JS -->
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>

    <script>
        function downloadDocument() {
            showToast('Downloading document...', 'info');
            setTimeout(() => {
                showToast('Document downloaded successfully!', 'success');
            }, 1500);
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
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(shareUrl);
                showToast('Link copied to clipboard!', 'success');
            }
        }

        function markAsReceived() {
            const docIdEl = document.getElementById('docId');
            if (!docIdEl) return;
            const docId = docIdEl.textContent.trim();

            if (!confirm('Mark this document as received?')) return;

            if (typeof window.advanceRoute !== 'function') {
                showToast('Receive action not available', 'warning');
                return;
            }

            const res = window.advanceRoute(docId);
            if (res && res.updated) {
                showToast('Document updated', 'success');
                const status = res.status || 'In Transit';
                const badgeClass = (typeof getStatusBadgeClass === 'function') ? getStatusBadgeClass(status) : 'badge-received';
                document.getElementById('docStatus').innerHTML = `<span class="badge ${badgeClass}">${status}</span>`;
                const receiverEl = document.getElementById('docReceiver');
                if (receiverEl) receiverEl.textContent = res.nextReceiver || '-';
            } else {
                showToast('Cannot receive document at this stage', 'warning');
            }
        }

        function requestUpdate() {
            showToast('Update request sent to sender', 'info');
        }

        function reportIssue() {
            // Get current document ID and populate the modal
            const docId = document.getElementById('docId').textContent;
            document.getElementById('issueDocId').value = docId;

            // Reset form
            document.getElementById('reportIssueForm').reset();
            document.getElementById('issueDepartment').value = '';
            document.getElementById('issuePriority').value = 'Medium';

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('reportIssueModal'));
            modal.show();
        }

        function handleReportIssueSubmit(event) {
            event.preventDefault();

            // Get form data
            const docId = document.getElementById('issueDocId').value;
            const description = document.getElementById('issueDescription').value;
            const department = document.getElementById('issueDepartment').value;
            const priority = document.getElementById('issuePriority').value;

            // Validate form
            if (!description.trim() || !department) {
                showToast('Please fill in all required fields', 'warning');
                return;
            }

            // Show processing
            showSpinner();

            // Simulate API call
            setTimeout(() => {
                hideSpinner();

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('reportIssueModal'));
                modal.hide();

                // Show success message with details
                showToast(`Issue reported successfully to ${department}!`, 'success');

                // Reset form
                document.getElementById('reportIssueForm').reset();

                // Log the issue (for demonstration purposes)
                console.log('Issue Report:', {
                    docId: docId,
                    description: description,
                    department: department,
                    priority: priority,
                    timestamp: new Date().toLocaleString(),
                    reportedBy: currentUserName
                });
            }, 1200);
        }

    </script>
</body>
</html>
