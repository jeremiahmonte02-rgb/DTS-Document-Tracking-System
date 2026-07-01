<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Routing Policies - Document Tracking System</title>

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

        .policy-header {
            margin-bottom: 2rem;
        }

        .policy-header h1 {
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .policy-header p {
            font-size: 0.9375rem;
            color: var(--steel);
            max-width: 65ch;
            margin-bottom: 0;
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
            cursor: pointer;
        }

        .table-container .table tbody tr:hover {
            background: var(--accent-soft);
        }

        .table-container .table tbody tr.active-row {
            background: var(--accent-soft);
            border-left: 3px solid var(--accent);
        }

        .badge-policy {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-badge);
        }

        .badge-immutable {
            background: rgba(239, 68, 68, 0.12);
            color: #DC2626;
        }

        .badge-mutable {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }

        .badge-default {
            background: rgba(100, 116, 139, 0.12);
            color: #475569;
        }

        .editor-card {
            background: var(--surface);
            border: 1px solid var(--whisper);
            border-radius: 2rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .editor-card h5 {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--ink);
            margin-bottom: 0.25rem;
        }

        .editor-card .editor-subtitle {
            font-size: 0.8125rem;
            color: var(--steel);
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 0.375rem;
        }

        .form-select, .form-control {
            border: 1px solid var(--whisper);
            border-radius: 0.75rem;
            font-size: 0.8125rem;
            font-family: var(--font-display);
            color: var(--ink);
            padding: 0.5rem 0.75rem;
            background: var(--surface);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.15);
        }

        .route-pool {
            border: 1px solid var(--whisper);
            border-radius: 0.75rem;
            background: var(--surface);
            height: 220px;
            overflow-y: auto;
        }

        .route-pool .list-group-item {
            font-size: 0.8125rem;
            border: none;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border-radius: 0.375rem;
            transition: background 0.15s ease;
        }

        .route-pool .list-group-item:hover {
            background: var(--accent-soft);
        }

        .route-pool .list-group-item.selected {
            background: var(--accent-soft);
            color: #059669;
            font-weight: 600;
        }

        .route-list-container {
            border: 1px solid var(--whisper);
            border-radius: 0.75rem;
            background: var(--surface);
            height: 220px;
            overflow-y: auto;
        }

        .route-list-container .route-item {
            font-size: 0.8125rem;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--whisper);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .route-list-container .route-item:last-child {
            border-bottom: none;
        }

        .route-order-badge {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.625rem;
            font-weight: 700;
            border-radius: 50%;
            background: var(--accent);
            color: white;
        }

        .btn-accent {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: background 0.15s ease;
        }

        .btn-accent:hover {
            background: #059669;
            color: white;
        }

        .btn-accent-outline {
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
            border-radius: 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            transition: all 0.15s ease;
        }

        .btn-accent-outline:hover {
            background: var(--accent-soft);
        }

        .btn-outline-move {
            background: transparent;
            border: 1px solid var(--whisper);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            color: var(--steel);
            transition: all 0.15s ease;
        }

        .btn-outline-move:hover {
            background: var(--accent-soft);
            color: var(--accent);
            border-color: var(--accent);
        }

        .btn-outline-remove {
            background: transparent;
            border: 1px solid var(--whisper);
            border-radius: 0.375rem;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            color: #EF4444;
            transition: all 0.15s ease;
        }

        .btn-outline-remove:hover {
            background: rgba(239, 68, 68, 0.12);
            border-color: #EF4444;
        }

        .placeholder-text {
            font-size: 0.75rem;
            color: var(--muted);
        }

        .policy-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .policy-toggle .form-check-input {
            width: 2.5rem;
            height: 1.25rem;
            cursor: pointer;
        }

        .policy-toggle .form-check-input:checked {
            background-color: #DC2626;
            border-color: #DC2626;
        }

        .policy-toggle .form-check-input:focus {
            border-color: #DC2626;
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.15);
        }

        .policy-toggle .toggle-label {
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .policy-toggle .toggle-label.immutable {
            color: #DC2626;
        }

        .policy-toggle .toggle-label.mutable {
            color: var(--accent);
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.75rem;
        }

        .empty-state p {
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        .alert-flash {
            border-radius: 1rem;
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
        }

        .hidden-pool {
            display: none;
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
                <h5 class="mb-0">Routing Policies</h5>
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
            <div class="policy-header">
                <h1>Document Type Routing Policies</h1>
                <p>Configure whether each document type follows a fixed immutable path or a mutable template path across departments.</p>
            </div>

            @if (session('success'))
            <div class="alert alert-success alert-flash d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
            </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Document Type</th>
                                    <th>Policy Status</th>
                                </tr>
                            </thead>
                            <tbody id="policyTableBody">
                                @forelse($documentTypes as $type)
                                <tr data-type-id="{{ $type->id }}"
                                    data-type-name="{{ $type->name }}"
                                    data-is-immutable="{{ $type->routingPolicy?->is_immutable ?? 'null' }}"
                                    data-predefined-route="{{ json_encode($type->routingPolicy?->predefined_route ?? []) }}"
                                    onclick="selectPolicyType(this)">
                                    <td class="fw-semibold">{{ $type->name }}</td>
                                    <td>
                                        @if ($type->routingPolicy)
                                            @if ($type->routingPolicy->is_immutable)
                                            <span class="badge badge-policy badge-immutable">Immutable</span>
                                            @else
                                            <span class="badge badge-policy badge-mutable">Mutable</span>
                                            @endif
                                        @else
                                        <span class="badge badge-policy badge-default">Default</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2">
                                        <div class="empty-state">
                                            <i class="bi bi-folder2-open"></i>
                                            <p>No document types defined.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="editor-card" id="policyEditor">
                        <div class="empty-state" id="editorPlaceholder">
                            <i class="bi bi-arrow-left-circle"></i>
                            <p>Select a document type from the list to configure its routing policy.</p>
                        </div>

                        <div id="editorContent" style="display: none;">
                            <form method="POST" action="{{ route('audit.policies.store') }}" id="policyForm">
                                @csrf
                                <input type="hidden" name="document_type_id" id="policyDocumentTypeId">
                                <input type="hidden" name="is_immutable" id="policyIsImmutable" value="0">
                                <input type="hidden" name="predefined_route" id="policyPredefinedRoute">

                                <h5 id="editorTitle">Configure Policy</h5>
                                <p class="editor-subtitle" id="editorSubtitle">Set the routing behavior for this document type.</p>

                                <div class="mb-4">
                                    <label class="form-label">Routing Mode</label>
                                    <div class="policy-toggle">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="immutableToggle" onchange="toggleImmutable(this)">
                                        </div>
                                        <span class="toggle-label mutable" id="modeLabel">Mutable — departments can be reordered during upload</span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Default Route Path</label>
                                    <p class="placeholder-text mb-2">Select departments and build the ordered routing sequence.</p>

                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <select id="deptPool" class="form-select mb-2" size="8" multiple>
                                                @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" data-name="{{ $dept->name }}">{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-accent btn-sm flex-grow-1" onclick="addSelectedDepts()">
                                                    <i class="bi bi-plus-lg"></i> Add
                                                </button>
                                                <button type="button" class="btn btn-accent-outline btn-sm" onclick="clearRoute()">
                                                    <i class="bi bi-x-lg"></i> Clear
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-7">
                                            <div class="route-list-container" id="routeListContainer">
                                                <div class="placeholder-text d-flex align-items-center justify-content-center h-100" id="routePlaceholder">
                                                    Selected departments appear here in order
                                                </div>
                                                <ul class="list-unstyled mb-0" id="routeList"></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 pt-2 border-top" style="border-color: var(--whisper) !important;">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="bi bi-check-lg"></i> Save Policy
                                    </button>
                                    <button type="button" class="btn btn-accent-outline" onclick="resetEditor()">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.access-denied-modal')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/modules/audit-policies.js') }}"></script>
</body>
</html>
