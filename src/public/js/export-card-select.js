/**
 * Export Card Selection - Document Tracking System
 *
 * Lets the user pick which analytics cards are included in the Excel export
 * by clicking the cards themselves. Vanilla JS, no dependencies.
 *
 * Behavior:
 * - Click (or Enter/Space on a focused card) toggles .is-selected and
 *   aria-pressed on elements carrying [data-card-key].
 * - The "Export as Excel" link (#exportExcelLink) is rebuilt from its
 *   data-base-href on every toggle with the URL API: old cards[] params are
 *   removed and one cards[]=<key> per selected card is appended in DOM order
 *   (dashboard order, never click order). The month param is preserved.
 * - A visually hidden live region announces each change (no visible count).
 * - No state survives reloads; back/forward restores via pageshow reset.
 * - Inert when the export link is absent (e.g. standard dashboard without
 *   the users.manage-gated Export dropdown): cards stay exactly as today.
 */
(function () {
    'use strict';

    function selectedCards(cards) {
        return cards.filter(function (card) {
            return card.classList.contains('is-selected');
        });
    }

    function cardName(card) {
        var label = card.getAttribute('aria-label') || '';
        return label.replace(/^Include\s+/, '').replace(/\s+in export\s*$/, '') || (card.getAttribute('data-card-key') || 'card');
    }

    function rebuildHref(link, baseHref, cards) {
        var url;
        try {
            url = new URL(baseHref, window.location.origin);
        } catch (e) {
            return;
        }
        url.searchParams.delete('cards[]');
        selectedCards(cards).forEach(function (card) {
            url.searchParams.append('cards[]', card.getAttribute('data-card-key'));
        });
        link.setAttribute('href', url.pathname + url.search);
    }

    function announce(liveRegion, cards) {
        if (!liveRegion) {
            return;
        }
        var count = selectedCards(cards).length;
        liveRegion.textContent = count === 0
            ? 'No cards selected. The export will include all cards.'
            : count + (count === 1 ? ' card selected for export.' : ' cards selected for export.');
    }

    function reset(link, baseHref, cards, liveRegion) {
        cards.forEach(function (card) {
            card.classList.remove('is-selected');
            card.setAttribute('aria-pressed', 'false');
        });
        link.setAttribute('href', baseHref);
        if (liveRegion) {
            liveRegion.textContent = '';
        }
    }

    function init() {
        var link = document.getElementById('exportExcelLink');
        if (!link) {
            return;
        }
        var baseHref = link.getAttribute('data-base-href') || link.getAttribute('href') || '';
        if (!baseHref) {
            return;
        }
        var cards = Array.prototype.slice.call(document.querySelectorAll('[data-card-key]'));
        if (cards.length === 0) {
            return;
        }
        var liveRegion = document.getElementById('exportCardLiveRegion');

        function toggle(card) {
            var selected = card.classList.toggle('is-selected');
            card.setAttribute('aria-pressed', selected ? 'true' : 'false');
            rebuildHref(link, baseHref, cards);
            if (liveRegion) {
                liveRegion.textContent = cardName(card) + (selected ? ' selected for export. ' : ' removed from export. ')
                    + selectedCards(cards).length + ' selected in total. None selected exports all.';
            }
        }

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                toggle(card);
            });
            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    if (event.key === ' ') {
                        event.preventDefault();
                    }
                    toggle(card);
                }
            });
        });

        // Back/forward cache restores can replay stale highlights while the
        // link keeps the old href: reset so the two never disagree.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                reset(link, baseHref, cards, liveRegion);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
