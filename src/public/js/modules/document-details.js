(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var docIdEl = document.getElementById('docId');
        if (!docIdEl) return;

        var docNumber = docIdEl.textContent.trim();
        if (!docNumber) return;

        var lookupUrl = '/scan/lookup?document_number=' + encodeURIComponent(docNumber);

        fetch(lookupUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success && typeof window.renderTimeline === 'function') {
                window.renderTimeline('auditTrail', data.events, { reverseOrder: true });
            }
        })
        .catch(function (err) {
            console.error('[document-details.js] Failed to load timeline:', err);
        });
    });
})();
