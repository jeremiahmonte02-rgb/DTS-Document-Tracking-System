@extends('layouts.app')

@section('title')
{{ $department->name }} - Department Analytics - Document Tracking System
@endsection



@section('pageTitle', 'Department Analytics')

@section('content')
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mx-4 mt-3" role="alert">
                <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> <strong>Form Validation Failed:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div id="dept-details-wrapper"
             data-fetch-url="{{ route('api.audit.departments.details.data', $department->id) }}"
             data-department-id="{{ $department->id }}"
             data-document-types="{{ json_encode($documentTypes->map(fn($dt) => ['id' => $dt->id, 'name' => $dt->name, 'code' => $dt->code])) }}">

        <div class="page-header">
            <div class="breadcrumb-links">
                <a href="{{ route('audit.departments') }}">Department Management</a>
                <span class="mx-1">/</span>
                <span id="dept-name-header">{{ $department->name }}</span>
            </div>
            <h1>
                <span class="dept-code">{{ $department->code }}</span>
                <span id="page-title">{{ $department->name }}</span>
            </h1>
            <p id="dept-description">{{ $department->description ?? 'Department analytics and document activity overview.' }}</p>
        </div>

        <div class="row g-3 mb-4" id="metrics-row">
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon emerald">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Total Current Documents</p>
                            <p class="metrics-value" id="metric-total-current">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon amber">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Pending</p>
                            <p class="metrics-value" id="metric-pending">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon blue">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <p class="metrics-label">In Transit</p>
                            <p class="metrics-value" id="metric-in-transit">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon emerald">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Completed</p>
                            <p class="metrics-value" id="metric-completed">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon red">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Overdue</p>
                            <p class="metrics-value" id="metric-overdue">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon violet">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Received Today</p>
                            <p class="metrics-value" id="metric-received-today">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon orange">
                            <i class="bi bi-upload"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Total Originated</p>
                            <p class="metrics-value" id="metric-total-originated">0</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metrics-card">
                    <div class="metrics-body">
                        <div class="metrics-icon slate">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <p class="metrics-label">Avg Dwell Time</p>
                            <p class="metrics-value small" id="metric-avg-dwell">--</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn" style="background: var(--accent); color: #fff; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 600;" data-bs-toggle="modal" data-bs-target="#configureSlaModal">
                <i class="bi bi-clock me-1"></i> Configure SLAs
            </button>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-container">
                    <div class="section-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0; border-bottom: 1px solid var(--whisper);">
                        <h5>Current Documents</h5>
                        <button class="btn btn-sm btn-outline-primary border-0" id="refresh-documents-btn" title="Refresh documents" style="font-size: 0.8125rem; color: var(--steel);">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-fixed">
                            <thead>
                                <tr>
                                    <th style="width:13%">Document ID</th>
                                    <th style="width:25%">Title</th>
                                    <th style="width:14%">Type</th>
                                    <th style="width:15%">From</th>
                                    <th style="width:15%">Status</th>
                                    <th style="width:18%">Created</th>
                                </tr>
                            </thead>
                            <tbody id="dept-documents-body">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        Loading documents...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="documents-pagination" class="p-3 d-flex justify-content-center"></div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="table-container">
                    <div class="section-header" style="padding: 1.25rem 1.5rem; margin-bottom: 0; border-bottom: 1px solid var(--whisper);">
                        <h5>Recent Activity</h5>
                    </div>
                    <div style="padding: 1.25rem 1.5rem;">
                        <div id="event-timeline" class="event-timeline">
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Loading events...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div><!-- /dept-details-wrapper -->

    <div class="modal fade" id="configureSlaModal" tabindex="-1" aria-labelledby="configureSlaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="department-sla-form" method="POST" action="{{ route('audit.departments.update-slas', $department->id) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="configureSlaModalLabel">Configure Document Processing SLAs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Set unique processing time thresholds for this department. Leave the value blank to remove the custom threshold and revert to global system fallbacks.</p>
                    <div class="table-responsive">
                        <table class="table sla-table" id="sla-configuration-table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th style="width: 200px;">Time Value</th>
                                    <th style="width: 180px;">Time Unit</th>
                                </tr>
                            </thead>
                            <tbody id="sla-configuration-body">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="border: 1px solid var(--whisper); border-radius: 0.75rem; color: var(--steel); font-size: 0.8125rem; font-weight: 600;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" style="background: var(--accent); color: #fff; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 600;">Save SLA Settings</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/audit-department-details.js') }}"></script>
@endsection
