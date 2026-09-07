// Document Tracking System - Main JavaScript
// Shared utilities only — mock data and simulation functions removed (Phase 4 S1).

let isBackfilling = false;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    setupNotificationMarkRead();
    setupAnnouncementMarkRead();
    setupDismissAll();
    highlightActiveNav();
    setupSidebarCloseOnOutsideClick();
    setupSidebarCloseOnNavClick();
    restoreSidebarState();
    setupSidebarCollapse();

    // Mobile hamburger button
    const mobileToggle = document.getElementById('mobileMenuToggle');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.toggle('show');
        });
    }

    // Global navigation interceptor to mitigate multi-click latency drops
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (link &&
            link.hostname === window.location.hostname &&
            !link.hasAttribute('data-no-spinner') &&
            link.getAttribute('href') !== '#' &&
            !link.getAttribute('href').startsWith('#')
        ) {
            if (typeof showSpinner === 'function') {
                showSpinner();
            }
        }
    });
});

// Resolve the CSRF token from the layout meta tag (fallback: hidden form input)
function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');

    const input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
}

function ensureDropdownEmptyState() {
    var menu = document.querySelector('.notification-menu');
    if (!menu) return;
    var hasItems = menu.querySelectorAll('[data-notification-id], [data-announcement-id]').length > 0;
    if (!hasItems) {
        menu.querySelectorAll('hr.dropdown-divider').forEach(function(hr) {
            var li = hr.closest('li');
            if (li) li.remove();
        });
        var header = menu.querySelector('.dropdown-header');
        if (header) {
            var headerLi = header.closest('li');
            if (headerLi) {
                var prev = headerLi.previousElementSibling;
                if (prev && prev.querySelector('hr.dropdown-divider')) prev.remove();
                headerLi.remove();
            }
        }
        if (!menu.querySelector('.empty-state')) {
            // Keep in sync with the empty-state <li> in resources/views/layouts/app.blade.php
            var emptyLi = document.createElement('li');
            emptyLi.className = 'empty-state';
            emptyLi.innerHTML = '<span class="dropdown-item-text text-center py-4 text-muted"><i class="bi bi-bell-slash d-block fs-4 mb-2"></i>No new notifications<small class="d-block text-muted mt-1" style="font-size:0.75rem;">You\'re all caught up</small></span>';
            var footerEl = menu.querySelector('.dropdown-footer-sticky');
            if (footerEl) menu.insertBefore(emptyLi, footerEl);
            else menu.appendChild(emptyLi);
        }
    }
}

function cleanupSingleDivider(prev, next) {
    var isDivider = function(el) { return el && el.querySelector && el.querySelector('hr.dropdown-divider'); };
    if (prev && next && isDivider(prev) && isDivider(next)) {
        next.remove();
    } else if (prev && isDivider(prev) && (!next || next.classList.contains('dropdown-footer-sticky') || next.classList.contains('empty-state'))) {
        prev.remove();
    } else if (next && isDivider(next) && !prev) {
        next.remove();
    }
}

function updateBadgeFromCounts(total) {
    var badge = document.getElementById('notificationBadge');
    if (!badge) return;
    badge.textContent = total > 99 ? '99+' : String(total);
    badge.classList.toggle('d-none', total <= 0);
}

function createDropdownItem(item) {
    var li = document.createElement('li');
    var isNotification = item.type === 'notification';
    var href = isNotification && item.document_number ? '/document-details/' + encodeURIComponent(item.document_number) : '#';
    var iconClass = isNotification ? 'bi-file-earmark-text' : 'bi-megaphone-fill';
    var dataAttr = isNotification ? 'data-notification-id' : 'data-announcement-id';
    var extraClass = isNotification ? '' : ' announcement-item';
    li.innerHTML = '<a class="dropdown-item notification-item' + extraClass + '" href="' + href + '" ' + dataAttr + '="' + item.id + '" data-no-spinner="true">'
        + '<div class="d-flex gap-2">'
        + '<i class="bi ' + iconClass + ' text-muted mt-1" style="font-size:0.875rem;"></i>'
        + '<div class="flex-grow-1" style="min-width:0;">'
        + '<div class="notification-title fw-semibold">' + item.title + '</div>'
        + '<div class="notification-message small text-muted text-truncate">' + (item.message || '') + '</div>'
        + '<div class="notification-time small text-muted mt-1">' + (item.time_ago || '') + '</div>'
        + '</div></div></a>';
    return li;
}

function backfillDropdown() {
    if (isBackfilling) return;
    isBackfilling = true;

    fetch('/api/notifications/feed', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() }
    })
    .then(function(r) { if (!r.ok) throw new Error('Feed fetch failed ' + r.status); return r.json(); })
    .then(function(data) {
        updateBadgeFromCounts(data.totalUnread);
        var menu = document.querySelector('.notification-menu');
        if (!menu) return;
        var renderedIds = new Set();
        menu.querySelectorAll('[data-notification-id]').forEach(function(el) { renderedIds.add('notification-' + el.getAttribute('data-notification-id')); });
        menu.querySelectorAll('[data-announcement-id]').forEach(function(el) { renderedIds.add('announcement-' + el.getAttribute('data-announcement-id')); });
        var expected = Math.min(data.totalUnread, 10);
        var currentCount = renderedIds.size;
        if (currentCount >= expected) {
            if (expected === 0) ensureDropdownEmptyState();
            return;
        }
        var toAdd = [];
        (data.feed || []).forEach(function(item) {
            var key = item.type + '-' + item.id;
            if (!renderedIds.has(key) && toAdd.length < (expected - currentCount)) {
                toAdd.push(item);
            }
        });
        var footer = menu.querySelector('.dropdown-footer-sticky');
        if (footer && currentCount > 0 && toAdd.length > 0) {
            var preDivider = document.createElement('li');
            preDivider.innerHTML = '<hr class="dropdown-divider">';
            menu.insertBefore(preDivider, footer);
        }
        toAdd.forEach(function(item, idx) {
            var li = createDropdownItem(item);
            if (footer) {
                menu.insertBefore(li, footer);
                if (idx < toAdd.length - 1) {
                    var divider = document.createElement('li');
                    divider.innerHTML = '<hr class="dropdown-divider">';
                    menu.insertBefore(divider, footer);
                }
            } else {
                menu.appendChild(li);
                if (idx < toAdd.length - 1) {
                    var divider = document.createElement('li');
                    divider.innerHTML = '<hr class="dropdown-divider">';
                    menu.appendChild(divider);
                }
            }
            var el = li.querySelector('[data-notification-id], [data-announcement-id]');
            if (el) {
                el.addEventListener('click', function handler(e) {
                    // Re-attach per-item logic by re-dispatching to existing setup would be complex;
                    // simplest is to reload the page's handlers via re-binding, but we can just
                    // attach a one-off listener that mirrors the original behavior:
                    // For brevity, rely on the fact that the new item will be handled on next page load;
                    // immediate click on backfilled item will be handled by the next full reload.
                    // To make it work immediately, we attach a minimal handler:
                    var id = el.getAttribute('data-notification-id') || el.getAttribute('data-announcement-id');
                    var isNotif = !!el.getAttribute('data-notification-id');
                    var url = isNotif ? '/notifications/' + encodeURIComponent(id) + '/read' : '/announcements/' + encodeURIComponent(id) + '/read';
                    e.preventDefault();
                    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }, keepalive: true })
                        .then(function(r){ if(!r.ok) throw new Error(); return r.json(); })
                        .then(function(d){ if(!d.success) throw new Error(); var l=el.closest('li'); var p=l?l.previousElementSibling:null; var n=l?l.nextElementSibling:null; if(l) l.remove(); if(p && n && p.querySelector('hr') && n.querySelector('hr')) n.remove(); if(d.totalUnread!==undefined) updateBadgeFromCounts(d.totalUnread); })
                        .catch(function(){});
                });
            }
        });
        if (expected === 0) ensureDropdownEmptyState();
    })
    .catch(function(e) { console.warn('Backfill failed:', e); })
    .finally(function() { isBackfilling = false; });
}

// Mark a notification as read in the background, decrement the bell badge,
// then navigate the user to the referenced document.
function setupNotificationMarkRead() {
    document.querySelectorAll('.notification-item[data-notification-id]').forEach(function(item) {
        item.addEventListener('click', function(event) {
            event.preventDefault();

            const notificationId = item.getAttribute('data-notification-id');
            const targetUrl = item.getAttribute('href');
            const hasRealTarget = targetUrl && targetUrl !== '#' && targetUrl !== window.location.href;

            if (notificationId) {
                if (hasRealTarget) {
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        const current = parseInt(badge.textContent || '0', 10);
                        const next = Math.max(0, current - 1);
                        badge.textContent = next > 99 ? '99+' : String(next);
                        badge.classList.toggle('d-none', next <= 0);
                    }
                    fetch('/notifications/' + encodeURIComponent(notificationId) + '/read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(),
                            'Accept': 'application/json'
                        },
                        keepalive: true
                    })
                    .then(function(response) {
                        if (!response.ok) throw new Error('Notification mark-as-read failed with status ' + response.status);
                        return response.json();
                    })
                    .catch(function(error) {
                        console.warn('Notification mark-as-read failed:', error);
                    })
                    .finally(function() {
                        if (typeof hideSpinner === 'function') hideSpinner();
                    });
                    window.location.href = targetUrl;
                    return;
                }

                fetch('/notifications/' + encodeURIComponent(notificationId) + '/read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json'
                    },
                    keepalive: true
                })
                .then(function(response) {
                    if (!response.ok) throw new Error('Notification mark-as-read failed with status ' + response.status);
                    return response.json();
                })
                .then(function(data) {
                    if (!data.success) throw new Error('Notification mark-as-read returned success=false');
                    var li = item.closest('li');
                    var prev = li ? li.previousElementSibling : null;
                    var next = li ? li.nextElementSibling : null;
                    if (li) li.remove();
                    cleanupSingleDivider(prev, next);
                    backfillDropdown();
                })
                .catch(function(error) {
                    console.error('Notification mark-as-read failed:', error);
                })
                .finally(function() {
                    if (typeof hideSpinner === 'function') hideSpinner();
                });
                return;
            }

            if (hasRealTarget) {
                window.location.href = targetUrl;
            } else if (targetUrl === window.location.href) {
                if (typeof hideSpinner === 'function') hideSpinner();
            }
        });
    });
}

// Mark an announcement as read/dismissed for the current user. Handles both
// the bell dropdown items and the dashboard banner close buttons.
function setupAnnouncementMarkRead() {
    document.querySelectorAll('[data-announcement-id]').forEach(function(el) {
        el.addEventListener('click', function(event) {
            const announcementId = el.getAttribute('data-announcement-id');
            if (!announcementId) return;

            if (el.classList.contains('btn-close') || el.classList.contains('announcement-item')) {
                event.preventDefault();
                if (el.classList.contains('btn-close')) {
                    event.stopPropagation();
                }
            }

            fetch('/announcements/' + encodeURIComponent(announcementId) + '/read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                keepalive: true
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Announcement mark-as-read failed with status ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error('Announcement mark-as-read returned success=false');
                }
                var isBanner = !!el.closest('.announcement-banner');
                var targetLi = el.closest('li');
                var prev = targetLi ? targetLi.previousElementSibling : null;
                var next = targetLi ? targetLi.nextElementSibling : null;

                document.querySelectorAll('[data-announcement-id="' + CSS.escape(announcementId) + '"]').forEach(function(otherEl) {
                    var otherLi = otherEl.closest('li');
                    var oPrev = otherLi ? otherLi.previousElementSibling : null;
                    var oNext = otherLi ? otherLi.nextElementSibling : null;
                    var otherBanner = otherEl.closest('.announcement-banner');
                    if (otherBanner) otherBanner.remove();
                    if (otherLi) otherLi.remove();
                    if (oPrev || oNext) cleanupSingleDivider(oPrev, oNext);
                });

                if (!isBanner) {
                    cleanupSingleDivider(prev, next);
                }

                var menu = document.querySelector('.notification-menu');
                if (menu) {
                    var bannerWrap = document.querySelector('.announcements-banner');
                    if (bannerWrap && bannerWrap.children.length === 0) bannerWrap.remove();
                }

                backfillDropdown();
            })
            .catch(function(error) {
                console.error('Announcement mark-as-read failed:', error);
            })
            .finally(function() {
                if (typeof hideSpinner === 'function') {
                    hideSpinner();
                }
            });
        });
    });
}

function setupDismissAll() {
    var btn = document.getElementById('dismissAllBtn');
    if (!btn) return;

    btn.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();

        if (btn.disabled) return;
        btn.disabled = true;

        var headers = {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json'
        };

        Promise.all([
            fetch('/notifications/read-all', { method: 'POST', headers: headers, keepalive: true }),
            fetch('/announcements/read-all', { method: 'POST', headers: headers, keepalive: true })
        ])
        .then(function(responses) {
            var notifRes = responses[0];
            var annRes = responses[1];
            if (!notifRes.ok) throw new Error('Dismiss all notifications failed with status ' + notifRes.status);
            if (!annRes.ok) throw new Error('Dismiss all announcements failed with status ' + annRes.status);
            return Promise.all([notifRes.json(), annRes.json()]);
        })
        .then(function(payloads) {
            if (!payloads[0].success) throw new Error(payloads[0].message || 'Notifications dismiss all returned success=false');
            if (!payloads[1].success) throw new Error(payloads[1].message || 'Announcements dismiss all returned success=false');

            document.querySelectorAll('.notification-menu [data-notification-id]').forEach(function(el) {
                var li = el.closest('li');
                if (li) li.remove(); else el.remove();
            });
            document.querySelectorAll('.notification-menu [data-announcement-id]').forEach(function(el) {
                var li = el.closest('li');
                if (li) li.remove(); else el.remove();
            });

            var menu = document.querySelector('.notification-menu');
            if (menu) {
                var remainingAnn = menu.querySelectorAll('[data-announcement-id]').length;
                if (remainingAnn === 0) {
                    var header = menu.querySelector('.dropdown-header');
                    if (header) {
                        var headerLi = header.closest('li');
                        if (headerLi) {
                            var prev = headerLi.previousElementSibling;
                            if (prev && prev.querySelector('hr.dropdown-divider')) prev.remove();
                            headerLi.remove();
                        }
                    }
                }
            }

            document.querySelectorAll('.announcement-banner').forEach(function(b) { b.remove(); });
            var bannerWrap = document.querySelector('.announcements-banner');
            if (bannerWrap && bannerWrap.children.length === 0) bannerWrap.remove();

            if (menu) {
                var hasItems = menu.querySelectorAll('[data-notification-id], [data-announcement-id]').length > 0;
                if (!hasItems) {
                    menu.querySelectorAll('hr.dropdown-divider').forEach(function(hr) {
                        var li = hr.closest('li');
                        if (li) li.remove();
                    });
                    if (!menu.querySelector('.empty-state')) {
                        // Keep in sync with the empty-state <li> in resources/views/layouts/app.blade.php
                        var emptyLi = document.createElement('li');
                        emptyLi.className = 'empty-state';
                        emptyLi.innerHTML = '<span class="dropdown-item-text text-center py-4 text-muted"><i class="bi bi-bell-slash d-block fs-4 mb-2"></i>No new notifications<small class="d-block text-muted mt-1" style="font-size:0.75rem;">You\'re all caught up</small></span>';
                        var footerEl = menu.querySelector('.dropdown-footer-sticky');
                        if (footerEl) menu.insertBefore(emptyLi, footerEl);
                        else menu.appendChild(emptyLi);
                    }
                }
            }

            var badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.textContent = '0';
                badge.classList.add('d-none');
            }
            btn.classList.add('d-none');
            btn.style.display = 'none';
            var footer = btn.closest('.dropdown-footer-sticky');
            if (footer) {
                footer.classList.add('d-none');
                footer.style.display = 'none';
            }
        })
        .catch(function(error) {
            console.error('Dismiss all failed:', error);
            btn.disabled = false;
        })
        .finally(function() {
            if (typeof hideSpinner === 'function') hideSpinner();
        });
    });
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    // Filter functionality
    const filterInputs = document.querySelectorAll('[data-filter]');
    filterInputs.forEach(input => {
        input.addEventListener('change', handleFilter);
    });
}

// Close sidebar when clicking outside on mobile
function setupSidebarCloseOnOutsideClick() {
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarCollapseBtn');
        const mobileToggleBtn = document.getElementById('mobileMenuToggle');

        if (sidebar && sidebar.classList.contains('show')) {
            const isOutsideSidebar = !sidebar.contains(e.target);
            const isOutsideToggleBtn = toggleBtn ? !toggleBtn.contains(e.target) : true;
            const isOutsideMobileBtn = mobileToggleBtn ? !mobileToggleBtn.contains(e.target) : true;

            if (isOutsideSidebar && isOutsideToggleBtn && isOutsideMobileBtn) {
                sidebar.classList.remove('show');
            }
        }
    });
}

// Close sidebar when nav link clicked on mobile
function setupSidebarCloseOnNavClick() {
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar && window.innerWidth < 768) {
                sidebar.classList.remove('show');
            }
        });
    });
}

// Restore sidebar collapsed state from localStorage
function restoreSidebarState() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    // Only apply collapse on desktop
    if (window.innerWidth >= 768) {
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
        }
    }
}

// Setup sidebar collapse toggle (single button for mobile + desktop)
function setupSidebarCollapse() {
    const collapseBtn = document.getElementById('sidebarCollapseBtn');
    if (!collapseBtn) return;

    collapseBtn.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        if (window.innerWidth < 768) {
            // Mobile: slide in/out
            sidebar.classList.toggle('show');
        } else {
            // Desktop: expand/collapse
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        }
    });

    // Handle window resize — clean up mobile state
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        if (window.innerWidth < 768) {
            sidebar.classList.remove('collapsed');
        } else {
            sidebar.classList.remove('show');
        }
    });
}

// Highlight active navigation item
function highlightActiveNav() {
    const currentPath = window.location.pathname === '/' ? '/dashboard' : window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// View document details
function viewDocument(docId) {
    sessionStorage.setItem('currentDocId', docId);
    window.location.href = '/document-details';
}

// Print QR code
function printQRCode() {
    window.print();
}

// Search functionality
function handleSearch(event) {
    const searchTerm = event.target.value.toLowerCase();
    const tableRows = document.querySelectorAll('tbody tr');

    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Filter functionality
function handleFilter() {
    const filters = {};
    document.querySelectorAll('[data-filter]').forEach(input => {
        const filterType = input.dataset.filter;
        const value = input.value;
        if (value) {
            filters[filterType] = value.toLowerCase();
        }
    });

    const tableRows = document.querySelectorAll('tbody tr');

    tableRows.forEach(row => {
        let show = true;

        Object.keys(filters).forEach(filterType => {
            const filterValue = filters[filterType];
            const cellIndex = parseInt(row.querySelector(`td[data-${filterType}]`)?.dataset.index || -1);

            if (cellIndex >= 0) {
                const cellText = row.cells[cellIndex].textContent.toLowerCase();
                if (!cellText.includes(filterValue)) {
                    show = false;
                }
            }
        });

        row.style.display = show ? '' : 'none';
    });
}

// Show toast notification
function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' :
                    type === 'error' ? 'bg-danger' :
                    type === 'warning' ? 'bg-warning' : 'bg-info';

    const toastHtml = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Show loading spinner
function showSpinner() {
    let spinner = document.getElementById('spinnerOverlay');
    if (!spinner) {
        spinner = document.createElement('div');
        spinner.id = 'spinnerOverlay';
        spinner.className = 'spinner-overlay';
        spinner.innerHTML = `
            <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(spinner);
    }
    spinner.style.display = 'flex';

    // Force a synchronous layout reflow to flush style mutations before browser navigation drops the frame
    void spinner.offsetHeight;
}

// Hide loading spinner
function hideSpinner() {
    const spinner = document.getElementById('spinnerOverlay');
    if (spinner) {
        spinner.style.display = 'none';
    }
}

// Fix blank Chart.js canvases in print — resize all registered instances before snapshot
window.addEventListener('beforeprint', function() {
    try {
        if (typeof Chart !== 'undefined' && Chart.instances) {
            Object.values(Chart.instances).forEach(function(instance) {
                if (instance && typeof instance.resize === 'function') {
                    instance.resize();
                }
            });
        }
    } catch (e) {
        // Silently ignore — no charts on this page or Chart not yet loaded
    }
});

// Export for global access
window.viewDocument = viewDocument;
window.printQRCode = printQRCode;
