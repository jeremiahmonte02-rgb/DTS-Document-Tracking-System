@extends('layouts.app')

@section('title', 'Audit Dashboard - Document Tracking System')

@section('pageTitle', 'Audit Portal')

<style>
    @media print {
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .card {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>

@section('content')
            <!-- Month Selector -->
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
                    <button type="button" class="btn btn-sm btn-light text-secondary border-0" onclick="window.print()"><i class="bi bi-printer"></i> Export</button>
                    @if($startOfMonth->format('Y-m') !== now()->format('Y-m'))
                        <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Current
                        </a>
                    @endif
                </form>
            </div>

            <!-- KPI Metric Cards -->
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
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Completed</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $completedDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle"></i>
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
                                    <h6 class="text-muted mb-2">SLA Overdue</h6>
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

                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="min-width: 0;">
                                    <h6 class="text-muted mb-2">Open Issues</h6>
                                    <h2 class="mb-0 tabular-nums">{{ $openIssues }} <small class="fs-6 text-muted">/ {{ $totalIssues }}</small></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-flag"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Navigation -->
            <div class="row g-4 mb-4 no-print d-print-none">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-lightning"></i> Quick Navigation
                            </h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('audit.documents') }}" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-text"></i> Documents Directory
                                </a>
                                <a href="{{ route('audit.departments') }}" class="btn btn-success">
                                    <i class="bi bi-building"></i> Department Management
                                </a>
                                <a href="{{ route('audit.policies') }}" class="btn btn-warning">
                                    <i class="bi bi-diagram-3"></i> Routing Policies
                                </a>
                                <a href="{{ route('audit.issues') }}" class="btn btn-danger">
                                    <i class="bi bi-flag"></i> Issue Tracker
                                </a>
                                <a href="{{ route('activity-log') }}" class="btn btn-secondary">
                                    <i class="bi bi-clock-history"></i> Activity Log
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1: Status Distribution + Department Workload -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-pie-chart"></i> Document Status Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="auditStatusChart" data-metrics='@json($statusMetrics ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-bar-chart"></i> Department Workload
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="auditDepartmentChart" data-metrics='@json($departmentDistribution ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2: Issue Priority + Issues by Department -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-hourglass-bottom text-danger"></i> System-Detected Delays
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="auditSlaBottleneckChart" data-metrics='@json($slaBottlenecksByDept ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-building"></i> Issues by Department
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="auditIssuesByDeptChart" data-metrics='@json($issuesByDepartment ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="row g-4 no-print d-print-none">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-activity"></i> Recent System Activity
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
                                                <strong>{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</strong>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</small>
                                            </div>
                                            <small class="text-muted">
                                                {{ $event->note }}<br>
                                                <span class="text-dark fw-medium">{{ $event->user_name }}</span>
                                                &middot;
                                                <span class="text-muted">{{ $event->document_number }}</span>
                                                @if ($event->department_name)
                                                    &middot;
                                                    <span class="badge bg-secondary">{{ $event->department_name }}</span>
                                                @endif
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
@endsection

@section('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script src="{{ asset('js/modules/audit-dashboard.js') }}?v={{ filemtime(public_path('js/modules/audit-dashboard.js')) }}"></script>
@endsection
