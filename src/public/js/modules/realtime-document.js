// Live document-details nudge: when this document's route state changes,
// re-fetch its steps from the policy-gated routes endpoint and re-render
// the timeline with the shared RouteStatus module, then re-evaluate the
// "Mark as Received" button for the current user's department.
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var docIdEl = document.getElementById('docId');
        if (!docIdEl) {
            return;
        }
        var docNumber = docIdEl.textContent.trim();
        var docId = docIdEl.getAttribute('data-document-id');
        if (!docNumber || !docId) {
            return;
        }
        if (typeof window.Echo === 'undefined' || !window.Echo) {
            return;
        }
        if (typeof window.RouteStatus === 'undefined') {
            return;
        }

        var deptId = window.DTS_AUTH_CONTEXT ? window.DTS_AUTH_CONTEXT.departmentId : null;

        function updateReceiveButton(routes) {
            var btn = document.getElementById('markAsReceivedBtn');
            var isNext = window.RouteStatus.isUserNext(routes, deptId);
            if (btn) {
                btn.classList.toggle('d-none', !isNext);
                if (isNext) {
                    btn.disabled = false;
                }
                return;
            }
            // No server-rendered button exists (user was not next at page
            // load) but they are now: only a full server render can provide
            // the button with its bound handler, so reload once. After the
            // reload the button exists server-side and no further reload
            // can trigger.
            if (isNext) {
                window.location.reload();
            }
        }

        function refreshTimeline() {
            fetch('/api/documents/' + encodeURIComponent(docNumber) + '/routes', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('Routes fetch failed ' + r.status);
                }
                return r.json();
            })
            .then(function (data) {
                if (!data.success || !Array.isArray(data.routes)) {
                    return;
                }
                var container = document.getElementById('routeTimelineSteps');
                if (container) {
                    window.RouteStatus.renderInto(container, data.routes);
                }
                updateReceiveButton(data.routes);
            })
            .catch(function (e) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('[realtime-document] timeline refresh failed:', e);
                }
            });
        }

        try {
            window.Echo.private('document.' + docId).listen('DocumentRouteUpdated', function () {
                refreshTimeline();
                // Also refresh the audit-trail event list via the exact same
                // code path the local issue-report flow already uses. This
                // additionally re-confirms route badges and the header doc
                // status badge from server truth — all idempotent, so call
                // order versus refreshTimeline() cannot diverge.
                if (typeof refreshDocumentState === 'function') {
                    refreshDocumentState(docNumber);
                }
            });
        } catch (e) {
            // A dead socket must never break the page; the server-rendered
            // timeline remains the fallback.
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[realtime-document] listener setup failed:', e);
            }
        }
    });
})();
