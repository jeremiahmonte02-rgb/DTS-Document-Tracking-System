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
    @include('partials.sidebar-nav')

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
                        <span class="notification-badge">0</span>
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

                                <div class="w-100 my-4" data-purpose="route-builder" style="font-family: 'Manrope', sans-serif;">
                                    <div class="row g-4 align-items-center">

                                        <div class="col-md-5">
                                            <label class="form-label text-secondary fw-semibold mb-2" style="font-size: 0.875rem;">Receiver Departments (ordered)</label>
                                            <div class="border rounded-3 bg-white shadow-sm" style="border-color: #d1d5db !important; height: 240px; overflow: hidden; position: relative;">
                                                <div class="w-100 h-100 p-1" style="overflow-x: auto; overflow-y: auto;">

                                                    <ul class="list-group list-group-flush" id="visual-dept-pool" style="min-width: 360px; font-size: 0.875rem;">
                                                        @foreach($departments ?? [] as $dept)
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark transition-colors"
                                                                data-value="{{ $dept->id ?? $dept }}" style="letter-spacing: -0.01em;">
                                                                {{ $dept->name ?? $dept }}
                                                            </li>
                                                        @endforeach

                                                        @if(empty($departments))
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="Central Services">Central Services</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="College of Computing and Information Sciences">College of Computing and Information Sciences</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="Customer Service">Customer Service</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="Executive Office">Executive Office</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="Facilities">Facilities</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="Finance Department">Finance Department</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="HR Department">HR Department</li>
                                                            <li class="list-group-item list-group-item-action border-0 py-2.5 px-3 rounded-2 text-nowrap cursor-pointer mb-1 text-dark" data-value="IT Department">IT Department</li>
                                                        @endif
                                                    </ul>

                                                    <div id="immutablePolicyNotice" class="d-none my-auto p-4 text-center d-flex flex-column align-items-center justify-content-center w-100 h-100">
                                                        <i class="bi bi-shield-lock text-danger mb-2" style="font-size: 1.75rem;"></i>
                                                        <h6 class="fw-bold text-dark mb-1" style="font-family: 'Satoshi', sans-serif;">Enforced Routing Policy</h6>
                                                        <p class="text-muted small mb-0 px-2" style="font-size: 0.85rem;">
                                                            The workflow pathway for this document type has been strictly locked by the system Auditor to ensure organizational compliance.
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>

                                            <select id="receiverDepartments" name="receiver_departments[]" class="d-none" multiple>
                                                @foreach($departments ?? [] as $dept)
                                                    <option value="{{ $dept->id ?? $dept }}">{{ $dept->name ?? $dept }}</option>
                                                @endforeach
                                                @if(empty($departments))
                                                    <option value="Central Services">Central Services</option>
                                                    <option value="College of Computing and Information Sciences">College of Computing and Information Sciences</option>
                                                    <option value="Customer Service">Customer Service</option>
                                                    <option value="Executive Office">Executive Office</option>
                                                    <option value="Facilities">Facilities</option>
                                                    <option value="Finance Department">Finance Department</option>
                                                    <option value="HR Department">HR Department</option>
                                                    <option value="IT Department">IT Department</option>
                                                @endif
                                            </select>
                                        </div>

                                        <div class="col-md-2 d-flex flex-column gap-2.5 px-1 pt-4">
                                            <button id="addToRouteBtn" type="button" class="btn btn-custom-academic btn-sm w-100 fw-bold border-2 text-center text-nowrap py-2" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 8px;">
                                                ADD SELECTED
                                            </button>
                                            <button id="clearRouteBtn" type="button" class="btn btn-custom-clear btn-sm w-100 fw-bold border-2 text-center text-nowrap py-2" style="font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 8px;">
                                                CLEAR
                                            </button>
                                        </div>

                                        <div class="col-md-5">
                                            <label class="form-label text-secondary fw-semibold mb-2" style="font-size: 0.875rem;">Route (ordered)</label>
                                            <div class="border rounded-3 bg-white d-flex flex-column align-items-center justify-content-center text-center p-4 shadow-sm position-relative" style="height: 240px; border-color: #d1d5db !important;">

                                                <div id="route-placeholder" class="d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                                                    <svg class="mb-2" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                    </svg>
                                                    <p class="mb-0" style="font-size: 0.75rem;">Selected departments will appear here in sequence</p>
                                                </div>

                                                <ul id="routeList" class="list-group list-group-flush w-100 h-100 overflow-y-auto custom-scrollbar d-none" style="font-size: 0.875rem; max-height: 210px;"></ul>
                                            </div>
                                        </div>

                                    </div>

                                    <input type="hidden" id="routesInput" name="routes">
                                    <p class="text-muted fst-italic mt-2 mb-0" style="font-size: 0.75rem;">Select departments above, then click "Add Selected" to build the routing sequence.</p>
                                </div>

                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const listItems = document.querySelectorAll('#visual-dept-pool .list-group-item');
                                    const hiddenSelect = document.getElementById('receiverDepartments');

                                    listItems.forEach(item => {
                                        item.addEventListener('click', function() {
                                            const val = this.getAttribute('data-value');
                                            const correspondingOption = Array.from(hiddenSelect.options).find(opt => opt.value === val);

                                            if (correspondingOption) {
                                                correspondingOption.selected = !correspondingOption.selected;

                                                if (correspondingOption.selected) {
                                                    this.classList.add('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
                                                    this.style.backgroundColor = 'rgba(27, 115, 68, 0.08)';
                                                    this.style.color = '#1b7344';
                                                } else {
                                                    this.classList.remove('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
                                                    this.style.backgroundColor = '';
                                                    this.style.color = '';
                                                }

                                                hiddenSelect.dispatchEvent(new Event('change'));
                                            }
                                        });
                                    });

                                    const clearButton = document.getElementById('clearRouteBtn');
                                    if (clearButton) {
                                        clearButton.addEventListener('click', function() {
                                            listItems.forEach(item => {
                                                item.classList.remove('bg-success', 'bg-opacity-10', 'text-success', 'fw-semibold');
                                                item.style.backgroundColor = '';
                                                item.style.color = '';
                                            });
                                        });
                                    }

                                    const routeList = document.getElementById('routeList');
                                    const placeholder = document.getElementById('route-placeholder');
                                    if (routeList && placeholder) {
                                        const checkVisibility = () => {
                                            if (routeList.children.length > 0) {
                                                routeList.classList.remove('d-none');
                                                placeholder.classList.add('d-none');
                                            } else {
                                                routeList.classList.add('d-none');
                                                placeholder.classList.remove('d-none');
                                            }
                                        };
                                        checkVisibility();
                                        new MutationObserver(checkVisibility).observe(routeList, { childList: true });
                                    }
                                });
                                </script>

                                <style>
                                .cursor-pointer { cursor: pointer; }
                                #visual-dept-pool .list-group-item:hover {
                                    background-color: rgba(27, 115, 68, 0.04) !important;
                                }
                                .btn-custom-academic {
                                    color: #1b7344 !important;
                                    border-color: #1b7344 !important;
                                    background-color: transparent;
                                    transition: all 0.2s ease-in-out;
                                }
                                .btn-custom-academic:hover {
                                    color: #ffffff !important;
                                    background-color: #1b7344 !important;
                                }
                                .btn-custom-clear {
                                    color: #6c757d !important;
                                    border-color: #ced4da !important;
                                    background-color: transparent;
                                    transition: all 0.2s ease-in-out;
                                }
                                .btn-custom-clear:hover {
                                    color: #ffffff !important;
                                    background-color: #6c757d !important;
                                    border-color: #6c757d !important;
                                }
                                </style>

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
                                    <select class="form-select" id="documentSelect">
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
                                    <button type="button" class="btn btn-info" id="viewRoutesBtn" style="display: none;">
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <button type="button" id="modalPrintQrBtn" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print QR Code
                    </button>
                    <button type="button" id="modalViewDetailsBtn" class="btn btn-outline-secondary">
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

    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/modules/upload.js') }}?v={{ filemtime(public_path('js/modules/upload.js')) }}"></script>
</body>
</html>
