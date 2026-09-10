// Live Inbox nudge: when any route change affects this department's queue,
// re-run the existing inbox table fetch. No rendering logic here —
// fetchInboxRecords() (exposed by inbox.js) owns that entirely.
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Echo === 'undefined' || !window.Echo) {
            return;
        }
        if (typeof window.fetchInboxRecords !== 'function') {
            return;
        }

        var deptId = window.DTS_AUTH_CONTEXT ? window.DTS_AUTH_CONTEXT.departmentId : null;
        if (!deptId) {
            return;
        }

        try {
            window.Echo.private('department.' + deptId).listen('DocumentRouteUpdated', function () {
                if (typeof window.fetchInboxRecords === 'function') {
                    window.fetchInboxRecords();
                }
            });
        } catch (e) {
            // A dead socket must never break the page; the manual
            // refresh-inbox button remains the fallback.
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[realtime-inbox] listener setup failed:', e);
            }
        }
    });
})();
