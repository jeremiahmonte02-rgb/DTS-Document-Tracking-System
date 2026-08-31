@if(($activeAnnouncements ?? collect())->isNotEmpty())
    <div class="announcements-banner mb-4">
        @foreach($activeAnnouncements as $announcement)
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
        @endforeach
    </div>
@endif
