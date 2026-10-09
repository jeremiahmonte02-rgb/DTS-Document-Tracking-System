// Live near-overdue nudge: when the scheduler persists a near-overdue
// warning for this department, re-run the existing shared bell/banner
// refresh. No rendering logic here — refreshAnnouncementSurfaces()
// (in main.js) owns the dropdown and dashboard banner entirely, fetching
// authoritative per-user data from GET /api/notifications/feed.
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Echo === 'undefined' || !window.Echo) {
            return;
        }

        var deptId = window.DTS_AUTH_CONTEXT ? window.DTS_AUTH_CONTEXT.departmentId : null;
        if (!deptId) {
            return;
        }

        try {
            window.Echo.private('department.' + deptId).listen('NearOverdueWarningCreated', function () {
                // Nudge-then-refetch: the socket payload carries no renderable
                // data, so refresh both warning surfaces (dropdown and
                // dashboard banner) from the authoritative feed endpoint
                // with a single shared fetch.
                if (typeof refreshAnnouncementSurfaces === 'function') {
                    refreshAnnouncementSurfaces();
                } else if (typeof backfillDropdown === 'function') {
                    backfillDropdown();
                }
            });
        } catch (e) {
            // A dead socket must never break the page; the next page load
            // or feed fetch remains the fallback for users without WebSocket.
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[realtime-near-overdue] listener setup failed:', e);
            }
        }
    });
})();
