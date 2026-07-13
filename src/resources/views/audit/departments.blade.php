@extends('layouts.app')

@section('title', 'Department Management - Document Tracking System')



@section('pageTitle', 'Department Management')

@section('content')
            <div class="page-header">
                <h1>Department Management</h1>
                <p>Manage departments, their metadata, and activation status across the system.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon emerald">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Total Departments</p>
                                <p class="metrics-value" id="metric-total-depts">0</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon emerald">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Active</p>
                                <p class="metrics-value" id="metric-active-depts">0</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon slate">
                                <i class="bi bi-pause-circle"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Inactive</p>
                                <p class="metrics-value" id="metric-inactive-depts">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container"
                 id="department-table-wrapper"
                 data-fetch-url="{{ route('api.audit.departments.data') }}"
                 data-store-url="{{ route('api.audit.departments.store') }}"
                 data-toggle-url="{{ route('api.audit.departments.toggle', '__ID__') }}"
                 data-update-url="{{ route('api.audit.departments.update', '__ID__') }}"
                 data-details-url="{{ url('/audit/departments') }}">
                <div class="table-header-section">
                    <h5>
                        System Departments
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-action="refresh-departments" title="Refresh">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button class="btn btn-sm btn-accent" style="background: var(--accent); color: #fff; border: none; border-radius: 0.75rem; font-family: var(--font-display); font-weight: 600; font-size: 0.8125rem; padding: 0.375rem 1rem;" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                            <i class="bi bi-plus-circle"></i> Add Department
                        </button>
                    </div>
                </div>
                <div class="p-3" style="border-bottom: 1px solid var(--whisper);">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search" style="font-size: 0.875rem;"></i>
                                </span>
                                <input type="text" class="form-control search-field" id="search-input"
                                       placeholder="Search by name or code...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="status-filter">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3"></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-fixed">
                        <thead>
                            <tr>
                                <th style="width:15%">Code</th>
                                <th style="width:25%">Name</th>
                                <th style="width:30%">Description</th>
                                <th style="width:15%">Status</th>
                                <th style="width:15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="department-table-body">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Loading departments...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="pagination-links" class="p-3 d-flex justify-content-center"></div>
            </div>

    <div class="modal fade dept-modal" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDepartmentModalLabel">
                        <i class="bi bi-plus-circle" style="color: var(--accent);"></i> Add Department
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="add-department-form">
                        <div class="mb-3">
                            <label for="input-name" class="form-label">Department Name *</label>
                            <input type="text" class="form-control" id="input-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="input-code" class="form-label">Department Code *</label>
                            <input type="text" class="form-control code-field" id="input-code" name="code" required
                                   placeholder="e.g. HR, IT, FIN">
                        </div>
                        <div class="mb-3">
                            <label for="input-description" class="form-label">Description</label>
                            <textarea class="form-control" id="input-description" name="description" rows="3"></textarea>
                        </div>
                        <div id="add-form-feedback" class="alert d-none alert-feedback"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-submit submit-add" data-action="submit-add-department">
                        <i class="bi bi-check-circle"></i> Add Department
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade dept-modal" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartmentModalLabel">
                        <i class="bi bi-pencil-square" style="color: #F59E0B;"></i> Edit Department
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-department-form">
                        <input type="hidden" id="edit-department-id">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label">Department Name *</label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-code" class="form-label">Department Code *</label>
                            <input type="text" class="form-control code-field" id="edit-code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit-description" name="description" rows="3"></textarea>
                        </div>
                        <div id="edit-form-feedback" class="alert d-none alert-feedback"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-submit submit-edit" data-action="submit-edit-department">
                        <i class="bi bi-check-circle"></i> Update Department
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/audit-departments.js') }}"></script>
@endsection
