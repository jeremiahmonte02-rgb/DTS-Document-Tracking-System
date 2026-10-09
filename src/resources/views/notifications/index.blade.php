@extends('layouts.app')

@section('title', 'Notifications - Document Tracking System')

@section('pageTitle', 'Notifications')

@section('content')
            <div class="card mb-4">
                <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="btn-group" role="group" aria-label="Filter by type">
                        <a href="{{ route('notifications.index') }}" class="btn btn-sm {{ $activeType === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                        <a href="{{ route('notifications.index', ['type' => 'notification']) }}" class="btn btn-sm {{ $activeType === 'notification' ? 'btn-primary' : 'btn-outline-primary' }}">Notifications</a>
                        <a href="{{ route('notifications.index', ['type' => 'announcement']) }}" class="btn btn-sm {{ $activeType === 'announcement' ? 'btn-primary' : 'btn-outline-primary' }}">Announcements</a>
                    </div>
                    <button type="button" id="pageMarkAllRead" class="btn btn-sm btn-outline-secondary">Mark All as Read</button>
                </div>
            </div>

            <div class="card">
                <div class="list-group list-group-flush" id="notificationsList">
                    @forelse($feed as $item)
                        @php
                            $isNotif = $item['type'] === 'notification';
                            $hasDoc = $isNotif && !empty($item['document_number']);
                            $href = $hasDoc ? route('document-details.show', $item['document_number']) : '#';
                        @endphp
                        <a href="{{ $href }}" class="list-group-item list-group-item-action {{ $item['is_read'] ? '' : 'notif-unread' }}"
                           @if($isNotif) data-feed-notification-id="{{ $item['id'] }}" @else data-feed-announcement-id="{{ $item['id'] }}" @endif>
                            <div class="d-flex gap-2">
                                <i class="bi {{ $isNotif ? 'bi-file-earmark-text' : 'bi-megaphone-fill' }} text-muted mt-1"></i>
                                <div class="flex-grow-1" style="min-width:0;">
                                    <div class="feed-title {{ $item['is_read'] ? 'fw-normal' : 'fw-bold' }}">{{ $item['title'] }}</div>
                                    <div class="small text-muted">{{ $item['message'] }}</div>
                                    <div class="small text-muted mt-1">{{ $item['time_ago'] }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item text-center text-muted py-4">No notifications found.</div>
                    @endforelse
                </div>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $feed->links() }}
            </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/modules/notifications.js') }}?v={{ filemtime(public_path('js/modules/notifications.js')) }}"></script>
@endsection
