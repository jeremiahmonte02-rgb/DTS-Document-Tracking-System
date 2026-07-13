@extends('layouts.app')

@section('title', 'User Management - Document Tracking System')

@section('pageTitle', 'User Management')

@section('content')
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="search-input"
                                       placeholder="Search users...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="department_id">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="role_id">
                                <option value="">All Roles</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="status">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="bi bi-plus-circle"></i> Add User
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Users</h6>
                                    <h3 class="mb-0" id="stat-total-users">--</h3>
                                </div>
                                <div class="text-primary">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Active Users</h6>
                                    <h3 class="mb-0" id="stat-active-users">--</h3>
                                </div>
                                <div class="text-success">
                                    <i class="bi bi-person-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Administrators</h6>
                                    <h3 class="mb-0" id="stat-admin-users">--</h3>
                                </div>
                                <div class="text-info">
                                    <i class="bi bi-shield-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Departments</h6>
                                    <h3 class="mb-0" id="stat-dept-count">--</h3>
                                </div>
                                <div class="text-warning">
                                    <i class="bi bi-building fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card"
                 data-fetch-url="{{ route('api.users.data') }}"
                 data-stats-url="{{ route('api.users.stats') }}"
                 data-store-url="{{ route('api.users.store') }}"
                 data-toggle-url="{{ route('api.users.toggle-status', '__ID__') }}"
                 data-update-url="{{ route('api.users.update', '__ID__') }}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> System Users
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" data-action="refresh-users">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-action="export-users">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <tr id="loading-row">
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading users...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination-links" class="p-3 d-flex justify-content-center"></div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-info-circle text-info"></i> User Management Information
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="small fw-bold">Roles & Permissions:</h6>
                            <ul class="small mb-0">
                                <li><strong>Administrator:</strong> Full system access, can manage all users and documents</li>
                                <li><strong>Department User:</strong> Can upload, scan, and view documents in their department</li>
                                <li><strong>Auditor:</strong> Read-only access to all documents and audit trails</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="small fw-bold">Security Features:</h6>
                            <ul class="small mb-0">
                                <li>All user actions are logged for security monitoring</li>
                                <li>Password policies enforce strong authentication</li>
                                <li>Role-based access control (RBAC) protects sensitive data</li>
                                <li>Session management prevents unauthorized access</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <i class="bi bi-person-plus"></i> Add New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="add-user-form">
                        <div class="mb-3">
                            <label for="input-name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="input-name" name="name" placeholder="Given Name, Surname" required>
                        </div>
                        <div class="mb-3">
                            <label for="input-email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="input-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="input-department" class="form-label">Department *</label>
                            <select class="form-select" id="input-department" name="department_id" required>
                                <option value="">Select department</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="input-role" class="form-label">Role *</label>
                            <select class="form-select" id="input-role" name="role_id" required>
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="input-password" class="form-label">Initial Password *</label>
                            <input type="password" class="form-control" id="input-password" name="password" required>
                            <small class="form-text text-muted">
                                User will be required to change password on first login
                            </small>
                        </div>
                        <div id="form-feedback" class="alert d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" data-action="submit-add-user">
                        <i class="bi bi-check-circle"></i> Add User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="bi bi-pencil-square"></i> Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-user-form">
                        <input type="hidden" id="edit-user-id">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="edit-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="edit-email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-department" class="form-label">Department *</label>
                            <select class="form-select" id="edit-department" name="department_id" required>
                                <option value="">Select department</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-role" class="form-label">Role *</label>
                            <select class="form-select" id="edit-role" name="role_id" required>
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit-password" name="password">
                            <small class="form-text text-muted">Leave blank to keep current password.</small>
                        </div>
                        <div id="edit-form-feedback" class="alert d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" data-action="submit-edit-user">
                        <i class="bi bi-check-circle"></i> Update User
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/users.js') }}?v={{ filemtime(public_path('js/modules/users.js')) }}"></script>
@endsection
