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
                        @php $totalUnread = ($unreadNotificationsCount ?? 0) + ($unreadAnnouncementsCount ?? 0); @endphp
                        <span id="notificationBadge" class="notification-badge {{ $totalUnread > 0 ? '' : 'd-none' }}">{{ $totalUnread > 99 ? '99+' : $totalUnread }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-menu shadow" aria-labelledby="notificationBell">
                        {{-- Keep in sync with the empty-state HTML in public/js/main.js (ensureDropdownEmptyState and backfillDropdown) --}}
                        @if($totalUnread === 0)
                            <li>
                                <span class="dropdown-item-text text-center py-4 text-muted">
                                    <i class="bi bi-bell-slash d-block fs-4 mb-2"></i>
                                    No new notifications
                                    <small class="d-block text-muted mt-1" style="font-size:0.75rem;">You're all caught up</small>
                                </span>
                            </li>
                        @else
                            @forelse($unifiedFeed as $item)
                                <li>
                                    <a class="dropdown-item notification-item {{ $item['type'] === 'announcement' ? 'announcement-item' : '' }}"
                                       href="{{ $item['type'] === 'notification' && $item['document'] ? route('document-details.show', $item['document']->document_number) : '#' }}"
                                       data-{{ $item['type'] === 'notification' ? 'notification' : 'announcement' }}-id="{{ $item['id'] }}"
                                       data-no-spinner="true">
                                        <div class="d-flex gap-2">
                                            <i class="bi {{ $item['type'] === 'notification' ? 'bi-file-earmark-text' : 'bi-megaphone-fill' }} text-muted mt-1" style="font-size:0.875rem;"></i>
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <div class="notification-title fw-semibold">{{ $item['title'] }}</div>
                                                <div class="notification-message small text-muted text-truncate">{{ \Illuminate\Support\Str::limit($item['message'], 90) }}</div>
                                                <div class="notification-time small text-muted mt-1">{{ $item['created_at']?->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                @if (!$loop->last)
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                            @empty
                                <li><span class="dropdown-item-text text-muted text-center py-3 small">No notifications</span></li>
                            @endforelse
                        @endif
                        @if($totalUnread > 0)
                            <li class="dropdown-footer-sticky"><hr class="dropdown-divider my-0"><button id="dismissAllBtn" class="dropdown-item text-center small text-muted">Dismiss All</button></li>
                        @endif
                        </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center gap-2"
                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        @if(auth()->user()->avatar_path)
                            <img src="{{ auth()->user()->avatarUrl() }}" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" alt="Profile picture">
                        @else
                            <i class="bi bi-person-circle fs-5"></i>
                        @endif
                        <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <h6 class="profile-name mb-1">{{ auth()->user()->name }}</h6>
                            <p class="profile-department small text-muted mb-1">{{ auth()->user()->department->name ?? 'No Department Assigned' }}</p>
                            <span class="profile-role-badge badge bg-primary">{{ auth()->user()->role->name ?? 'Standard User' }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person"></i> Profile</a></li>
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

    @include('partials.confirm-modal')

    <!-- Auth Context -->
    @include('partials.auth-context')

    <!-- Real-time (Laravel Reverb via CDN; plain script tags per app convention) -->
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
    <script>
        (function () {
            try {
                if (typeof Echo === 'undefined' || typeof Pusher === 'undefined') return;
                window.Pusher = Pusher;
                window.Echo = new Echo({
                    broadcaster: 'reverb',
                    key: "{{ config('broadcasting.connections.reverb.key') }}",
                    wsHost: window.location.hostname,
                    wsPort: {{ (int) config('broadcasting.connections.reverb.options.port', 8080) }},
                    wssPort: {{ (int) config('broadcasting.connections.reverb.options.port', 8080) }},
                    forceTLS: {{ config('broadcasting.connections.reverb.options.scheme', 'http') === 'https' ? 'true' : 'false' }},
                    enabledTransports: ['ws', 'wss']
                });
            } catch (e) {
                window.Echo = undefined;
            }
        })();
    </script>
    <script src="{{ asset('js/modules/realtime-announcements.js') }}?v={{ filemtime(public_path('js/modules/realtime-announcements.js')) }}"></script>

    <!-- Global JS -->
    <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>

    <script src="{{ asset('js/modules/confirm-modal.js') }}?v={{ filemtime(public_path('js/modules/confirm-modal.js')) }}"></script>

    <!-- Global Formatting Utility -->
    <script src="{{ asset('js/core/format.js') }}?v={{ filemtime(public_path('js/core/format.js')) }}"></script>

    @yield('scripts')
</body>
</html>
