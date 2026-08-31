document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('audit-table-wrapper');
    if (!wrapper) return;

    const fetchUrl = wrapper.getAttribute('data-fetch-url');
    const detailsUrl = wrapper.getAttribute('data-details-url');
    const tableBody = document.getElementById('audit-table-body');

    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('type-filter');
    const statusFilter = document.getElementById('status-filter');
    const dateFilter = document.getElementById('date-filter');
    const clearBtn = document.querySelector('[data-action="clear-filters"]');
    const refreshBtn = document.getElementById('refreshTableBtn');
    const countBadge = document.getElementById('documentCount');
    const overdueBanner = document.getElementById('overdue-banner');
    const overdueText = document.getElementById('overdue-text');
    const btnToggleOverdue = document.getElementById('btn-toggle-overdue');

    let isOverdueFilterActive = false;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var statusMap = {
        pending_transfer: 'status-pending',
        in_transit: 'status-transit',
        received: 'status-received',
        completed: 'status-completed',
        rejected: 'status-rejected',
        cancelled: 'status-cancelled'
    };

    var labelMap = {
        pending_transfer: 'Pending',
        in_transit: 'In Transit',
        received: 'Received',
        completed: 'Completed',
        rejected: 'Rejected',
        cancelled: 'Cancelled'
    };

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        if (isNaN(d.getTime())) return escapeHtml(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function updateMetrics(counts) {
        if (!counts) return;
        var totalEl = document.getElementById('metric-total');
        var pendingEl = document.getElementById('metric-pending');
        var transitEl = document.getElementById('metric-transit');
        var completedEl = document.getElementById('metric-completed');
        if (totalEl) totalEl.textContent = counts.total || 0;
        if (pendingEl) pendingEl.textContent = counts.pending || 0;
        if (transitEl) transitEl.textContent = counts.in_transit || 0;
        if (completedEl) completedEl.textContent = counts.completed || 0;
    }

    function renderTableRows(documents) {
        if (!tableBody) return;

        if (documents.length === 0) {
            tableBody.innerHTML = ''
                + '<tr>'
                + '<td colspan="7" class="text-center py-5 text-muted">'
                + 'No matching documents found within this filter scope.'
                + '</td>'
                + '</tr>';
            return;
        }

        if (countBadge) countBadge.textContent = documents.length;

        var html = '';
        for (var i = 0; i < documents.length; i++) {
            var doc = documents[i];
            var status = doc.status || 'pending_transfer';
            var label = labelMap[status] || status.replace(/_/g, ' ');
            var badgeClass = statusMap[status] || 'status-pending';
            var formattedDate = formatDate(doc.date_created);
            var isOverdue = doc.is_overdue === true;
            var rowClass = isOverdue ? 'table-danger bg-danger-subtle' : '';
            var overdueBadge = isOverdue ? '<span class="badge bg-danger ms-1">OVERDUE</span>' : '';

            html += ''
                + '<tr style="cursor: pointer;" class="' + rowClass + '" data-document-number="' + escapeHtml(doc.document_number) + '">'
                + '<td><span class="doc-number">' + escapeHtml(doc.document_number) + '</span></td>'
                + '<td class="cell-title"><span class="doc-title-cell" title="' + escapeHtml(doc.title) + '">' + escapeHtml(doc.title) + '</span></td>'
                + '<td class="meta-cell">' + escapeHtml(doc.document_type_name) + '</td>'
                + '<td class="meta-cell">' + escapeHtml(doc.origin_department) + '</td>'
                + '<td class="meta-cell">' + escapeHtml(doc.current_department || 'N/A') + '</td>'
                + '<td class="meta-cell">' + formattedDate + '</td>'
                + '<td><span class="audit-badge ' + badgeClass + '">' + escapeHtml(label) + '</span>' + overdueBadge + '</td>'
                + '</tr>';
        }

        tableBody.innerHTML = html;
    }

    function fetchAuditorRecords() {
        var queryParams = new URLSearchParams();
        if (searchInput && searchInput.value.trim()) queryParams.set('search', searchInput.value.trim());
        if (typeFilter && typeFilter.value) queryParams.set('type', typeFilter.value);
        if (statusFilter && statusFilter.value) queryParams.set('status', statusFilter.value);
        if (dateFilter && dateFilter.value) queryParams.set('date', dateFilter.value);
        if (isOverdueFilterActive) queryParams.set('overdue', '1');

        fetch(fetchUrl + '?' + queryParams.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Data pipeline network fault encountered.');
            return response.json();
        })
        .then(function (payload) {
            if (payload.status === 'success') {
                renderTableRows(payload.data);
                updateMetrics(payload.counts);

                var overdueCount = (payload.counts && payload.counts.monthly_overdue) || 0;
                if (overdueBanner && overdueText) {
                    if (overdueCount > 0) {
                        overdueBanner.classList.remove('d-none');
                        overdueBanner.classList.add('d-flex');
                        overdueText.textContent = '\u26A0\uFE0F ' + overdueCount + ' document' + (overdueCount !== 1 ? 's are' : ' is') + ' currently overdue across active steps.';
                    } else {
                        overdueBanner.classList.remove('d-flex');
                        overdueBanner.classList.add('d-none');
                    }
                }
            }
        })
        .catch(function (err) {
            console.error('Error execution failed during auditor fetch processing:', err);
        });
    }

    function debounce(func, delay) {
        var timeout;
        return function () {
            var args = arguments;
            var context = this;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, delay);
        };
    }

    if (searchInput) searchInput.addEventListener('input', debounce(fetchAuditorRecords, 300));
    if (typeFilter) typeFilter.addEventListener('change', fetchAuditorRecords);
    if (statusFilter) statusFilter.addEventListener('change', fetchAuditorRecords);
    if (dateFilter) dateFilter.addEventListener('change', fetchAuditorRecords);
    if (refreshBtn) refreshBtn.addEventListener('click', fetchAuditorRecords);

    if (tableBody) {
        tableBody.addEventListener('click', function (e) {
            var targetedRow = e.target.closest('tr[data-document-number]');
            if (targetedRow) {
                var docNum = targetedRow.getAttribute('data-document-number');
                window.location.href = detailsUrl + '/' + docNum;
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            if (typeFilter) typeFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            if (dateFilter) dateFilter.value = '';
            isOverdueFilterActive = false;
            if (btnToggleOverdue) btnToggleOverdue.textContent = 'Filter to Overdue';
            fetchAuditorRecords();
        });
    }

    if (btnToggleOverdue) {
        btnToggleOverdue.addEventListener('click', function () {
            isOverdueFilterActive = !isOverdueFilterActive;
            btnToggleOverdue.textContent = isOverdueFilterActive ? 'Show All Documents' : 'Filter to Overdue';
            fetchAuditorRecords();
        });
    }

    fetchAuditorRecords();
});
