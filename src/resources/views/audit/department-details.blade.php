<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $department->name }} - Department Analytics - Document Tracking System</title>

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

        .page-header .breadcrumb-links {
            font-size: 0.8125rem;
            color: var(--muted);
            margin-bottom: 0.5rem;
        }

        .page-header .breadcrumb-links a {
            color: var(--accent);
            text-decoration: none;
        }

        .page-header .breadcrumb-links a:hover {
            text-decoration: underline;
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

        .metrics-icon.amber {
            background: rgba(245, 158, 11, 0.12);
            color: #D97706;
        }

        .metrics-icon.blue {
            background: rgba(59, 130, 246, 0.12);
            color: #2563EB;
        }

        .metrics-icon.red {
            background: rgba(239, 68, 68, 0.12);
            color: #DC2626;
        }

        .metrics-icon.slate {
            background: rgba(100, 116, 139, 0.12);
            color: #475569;
        }

        .metrics-icon.violet {
            background: rgba(139, 92, 246, 0.12);
            color: #7C3AED;
        }

        .metrics-icon.orange {
            background: rgba(249, 115, 22, 0.12);
            color: #EA580C;
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

        .metrics-value.small {
            font-size: 1.125rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--whisper);
        }

        .section-header h5 {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.02em;
            margin-bottom: 0;
            color: var(--ink);
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

        .audit-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-badge);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            font-variant-numeric: tabular-nums;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .audit-badge.status-pending {
            background: #F59E0B;
            color: #FFFFFF;
            border-color: #D97706;
        }

        .audit-badge.status-transit {
            background: #3B82F6;
            color: #FFFFFF;
            border-color: #2563EB;
        }

        .audit-badge.status-received {
            background: #10B981;
            color: #FFFFFF;
            border-color: #059669;
        }

        .audit-badge.status-completed {
            background: #059669;
            color: #FFFFFF;
            border-color: #047857;
        }

        .audit-badge.status-rejected {
            background: #EF4444;
            color: #FFFFFF;
            border-color: #DC2626;
        }

        .audit-badge.status-cancelled {
            background: #6B7280;
            color: #FFFFFF;
            border-color: #4B5563;
        }

        .event-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .event-timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0.5rem;
            bottom: 0.5rem;
            width: 2px;
            background: var(--whisper);
        }

        .event-item {
            position: relative;
            padding-bottom: 1.25rem;
        }

        .event-item:last-child {
            padding-bottom: 0;
        }

        .event-dot {
            position: absolute;
            left: -1.625rem;
            top: 0.375rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            background: var(--accent);
            border: 2px solid var(--surface);
            z-index: 1;
        }

        .event-dot.sent {
            background: #3B82F6;
        }

        .event-dot.received {
            background: #10B981;
        }

        .event-dot.completed {
            background: #059669;
        }

        .event-dot.rejected {
            background: #EF4444;
        }

        .event-dot.transferred {
            background: #F59E0B;
        }

        .event-action {
            font-size: 0.8125rem;
            font-weight: 600;
            text-transform: capitalize;
            margin-bottom: 0.125rem;
        }

        .event-meta {
            font-size: 0.75rem;
            color: var(--muted);
        }

        .event-notes {
            font-size: 0.8125rem;
            color: var(--steel);
            margin-top: 0.25rem;
        }

        .event-ref {
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 0.125rem;
        }

        .event-ref a {
            color: var(--ink);
            text-decoration: none;
        }

        .event-ref a:hover {
            color: var(--accent);
        }

        .doc-number {
            font-family: 'Geist Mono', 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: -0.01em;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 2rem;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 48px -18px rgba(0, 0, 0, 0.08);
        }

        .stat-card .card-body {
            padding: 1.5rem 1.75rem;
        }

        .stat-card .stat-label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--steel);
            letter-spacing: -0.01em;
            margin-bottom: 0.25rem;
        }

        .stat-card .stat-value {
            font-size: clamp(1.75rem, 3vw, 2.25rem);
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.025em;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }

        #documents-pagination .page-link {
            border: 1px solid var(--whisper);
            color: var(--steel);
            font-size: 0.8125rem;
            font-family: var(--font-display);
            padding: 0.375rem 0.75rem;
            background: var(--surface);
        }

        #documents-pagination .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: #FFFFFF;
        }

        #documents-pagination .page-item .page-link:hover {
            background: var(--accent-soft);
            border-color: var(--accent);
            color: var(--accent);
        }

        .dept-code {
            font-family: 'Geist Mono', 'JetBrains Mono', ui-monospace, monospace;
            font-size: 1rem;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 0.02em;
            text-transform: uppercase;
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
                <h5 class="mb-0">Department Analytics</h5>
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

        <div class="container-fluid p-4" id="dept-details-wrapper"
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
                                        <th style="width:18%">Document ID</th>
                                        <th style="width:32%">Title</th>
                                        <th style="width:15%">Type</th>
                                        <th style="width:15%">From</th>
                                        <th style="width:10%">Status</th>
                                        <th style="width:10%">Created</th>
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
        </div>
    </div>

    <!-- Configure SLA Modal -->
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
                        <table class="table" id="sla-configuration-table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th style="width: 200px;">Time Value</th>
                                    <th style="width: 180px;">Time Unit</th>
                                </tr>
                            </thead>
                            <tbody id="sla-configuration-body">
                                <!-- Hydrated by JavaScript -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/modules/audit-department-details.js') }}"></script>
</body>
</html>