@extends('layouts.app')

@section('title', 'Documents Directory - Document Tracking System')



@section('pageTitle', 'Audit Portal')

@section('content')
            <div class="audit-header">
                <h1>Documents Directory</h1>
                <p>Global document archive with search, filter, and inspection capabilities across all departments.</p>
            </div>

            <div class="row g-3 mb-4" id="metrics-row">
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon emerald">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Total Documents</p>
                                <p class="metrics-value" id="metric-total">0</p>
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
                                <p class="metrics-value" id="metric-transit">0</p>
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
            </div>

            <div class="filter-bar">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-search" style="font-size: 0.875rem;"></i>
                            </span>
                            <input type="text" class="form-control search-field" id="searchInput"
                                   placeholder="Search documents...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="type-filter">
                            <option value="">All Types</option>
                            @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="status-filter">
                            <option value="">All Status</option>
                            <option value="pending_transfer">Pending</option>
                            <option value="in_transit">In Transit</option>
                            <option value="received">Received</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" id="date-filter">
                    </div>
                    <div class="col-md-2">
                        <a href="#" class="filter-btn-clear" data-action="clear-filters">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header-section">
                    <h5>
                        Global Document Directory
                        <span class="count-badge" id="documentCount">0</span>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary border-0" id="refreshTableBtn" title="Refresh data" style="font-size: 0.8125rem; color: var(--steel);">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive" id="audit-table-wrapper"
                     data-fetch-url="{{ route('api.audit.documents.data') }}"
                     data-details-url="{{ url('/document-details') }}">
                    <table class="table table-fixed">
                        <thead>
                            <tr>
                                <th style="width:13%">Document ID</th>
                                <th style="width:25%">Title</th>
                                <th style="width:10%">Type</th>
                                <th style="width:15%">Origin</th>
                                <th style="width:15%">Current Location</th>
                                <th style="width:12%">Date Created</th>
                                <th style="width:10%">Status</th>
                            </tr>
                        </thead>
                        <tbody id="audit-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/audit.js') }}"></script>
@endsection
