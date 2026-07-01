<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Department Management - Document Tracking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <link href="https://api.fontshare.com/css?f[]=satoshi@400,500,600,700,900&display=swap" rel="stylesheet">

    <style>
        :root {
            --accent: #10B981;
            --accent-soft: rgba(16, 185, 129, 0.08);
            --canvas: #F9FAFB;
            --surface: #FFFFFF;
            --ink: #18181B;
            --steel: #71717A;
            --muted: #94A3B8;
            --whisper: rgba(226, 232, 240, 0.5);
            --shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
            --radius-card: 2.5rem;
            --radius-badge: 0.375rem;
            --font-display: 'Satoshi', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--canvas);
            font-family: var(--font-display);
            color: var(--ink);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .page-header p {
            font-size: 0.9375rem;
            color: var(--steel);
            max-width: 65ch;
            margin-bottom: 0;
        }

        .metrics-card {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 1.25rem;
            box-shadow: var(--shadow);
            padding: 1.25rem 1.5rem;
        }

        .metrics-body {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .metrics-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .metrics-icon.emerald {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }

        .metrics-icon.blue {
            background: rgba(59, 130, 246, 0.12);
            color: #2563EB;
        }

        .metrics-icon.slate {
            background: rgba(100, 116, 139, 0.12);
            color: #475569;
        }

        .metrics-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.125rem;
        }

        .metrics-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.025em;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
            margin-bottom: 0;
        }

        .filter-bar {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 2rem;
            box-shadow: var(--shadow);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-bar .input-group-text {
            background: transparent;
            border: none;
            color: var(--muted);
            padding-right: 0;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            border: 1px solid var(--whisper);
            border-radius: 0.375rem;
            font-size: 0.8125rem;
            font-family: var(--font-display);
            color: var(--ink);
            padding: 0.5rem 0.75rem;
            background: var(--surface);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.15);
        }

        .filter-bar .form-control::placeholder {
            color: var(--muted);
            font-size: 0.8125rem;
        }

        .filter-bar .form-control.search-field {
            padding-left: 0.5rem;
        }

        .table-container {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 2rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-container .table {
            margin-bottom: 0;
        }

        .table-container .table.table-fixed {
            table-layout: fixed;
        }

        .table-container .table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--whisper);
            background: var(--canvas);
        }

        .table-container .table tbody td {
            font-size: 0.875rem;
            padding: 0.875rem 1.25rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--whisper);
            color: var(--ink);
        }

        .table-container .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-container .table tbody tr {
            transition: background 0.15s ease;
        }

        .table-container .table tbody tr:hover {
            background: rgba(16, 185, 129, 0.03);
        }

        .table-container .table tbody td.cell-title {
            overflow: hidden;
        }

        .table-header-section {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--whisper);
        }

        .table-header-section h5 {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.02em;
            margin-bottom: 0;
            color: var(--ink);
        }

        .dept-code {
            font-family: 'Geist Mono', 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .dept-name {
            font-weight: 500;
            display: block;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dept-desc {
            display: block;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--steel);
            font-size: 0.8125rem;
        }

        .dept-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-badge);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .dept-badge.dept-active {
            background: #10B981;
            color: #FFFFFF;
            border-color: #059669;
        }

        .dept-badge.dept-inactive {
            background: #6B7280;
            color: #FFFFFF;
            border-color: #4B5563;
        }

        .btn-outline-primary {
            border-color: var(--whisper);
            color: var(--steel);
        }

        .btn-outline-primary:hover {
            background: rgba(16, 185, 129, 0.08);
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-outline-warning {
            border-color: var(--whisper);
            color: var(--steel);
        }

        .btn-outline-warning:hover {
            background: rgba(245, 158, 11, 0.08);
            border-color: #F59E0B;
            color: #D97706;
        }

        #pagination-links .page-link {
            border: 1px solid var(--whisper);
            color: var(--steel);
            font-size: 0.8125rem;
            font-family: var(--font-display);
            padding: 0.375rem 0.75rem;
            background: var(--surface);
        }

        #pagination-links .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: #FFFFFF;
        }

        #pagination-links .page-item .page-link:hover {
            background: var(--accent-soft);
            border-color: var(--accent);
            color: var(--accent);
        }

        @media (max-width: 767.98px) {
            .metrics-card {
                margin-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body>
    @include('partials.sidebar-nav')

    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0">Department Management</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <button class="btn btn-link position-relative">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">0</span>
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

        <div class="container-fluid p-4">
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
        </div>
    </div>

    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 1.5rem; border: 1px solid var(--whisper); box-shadow: var(--shadow);">
                <div class="modal-header" style="border-bottom: 1px solid var(--whisper); padding: 1.25rem 1.5rem;">
                    <h5 class="modal-title" id="addDepartmentModalLabel" style="font-weight: 700; font-size: 1rem; letter-spacing: -0.02em;">
                        <i class="bi bi-plus-circle" style="color: var(--accent);"></i> Add Department
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <form id="add-department-form">
                        <div class="mb-3">
                            <label for="input-name" class="form-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--ink);">Department Name *</label>
                            <input type="text" class="form-control" id="input-name" name="name" required
                                   style="border: 1px solid var(--whisper); border-radius: 0.375rem; font-size: 0.875rem; font-family: var(--font-display);">
                        </div>
                        <div class="mb-3">
                            <label for="input-code" class="form-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--ink);">Department Code *</label>
                            <input type="text" class="form-control" id="input-code" name="code" required
                                   style="border: 1px solid var(--whisper); border-radius: 0.375rem; font-size: 0.875rem; font-family: var(--font-display); text-transform: uppercase;"
                                   placeholder="e.g. HR, IT, FIN">
                        </div>
                        <div class="mb-3">
                            <label for="input-description" class="form-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--ink);">Description</label>
                            <textarea class="form-control" id="input-description" name="description" rows="3"
                                      style="border: 1px solid var(--whisper); border-radius: 0.375rem; font-size: 0.875rem; font-family: var(--font-display);"></textarea>
                        </div>
                        <div id="add-form-feedback" class="alert d-none" style="border-radius: 0.75rem; font-size: 0.8125rem;"></div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--whisper); padding: 1rem 1.5rem;">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="border: 1px solid var(--whisper); border-radius: 0.75rem; color: var(--steel); font-family: var(--font-display); font-size: 0.8125rem; font-weight: 500;">Cancel</button>
                    <button type="button" class="btn" data-action="submit-add-department" style="background: var(--accent); color: #fff; border: none; border-radius: 0.75rem; font-family: var(--font-display); font-size: 0.8125rem; font-weight: 600; padding: 0.5rem 1.25rem;">
                        <i class="bi bi-check-circle"></i> Add Department
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 1.5rem; border: 1px solid var(--whisper); box-shadow: var(--shadow);">
                <div class="modal-header" style="border-bottom: 1px solid var(--whisper); padding: 1.25rem 1.5rem;">
                    <h5 class="modal-title" id="editDepartmentModalLabel" style="font-weight: 700; font-size: 1rem; letter-spacing: -0.02em;">
                        <i class="bi bi-pencil-square" style="color: #F59E0B;"></i> Edit Department
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <form id="edit-department-form">
                        <input type="hidden" id="edit-department-id">
                        <div class="mb-3">
                            <label for="edit-name" class="form-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--ink);">Department Name *</label>
                            <input type="text" class="form-control" id="edit-name" name="name" required
                                   style="border: 1px solid var(--whisper); border-radius: 0.375rem; font-size: 0.875rem; font-family: var(--font-display);">
                        </div>
                        <div class="mb-3">
                            <label for="edit-code" class="form-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--ink);">Department Code *</label>
                            <input type="text" class="form-control" id="edit-code" name="code" required
                                   style="border: 1px solid var(--whisper); border-radius: 0.375rem; font-size: 0.875rem; font-family: var(--font-display); text-transform: uppercase;">
                        </div>
                        <div class="mb-3">
                            <label for="edit-description" class="form-label" style="font-size: 0.8125rem; font-weight: 600; color: var(--ink);">Description</label>
                            <textarea class="form-control" id="edit-description" name="description" rows="3"
                                      style="border: 1px solid var(--whisper); border-radius: 0.375rem; font-size: 0.875rem; font-family: var(--font-display);"></textarea>
                        </div>
                        <div id="edit-form-feedback" class="alert d-none" style="border-radius: 0.75rem; font-size: 0.8125rem;"></div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--whisper); padding: 1rem 1.5rem;">
                    <button type="button" class="btn" data-bs-dismiss="modal" style="border: 1px solid var(--whisper); border-radius: 0.75rem; color: var(--steel); font-family: var(--font-display); font-size: 0.8125rem; font-weight: 500;">Cancel</button>
                    <button type="button" class="btn" data-action="submit-edit-department" style="background: #F59E0B; color: #fff; border: none; border-radius: 0.75rem; font-family: var(--font-display); font-size: 0.8125rem; font-weight: 600; padding: 0.5rem 1.25rem;">
                        <i class="bi bi-check-circle"></i> Update Department
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/modules/audit-departments.js') }}"></script>
</body>
</html>
