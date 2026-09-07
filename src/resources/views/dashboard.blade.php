@extends('layouts.app')

@section('title', 'Dashboard - Document Tracking System')

@section('pageTitle', 'Dashboard')

@section('content')

            @if(session('success'))
            <div class="alert alert-success d-flex align-items-center alert-dismissible fade show shadow-sm mb-4 pe-5" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; padding: 1rem 1.25rem; border-radius: 0.375rem; position: relative;">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>
                    <strong>Success!</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close position-absolute" data-bs-dismiss="alert" aria-label="Close" style="right: 0.5rem; top: 50%; transform: translateY(-50%); padding: 1.25rem; min-width: 44px; min-height: 44px; background: none; border: none; cursor: pointer; color: #0f5132; filter: invert(20%) sepia(50%) saturate(500%) hue-rotate(100deg);"></button>
            </div>
            @endif

            @if(auth()->user()->hasPermission('users.manage'))
            <!-- Month Selector — Admin Only -->
            <div class="dashboard-filter-bar">
                <div>
                    <small class="text-muted">Analytics for <strong>{{ $startOfMonth->format('F Y') }}</strong></small>
                </div>
                <form method="GET" action="{{ request()->url() }}" class="dashboard-filter-form no-print d-print-none">
                    <label for="monthPicker" class="text-muted small mb-0">Month:</label>
                    <input
                        type="month"
                        id="monthPicker"
                        name="month"
                        class="form-control form-control-sm"
                        value="{{ $startOfMonth->format('Y-m') }}"
                        max="{{ now()->format('Y-m') }}"
                        onchange="this.form.submit()"
                    >
                    <div class="dropdown d-inline">
                        <button type="button" class="btn btn-sm btn-light text-secondary border-0 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-printer"></i> Export</button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['month' => $startOfMonth->format('Y-m')]) }}" data-no-spinner="true">Export as Excel</a></li>
                            <li><button type="button" class="dropdown-item" onclick="window.print()">Export as Snapshot</button></li>
                        </ul>
                    </div>
                    @if($startOfMonth->format('Y-m') !== now()->format('Y-m'))
                        <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Current
                        </a>
                    @endif
                </form>
            </div>
            @endif

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Total Documents</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $totalDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Pending Transfer</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $pendingDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Received in Month</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $receivedInMonth }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">In Transit</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $inTransitDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-arrow-left-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Avg Dwell Time</h6>
                                    <h2 class="mb-0 tabular-nums js-format-time" data-hours="{{ $avgDwellHours }}">{{ $avgDwellHours }} <small class="fs-6 text-muted">hrs</small></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card {{ $overdueCount > 0 ? 'danger' : 'success' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Overdue</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $overdueCount }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Avg Completion</h6>
                                    <h2 class="mb-0 tabular-nums js-format-time" data-hours="{{ $avgCompletionHours }}">{{ $avgCompletionHours }} <small class="fs-6 text-muted">hrs</small></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-check2-all"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mb-4 no-print d-print-none">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-lightning"></i> Quick Actions
                            </h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="/upload" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload"></i> Upload Document
                                </a>
                                <a href="/scan" class="btn btn-success">
                                    <i class="bi bi-qr-code-scan"></i> Scan QR Code
                                </a>
                                <a href="/inbox" class="btn btn-info">
                                    <i class="bi bi-inbox"></i> View Inbox
                                </a>
                                <a href="/outbox" class="btn btn-warning">
                                    <i class="bi bi-send"></i> View Outbox
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.announcement-banner')

            <!-- Charts and Activity Feed -->
            <div class="row g-4">
                <!-- Charts -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-bar-chart"></i> Document Status Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="statusChart" data-metrics='@json($statusMetrics ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up"></i> Documents by Department
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="departmentChart" data-metrics='@json($departmentDistribution ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="col-lg-4 no-print d-print-none">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-activity"></i> Recent Activity
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-feed">
                                @forelse ($activityFeed as $event)
                                    <div class="activity-item d-flex align-items-start">
                                        <div class="activity-icon bg-primary text-white me-3">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $event->event_label }}</strong>
                                                <small class="text-muted">{{ $event->created_at ? \Carbon\Carbon::parse($event->created_at)->diffForHumans() : '-' }}</small>
                                            </div>
                                            <small class="text-muted">
                                                {{ $event->note }}<br>
                                                <span class="text-dark fw-medium">{{ $event->user_name }}</span>
                                                &middot;
                                                <span class="text-muted">{{ $event->document_number }}</span>
                                            </small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center p-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        <small>No recent activity</small>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code Modal -->
            <div id="qrModal" class="modal-backdrop-custom d-none qr-modal-backdrop">
                <div class="modal-card bg-white p-4 rounded shadow-lg text-center qr-modal-card">
                    <h5 class="mb-2 fw-bold">Document Tracking Label</h5>
                    <p id="qrDocNumber" class="text-primary font-monospace fw-bold mb-3"></p>
                    <div id="qrCodeContainer" class="d-flex justify-content-center p-2 bg-light mb-3"></div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button id="downloadQrBtn" class="btn btn-sm btn-success"><i class="bi bi-download"></i> Download</button>
                        <button class="btn btn-light" onclick="closeQrModal()">Close</button>
                    </div>
                </div>
            </div>
@endsection

@section('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <script src="{{ asset('js/modules/dashboard.js') }}?v={{ filemtime(public_path('js/modules/dashboard.js')) }}"></script>
@endsection
