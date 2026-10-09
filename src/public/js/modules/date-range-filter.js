/**
 * Shared date-range filter module (presets + custom From/To + Clear).
 *
 * Single implementation reused by every AJAX-paginated table with a date
 * filter (Inbox, Outbox, Audit Documents, Profile Uploads) and by the
 * server-rendered Reported Issues page. Do not duplicate this logic per
 * page — call window.DateRangeFilter.init() with the page's own refresh
 * callback instead. This module never fetches anything itself.
 *
 * State contract with backends: getState() returns null (no filter — the
 * page must show everything) or { from, to } with 'YYYY-MM-DD' strings
 * where either side may be '' for an open-ended range. Pages map that to
 * date_from/date_to query params.
 */

(function () {
    'use strict';

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    // Local-time YYYY-MM-DD (never toISOString — that shifts by UTC offset).
    function fmt(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    function startOfDay(d) {
        var c = new Date(d.getTime());
        c.setHours(0, 0, 0, 0);
        return c;
    }

    function endOfMonth(d) {
        return new Date(d.getFullYear(), d.getMonth() + 1, 0);
    }

    // Resolve a preset name to { from, to }. Returns null for '' (all dates).
    function presetRange(name) {
        var now = new Date();
        var today = startOfDay(now);
        var oneDay = 86400000;
        switch (name) {
            case 'today':
                return { from: fmt(today), to: fmt(today) };
            case 'yesterday': {
                var y = new Date(today.getTime() - oneDay);
                return { from: fmt(y), to: fmt(y) };
            }
            case 'last7':
                return { from: fmt(new Date(today.getTime() - 6 * oneDay)), to: fmt(today) };
            case 'last30':
                return { from: fmt(new Date(today.getTime() - 29 * oneDay)), to: fmt(today) };
            case 'this_month':
                return { from: fmt(new Date(today.getFullYear(), today.getMonth(), 1)), to: fmt(today) };
            case 'last_month': {
                var first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                return { from: fmt(first), to: fmt(endOfMonth(first)) };
            }
            default:
                return null;
        }
    }

    function findParts(root) {
        if (!root || !root.querySelector) return null;
        return {
            root: root,
            preset: root.querySelector('[data-filter="date-preset"]'),
            from: root.querySelector('[data-filter="date-from"]'),
            to: root.querySelector('[data-filter="date-to"]'),
            clear: root.querySelector('[data-filter="date-clear"]'),
            customs: root.querySelectorAll('[data-date-range-custom]')
        };
    }

    function resolveContainer(container) {
        if (!container) return null;
        if (typeof container === 'string') {
            return document.querySelector(container);
        }
        return container;
    }

    function setCustomVisible(parts, visible) {
        if (!parts || !parts.customs) return;
        Array.prototype.forEach.call(parts.customs, function (el) {
            el.classList.toggle('d-none', !visible);
        });
    }

    // Current state, or null when no date filter is active (show everything).
    function getState(container) {
        var parts = findParts(resolveContainer(container));
        if (!parts || !parts.preset) return null;
        var preset = parts.preset.value;
        if (preset === '' ) return null;
        if (preset === 'custom') {
            var from = parts.from ? parts.from.value : '';
            var to = parts.to ? parts.to.value : '';
            if (!from && !to) return null;
            return { from: from, to: to };
        }
        return presetRange(preset);
    }

    function reset(container) {
        var parts = findParts(resolveContainer(container));
        if (!parts) return;
        if (parts.preset) parts.preset.value = '';
        if (parts.from) parts.from.value = '';
        if (parts.to) parts.to.value = '';
        setCustomVisible(parts, false);
    }

    // Apply server-provided initial values (server-rendered pages only):
    // a non-empty initial range selects the Custom preset.
    function applyInitials(parts) {
        if (!parts || !parts.root) return;
        var initFrom = parts.root.getAttribute('data-initial-from') || '';
        var initTo = parts.root.getAttribute('data-initial-to') || '';
        if (!initFrom && !initTo) return;
        if (parts.preset) parts.preset.value = 'custom';
        if (parts.from) parts.from.value = initFrom;
        if (parts.to) parts.to.value = initTo;
        setCustomVisible(parts, true);
    }

    // Wire one filter instance. onChange(state) fires on every user change
    // with the new state (null = no filter); the page owns what happens next
    // (re-fetch its table, or navigate for server-rendered pages).
    function init(container, onChange) {
        var parts = findParts(resolveContainer(container));
        if (!parts || !parts.preset) return null;

        var notify = function () {
            if (typeof onChange === 'function') {
                onChange(getState(parts.root));
            }
        };

        applyInitials(parts);

        parts.preset.addEventListener('change', function () {
            var value = parts.preset.value;
            if (value === 'custom') {
                setCustomVisible(parts, true);
                notify();
                return;
            }
            setCustomVisible(parts, false);
            if (parts.from) parts.from.value = '';
            if (parts.to) parts.to.value = '';
            notify();
        });

        var onCustomInput = function () {
            if (parts.preset && parts.preset.value !== 'custom') return;
            notify();
        };
        if (parts.from) parts.from.addEventListener('change', onCustomInput);
        if (parts.to) parts.to.addEventListener('change', onCustomInput);

        if (parts.clear) {
            parts.clear.addEventListener('click', function () {
                reset(parts.root);
                notify();
            });
        }

        return {
            getState: function () { return getState(parts.root); },
            reset: function () { reset(parts.root); }
        };
    }

    window.DateRangeFilter = {
        init: init,
        getState: getState,
        reset: reset,
        presetRange: presetRange
    };
})();
