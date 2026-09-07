(function () {
    'use strict';

    window.renderTimeline = function (containerId, events, options) {
        options = options || {};
        var container = document.getElementById(containerId);
        if (!container) return;

        var reverseOrder = options.reverseOrder !== false;
        var list = events || [];

        if (reverseOrder) {
            list = list.slice().reverse();
        }

        container.innerHTML = '';

        if (list.length === 0) {
            container.innerHTML = '<div class="text-center text-muted text-xs py-3"><i class="bi bi-inbox"></i> No transactional logging history logs discovered.</div>';
            return;
        }

        list.forEach(function (ev) {
            var processingHtml = '';

            if (ev.processing_time && ev.event_type !== 'issue') {
                var timeClass = ev.is_sla_breached ? 'text-danger fw-bold' : 'text-muted opacity-75 fw-semibold';
                processingHtml = '<span> · <span class="' + timeClass + '">Processing Time: ' + escapeHtml(ev.processing_time) + '</span>';
                if (ev.is_sla_breached) {
                    processingHtml += '<span class="badge bg-danger text-white ms-1 align-middle" style="font-size: 0.75rem;">OVERDUE AT STATION</span>';
                }
                processingHtml += '</span>';
            }

            var card = document.createElement('div');
            card.className = 'timeline-item border-start ps-3 pb-3 position-relative';
            card.innerHTML = ''
                + '<span class="position-absolute start-0 top-0 translate-middle-x badge rounded-circle bg-primary p-1" style="margin-left:-1px; margin-top:4px;"><span class="visually-hidden">.</span></span>'
                + '<div class="text-xxs text-muted font-mono tabular-nums">' + escapeHtml(ev.formatted_date || ev.created_at) + processingHtml + '</div>'
                + '<div class="text-xs font-semibold text-dark mt-0.5">' + escapeHtml(ev.event_label) + ' - <span class="text-primary font-normal">' + escapeHtml(ev.execution_department) + '</span></div>'
                + '<p class="text-muted text-xxs mb-0 mt-0.5 bg-light p-1 rounded border">Note: ' + escapeHtml(ev.note || 'No transaction notes added.') + ' <br><span class="text-dark font-medium">By: ' + escapeHtml(ev.processed_by_user) + '</span></p>';

            container.appendChild(card);
        });
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})();
