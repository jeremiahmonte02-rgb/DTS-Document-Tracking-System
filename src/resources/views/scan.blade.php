@extends('layouts.app')

@section('title', 'Scan Document - Document Tracking System')



@section('pageTitle', 'Scan QR Code')

@section('content')
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
                            <div id="qr-reader" class="mx-auto mb-3 rounded border bg-dark text-white d-flex align-items-center justify-content-center" style="max-width: 450px; width: 100%; min-height: 300px; position: relative; overflow: hidden;">
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
                                        <button type="button" class="btn btn-xs btn-outline-secondary quick-test-btn" data-target-id="DTS-2026-0001">Test 1</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary quick-test-btn" data-target-id="DTS-2026-0002">Test 2</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary quick-test-btn" data-target-id="DTS-2026-0003">Test 3</button>
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

            @include('partials.access-denied-modal')
@endsection

@section('scripts')
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>window.DTS_USER_DEPT_ID = {{ auth()->user()->department_id ?? 'null' }};</script>
    <script src="{{ asset('js/modules/timeline-renderer.js') }}"></script>
    <script src="{{ asset('js/core/api.js') }}"></script>
    <script src="{{ asset('js/modules/scan.js') }}"></script>
@endsection
