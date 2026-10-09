{{--
    Shared date-range filter (presets + custom From/To + Clear).

    Markup base: the Inbox/Outbox filter controls (form-select + form-control,
    data-filter attributes); visual treatment follows the app's .filter-bar
    language (whisper borders, 0.375rem radius, 0.8125rem type). Reused
    identically by Inbox, Outbox, Audit Documents, Profile Uploads, and
    Reported Issues — do not duplicate this markup per page; include this
    partial instead.

    Optional variables:
        $dateFrom, $dateTo — 'YYYY-MM-DD' strings or null, for
            server-rendered pages that repopulate from the request
            (e.g. Reported Issues). AJAX pages leave them null; the JS
            module owns all state there.
        $showClear — show this component's own Clear button. Pages that
            already have a page-level "Clear filters" action pass false so
            exactly one Clear remains (their handler already resets this
            component via DateRangeFilter.reset()). Pages without one
            (Profile, Reported Issues) leave the default true.

    Structural hooks (data-date-range-filter, data-initial-from/to,
    data-filter="date-preset|date-from|date-to|date-clear",
    data-date-range-custom) are read by
    public/js/modules/date-range-filter.js — do not rename them; only
    classes and layout may change here.

    Wired up via window.DateRangeFilter.init(). Each page passes its own
    table-refresh callback; this partial never fetches anything itself.
--}}
@php
    $dateFrom = $dateFrom ?? null;
    $dateTo = $dateTo ?? null;
    $showClear = $showClear ?? true;
@endphp
<div class="date-range-filter" data-date-range-filter
     data-initial-from="{{ $dateFrom ?? '' }}" data-initial-to="{{ $dateTo ?? '' }}">
    <select class="form-select drf-preset" data-filter="date-preset" aria-label="Date range preset">
        <option value="">All dates</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="last7">Last 7 Days</option>
        <option value="last30">Last 30 Days</option>
        <option value="this_month">This Month</option>
        <option value="last_month">Last Month</option>
        <option value="custom">Custom Range</option>
    </select>
    <span class="drf-range d-none" data-date-range-custom>
        <input type="date" class="form-control drf-date" data-filter="date-from" aria-label="From date">
        <span class="drf-to" aria-hidden="true">to</span>
        <input type="date" class="form-control drf-date" data-filter="date-to" aria-label="To date">
    </span>
    @if($showClear)
    <button type="button" class="filter-btn-clear drf-clear" data-filter="date-clear">
        <i class="bi bi-x-lg"></i> Clear
    </button>
    @endif
</div>
