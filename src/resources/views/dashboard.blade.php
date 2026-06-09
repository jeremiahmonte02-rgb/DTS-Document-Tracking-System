
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Document Tracking System</title>
    
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
                    <span class="badge bg-danger ms-auto">{{ $unreadNotificationsCount }}</span>
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
                <h5 class="mb-0">Dashboard</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <button class="btn btn-link position-relative" onclick="showNotifications()">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="notification-badge">{{ $unreadNotificationsCount }}</span>
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

        <!-- Dashboard Content -->
        <div class="container-fluid p-4">
            @if(session('success'))
            <div class="alert alert-success d-flex align-items-center alert-dismissible fade show shadow-sm mb-4" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; padding: 1rem 1.25rem; border-radius: 0.375rem; position: relative;">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>
                    <strong>Success!</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="position: absolute; right: 1.25rem; top: 1rem; background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #0f5132;"></button>
            </div>
            @endif
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Documents</h6>
                                    <h2 class="mb-0">{{ $totalDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Pending Transfer</h6>
                                    <h2 class="mb-0">{{ $pendingDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Received Today</h6>
                                    <h2 class="mb-0">{{ $receivedToday }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">In Transit</h6>
                                    <h2 class="mb-0">{{ $inTransitDocuments }}</h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi bi-arrow-left-right"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4 mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-lightning"></i> Quick Actions
                            </h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="/upload" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload"></i> Upload Document
                                </a>
                                <a href="/scan" class="btn btn-success">
                                    <i class="bi bi-qr-code-scan"></i> Scan QR Code
                                </a>
                                <a href="/inbox" class="btn btn-info">
                                    <i class="bi bi-inbox"></i> View Inbox
                                </a>
                                <a href="/outbox" class="btn btn-warning">
                                    <i class="bi bi-send"></i> View Outbox
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Activity Feed -->
            <div class="row g-4">
                <!-- Charts -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-bar-chart"></i> Document Status Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="statusChart" data-metrics='@json($statusMetrics ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up"></i> Documents by Department
                            </h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <canvas id="departmentChart" data-metrics='@json($departmentDistribution ?? [])'></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-activity"></i> Recent Activity
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-feed">
                                @forelse ($activityFeed as $event)
                                    <div class="activity-item d-flex align-items-start">
                                        <div class="activity-icon bg-primary text-white me-3">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $event->event_label }}</strong>
                                                <small class="text-muted">{{ $event->created_at ? \Carbon\Carbon::parse($event->created_at)->diffForHumans() : '-' }}</small>
                                            </div>
                                            <small class="text-muted">
                                                {{ $event->note }}<br>
                                                <span class="text-dark fw-medium">{{ $event->user_name }}</span>
                                                &middot;
                                                <span class="text-muted">{{ $event->document_number }}</span>
                                            </small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center p-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        <small>No recent activity</small>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="modal-backdrop-custom d-none" style="position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 1050; display: flex; align-items: center; justify-content: center;">
        <div class="modal-card bg-white p-4 rounded shadow-lg text-center" style="width: 350px;">
            <h5 class="mb-2 fw-bold">Document Tracking Label</h5>
            <p id="qrDocNumber" class="text-primary font-monospace fw-bold mb-3"></p>
            <div id="qrCodeContainer" class="d-flex justify-content-center p-2 bg-light mb-3"></div>
            <div class="d-flex gap-2 justify-content-center">
                <button id="downloadQrBtn" class="btn btn-sm btn-success"><i class="bi bi-download"></i> Download</button>
                <button class="btn btn-sm btn-light" onclick="closeQrModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    
    <!-- Auth Context -->
    @include('partials.auth-context')
    
    <script src="{{ asset('js/modules/dashboard.js') }}"></script>
</body>
</html>
