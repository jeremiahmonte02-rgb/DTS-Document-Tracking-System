/**
 * Profile Documents Module
 * Renders the authenticated user's uploaded documents with server-side pagination.
 * Mirrors the structural pattern used by inbox.js / outbox.js.
 */

document.addEventListener('DOMContentLoaded', function () {
    const tableWrapper = document.getElementById('profile-documents-table-wrapper');
    if (!tableWrapper) return;

    const fetchUrl = tableWrapper.getAttribute('data-fetch-url');
    const tableBody = document.getElementById('profileDocumentsTable');
    const paginationContainer = tableWrapper.closest('.card').querySelector('.pagination');

    let currentPage = 1;

    fetchDocuments();

    function fetchDocuments() {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    Loading your uploaded documents...
                </td>
            </tr>`;

        const queryParams = new URLSearchParams({ page: currentPage }).toString();

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
        })
        .catch(err => {
            console.error('Failed to load uploaded documents:', err);
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">Error loading your documents.</td></tr>`;
        });
    }

    function renderTableDataRows(documents) {
        if (!documents || documents.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">You have not uploaded any documents yet.</td></tr>`;
            return;
        }

        tableBody.innerHTML = documents.map(doc => {
            const formattedDate = new Date(doc.date_uploaded).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });

            let badgeClass = 'bg-secondary';
            const status = doc.status || '';
            const displayStatus = status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            if (status === 'received') badgeClass = 'bg-success';
            else if (status === 'in_transit') badgeClass = 'bg-info text-white';
            else if (status === 'pending_transfer') badgeClass = 'bg-warning text-dark';
            else if (status === 'rejected') badgeClass = 'bg-danger';
            else if (status === 'completed') badgeClass = 'bg-primary';
            else if (status === 'returned_for_correction') badgeClass = 'bg-warning text-dark';

            return `
                <tr class="clickable-row" data-document-number="${escapeHtml(doc.document_number)}" style="cursor: pointer;">
                    <td><strong class="text-primary">${escapeHtml(doc.document_number)}</strong></td>
                    <td>${escapeHtml(doc.title)}</td>
                    <td><span class="badge bg-light text-dark border">${escapeHtml(doc.document_type_name)}</span></td>
                    <td><span class="text-muted">${escapeHtml(doc.current_department)}</span></td>
                    <td><span class="badge ${badgeClass}">${escapeHtml(displayStatus)}</span></td>
                    <td>${formattedDate}</td>
                </tr>`;
        }).join('');

        tableBody.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function () {
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
            btn.addEventListener('click', function () {
                currentPage = parseInt(this.getAttribute('data-page'));
                fetchDocuments();
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
