<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted - Document Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>
    @include('partials.sidebar-nav')

    <div class="main-content">
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0">Access Restricted</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span class="d-none d-md-inline">{{ auth()->user()->name ?? 'User' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <h6 class="profile-name mb-1">{{ auth()->user()->name ?? 'User' }}</h6>
                            <p class="profile-department small text-muted mb-1">{{ auth()->user()->department->name ?? 'No Department' }}</p>
                            <span class="profile-role-badge badge bg-primary">{{ auth()->user()->role->name ?? 'Standard User' }}</span>
                        </li>
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
            <div class="row justify-content-center mt-5">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-shield-x text-danger" style="font-size: 4rem;"></i>
                            <h2 class="mt-4 fw-bold">Access Restricted</h2>
                            <p class="text-muted mb-4">
                                {{ $exception->getMessage() ?: 'This action is unauthorized for your role or department.' }}
                            </p>
                            <div class="alert alert-info d-flex align-items-center gap-2 text-start" role="alert">
                                <i class="bi bi-info-circle-fill fs-5"></i>
                                <div>
                                    <strong>Your Context:</strong><br>
                                    Role: <strong>{{ auth()->user()->role->name ?? 'N/A' }}</strong>
                                    &middot;
                                    Department: <strong>{{ auth()->user()->department->name ?? 'Not Assigned' }}</strong>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                                <a href="/dashboard" class="btn btn-primary">
                                    <i class="bi bi-speedometer2"></i> Return to Dashboard
                                </a>
                                <a href="/inbox" class="btn btn-outline-primary">
                                    <i class="bi bi-inbox"></i> View My Department Queue
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
