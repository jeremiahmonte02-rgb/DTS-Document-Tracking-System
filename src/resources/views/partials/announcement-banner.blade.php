@php
    // Merged banner content: system-wide announcements plus the viewing user's
    // own department-scoped near-overdue warnings (newest-first, capped at 3
    // by NotificationServiceProvider). Falls back to announcements-only when
    // the merged variable is unavailable.
    $bannerItems = ($activeBannerItems ?? collect());
    if ($bannerItems->isEmpty() && ($activeAnnouncements ?? collect())->isNotEmpty()) {
        $bannerItems = $activeAnnouncements->map(fn ($a) => ['kind' => 'announcement', 'item' => $a]);
    }
@endphp
@if($bannerItems->isNotEmpty())
    <div class="announcements-banner mb-4">
        @foreach($bannerItems as $entry)
            @if(($entry['kind'] ?? 'announcement') === 'near_overdue')
                @php $warning = $entry['item']; @endphp
                <div class="announcement-banner announcement-banner-warning" role="alert" data-notification-id="{{ $warning->id }}">
                    <div class="announcement-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="announcement-content">
                        <div class="fw-semibold">{{ $warning->title }}</div>
                        <div class="small">{{ $warning->message }}</div>
                        <div class="small text-muted mt-1">{{ $warning->created_at?->diffForHumans() }}</div>
                    </div>
                    <button type="button" class="btn-close" aria-label="Dismiss near-overdue warning"
                            data-notification-id="{{ $warning->id }}"></button>
                </div>
            @else
                @php $announcement = $entry['item']; @endphp
                <div class="announcement-banner" role="alert" data-announcement-id="{{ $announcement->id }}">
                    <div class="announcement-icon">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div class="announcement-content">
                        <div class="fw-semibold">{{ $announcement->title }}</div>
                        <div class="small">{{ $announcement->message }}</div>
                        <div class="small text-muted mt-1">{{ $announcement->created_at?->diffForHumans() }}</div>
                    </div>
                    <button type="button" class="btn-close" aria-label="Dismiss announcement"
                            data-announcement-id="{{ $announcement->id }}"></button>
                </div>
            @endif
        @endforeach
    </div>
@endif
