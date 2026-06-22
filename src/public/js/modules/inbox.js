/**
 * Inbox Management Module
 * Handles asynchronous data table pipelines, server-side pagination, and filter queries
 */

console.log("[inbox.js] Module loaded, registering DOMContentLoaded handler.");

document.addEventListener('DOMContentLoaded', function () {
    const tableWrapper = document.getElementById('inbox-table-wrapper');
    if (!tableWrapper) return;

    const fetchUrl = tableWrapper.getAttribute('data-fetch-url');
    const tableBody = document.getElementById('inboxTable');
    const searchInput = document.getElementById('searchInput');
    const docCountBadge = document.getElementById('documentCount');
    const showingCountLabel = document.getElementById('showingCount');
    const paginationContainer = document.querySelector('.pagination');

    let currentFilters = {
        page: 1,
        search: '',
        type: '',
        status: '',
        date: ''
    };

    initEventListeners();
    fetchInboxRecords();

    function initEventListeners() {
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function (e) {
                currentFilters.search = e.target.value;
                currentFilters.page = 1;
                fetchInboxRecords();
            }, 300));
        }

        document.querySelectorAll('select[data-filter]').forEach(select => {
            select.addEventListener('change', function () {
                const filterType = this.getAttribute('data-filter');
                currentFilters[filterType] = this.value;
                currentFilters.page = 1;
                fetchInboxRecords();
            });
        });

        const statusEl = document.querySelector('[data-filter="status"]') || document.getElementById('status-filter') || document.querySelector('select[name="status"]');
        if (statusEl) {
            statusEl.addEventListener('change', function(e) {
                console.log("Status filter change detected! New value:", e.target.value);
                currentFilters.status = this.value;
                currentFilters.page = 1;
                fetchInboxRecords();
            });
        } else {
            console.error("Critical: Status filter element missing from DOM during init.");
        }

        const typeEl = document.querySelector('[data-filter="type"]') || document.getElementById('type-filter') || document.querySelector('select[name="type"]');
        if (typeEl) {
            typeEl.addEventListener('change', function(e) {
                console.log("Type filter change detected! New value:", e.target.value);
                currentFilters.type = this.value;
                currentFilters.page = 1;
                fetchInboxRecords();
            });
        } else {
            console.error("Critical: Type filter element missing from DOM during init.");
        }

        const dateInput = document.querySelector('[data-filter="date"]') || document.getElementById('date-filter') || document.querySelector('input[type="date"]');
        if (dateInput) {
            dateInput.addEventListener('change', function(e) {
                console.log("Date input change detected! New value:", e.target.value);
                currentFilters.date = this.value;
                currentFilters.page = 1;
                fetchInboxRecords();
            });
        } else {
            console.error("Critical: Date filter input element was missing from the DOM during script initialization.");
        }

        document.addEventListener('click', function (e) {
            const actionButton = e.target.closest('[data-action]');
            if (!actionButton) return;

            const action = actionButton.getAttribute('data-action');

            if (action === 'clear-filters') {
                clearAllActiveFilters();
            } else if (action === 'refresh-inbox') {
                fetchInboxRecords();
            } else if (action === 'export-inbox') {
                handleInboxDataExport();
            }
        });
    }

    function fetchInboxRecords() {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Retrieving department incoming records queue...
                </td>
            </tr>`;

        const dateInput = document.querySelector('[data-filter="date"]') || document.getElementById('date-filter') || document.querySelector('input[type="date"]');
        const statusEl = document.querySelector('[data-filter="status"]') || document.getElementById('status-filter') || document.querySelector('select[name="status"]');
        const typeEl = document.querySelector('[data-filter="type"]') || document.getElementById('type-filter') || document.querySelector('select[name="type"]');

        const queryParams = new URLSearchParams({
            page: currentFilters.page,
            search: searchInput ? searchInput.value : '',
            type: typeEl ? typeEl.value : '',
            status: statusEl ? statusEl.value : '',
            date: dateInput ? dateInput.value : ''
        }).toString();

        console.log("Params built:", { search: searchInput ? searchInput.value : '', type: typeEl ? typeEl.value : '', status: statusEl ? statusEl.value : '', date: dateInput ? dateInput.value : '' });
        
        fetch(`${fetchUrl}?${queryParams}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(payload => {
            renderTableDataRows(payload.data);
            updatePaginationControls(payload);
            updateStatusCounters(payload);
        })
        .catch(err => {
            console.error("Failed to sync queue data arrays:", err);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-3">Error pulling live incoming channel records.</td></tr>`;
        });
    }

    function renderTableDataRows(documents) {
        if (!documents || documents.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">No pending incoming documents detected in your queue.</td></tr>`;
            return;
        }

        tableBody.innerHTML = documents.map(doc => {
            const formattedDate = new Date(doc.date_uploaded).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });

            let badgeClass = 'bg-secondary';
            const status = doc.step_status || '';
            const displayStatus = status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            if (status === 'received') badgeClass = 'bg-success';
            else if (status === 'in_transit') badgeClass = 'bg-info text-white';
            else if (status === 'pending_transfer') badgeClass = 'bg-warning text-dark';
            else if (status === 'rejected') badgeClass = 'bg-danger';

            return `
                <tr class="clickable-row" data-document-number="${escapeHtml(doc.document_number)}" style="cursor: pointer;">
                    <td><strong class="text-primary">${escapeHtml(doc.document_number)}</strong></td>
                    <td>${escapeHtml(doc.title)}</td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(doc.type_name)}</span></td>
                    <td>${escapeHtml(doc.sender_name)}</td>
                    <td><span class="text-muted">${escapeHtml(doc.current_department)}</span></td>
                    <td>${formattedDate}</td>
                    <td><span class="badge ${badgeClass}">${escapeHtml(displayStatus)}</span></td>
                </tr>`;
        }).join('');

        tableBody.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function() {
                const docNumber = this.getAttribute('data-document-number');
                window.location.href = `/document-details/${encodeURIComponent(docNumber)}`;
            });
        });
    }

    function updatePaginationControls(meta) {
        if (!paginationContainer) return;
        if (meta.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let linksHtml = '';
        
        for (let i = 1; i <= meta.last_page; i++) {
            linksHtml += `
                <li class="page-item ${meta.current_page === i ? 'active' : ''}">
                    <button class="page-link pagination-trigger" data-page="${i}">${i}</button>
                </li>`;
        }

        paginationContainer.innerHTML = linksHtml;

        paginationContainer.querySelectorAll('.pagination-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                currentFilters.page = parseInt(this.getAttribute('data-page'));
                fetchInboxRecords();
            });
        });
    }

    function updateStatusCounters(meta) {
        if (docCountBadge) docCountBadge.textContent = meta.total;
        if (showingCountLabel) showingCountLabel.textContent = `Showing ${meta.from ?? 0} to ${meta.to ?? 0} of ${meta.total} records`;
    }

    function clearAllActiveFilters() {
        if (searchInput) searchInput.value = '';
        const statusEl = document.querySelector('[data-filter="status"]') || document.getElementById('status-filter') || document.querySelector('select[name="status"]');
        const typeEl = document.querySelector('[data-filter="type"]') || document.getElementById('type-filter') || document.querySelector('select[name="type"]');
        const dateInput = document.querySelector('[data-filter="date"]') || document.getElementById('date-filter') || document.querySelector('input[type="date"]');
        if (statusEl) statusEl.value = '';
        if (typeEl) typeEl.value = '';
        if (dateInput) dateInput.value = '';
        
        currentFilters = { page: 1, search: '', type: '', status: '', date: '' };
        fetchInboxRecords();
    }

    function handleInboxDataExport() {
        alert("Preparing document records dataset data manifest spreadsheet compilation...");
    }

    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
