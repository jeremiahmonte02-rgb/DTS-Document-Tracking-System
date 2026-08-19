/**
 * Global Time Formatting Utility - Document Tracking System
 * Single source of truth for converting raw hour metrics into
 * human-readable durations across all dashboards.
 */
window.FormatUtils = (function () {
    'use strict';

    /**
     * Convert a raw hour value into a human-readable duration string.
     *
     * Rules:
     *  - 0           -> "0 mins"
     *  - < 1 hour    -> minutes (e.g. 0.5 -> "30 mins")
     *  - 1 - 23.9    -> hours with one decimal (e.g. 5 -> "5.0 hrs")
     *  - >= 24 hours -> days + hours (e.g. 329.4 -> "13d 17.4h");
     *                   omits the hours part when the remainder is zero
     *
     * @param {number|string} hours - Raw hour metric (e.g. 329.4, 0.5, 12)
     * @returns {string} Formatted duration string, or '--' for invalid input
     */
    function formatHoursToDays(hours) {
        if (hours === null || hours === undefined || hours === '') {
            return '--';
        }

        const value = Number(hours);

        if (Number.isNaN(value) || value < 0) {
            return '--';
        }

        if (value === 0) {
            return '0 mins';
        }

        if (value < 1) {
            return Math.round(value * 60) + ' mins';
        }

        if (value < 24) {
            return value.toFixed(1) + ' hrs';
        }

        const days = Math.floor(value / 24);
        const remainingHours = value - days * 24;

        if (remainingHours === 0) {
            return days + 'd';
        }

        const hoursPart = Number.isInteger(remainingHours)
            ? String(remainingHours)
            : remainingHours.toFixed(1);

        return days + 'd ' + hoursPart + 'h';
    }

    /**
     * Re-run the auto-format pass over every element tagged .js-format-time.
     * Safe to call repeatedly (e.g. after AJAX partial refreshes).
     */
    function init() {
        document.querySelectorAll('.js-format-time[data-hours]').forEach(function (el) {
            el.textContent = formatHoursToDays(el.getAttribute('data-hours'));
        });
    }

    return { formatHoursToDays: formatHoursToDays, init: init };
})();

// Auto-format on initial load and on bfcache restores (back/forward,
// which skip DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function () {
    window.FormatUtils.init();
});
window.addEventListener('pageshow', function () {
    window.FormatUtils.init();
});
