(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var wrapper = document.getElementById('dept-details-wrapper');
        if (!wrapper) return;

        var fetchUrl = wrapper.getAttribute('data-fetch-url');

        var documentsBody = document.getElementById('dept-documents-body');
        var paginationEl = document.getElementById('documents-pagination');
        var timelineEl = document.getElementById('event-timeline');
        var refreshBtn = document.getElementById('refresh-documents-btn');

        var currentDocPage = 1;

        fetchDetails();
        initRefresh();

        function initRefresh() {
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function () {
                    fetchDetails();
                });
            }
        }

        function fetchDetails() {
            if (!fetchUrl) return;

            showLoading();

            var params = new URLSearchParams();
            params.set('page', currentDocPage);

            fetch(fetchUrl + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (payload.status === 'success') {
                    updateMetrics(payload.counts);
                    renderDocumentsTable(payload.documents);
                    renderEventTimeline(payload.recent_events);
                    hydrateSlaModal(payload.slas);
                } else {
                    showError(
                        payload.message || 'Unable to load department analytics. Please try again later.'
                    );
                }
            })
            .catch(function (err) {
                console.error('[audit-department-details.js] Fetch failed:', err);
                showError('Unable to load department analytics. Please try again later.');
            });

        function showError(msg) {
            if (documentsBody) {
                documentsBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">'
                    + escapeHtml(msg) + '</td></tr>';
            }
            if (timelineEl) {
                timelineEl.innerHTML = '<div class="text-center text-danger py-3">'
                    + escapeHtml(msg) + '</div>';
            }
        }
        }

        function showLoading() {
            if (documentsBody) {
                documentsBody.innerHTML = ''
                    + '<tr>'
                    + '<td colspan="6" class="text-center py-4 text-muted">'
                    + '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>'
                    + 'Loading documents...'
                    + '</td>'
                    + '</tr>';
            }
            if (timelineEl) {
                timelineEl.innerHTML = ''
                    + '<div class="text-center py-4 text-muted">'
                    + '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>'
                    + 'Loading events...'
                    + '</div>';
            }
        }

        function updateMetrics(counts) {
            if (!counts) return;

            var setVal = function (id, val) {
                var el = document.getElementById(id);
                if (el) el.textContent = val !== null && val !== undefined ? val : 0;
            };

            setVal('metric-total-current', counts.total_current);
            setVal('metric-pending', counts.pending);
            setVal('metric-in-transit', counts.in_transit);
            setVal('metric-completed', counts.completed);
            setVal('metric-overdue', counts.overdue);
            setVal('metric-received-today', counts.received_today);
            setVal('metric-total-originated', counts.total_originated);

            var dwellEl = document.getElementById('metric-avg-dwell');
            if (dwellEl) {
                dwellEl.textContent = (counts.avg_dwell_hours !== null && counts.avg_dwell_hours !== undefined)
                    ? window.FormatUtils.formatHoursToDays(counts.avg_dwell_hours)
                    : '--';
            }
        }

        function getStatusBadgeHtml(status) {
            var map = {
                'pending_transfer': { label: 'Pending', cls: 'status-pending' },
                'in_transit': { label: 'In Transit', cls: 'status-transit' },
                'received': { label: 'Received', cls: 'status-received' },
                'completed': { label: 'Completed', cls: 'status-completed' },
                'rejected': { label: 'Rejected', cls: 'status-rejected' },
                'cancelled': { label: 'Cancelled', cls: 'status-cancelled' }
            };
            var s = map[status] || { label: status || 'Unknown', cls: 'status-pending' };
            return '<span class="audit-badge ' + s.cls + '">' + s.label + '</span>';
        }

        function renderDocumentsTable(docPayload) {
            if (!documentsBody) return;

            var docs = docPayload.data || [];

            if (docs.length === 0) {
                documentsBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No documents currently in this department.</td></tr>';
                renderPagination(null);
                return;
            }

            var html = '';
            for (var i = 0; i < docs.length; i++) {
                var doc = docs[i];
                var typeName = doc.document_type ? doc.document_type.name : (doc.document_type_name || '--');
                var senderName = doc.sender_department ? doc.sender_department.name : (doc.origin_department || '--');
                var created = doc.created_at ? formatDate(doc.created_at) : '--';

                html += ''
                    + '<tr>'
                    + '<td><span class="doc-number">' + escapeHtml(doc.document_number) + '</span></td>'
                    + '<td class="cell-title"><span class="doc-title-cell" title="' + escapeHtml(doc.title) + '">' + escapeHtml(doc.title) + '</span></td>'
                    + '<td>' + escapeHtml(typeName) + '</td>'
                    + '<td>' + escapeHtml(senderName) + '</td>'
                    + '<td>' + getStatusBadgeHtml(doc.status) + '</td>'
                    + '<td class="meta-cell">' + created + '</td>'
                    + '</tr>';
            }

            documentsBody.innerHTML = html;
            renderPagination(docPayload);
        }

        function renderPagination(meta) {
            if (!paginationEl) return;

            if (!meta || meta.last_page <= 1) {
                paginationEl.innerHTML = '';
                return;
            }

            var html = '<nav><ul class="pagination pagination-sm mb-0">';

            if (meta.current_page > 1) {
                html += '<li class="page-item"><button class="page-link page-trigger" data-page="' + (meta.current_page - 1) + '">&laquo;</button></li>';
            }

            for (var i = 1; i <= meta.last_page; i++) {
                html += '<li class="page-item' + (meta.current_page === i ? ' active' : '') + '">'
                    + '<button class="page-link page-trigger" data-page="' + i + '">' + i + '</button></li>';
            }

            if (meta.current_page < meta.last_page) {
                html += '<li class="page-item"><button class="page-link page-trigger" data-page="' + (meta.current_page + 1) + '">&raquo;</button></li>';
            }

            html += '</ul></nav>';
            paginationEl.innerHTML = html;

            var triggers = paginationEl.querySelectorAll('.page-trigger');
            for (var j = 0; j < triggers.length; j++) {
                triggers[j].addEventListener('click', function () {
                    currentDocPage = parseInt(this.getAttribute('data-page'), 10);
                    fetchDetails();
                });
            }
        }

        function renderEventTimeline(events) {
            if (!timelineEl) return;

            if (!events || events.length === 0) {
                timelineEl.innerHTML = '<div class="text-center py-4 text-muted">No recent activity.</div>';
                return;
            }

            var html = '';
            var maxEvents = Math.min(events.length, 15);

            for (var i = 0; i < maxEvents; i++) {
                var ev = events[i];
                var dotClass = 'event-dot ' + (ev.action || '');
                var actionLabel = ev.action ? ev.action.charAt(0).toUpperCase() + ev.action.slice(1) : 'Unknown';
                var timeAgo = getTimeAgo(ev.created_at);
                var docInfo = escapeHtml(ev.document_number || 'N/A');
                var docTitle = escapeHtml(ev.document_title || '');
                var notes = ev.note ? escapeHtml(ev.note) : '';
                var deptInfo = ev.department_name ? escapeHtml(ev.department_name) : '';

                html += ''
                    + '<div class="event-item">'
                    + '<div class="' + dotClass + '"></div>'
                    + '<div class="event-ref"><a href="/document-details/' + escapeHtml(ev.document_number || '') + '">' + docInfo + '</a></div>'
                    + '<div class="event-action">' + actionLabel + '</div>'
                    + '<div class="event-meta">' + timeAgo;

                if (deptInfo) {
                    html += ' &middot; ' + deptInfo;
                }

                html += '</div>';

                if (notes) {
                    html += '<div class="event-notes">' + notes + '</div>';
                }

                html += '</div>';
            }

            if (events.length > maxEvents) {
                html += '<div class="text-center text-muted" style="font-size:0.75rem;padding-top:0.5rem;">+' + (events.length - maxEvents) + ' more events</div>';
            }

            timelineEl.innerHTML = html;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '--';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            var year = d.getFullYear();
            var month = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            return year + '-' + month + '-' + day;
        }

        function getTimeAgo(dateStr) {
            if (!dateStr) return '';
            var now = new Date();
            var d = new Date(dateStr);
            var diffMs = now - d;
            var diffMins = Math.floor(diffMs / 60000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return diffMins + 'm ago';

            var diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return diffHours + 'h ago';

            var diffDays = Math.floor(diffHours / 24);
            if (diffDays < 7) return diffDays + 'd ago';

            return formatDate(dateStr);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        var slaBody = document.getElementById('sla-configuration-body');
        var documentTypes = (function () {
            try {
                return JSON.parse(wrapper.getAttribute('data-document-types') || '[]');
            } catch (_) {
                return [];
            }
        })();

        function hydrateSlaModal(slas) {
            var tbody = document.getElementById('sla-configuration-body');
            if (!tbody || !documentTypes.length) return;

            var slaMap = slas || {};
            tbody.innerHTML = '';

            documentTypes.forEach(function (dt, index) {
                var activeSla = slaMap[dt.id];
                var minutes = activeSla ? activeSla.processing_time_minutes : null;

                var value = '';
                var unit = 'minutes';

                if (minutes) {
                    if (minutes % 1440 === 0) {
                        value = minutes / 1440;
                        unit = 'days';
                    } else if (minutes % 60 === 0) {
                        value = minutes / 60;
                        unit = 'hours';
                    } else {
                        value = minutes;
                        unit = 'minutes';
                    }
                }

                var rowHTML = ''
                    + '<tr>'
                    + '<td><strong>' + escapeHtml(dt.name) + '</strong> <span class="badge bg-light text-dark border">' + escapeHtml(dt.code) + '</span></td>'
                    + '<td>'
                    + '<input type="hidden" name="sla[' + index + '][document_type_id]" value="' + dt.id + '">'
                    + '<input type="number" class="form-control form-control-sm" name="sla[' + index + '][value]" value="' + value + '" min="1" placeholder="Global Default">'
                    + '</td>'
                    + '<td>'
                    + '<select class="form-select form-select-sm" name="sla[' + index + '][unit]">'
                    + '<option value="minutes"' + (unit === 'minutes' ? ' selected' : '') + '>Minutes</option>'
                    + '<option value="hours"' + (unit === 'hours' ? ' selected' : '') + '>Hours</option>'
                    + '<option value="days"' + (unit === 'days' ? ' selected' : '') + '>Days</option>'
                    + '</select>'
                    + '</td>'
                    + '</tr>';

                tbody.insertAdjacentHTML('beforeend', rowHTML);
            });
        }

        // Scoped SLA table refresh — no full page-data reload
        var slaModal = document.getElementById('configureSlaModal');
        if (slaModal) {
            slaModal.addEventListener('show.bs.modal', function () {
                if (!fetchUrl) return;
                fetch(fetchUrl + '?_=' + Date.now())
                    .then(function (r) { return r.json(); })
                    .then(function (payload) {
                        if (payload.status === 'success') {
                            hydrateSlaModal(payload.slas);
                        }
                    })
                    .catch(function (err) {
                        console.error('[audit-department-details.js] Failed to sync SLA metrics:', err);
                    });
            });
        }
    });
})();