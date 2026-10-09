/**
 * Reported Issues date filtering.
 *
 * Unlike the AJAX tables, this page is server-rendered with paginator links,
 * so there is no table-refresh function to call. The shared date-range
 * module owns all filter state; this page's callback simply navigates to
 * the same URL with the range as query params (the same pattern the
 * Activity Log page uses), letting the backend apply it.
 */

document.addEventListener('DOMContentLoaded', function () {
    if (!window.DateRangeFilter) return;

    var baseUrl = window.location.pathname;

    window.DateRangeFilter.init('[data-date-range-filter]', function (state) {
        var params = new URLSearchParams();
        if (state) {
            if (state.from) params.set('date_from', state.from);
            if (state.to) params.set('date_to', state.to);
        }
        var qs = params.toString();
        window.location.href = baseUrl + (qs ? '?' + qs : '');
    });
});
