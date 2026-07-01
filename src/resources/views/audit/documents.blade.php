<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Directory - Document Tracking System</title>

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

        .stat-card .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon.emerald {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .stat-icon.amber {
            background: rgba(245, 158, 11, 0.1);
            color: #D97706;
        }

        .stat-icon.blue {
            background: rgba(59, 130, 246, 0.1);
            color: #2563EB;
        }

        .stat-icon.slate {
            background: rgba(100, 116, 139, 0.1);
            color: #475569;
        }

        .stat-icon.zinc {
            background: rgba(24, 24, 27, 0.06);
            color: var(--ink);
        }

        .audit-header {
            margin-bottom: 2rem;
        }

        .audit-header h1 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .audit-header p {
            font-size: 0.9375rem;
            color: var(--steel);
            max-width: 65ch;
            margin-bottom: 0;
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

        .doc-number {
            font-family: 'Geist Mono', 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: -0.01em;
        }

        .table-container .table tbody td.cell-title {
            overflow: hidden;
        }

        .doc-title-cell {
            font-weight: 500;
            display: block;
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .meta-cell {
            color: var(--steel);
            font-size: 0.8125rem;
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

        .table-header-section .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.5rem;
            border-radius: var(--radius-badge);
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            margin-left: 0.5rem;
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

        .filter-btn-clear {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            font-size: 0.8125rem;
            font-weight: 500;
            font-family: var(--font-display);
            color: var(--steel);
            background: transparent;
            border: 1px solid var(--whisper);
            border-radius: 0.375rem;
            transition: background 0.15s ease, color 0.15s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .filter-btn-clear:hover {
            background: rgba(239, 68, 68, 0.06);
            color: #B91C1C;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state .empty-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 1.25rem;
            background: var(--accent-soft);
            color: var(--accent);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .empty-state h6 {
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 0.375rem;
        }

        .empty-state p {
            font-size: 0.875rem;
            color: var(--muted);
            max-width: 28rem;
            margin: 0 auto;
        }

        @media (max-width: 767.98px) {
            .stat-card .card-body {
                padding: 1.25rem;
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
                <h5 class="mb-0">Audit Portal</h5>
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
                                <th style="width:15%">Document ID</th>
                                <th style="width:30%">Title</th>
                                <th style="width:10%">Type</th>
                                <th style="width:15%">Origin</th>
                                <th style="width:15%">Current Location</th>
                                <th style="width:10%">Date Created</th>
                                <th style="width:5%">Status</th>
                            </tr>
                        </thead>
                        <tbody id="audit-table-body">
                            <!-- Populated asynchronously via the decoupled audit.js asset module -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/modules/audit.js') }}"></script>
</body>
</html>
