<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document - Document Tracking System</title>

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
                <h5 class="mb-0">Upload Document</h5>
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

        <!-- Upload Content -->
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Upload Form Card -->
                    <div class="card form-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-cloud-upload"></i> Upload New Document
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="uploadForm"
                                  data-store-url="{{ route('documents.store') }}"
                                  data-existing-documents='@json($existingDocuments ?? [])'
                                  method="POST"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="title" class="form-label">Document Title *</label>
                                        <input type="text" class="form-control" id="title" name="title"
                                               placeholder="Enter document title" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="documentType" class="form-label">Document Type *</label>
                                        <select class="form-select" id="documentType" name="documentType" required>
                                            <option value="">Select document type</option>
                                            @foreach($documentTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-3">
                                            <label for="department" class="form-label">Your Department *</label>
                                            <select class="form-select" id="department" name="department" required>
                                                <option value="">Select your department</option>
                                                @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="receiverDepartments" class="form-label">Receiver Departments (ordered)</label>
                                            <select class="form-select" id="receiverDepartments" multiple size="8" style="min-width:380px;">
                                                @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                @endforeach
                                            </select>

                                            <input type="hidden" id="routesInput" name="routes">
                                            <small class="form-text text-muted d-block mt-1">
                                                Select departments above, then click "Add Selected" on the right to build an ordered route. The document will be received by departments in this sequence.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="card h-100">
                                            <div class="card-body d-flex flex-column">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small class="text-muted">Route (ordered)</small>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" id="addToRouteBtn">Add Selected</button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearRouteBtn">Clear</button>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 overflow-auto">
                                                    <ul class="list-group" id="routeList" style="min-height:160px; max-height:420px; overflow:auto;"></ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description/Notes</label>
                                    <textarea class="form-control" id="description" name="description"
                                              rows="3" placeholder="Enter document description or notes"></textarea>
                                </div>

                                <div class="mb-4">
                                    <label for="fileUpload" class="form-label">Upload File *</label>
                                    <input type="file" class="form-control" id="fileUpload" name="fileUpload"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                                    <small class="form-text text-muted">
                                        Accepted formats: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Max 10MB)
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label for="documentSelect" class="form-label">Load Existing Document</label>
                                    <select class="form-select" id="documentSelect" onchange="populateFormFromDocument()">
                                        <option value="">-- Pre-fill form from document --</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select a document to automatically populate the form fields above
                                    </small>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-cloud-upload"></i> Upload Document
                                    </button>
                                    <button type="button" class="btn btn-info" id="viewRoutesBtn" style="display: none;" onclick="showRoutesModal()">
                                        <i class="bi bi-diagram-3"></i> View Routes
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Clear Form
                                    </button>
                                    <a href="/dashboard" class="btn btn-outline-danger">
                                        <i class="bi bi-arrow-left"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Instructions Card -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle text-info"></i> Upload Instructions
                            </h6>
                            <ul class="mb-0 small">
                                <li>Fill in all required fields marked with *</li>
                                <li>Choose the appropriate document type and department</li>
                                <li>Provide a clear, descriptive title for easy identification</li>
                                <li>Upload files must be less than 10MB in size</li>
                                <li>After uploading, a unique QR code will be generated</li>
                                <li>Print and attach the QR code to your physical document</li>
                                <li>The document status will be set to "Pending Transfer"</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Routes Modal -->
    <div class="modal fade" id="routesModal" tabindex="-1" aria-labelledby="routesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="routesModalLabel">
                        <i class="bi bi-diagram-3"></i> Document Routes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="document-routes-container">
                        <h6 class="mb-3" id="routesDocumentTitle"></h6>
                        <div id="routesDisplay" class="route-steps">
                            <!-- Routes will be populated here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="qrCodeModalLabel">
                        <i class="bi bi-check-circle"></i> Document Uploaded Successfully
                    </h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeQrModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="qr-code-container">
                        <h6 class="mb-3">Document Reference: <strong id="generatedDocId"></strong></h6>
                        <div id="modalQrCode" class="d-flex justify-content-center mb-3"></div>
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle"></i>
                            Print this QR code and attach it to your physical document for tracking.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printQRCode()">
                        <i class="bi bi-printer"></i> Print QR Code
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="viewDocument(document.getElementById('generatedDocId').textContent)">
                        <i class="bi bi-eye"></i> View Details
                    </button>
                    <button type="button" id="doneQrBtn" class="btn btn-success" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle me-1"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <!-- Custom JS -->
    @include('partials.auth-context')

    <script src="{{ asset('js/modules/upload.js') }}"></script>
</body>
</html>
