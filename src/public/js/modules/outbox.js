/**
 * Outbox Management Module
 * Handles asynchronous data table pipelines, server-side pagination, and filter queries
 */

document.addEventListener('DOMContentLoaded', function () {
    const tableWrapper = document.getElementById('outbox-table-wrapper');
    if (!tableWrapper) return;

    const fetchUrl = tableWrapper.getAttribute('data-fetch-url');
    const tableBody = document.getElementById('outboxTable');
    const searchInput = document.getElementById('searchInput');
    const docCountBadge = document.getElementById('documentCount');
    const showingCountLabel = document.getElementById('showingCount');
    const paginationContainer = document.querySelector('.pagination');

    let currentFilters = {
        page: 1,
        search: '',
        type: '',
        status: ''
    };

    const style = document.createElement('style');
    style.innerHTML = `
        #outboxTable tr { cursor: pointer; transition: background-color 0.15s ease-in-out; }
        #outboxTable tr:hover { background-color: rgba(var(--bs-primary-rgb), 0.05) !important; }
    `;
    document.head.appendChild(style);

    initEventListeners();
    loadOutboxData();

    function initEventListeners() {
        if (searchInput) {
            searchInput.addEventListener('input', debounce(function (e) {
                currentFilters.search = e.target.value;
                currentFilters.page = 1;
                loadOutboxData();
            }, 300));
        }

        document.querySelectorAll('select[data-filter]').forEach(select => {
            select.addEventListener('change', function () {
                const filterType = this.getAttribute('data-filter');
                currentFilters[filterType] = this.value;
                currentFilters.page = 1;
                loadOutboxData();
            });
        });

        document.addEventListener('click', function (e) {
            const actionButton = e.target.closest('[data-action]');
            if (!actionButton) return;

            const action = actionButton.getAttribute('data-action');

            if (action === 'clear-filters') {
                clearAllActiveFilters();
            } else if (action === 'refresh-outbox') {
                loadOutboxData();
            } else if (action === 'export-outbox') {
                handleOutboxDataExport();
            }
        });

        tableBody.addEventListener('click', function (e) {
            const row = e.target.closest('tr[data-doc-number]');
            if (row) {
                const docNumber = row.getAttribute('data-doc-number');
                window.location.href = `/document-details/${encodeURIComponent(docNumber)}`;
            }
        });
    }

    function loadOutboxData() {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Retrieving outbox ledger history...
                </td>
            </tr>`;

        const queryParams = new URLSearchParams(currentFilters).toString();

        fetch(`${fetchUrl}?${queryParams}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(payload => {
            renderTable(payload.data);
            updatePaginationControls(payload);
            updateStatusCounters(payload);
        })
        .catch(error => {
            console.error('Outbox fetch failure:', error);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Failed to sync outbox data records.</td></tr>`;
        });
    }

    function renderTable(documents) {
        if (!documents || documents.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No processed or sent documents detected in your department history.
                    </td>
                </tr>`;
            return;
        }

        tableBody.innerHTML = documents.map(doc => {
            let badgeClass = 'bg-secondary';
            if (doc.computed_status === 'Pending Transfer') badgeClass = 'bg-warning text-dark';
            if (doc.computed_status === 'In Transit') badgeClass = 'bg-info text-white';
            if (doc.computed_status === 'Received' || doc.computed_status === 'Completed') badgeClass = 'bg-success text-white';

            return `
                <tr data-doc-number="${doc.document_number}">
                    <td class="font-monospace fw-bold text-primary">${doc.document_number}</td>
                    <td class="fw-semibold">${doc.title}</td>
                    <td><span class="badge bg-light text-dark border">${doc.type_name}</span></td>
                    <td class="text-muted">${doc.current_department}</td>
                    <td><span class="text-secondary fw-medium">${doc.current_location}</span></td>
                    <td>${doc.date_sent_formatted}</td>
                    <td><span class="badge ${badgeClass}">${doc.computed_status}</span></td>
                </tr>`;
        }).join('');
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
                loadOutboxData();
            });
        });
    }

    function updateStatusCounters(meta) {
        if (docCountBadge) docCountBadge.textContent = meta.total;
        if (showingCountLabel) showingCountLabel.textContent = `Showing ${meta.from ?? 0} to ${meta.to ?? 0} of ${meta.total} records`;
    }

    function clearAllActiveFilters() {
        if (searchInput) searchInput.value = '';
        document.querySelectorAll('select[data-filter]').forEach(select => select.value = '');
        currentFilters = { page: 1, search: '', type: '', status: '' };
        loadOutboxData();
    }

    function handleOutboxDataExport() {
        alert("Preparing outbound document records dataset for spreadsheet compilation...");
    }

    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }
});
