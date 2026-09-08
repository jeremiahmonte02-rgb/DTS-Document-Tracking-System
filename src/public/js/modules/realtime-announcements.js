(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Echo === 'undefined' || !window.Echo) {
            return;
        }

        try {
            window.Echo.channel('announcements').listen('AnnouncementCreated', function () {
                // Nudge-then-refetch: the socket payload carries no renderable
                // data, so refresh both announcement surfaces (dropdown and
                // dashboard banner) from the authoritative feed endpoint
                // with a single shared fetch.
                if (typeof refreshAnnouncementSurfaces === 'function') {
                    refreshAnnouncementSurfaces();
                } else if (typeof backfillDropdown === 'function') {
                    backfillDropdown();
                }
            });
        } catch (e) {
            // A dead socket must never break the page; the full-page-load
            // composer remains the fallback for users without WebSocket.
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[realtime-announcements] listener setup failed:', e);
            }
        }
    });
})();
