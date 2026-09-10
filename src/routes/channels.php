<?php

use App\Models\Document;
use Illuminate\Support\Facades\Broadcast;

// The `announcements` broadcast channel used by the real-time pilot is a
// PUBLIC channel, so it needs no authorization callback here.

// Department-scoped nudge channel for document routing updates. A document
// arriving at (or otherwise affecting) a department's queue must never be
// visible to another department's browser, even at the transport level, so
// these are PRIVATE channels with server-side authorization — unlike the
// public announcements channel, which is intentionally visible to all.
Broadcast::channel('department.{departmentId}', function ($user, $departmentId) {
    if ($user->isAdmin() || $user->isAuditor()) {
        return true;
    }

    return $user->department_id
        && (int) $user->department_id === (int) $departmentId;
});

// Document-scoped nudge channel for the document-details live timeline.
// Reuses the exact same authorization as the page itself
// (DocumentPolicy::view) — no parallel authorization rules.
Broadcast::channel('document.{documentId}', function ($user, $documentId) {
    $document = Document::find($documentId);

    if (!$document) {
        return false;
    }

    return $user->can('view', $document);
});
