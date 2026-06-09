<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Document - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        #qr-reader {
            max-width: 450px !important;
            width: 100% !important;
            position: relative !important;
            overflow: hidden !important;
            border: none !important;
            background: transparent !important;
        }
        #qr-reader__dashboard {
            display: none !important;
        }
        #qr-reader__video-container {
            background-color: transparent !important;
            width: 100% !important;
            height: auto !important;
        }
        #qr-reader video {
            width: 100% !important;
            height: auto !important;
            min-height: 250px !important;
            object-fit: cover !important;
            border-radius: 8px;
        }
    </style>
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
                <h5 class="mb-0">Scan QR Code</h5>
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

        <!-- Scan Content -->
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Scanner Interface -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-qr-code-scan"></i> QR Code Scanner
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Camera/Scanner View -->
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div id="qr-reader" class="mx-auto mb-3 rounded border bg-dark text-white d-flex align-items-center justify-content-center" style="max-width: 450px; width: 100%; height: 300px; position: relative; overflow: hidden;">
                                        <div id="qr-placeholder-message" class="text-center p-3 text-muted">
                                            <i class="bi bi-camera fs-1 d-block mb-2"></i>
                                            <span class="fw-semibold">Camera Stream Ready</span>
                                            <small class="d-block text-secondary mt-1">Click "Start Scan" to initiate device hardware lens</small>
                                        </div>
                                    </div>
                                    <div class="text-center mt-3">
                                        <button class="btn btn-success btn-lg" id="startScanBtn">
                                            <i class="bi bi-camera"></i> Start Scan
                                        </button>
                                    </div>
                                </div>

                                <!-- Manual Entry -->
                                <div class="col-md-6">
                                    <div class="card bg-light h-100">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="bi bi-keyboard"></i> Manual Entry
                                            </h6>
                                            <p class="small text-muted">
                                                If you cannot scan the QR code, enter the document ID manually:
                                            </p>
                                            <form id="manualLookupForm" data-lookup-url="{{ route('scan.lookup') }}">
                                                <div class="mb-3">
                                                    <label for="manualDocId" class="form-label">Document ID</label>
                                                    <input type="text" class="form-control" id="manualDocId"
                                                           placeholder="e.g., DOC-2024-001">
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100" id="manualScanBtn">
                                                    <i class="bi bi-search"></i> Lookup Document
                                                </button>
                                            </form>

                                            <hr class="my-3">

                                            <h6 class="small fw-bold">Quick Test IDs:</h6>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-xs btn-outline-secondary quick-test-btn" data-target-id="DOC-2026-0001">Test 1</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary quick-test-btn" data-target-id="DOC-2026-0002">Test 2</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary quick-test-btn" data-target-id="DOC-2026-0003">Test 3</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scan Result -->
                     <div id="scanResultWrapper" class="d-none"
                         data-receive-url="{{ route('scan.receive') }}"
                         data-user-dept-id="{{ auth()->user()->department_id ?? '' }}">

                        <div id="scanCardResultContainer" class="mb-4"></div>

                        <div class="row pt-2">
                            <div class="col-md-5 border-end">
                                <h6 class="mb-3 text-secondary text-xs font-mono text-uppercase tracking-wider"><i class="bi bi-bezier2"></i> Scheduled Routing Path</h6>
                                <div class="route-step-pipeline" id="routeStepMapContainer"></div>
                            </div>
                            <div class="col-md-7 ps-md-4">
                                <h6 class="mb-3 text-secondary text-xs font-mono text-uppercase tracking-wider"><i class="bi bi-clock-history"></i> Transactional Action Logs</h6>
                                <div class="timeline" id="trackingTimeline"></div>
                            </div>
                        </div>

                        <div class="modal fade" id="fullDetailsModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="bi bi-folder2-open"></i> Full Document Manifest</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="fullDetailsModalBody"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Instructions -->
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle text-info"></i> Scanning Instructions
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="small fw-bold">How to Scan:</h6>
                                    <ul class="small mb-0">
                                        <li>Click "Start Scan" to activate the camera</li>
                                        <li>Position the QR code within the green frame</li>
                                        <li>Hold steady until the scan completes</li>
                                        <li>Review document information displayed</li>
                                        <li>Confirm receipt if the document is correct</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small fw-bold">What Happens Next:</h6>
                                    <ul class="small mb-0">
                                        <li>Document details are retrieved from the system</li>
                                        <li>Current status and transfer history are shown</li>
                                        <li>If already received, a warning is displayed</li>
                                        <li>Confirming receipt updates the document status</li>
                                        <li>Timestamp and your user ID are logged</li>
                                        <li>The document appears in your inbox</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Routed Document Error Modal -->
    <div class="modal fade" id="routedDocumentErrorModal" tabindex="-1" aria-labelledby="routedDocumentErrorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="routedDocumentErrorLabel">
                        <i class="bi bi-shield-x"></i> Access Denied
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                        <h5 class="mt-3">Department Not Involved</h5>
                        <p class="text-muted">
                            Your department is not involved in the routing process for this document.
                            This document requires specific departmental approval before it can be received.
                        </p>
                        <div class="alert alert-info">
                            <strong>Note:</strong> Please contact the appropriate department or document sender for routing approval.
                        </div>
                        <p class="mb-0">
                            You can still view the document details but cannot confirm receipt at this time.
                        </p>
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

    <!-- Html5Qrcode Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>

    <script>
        // Pass authenticated user context safely to external modules
        window.DTS_USER_DEPT_ID = {{ auth()->user()->department_id ?? 'null' }};
    </script>

    <script src="{{ asset('js/core/api.js') }}"></script>
    <script src="{{ asset('js/modules/scan.js') }}"></script>
</body>
</html>
