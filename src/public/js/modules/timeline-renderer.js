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

        container.appendChild(buildHero(list[0]));

        list.forEach(function (ev, idx) {
            container.appendChild(buildRow(ev, idx === 0));
        });
    };

    // Hero panel summarizing the single most recent existing event.
    // Uses only fields already present on the event object.
    function buildHero(ev) {
        var hero = document.createElement('div');
        hero.className = 'at-hero';
        hero.setAttribute('aria-label', 'Latest activity');

        var showTime = ev.processing_time && ev.event_type !== 'issue';
        var timeChip = showTime
            ? '<span class="at-chip at-chip-light">' + escapeHtml(ev.processing_time) + '</span>'
            : '<span class="at-herodate">' + escapeHtml(ev.formatted_date || ev.created_at) + '</span>';

        hero.innerHTML = ''
            + '<div>'
            + '<div class="at-hero-label">Latest activity</div>'
            + '<div class="at-hero-title">' + escapeHtml(ev.event_label) + '</div>'
            + '<div class="at-hero-sub">' + escapeHtml(ev.execution_department) + ' &middot; By ' + escapeHtml(ev.processed_by_user) + '</div>'
            + '</div>'
            + '<div class="at-hero-time">' + timeChip + '</div>';

        return hero;
    }

    // One row per existing event. Same content as before, restructured
    // into grid children: main content left, processing chip + overdue
    // flag right. The "Issue Reported" processing-time exclusion is
    // unchanged — only its position moves.
    function buildRow(ev, isNewest) {
        var showTime = ev.processing_time && ev.event_type !== 'issue';

        var dotClass = 'at-dot';
        if (ev.is_sla_breached) {
            dotClass += ' bad';
        } else if (isNewest) {
            dotClass += ' now';
        } else {
            dotClass += ' ok';
        }

        var rightHtml = '';
        if (showTime) {
            rightHtml += '<span class="at-chip"><span class="at-chip-label">Processing time</span>' + escapeHtml(ev.processing_time) + '</span>';
        }
        if (ev.is_sla_breached) {
            rightHtml += '<span class="badge bg-danger text-white align-middle" style="font-size: 0.75rem;">OVERDUE AT STATION</span>';
        }

        var card = document.createElement('div');
        card.className = 'timeline-item position-relative at-row' + (ev.is_sla_breached ? ' at-flagged' : '');
        card.innerHTML = ''
            + '<span class="' + dotClass + '" aria-hidden="true"></span>'
            + '<div class="at-grid">'
            + '<div>'
            + '<div class="text-xxs text-muted font-mono tabular-nums">' + escapeHtml(ev.formatted_date || ev.created_at) + '</div>'
            + '<div class="text-xs font-semibold text-dark mt-0.5">' + escapeHtml(ev.event_label) + ' - <span class="text-primary font-normal">' + escapeHtml(ev.execution_department) + '</span></div>'
            + '<p class="text-muted text-xxs mb-0 mt-0.5 bg-light p-1 rounded border">Note: ' + escapeHtml(ev.note || 'No transaction notes added.') + ' <br><span class="text-dark font-medium">By: ' + escapeHtml(ev.processed_by_user) + '</span></p>'
            + '</div>'
            + '<div class="at-side">' + rightHtml + '</div>'
            + '</div>';

        return card;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
})();
