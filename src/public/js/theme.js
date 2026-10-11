/**
 * Theme State - Document Tracking System (Batch 0 dark-mode foundation).
 *
 * Owns the `dts_theme` choice ("light" | "dark", localStorage key
 * `dts_theme`; anything else means light; default light; no OS following).
 * Vanilla JS, no dependencies. Must not touch page content, charts, or any
 * other feature: it only flips `data-bs-theme` on <html>, persists the
 * choice, updates the topbar toggle, and announces changes.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'dts_theme';
    var PREVIEW_KEY = 'dts_theme_preview';

    // Batch 1 developer preview gate (remove in the final batch with the
    // pre-paint and layout gates): dark mode is unreachable unless
    // localStorage dts_theme_preview === '1'. Without the flag, any stored
    // dts_theme value is ignored and the toggle stays hidden, so a stored
    // "dark" can never trap a user without a toggle.
    function previewEnabled() {
        try {
            return window.localStorage.getItem(PREVIEW_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function storedTheme() {
        if (!previewEnabled()) {
            return 'light';
        }
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
        // default light render) already set; no event on load. The toggle
        // stays hidden unless the Batch 1 preview gate is enabled.
        var toggle = document.getElementById('themeToggle');
        if (toggle && previewEnabled()) {
            toggle.hidden = false;
        }
        syncToggle(storedTheme());

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
