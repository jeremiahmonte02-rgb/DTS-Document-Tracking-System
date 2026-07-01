<nav class="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-file-earmark-text"></i> DTS</h4>
        <small class="text-white-50">Document Tracking</small>
    </div>
    <ul class="sidebar-nav nav flex-column">
        @if(auth()->user()->isAuditor())
            <li class="nav-item">
                <a class="nav-link" href="{{ route('audit.dashboard') }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Audit Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('audit.documents') }}">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Documents Directory</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('audit.departments') }}">
                    <i class="bi bi-building"></i>
                    <span>Department Management</span>
                </a>
            </li>
        @else
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
                    <span class="badge bg-danger ms-auto">{{ $unreadNotificationsCount ?? 0 }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/outbox">
                    <i class="bi bi-send"></i>
                    <span>Outbox</span>
                </a>
            </li>
            @can('viewAny', App\Models\User::class)
            <li class="nav-item">
                <a class="nav-link" href="/manage-users">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
            </li>
            @endcan
        @endif
    </ul>
</nav>
