<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Document Tracking System')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    @yield('styles')
</head>
<body>
    @include('partials.sidebar-nav')

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none me-2" id="mobileMenuToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <h5 class="mb-0">@yield('pageTitle', 'Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-link position-relative dropdown-toggle" type="button"
                            id="notificationBell" data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <span id="notificationBadge" class="notification-badge {{ (($unreadNotificationsCount ?? 0) + ($unreadAnnouncementsCount ?? 0)) > 0 ? '' : 'd-none' }}">{{ ($unreadNotificationsCount ?? 0) + ($unreadAnnouncementsCount ?? 0) }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-menu shadow" aria-labelledby="notificationBell">
                        @forelse($recentNotifications ?? [] as $notification)
                            <li>
                                <a class="dropdown-item notification-item"
                                   href="{{ $notification->document ? route('document-details.show', $notification->document->document_number) : '#' }}"
                                   data-notification-id="{{ $notification->id }}"
                                   data-no-spinner="true">
                                    <div class="notification-title fw-semibold">{{ $notification->title }}</div>
                                    <div class="notification-message small text-muted text-truncate">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</div>
                                    <div class="notification-time small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                                </a>
                            </li>
                            @if (!$loop->last)
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            @empty
                                <li><span class="dropdown-item-text text-muted text-center py-3">No new notifications</span></li>
                            @endforelse
                            @if(($unreadAnnouncements ?? collect())->isNotEmpty())
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Announcements</h6></li>
                                @foreach($unreadAnnouncements as $announcement)
                                    <li>
                                        <a class="dropdown-item notification-item announcement-item"
                                           href="#" data-announcement-id="{{ $announcement->id }}" data-no-spinner="true">
                                            <div class="notification-title fw-semibold">{{ $announcement->title }}</div>
                                            <div class="notification-message small text-muted text-truncate">{{ \Illuminate\Support\Str::limit($announcement->message, 90) }}</div>
                                            <div class="notification-time small text-muted mt-1">{{ $announcement->created_at?->diffForHumans() }}</div>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
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
                        <li><a class="dropdown-item" href="{{ route('activity-log') }}"><i class="bi bi-clock-history"></i> Activity Log</a></li>
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

        <!-- Page Content -->
        <div class="container-fluid p-4">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Auth Context -->
    @include('partials.auth-context')

    <!-- Global JS -->
    <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>

    <!-- Global Formatting Utility -->
    <script src="{{ asset('js/core/format.js') }}?v={{ filemtime(public_path('js/core/format.js')) }}"></script>

    @yield('scripts')
</body>
</html>
