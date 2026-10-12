/**
 * Theme State - Document Tracking System.
 *
 * Owns the `dts_theme` choice ("light" | "dark", localStorage key
 * `dts_theme`; anything else means light; default light; no OS following).
 * Vanilla JS, no dependencies. It flips `data-bs-theme` on <html>, persists
 * the choice, updates the topbar toggle, re-applies on bfcache restores
 * (see the pre-paint script in layouts/app.blade.php), and dispatches
 * `dts:themechange` (charts repaint on it). Print forces light (see below).
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'dts_theme';

    function storedTheme() {
        try {
            return window.localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'light';
        } catch (e) {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        }
    }

    function syncToggle(theme) {
        var toggle = document.getElementById('themeToggle');
        if (!toggle) {
            return;
        }
        var isDark = theme === 'dark';
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        var icon = document.getElementById('themeToggleIcon');
        if (icon) {
            icon.classList.remove(isDark ? 'bi-moon-stars' : 'bi-sun');
            icon.classList.add(isDark ? 'bi-sun' : 'bi-moon-stars');
        }
    }

    function applyTheme(theme, persist) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        if (persist) {
            try {
                window.localStorage.setItem(STORAGE_KEY, theme);
            } catch (e) {
                /* storage unavailable: theme still applies for this page view */
            }
        }
        syncToggle(theme);
        try {
            document.dispatchEvent(new CustomEvent('dts:themechange', { detail: { theme: theme } }));
        } catch (e) {
            /* legacy engine without CustomEvent: theme is still applied */
        }
    }

    function init() {
        // Align the toggle with whatever the pre-paint script (or the
        // default light render) already set; no event on load.
        syncToggle(storedTheme());

        var toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                applyTheme(storedTheme() === 'dark' ? 'light' : 'dark', true);
            });
        }

        // Printing and Export as Snapshot stay light: force light for the
        // print job, then restore. Separate listener from main.js on purpose
        // (that file must not be touched); matchMedia covers engines that
        // never fire afterprint. Restoration is idempotent. Both transitions
        // dispatch dts:themechange with the effective theme so charts repaint
        // synchronously (their update('none') is synchronous) for the print
        // and back — Batch 1 chart bridge.
        var printActive = false;
        function dispatchTheme(theme) {
            try {
                document.dispatchEvent(new CustomEvent('dts:themechange', { detail: { theme: theme } }));
            } catch (e) {
                /* legacy engine without CustomEvent: theme is still applied */
            }
        }
        function forceLightForPrint() {
            if (printActive) {
                return;
            }
            printActive = true;
            document.documentElement.setAttribute('data-bs-theme', 'light');
            dispatchTheme('light');
        }
        function restoreAfterPrint() {
            if (!printActive) {
                return;
            }
            printActive = false;
            var theme = storedTheme();
            document.documentElement.setAttribute('data-bs-theme', theme);
            dispatchTheme(theme);
        }
        window.addEventListener('beforeprint', forceLightForPrint);
        window.addEventListener('afterprint', restoreAfterPrint);
        try {
            if (window.matchMedia) {
                var printQuery = window.matchMedia('print');
                if (printQuery && typeof printQuery.addEventListener === 'function') {
                    printQuery.addEventListener('change', function (event) {
                        if (event.matches) {
                            forceLightForPrint();
                        } else {
                            restoreAfterPrint();
                        }
                    });
                }
            }
        } catch (e) {
            /* media queries unavailable: beforeprint/afterprint still guard */
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
