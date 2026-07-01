// Document Tracking System - Main JavaScript
// Shared utilities only — mock data and simulation functions removed (Phase 4 S1).

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    highlightActiveNav();

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

// Setup event listeners
function setupEventListeners() {
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

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

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
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
            <div class="toast-body">
                ${message}
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
