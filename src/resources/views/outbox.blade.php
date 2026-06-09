<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outbox - Document Tracking System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-file-earmark-text"></i> DTS</h4>
            <small class="text-white-50">Document Tracking</small>
        </div>
        <ul class="sidebar-nav nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="/dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/upload">
                    <i class="bi bi-cloud-upload"></i>
                    <span>Upload Document</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/scan">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Scan QR Code</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/inbox">
                    <i class="bi bi-inbox"></i>
                    <span>Inbox</span>
                    <span class="badge bg-danger ms-auto">3</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/outbox">
                    <i class="bi bi-send"></i>
                    <span>Outbox</span>
                </a>
            </li>
            @if(auth()->user()->role_id === 1)
            <li class="nav-item">
                <a class="nav-link" href="/users">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
            </li>
            @endif
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0">Outbox</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <button class="btn btn-link position-relative">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">3</span>
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

        <!-- Outbox Content -->
        <div class="container-fluid p-4">
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
                            <select class="form-select" data-filter="type">
                                <option value="">All Types</option>
                                <option value="Financial Report">Financial Report</option>
                                <option value="HR Document">HR Document</option>
                                <option value="Legal Document">Legal Document</option>
                                <option value="Marketing Report">Marketing Report</option>
                                <option value="Policy Document">Policy Document</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="status">
                                <option value="">All Status</option>
                                <option value="Received">Received</option>
                                <option value="Pending Transfer">Pending Transfer</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" data-filter="date"
                                   placeholder="Filter by date">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
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
                        <i class="bi bi-send"></i> Sent Documents
                        <span class="badge bg-primary ms-2" id="documentCount">0</span>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshOutbox()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="exportOutbox()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Document ID</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Sender</th>
                                    <th>Receiver</th>
                                    <th>Date Sent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="outboxTable">
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
                            Showing <span id="showingCount">0</span> documents
                        </small>
                        <nav aria-label="Outbox pagination">
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
                        <i class="bi bi-info-circle text-info"></i> Outbox Information
                    </h6>
                    <ul class="mb-0 small">
                        <li>Your outbox shows all documents sent by your department ({{ auth()->user()->department->name ?? 'No Department Assigned' }})</li>
                        <li>Click on any document row to view full details and track its delivery status</li>
                        <li>Track whether documents have been received by the destination department</li>
                        <li>Use filters to organize documents by type, status, or date sent</li>
                        <li>Documents with "Pending Transfer" status are awaiting pickup or delivery</li>
                        <li>Monitor document progress through the complete audit trail</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    @include('partials.auth-context')
    <script src="{{ asset('js/main.js') }}"></script>

    <script>
        // Update document count after loading
        setTimeout(() => {
            const outboxTable = document.getElementById('outboxTable');
            const rowCount = outboxTable.querySelectorAll('tr').length;
            document.getElementById('documentCount').textContent = rowCount;
            document.getElementById('showingCount').textContent = rowCount;
        }, 500);

        function clearFilters() {
            document.querySelectorAll('[data-filter]').forEach(input => {
                input.value = '';
            });
            document.getElementById('searchInput').value = '';

            // Show all rows
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = '';
            });
        }

        function refreshOutbox() {
            showToast('Outbox refreshed', 'success');
            location.reload();
        }

        function exportOutbox() {
            showToast('Exporting outbox data...', 'info');
            // In a real application, this would generate and download a CSV file
            setTimeout(() => {
                showToast('Export complete!', 'success');
            }, 1500);
        }

    </script>
</body>
</html>
