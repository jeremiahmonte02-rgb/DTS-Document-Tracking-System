(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('department-table-wrapper');
        if (!container) return;

        var fetchUrl = container.getAttribute('data-fetch-url');
        var storeUrl = container.getAttribute('data-store-url');
        var toggleUrlTemplate = container.getAttribute('data-toggle-url');
        var updateUrlTemplate = container.getAttribute('data-update-url');
        var detailsUrlPrefix = container.getAttribute('data-details-url');

        var tableBody = document.getElementById('department-table-body');
        var searchInput = document.getElementById('search-input');
        var statusFilter = document.getElementById('status-filter');
        var paginationContainer = document.getElementById('pagination-links');

        var currentPage = 1;

        initEventListeners();
        fetchDepartments();

        function initEventListeners() {
            if (searchInput) {
                searchInput.addEventListener('input', debounce(function () {
                    currentPage = 1;
                    fetchDepartments();
                }, 300));
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', function () {
                    currentPage = 1;
                    fetchDepartments();
                });
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-action]');
                if (!btn) return;

                var action = btn.getAttribute('data-action');

                if (action === 'submit-add-department') {
                    submitAddDepartment();
                } else if (action === 'edit-department') {
                    var id = btn.getAttribute('data-id');
                    if (id) openEditModal(parseInt(id, 10));
                } else if (action === 'submit-edit-department') {
                    submitEditDepartment();
                } else if (action === 'toggle-department') {
                    var id = btn.getAttribute('data-id');
                    if (id) toggleDepartment(parseInt(id, 10));
                } else if (action === 'view-department') {
                    var id = btn.getAttribute('data-id');
                    if (id && detailsUrlPrefix) {
                        window.location.href = detailsUrlPrefix + '/' + id;
                    }
                } else if (action === 'refresh-departments') {
                    fetchDepartments();
                }
            });
        }

        function fetchDepartments() {
            if (!tableBody || !fetchUrl) return;

            tableBody.innerHTML = ''
                + '<tr>'
                + '<td colspan="5" class="text-center py-4 text-muted">'
                + '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>'
                + 'Loading departments...'
                + '</td>'
                + '</tr>';

            var params = new URLSearchParams();
            params.set('page', currentPage);
            if (searchInput && searchInput.value.trim()) params.set('search', searchInput.value.trim());
            if (statusFilter && statusFilter.value) params.set('status', statusFilter.value);

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
                    renderTableRows(payload.data);
                    renderPagination(payload.pagination);
                    updateMetrics(payload.counts);
                }
            })
            .catch(function (err) {
                console.error('[audit-departments.js] Fetch failed:', err);
                if (tableBody) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error loading departments.</td></tr>';
                }
            });
        }

        function renderTableRows(departments) {
            if (!tableBody) return;

            if (!departments || departments.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No departments found.</td></tr>';
                return;
            }

            var html = '';
            for (var i = 0; i < departments.length; i++) {
                var dept = departments[i];
                var statusLabel = dept.is_active ? 'Active' : 'Inactive';
                var badgeClass = dept.is_active ? 'dept-badge dept-active' : 'dept-badge dept-inactive';
                var toggleLabel = dept.is_active ? 'Deactivate' : 'Activate';
                var toggleIcon = dept.is_active ? 'bi-pause-circle' : 'bi-play-circle';
                var desc = dept.description || '--';

                html += ''
                    + '<tr>'
                    + '<td><span class="dept-code">' + escapeHtml(dept.code) + '</span></td>'
                    + '<td class="cell-title"><span class="dept-name" title="' + escapeHtml(dept.name) + '">' + escapeHtml(dept.name) + '</span></td>'
                    + '<td><span class="dept-desc" title="' + escapeHtml(desc) + '">' + escapeHtml(desc) + '</span></td>'
                    + '<td><span class="' + badgeClass + '">' + statusLabel + '</span></td>'
                    + '<td class="text-end">'
                    + '<button class="btn btn-sm btn-outline-primary me-1" data-action="view-department" data-id="' + dept.id + '" title="View analytics">'
                    + '<i class="bi bi-graph-up"></i>'
                    + '</button>'
                    + '<button class="btn btn-sm btn-outline-primary me-1" data-action="edit-department" data-id="' + dept.id + '" title="Edit department">'
                    + '<i class="bi bi-pencil"></i>'
                    + '</button>'
                    + '<button class="btn btn-sm btn-outline-warning" data-action="toggle-department" data-id="' + dept.id + '" title="' + toggleLabel + '">'
                    + '<i class="bi ' + toggleIcon + '"></i>'
                    + '</button>'
                    + '</td>'
                    + '</tr>';
            }

            tableBody.innerHTML = html;
        }

        function renderPagination(meta) {
            if (!paginationContainer) return;

            if (!meta || meta.last_page <= 1) {
                paginationContainer.innerHTML = '';
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
            paginationContainer.innerHTML = html;

            var triggers = paginationContainer.querySelectorAll('.page-trigger');
            for (var j = 0; j < triggers.length; j++) {
                triggers[j].addEventListener('click', function () {
                    currentPage = parseInt(this.getAttribute('data-page'), 10);
                    fetchDepartments();
                });
            }
        }

        function updateMetrics(counts) {
            if (!counts) return;
            var totalEl = document.getElementById('metric-total-depts');
            var activeEl = document.getElementById('metric-active-depts');
            var inactiveEl = document.getElementById('metric-inactive-depts');
            if (totalEl) totalEl.textContent = counts.total || 0;
            if (activeEl) activeEl.textContent = counts.active || 0;
            if (inactiveEl) inactiveEl.textContent = counts.inactive || 0;
        }

        function submitAddDepartment() {
            var form = document.getElementById('add-department-form');
            if (!form) return;

            var nameInput = document.getElementById('input-name');
            var codeInput = document.getElementById('input-code');
            var descInput = document.getElementById('input-description');
            var feedback = document.getElementById('add-form-feedback');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (feedback) {
                feedback.classList.add('d-none');
                feedback.classList.remove('alert-success', 'alert-danger');
            }

            var payload = {
                name: nameInput.value,
                code: codeInput.value,
                description: descInput.value
            };

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(payload)
            })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.status === 'success') {
                    if (feedback) {
                        feedback.textContent = result.message;
                        feedback.classList.remove('d-none', 'alert-danger');
                        feedback.classList.add('alert-success');
                    }

                    var modalEl = document.getElementById('addDepartmentModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    form.reset();
                    fetchDepartments();
                } else {
                    if (feedback) {
                        feedback.textContent = result.message || 'Failed to create department.';
                        feedback.classList.remove('d-none', 'alert-success');
                        feedback.classList.add('alert-danger');
                    }
                }
            })
            .catch(function () {
                if (feedback) {
                    feedback.textContent = 'Network error. Please try again.';
                    feedback.classList.remove('d-none', 'alert-success');
                    feedback.classList.add('alert-danger');
                }
            });
        }

        function openEditModal(deptId) {
            var rows = tableBody.querySelectorAll('tr');
            var deptData = null;

            for (var i = 0; i < rows.length; i++) {
                var btn = rows[i].querySelector('[data-action="edit-department"][data-id]');
                if (btn && parseInt(btn.getAttribute('data-id'), 10) === deptId) {
                    var cells = rows[i].querySelectorAll('td');
                    if (cells.length >= 3) {
                        var codeEl = cells[0].querySelector('.dept-code');
                        var nameEl = cells[1].querySelector('.dept-name');
                        var descEl = cells[2].querySelector('.dept-desc');
                        deptData = {
                            id: deptId,
                            code: codeEl ? codeEl.textContent : '',
                            name: nameEl ? nameEl.textContent : '',
                            description: descEl ? descEl.textContent : ''
                        };
                    }
                    break;
                }
            }

            if (!deptData) {
                console.error('[audit-departments.js] Department not found in DOM:', deptId);
                return;
            }

            document.getElementById('edit-department-id').value = deptData.id;
            document.getElementById('edit-name').value = deptData.name;
            document.getElementById('edit-code').value = deptData.code;
            document.getElementById('edit-description').value = deptData.description === '--' ? '' : deptData.description;

            var fb = document.getElementById('edit-form-feedback');
            if (fb) {
                fb.classList.add('d-none');
                fb.classList.remove('alert-success', 'alert-danger');
            }

            var modalEl = document.getElementById('editDepartmentModal');
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        function submitEditDepartment() {
            var form = document.getElementById('edit-department-form');
            if (!form) return;

            var deptId = document.getElementById('edit-department-id').value;
            if (!deptId) return;

            var nameInput = document.getElementById('edit-name');
            var codeInput = document.getElementById('edit-code');
            var descInput = document.getElementById('edit-description');
            var fb = document.getElementById('edit-form-feedback');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (fb) {
                fb.classList.add('d-none');
                fb.classList.remove('alert-success', 'alert-danger');
            }

            var payload = {
                name: nameInput.value,
                code: codeInput.value,
                description: descInput.value
            };

            var url = updateUrlTemplate.replace('__ID__', deptId);

            fetch(url, {
                method: 'PUT',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify(payload)
            })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.status === 'success') {
                    if (fb) {
                        fb.textContent = result.message;
                        fb.classList.remove('d-none', 'alert-danger');
                        fb.classList.add('alert-success');
                    }

                    var modalEl = document.getElementById('editDepartmentModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    fetchDepartments();
                } else {
                    if (fb) {
                        fb.textContent = result.message || 'Failed to update department.';
                        fb.classList.remove('d-none', 'alert-success');
                        fb.classList.add('alert-danger');
                    }
                }
            })
            .catch(function () {
                if (fb) {
                    fb.textContent = 'Network error. Please try again.';
                    fb.classList.remove('d-none', 'alert-success');
                    fb.classList.add('alert-danger');
                }
            });
        }

        function toggleDepartment(deptId) {
            if (!toggleUrlTemplate) return;

            var url = toggleUrlTemplate.replace('__ID__', deptId);

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.status === 'success') {
                    fetchDepartments();
                }
            })
            .catch(function (err) {
                console.error('[audit-departments.js] Toggle failed:', err);
            });
        }

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function debounce(func, delay) {
            var timeout;
            return function () {
                var context = this;
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    func.apply(context, args);
                }, delay);
            };
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
    });
})();
