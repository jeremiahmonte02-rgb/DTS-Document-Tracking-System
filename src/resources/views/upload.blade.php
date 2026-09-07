@extends('layouts.app')

@section('title', 'Upload Document - Document Tracking System')



@section('pageTitle', 'Upload Document')

@section('content')
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

                                <div class="w-100 my-4 route-builder-container">
                                    <div class="row g-4 align-items-center">

                                        <div class="col-md-5">
                                            <label class="form-label text-secondary fw-semibold mb-2" style="font-size: 0.875rem;">Receiver Departments (ordered)</label>
                                            <div class="route-builder-pool">
                                                <div class="route-list-inner">

                                                    <ul class="list-group list-group-flush" id="visual-dept-pool">
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
                                            <div class="route-builder-route">

                                                <div id="route-placeholder" class="d-flex flex-column align-items-center justify-content-center text-muted opacity-50">
                                                    <svg class="mb-2" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                                    </svg>
                                                    <p class="mb-0" style="font-size: 0.75rem;">Selected departments will appear here in sequence</p>
                                                </div>

                                                <ul id="routeList" class="list-group list-group-flush w-100 h-100 overflow-y-auto custom-scrollbar d-none route-list-inner"></ul>
                                            </div>
                                        </div>

                                    </div>

                                    <input type="hidden" id="routesInput" name="routes">
                                    <p class="text-muted fst-italic mt-2 mb-0" style="font-size: 0.75rem;">Select departments above, then click "Add Selected" to build the routing sequence.</p>
                                    <div class="alert alert-warning alert-dismissible fade show d-none mt-2 mb-0 py-2" id="addedDeptWarning" role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <span>Departments added beyond the predefined route will use standard processing-time settings (a 30-minute default unless a specific override is configured for that department), not this document type's configured lifecycle SLA.</span>
                                        <button type="button" class="btn-close" id="addedDeptWarningClose" aria-label="Close"></button>
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
                                    <select class="form-select" id="documentSelect">
                                        <option value="">-- Pre-fill form from document --</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Select a document to automatically populate the form fields above
                                    </small>
                                </div>

                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end w-100">
                                    <button type="submit" class="btn btn-primary w-100 w-md-auto">
                                        <i class="bi bi-cloud-upload"></i> Upload Document
                                    </button>
                                    <button type="button" class="btn btn-info w-100 w-md-auto" id="viewRoutesBtn" style="display: none;">
                                        <i class="bi bi-diagram-3"></i> View Routes
                                    </button>
                                    <button type="reset" class="btn btn-outline-secondary w-100 w-md-auto">
                                        <i class="bi bi-x-circle"></i> Clear Form
                                    </button>
                                    <a href="/dashboard" class="btn btn-outline-danger w-100 w-md-auto">
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
                <div class="modal-footer d-flex flex-wrap gap-2 w-100 justify-content-center justify-content-md-end">
                    <button type="button" id="modalPrintQrBtn" class="btn btn-primary w-100 w-md-auto">
                        <i class="bi bi-printer"></i> Print QR Code
                    </button>
                    <button type="button" id="modalViewDetailsBtn" class="btn btn-outline-secondary w-100 w-md-auto">
                        <i class="bi bi-eye"></i> View Details
                    </button>
                    <button type="button" id="doneQrBtn" class="btn btn-success w-100 w-md-auto" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle me-1"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
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
    <script src="{{ asset('js/modules/upload.js') }}?v={{ filemtime(public_path('js/modules/upload.js')) }}"></script>
@endsection
