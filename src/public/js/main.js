// Document Tracking System - Main JavaScript
// Shared utilities only — mock data and simulation functions removed (Phase 4 S1).

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    setupNotificationMarkRead();
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

// Mark a notification as read in the background, decrement the bell badge,
// then navigate the user to the referenced document.
function setupNotificationMarkRead() {
    document.querySelectorAll('.notification-item[data-notification-id]').forEach(function(item) {
        item.addEventListener('click', function(event) {
            event.preventDefault();

            const notificationId = item.getAttribute('data-notification-id');
            const targetUrl = item.getAttribute('href');

            if (notificationId) {
                fetch('/notifications/' + encodeURIComponent(notificationId) + '/read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'Accept': 'application/json'
                    }
                }).catch(function() {
                    // Best-effort: navigation continues even if marking fails
                });

                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    const current = parseInt(badge.textContent || '0', 10);
                    const next = Math.max(0, current - 1);
                    badge.textContent = next;
                    badge.classList.toggle('d-none', next <= 0);
                }
            }

            if (targetUrl && targetUrl !== '#' && targetUrl !== window.location.href) {
                window.location.href = targetUrl;
            }
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

// Export for global access
window.viewDocument = viewDocument;
window.printQRCode = printQRCode;
