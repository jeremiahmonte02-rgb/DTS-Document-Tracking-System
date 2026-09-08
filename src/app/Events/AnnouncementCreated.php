<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AnnouncementCreated implements ShouldBroadcastNow
{
    public function __construct(public Announcement $announcement)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('announcements');
    }

    /**
     * Intentionally minimal payload (nudge-then-refetch pattern).
     *
     * The socket carries NO announcement content — no title, no message,
     * nothing the frontend should render. On receiving this signal the
     * browser re-fetches authoritative data from
     * GET /api/notifications/feed instead, so the socket can never carry
     * data that disagrees with the database. Do NOT add real data here.
     */
    public function broadcastWith(): array
    {
        return ['changed_at' => now()->toIso8601String()];
    }
}
