<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Document Tracking System</title>

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
                <h5 class="mb-0">User Management</h5>
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

        <!-- User Management Content -->
        <div class="container-fluid p-4">
            <!-- Filters and Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search users...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="department">
                                <option value="">All Departments</option>
                                <option value="Executive Office">Executive Office</option>
                                <option value="Finance">Finance</option>
                                <option value="HR">HR</option>
                                <option value="IT">IT</option>
                                <option value="Legal">Legal</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Operations">Operations</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="role">
                                <option value="">All Roles</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Department User">Department User</option>
                                <option value="Auditor">Auditor</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" data-filter="status">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
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

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Users</h6>
                                    <h3 class="mb-0">10</h3>
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
                                    <h3 class="mb-0">9</h3>
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
                                    <h3 class="mb-0">1</h3>
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
                                    <h3 class="mb-0">8</h3>
                                </div>
                                <div class="text-warning">
                                    <i class="bi bi-building fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> System Users
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshUsers()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="exportUsers()">
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
                            <tbody id="usersTable">
                                <!-- Table rows will be loaded by JavaScript -->
                                <tr>
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
                </div>
            </div>

            <!-- Info Card -->
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
                    <form id="addUserForm">
                        <div class="mb-3">
                            <label for="userName" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="userName" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="userEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="userDepartment" class="form-label">Department *</label>
                            <select class="form-select" id="userDepartment" required>
                                <option value="">Select department</option>
                                <option value="Executive Office">Executive Office</option>
                                <option value="Finance">Finance</option>
                                <option value="HR">HR</option>
                                <option value="IT">IT</option>
                                <option value="Legal">Legal</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Operations">Operations</option>
                                <option value="Customer Service">Customer Service</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="userRole" class="form-label">Role *</label>
                            <select class="form-select" id="userRole" required>
                                <option value="">Select role</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Department User">Department User</option>
                                <option value="Auditor">Auditor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Initial Password *</label>
                            <input type="password" class="form-control" id="userPassword" required>
                            <small class="form-text text-muted">
                                User will be required to change password on first login
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">
                        <i class="bi bi-check-circle"></i> Add User
                    </button>
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
        function addUser() {
            const form = document.getElementById('addUserForm');
            if (form.checkValidity()) {
                const userName = document.getElementById('userName').value;
                showSpinner();

                setTimeout(() => {
                    hideSpinner();
                    showToast(`User ${userName} added successfully!`, 'success');

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                    modal.hide();

                    // Reset form
                    form.reset();

                    // Reload users
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }, 1500);
            } else {
                form.reportValidity();
            }
        }

        function refreshUsers() {
            showToast('Users list refreshed', 'success');
            location.reload();
        }

        function exportUsers() {
            showToast('Exporting users data...', 'info');
            setTimeout(() => {
                showToast('Export complete!', 'success');
            }, 1500);
        }

    </script>
</body>
</html>
