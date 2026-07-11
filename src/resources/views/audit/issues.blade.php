<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reported Issues - Document Tracking System</title>

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

        .audit-badge.status-open {
            background: #3B82F6;
            color: #FFFFFF;
            border-color: #2563EB;
        }

        .audit-badge.status-in_progress {
            background: #F59E0B;
            color: #FFFFFF;
            border-color: #D97706;
        }

        .audit-badge.status-resolved {
            background: #10B981;
            color: #FFFFFF;
            border-color: #059669;
        }

        .audit-badge.status-closed {
            background: #6B7280;
            color: #FFFFFF;
            border-color: #4B5563;
        }

        .audit-badge.priority-low {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .audit-badge.priority-medium {
            background: rgba(245, 158, 11, 0.12);
            color: #D97706;
            border-color: rgba(245, 158, 11, 0.3);
        }

        .audit-badge.priority-high {
            background: rgba(239, 68, 68, 0.12);
            color: #DC2626;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .audit-badge.priority-critical {
            background: rgba(220, 38, 38, 0.15);
            color: #B91C1C;
            border-color: rgba(220, 38, 38, 0.35);
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

        .metrics-icon.red {
            background: rgba(239, 68, 68, 0.12);
            color: #DC2626;
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

        .doc-number {
            font-family: 'Geist Mono', 'JetBrains Mono', ui-monospace, monospace;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--accent);
            letter-spacing: -0.01em;
        }

        .issue-description {
            max-width: 18rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--steel);
            font-size: 0.8125rem;
        }

        .meta-cell {
            color: var(--steel);
            font-size: 0.8125rem;
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

        .pagination-wrapper {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--whisper);
        }

        .pagination-wrapper .pagination {
            margin-bottom: 0;
        }

        @media (max-width: 767.98px) {
            .metrics-card {
                padding: 1rem 1.25rem;
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
                <h1>Reported Issues</h1>
                <p>Review and manage issue reports filed against documents across all departments.</p>
            </div>

            @php
                $totalIssues = $issues->total();
                $openCount = $issues->where('status', 'open')->count();
                $inProgressCount = $issues->where('status', 'in_progress')->count();
                $resolvedCount = $issues->where('status', 'resolved')->count();
            @endphp

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon emerald">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Total Issues</p>
                                <p class="metrics-value">{{ $totalIssues }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon blue">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div>
                                <p class="metrics-label">Open</p>
                                <p class="metrics-value">{{ $openCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metrics-card">
                        <div class="metrics-body">
                            <div class="metrics-icon amber">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div>
                                <p class="metrics-label">In Progress</p>
                                <p class="metrics-value">{{ $inProgressCount }}</p>
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
                                <p class="metrics-label">Resolved</p>
                                <p class="metrics-value">{{ $resolvedCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <div class="table-header-section">
                    <h5>
                        Issue Reports
                        <span class="count-badge">{{ $totalIssues }}</span>
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:12%">Document ID</th>
                                <th style="width:14%">Issue Type</th>
                                <th style="width:14%">Department</th>
                                <th style="width:22%">Description</th>
                                <th style="width:12%">Reporter</th>
                                <th style="width:10%">Priority</th>
                                <th style="width:10%">Status</th>
                                <th style="width:6%">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issues as $issue)
                            <tr>
                                <td>
                                    <a href="{{ url('/document-details/' . ($issue->document->document_number ?? $issue->document_id)) }}"
                                       class="doc-number text-decoration-none">
                                        {{ $issue->document->document_number ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->issue_type ?? 'General' }}</span>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->assignedDepartment->name ?? 'Unassigned' }}</span>
                                </td>
                                <td>
                                    <span class="issue-description" title="{{ e($issue->description) }}">
                                        {{ Str::limit($issue->description, 60) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->reportedBy->name ?? 'Unknown' }}</span>
                                </td>
                                <td>
                                    <span class="audit-badge priority-{{ $issue->priority }}">
                                        {{ ucfirst($issue->priority) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="audit-badge status-{{ $issue->status }}">
                                        {{ str_replace('_', ' ', ucfirst($issue->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="meta-cell">{{ $issue->created_at->format('M d, Y') }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </div>
                                        <h6>No Issues Reported</h6>
                                        <p>There are currently no reported issues in the system. Issue reports will appear here once users flag documents.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($issues->hasPages())
                <div class="pagination-wrapper d-flex justify-content-center">
                    {{ $issues->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>
</body>
</html>
