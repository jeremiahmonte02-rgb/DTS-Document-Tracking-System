@extends('layouts.app')

@section('title', 'Inbox - Document Tracking System')

@section('pageTitle', 'Inbox')

@section('content')
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search documents...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="type-filter" data-filter="type">
                                <option value="">All Types</option>
                                @foreach($documentTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="status-filter" data-filter="status">
                                <option value="">All Status</option>
                                <option value="received">Received</option>
                                <option value="pending_transfer">Pending Transfer</option>
                                <option value="in_transit">In Transit</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" id="date-filter" data-filter="date"
                                   placeholder="Filter by date">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" data-action="clear-filters">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-inbox"></i> Received Documents
                        <span class="badge bg-primary ms-2" id="documentCount">0</span>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" data-action="refresh-inbox">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-action="export-inbox">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="inbox-table-wrapper" data-fetch-url="{{ route('api.inbox.data') }}">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Document ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Sender</th>
                                    <th>Receiver</th>
                                    <th>Date Uploaded</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="inboxTable">
                                <!-- Table rows will be loaded by JavaScript -->
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading documents...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <span id="showingCount">Loading...</span>
                        </small>
                        <nav aria-label="Inbox pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-info-circle text-info"></i> Inbox Information
                    </h6>
                    <ul class="mb-0 small">
                        <li>Your inbox shows all documents received by your department ({{ auth()->user()->department->name ?? 'No Department Assigned' }})</li>
                        <li>Click on any document row to view full details and audit trail</li>
                        <li>New documents are automatically added when scanned at your location</li>
                        <li>Use filters to narrow down documents by type, status, or date</li>
                        <li>Documents marked as "Received" have been confirmed by your department</li>
                        <li>Export feature allows you to download inbox data as CSV for reporting</li>
                    </ul>
                </div>
            </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/inbox.js') }}?v={{ filemtime(public_path('js/modules/inbox.js')) }}"></script>
    <script src="{{ asset('js/modules/realtime-inbox.js') }}?v={{ filemtime(public_path('js/modules/realtime-inbox.js')) }}"></script>
@endsection
