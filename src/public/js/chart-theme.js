/**
 * Chart Theme Bridge - Document Tracking System (Batch 1 dark mode).
 *
 * Resolves Chart.js chrome colors (tick/legend text, grid lines, doughnut
 * borders) from the canvas element's own computed style, so a chart inside
 * a pinned light region reads light values while one on a converted page
 * reads dark values. Status/series hues are intentionally identical in both
 * themes: every slice/bar color passes 3:1 against the dark card surface
 * (#1A2130), so no brightening is applied (see contrast table in docs).
 *
 * Usage per chart module: keep a reference to the Chart instance, then
 *   window.ChartTheme.register(chart, canvas, recolor);
 * where recolor(palette, isDark) mutates that chart's options back to the
 * light spec when isDark is false, or applies the palette when true.
 * This module redraws every registered chart synchronously on
 * `dts:themechange` (toggle and print guard both dispatch it).
 *
 * Vanilla JS, no dependencies. Depends only on window.Chart existing at
 * chart-creation time (pages already load Chart.js before their modules).
 */
(function () {
    'use strict';

    var registry = [];

    function cssVar(el, name, fallback) {
        var value = '';
        try {
            value = window.getComputedStyle(el).getPropertyValue(name);
        } catch (e) {
            value = '';
        }
        value = (value || '').trim();
        return value || fallback;
    }

    /**
     * Theme palette for the given canvas, read live so pinned regions and
     * converted pages each resolve their own effective theme.
     */
    function paletteFor(canvas) {
        return {
            text: cssVar(canvas, '--bs-secondary-color', '#6c757d'),
            grid: cssVar(canvas, '--bs-border-color', 'rgba(0, 0, 0, 0.1)'),
            surface: cssVar(canvas, '--bs-body-bg', '#ffffff'),
            body: cssVar(canvas, '--bs-body-color', '#212529')
        };
    }

    function isDarkTheme() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function register(chart, canvas, recolor) {
        if (!chart || !canvas || typeof recolor !== 'function') {
            return chart;
        }
        registry.push({ chart: chart, canvas: canvas, recolor: recolor });
        // Paint correctly for the theme active at creation (e.g. a dark
        // reload renders dark immediately instead of flashing light).
        try {
            recolor(paletteFor(canvas), isDarkTheme());
        } catch (e) {
            /* keep the as-created spec on failure */
        }
        return chart;
    }

    function refreshAll() {
        var dark = isDarkTheme();
        registry.forEach(function (entry) {
            try {
                entry.recolor(paletteFor(entry.canvas), dark);
                entry.chart.update('none');
            } catch (e) {
                /* a dead chart must never break the page or other charts */
            }
        });
    }

    document.addEventListener('dts:themechange', function () {
        refreshAll();
    });

    window.ChartTheme = {
        paletteFor: paletteFor,
        register: register,
        refreshAll: refreshAll
    };
})();
