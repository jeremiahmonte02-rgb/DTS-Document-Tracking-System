<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NearOverdueWarningCreated implements ShouldBroadcastNow
{
    /**
     * @param int $departmentId The department whose users were just warned.
     *                          A single id (not an array): each firing site in
     *                          CheckNearOverdueDocuments already loops
     *                          per-department, unlike DocumentRouteUpdated.
     */
    public function __construct(
        public int $departmentId
    ) {
    }

    public function broadcastOn(): array
    {
        // Reuses the existing department.{id} private channel (authorized in
        // routes/channels.php) — no new channel or authorization rule needed.
        return [new PrivateChannel('department.' . $this->departmentId)];
    }

    /**
     * Intentionally minimal payload (nudge-then-refetch pattern).
     *
     * The socket carries NO warning content — no title, no message, no
     * document details, nothing the frontend should render or another
     * department could observe. On receiving this signal the browser
     * re-fetches authoritative data from GET /api/notifications/feed
     * (itself scoped to the viewing user), so the socket can never carry
     * data that disagrees with the database. Do NOT add real data here.
     */
    public function broadcastWith(): array
    {
        return ['changed_at' => now()->toIso8601String()];
    }
}
