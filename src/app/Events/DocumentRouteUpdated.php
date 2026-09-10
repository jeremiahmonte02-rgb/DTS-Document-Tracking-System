<?php

namespace App\Events;

use App\Models\Document;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class DocumentRouteUpdated implements ShouldBroadcastNow
{
    /**
     * @param array<int> $departmentIds Departments whose queue or step view
     *                                  changed for this document.
     */
    public function __construct(
        public Document $document,
        public array $departmentIds
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('document.' . $this->document->id)];

        $seen = [];
        foreach ($this->departmentIds as $departmentId) {
            $departmentId = (int) $departmentId;
            if ($departmentId > 0 && !isset($seen[$departmentId])) {
                $seen[$departmentId] = true;
                $channels[] = new PrivateChannel('department.' . $departmentId);
            }
        }

        return $channels;
    }

    /**
     * Intentionally nudge-only payload (nudge-then-refetch pattern).
     *
     * The socket carries NO route or status data — no step states, no
     * department names, nothing a listener could render or leak. On
     * receiving this signal the browser re-fetches authoritative data
     * (Inbox table or the document-routes endpoint), both of which are
     * themselves authorization-gated, so the socket can never expose
     * data the recipient is not allowed to see. Do NOT add real data here.
     */
    public function broadcastWith(): array
    {
        return [
            'document_number' => $this->document->document_number,
            'changed_at' => now()->toIso8601String(),
        ];
    }
}
