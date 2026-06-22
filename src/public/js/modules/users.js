(function () {
    'use strict';

    console.log("[users.js] Module loaded, registering DOMContentLoaded handler.");

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.querySelector('[data-fetch-url]');
        if (!container) return;

        const fetchUrl = container.getAttribute('data-fetch-url');
        const statsUrl = container.getAttribute('data-stats-url');
        const storeUrl = container.getAttribute('data-store-url');
        const toggleUrlTemplate = container.getAttribute('data-toggle-url');
        const updateUrlTemplate = container.getAttribute('data-update-url');

        const tableBody = document.getElementById('users-table-body');
        const searchInput = document.getElementById('search-input');
        const paginationContainer = document.getElementById('pagination-links');
        const formFeedback = document.getElementById('form-feedback');

        let currentFilters = {
            page: 1,
            search: '',
            department_id: '',
            role_id: '',
            status: ''
        };

        var cachedUsers = [];

        initEventListeners();
        fetchUsers();
        fetchStats();

        function initEventListeners() {
            if (searchInput) {
                searchInput.addEventListener('input', debounce(function (e) {
                    currentFilters.search = e.target.value;
                    currentFilters.page = 1;
                    fetchUsers();
                }, 300));
            }

            document.querySelectorAll('select[data-filter]').forEach(function (select) {
                select.addEventListener('change', function () {
                    const filterType = this.getAttribute('data-filter');
                    currentFilters[filterType] = this.value;
                    currentFilters.page = 1;
                    fetchUsers();
                });
            });

            document.addEventListener('click', function (e) {
                const actionButton = e.target.closest('[data-action]');
                if (!actionButton) return;

                const action = actionButton.getAttribute('data-action');

                if (action === 'refresh-users') {
                    fetchUsers();
                    fetchStats();
                } else if (action === 'export-users') {
                    handleExport();
                } else if (action === 'submit-add-user') {
                    submitAddUser();
                } else if (action === 'toggle-user') {
                    const userId = actionButton.getAttribute('data-user-id');
                    if (userId) toggleUserStatus(userId);
                } else if (action === 'edit-user') {
                    const userId = actionButton.getAttribute('data-user-id');
                    if (userId) openEditModal(userId);
                } else if (action === 'submit-edit-user') {
                    submitEditUser();
                }
            });
        }

        function fetchUsers() {
            if (!tableBody || !fetchUrl) return;

            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        Loading users...
                    </td>
                </tr>`;

            const params = new URLSearchParams();
            params.set('page', currentFilters.page);
            if (currentFilters.search) params.set('search', currentFilters.search);
            if (currentFilters.department_id) params.set('department_id', currentFilters.department_id);
            if (currentFilters.role_id) params.set('role_id', currentFilters.role_id);
            if (currentFilters.status) params.set('status', currentFilters.status);

            console.log("[users.js] Fetching with params:", params.toString());

            fetch(fetchUrl + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                cachedUsers = payload.data;
                renderUsers(cachedUsers);
                renderPagination(payload);
            })
            .catch(function (err) {
                console.error("[users.js] Fetch failed:", err);
                if (tableBody) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error loading users.</td></tr>';
                }
            });
        }

        function renderUsers(users) {
            if (!tableBody) return;

            if (!users || users.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No users found.</td></tr>';
                return;
            }

            tableBody.innerHTML = users.map(function (user) {
                const statusLabel = user.status.charAt(0).toUpperCase() + user.status.slice(1);
                const badgeClass = user.status === 'active' ? 'bg-success' : 'bg-secondary';
                const toggleLabel = user.status === 'active' ? 'Deactivate' : 'Activate';
                const toggleIcon = user.status === 'active' ? 'bi-pause-circle' : 'bi-play-circle';

                return '<tr>' +
                    '<td>' + escapeHtml(user.name) + '</td>' +
                    '<td>' + escapeHtml(user.email) + '</td>' +
                    '<td>' + (user.department ? escapeHtml(user.department.name) : '--') + '</td>' +
                    '<td><span class="badge bg-info">' + (user.role ? escapeHtml(user.role.name) : '--') + '</span></td>' +
                    '<td><span class="badge ' + badgeClass + '">' + statusLabel + '</span></td>' +
                    '<td>' +
                        '<button class="btn btn-sm btn-outline-primary me-1" data-action="edit-user" data-user-id="' + user.id + '" title="Edit user">' +
                            '<i class="bi bi-pencil"></i>' +
                        '</button>' +
                        '<button class="btn btn-sm btn-outline-warning" data-action="toggle-user" data-user-id="' + user.id + '" title="' + toggleLabel + '">' +
                            '<i class="bi ' + toggleIcon + '"></i> ' + toggleLabel +
                        '</button>' +
                    '</td>' +
                    '</tr>';
            }).join('');
        }

        function renderPagination(meta) {
            if (!paginationContainer) return;

            if (meta.last_page <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            var html = '<nav><ul class="pagination pagination-sm mb-0">';

            if (meta.current_page > 1) {
                html += '<li class="page-item"><button class="page-link page-trigger" data-page="' + (meta.current_page - 1) + '">&laquo;</button></li>';
            }

            for (var i = 1; i <= meta.last_page; i++) {
                html += '<li class="page-item' + (meta.current_page === i ? ' active' : '') + '">' +
                    '<button class="page-link page-trigger" data-page="' + i + '">' + i + '</button></li>';
            }

            if (meta.current_page < meta.last_page) {
                html += '<li class="page-item"><button class="page-link page-trigger" data-page="' + (meta.current_page + 1) + '">&raquo;</button></li>';
            }

            html += '</ul></nav>';
            paginationContainer.innerHTML = html;

            paginationContainer.querySelectorAll('.page-trigger').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    currentFilters.page = parseInt(this.getAttribute('data-page'));
                    fetchUsers();
                });
            });
        }

        function fetchStats() {
            if (!statsUrl) return;

            fetch(statsUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var totalEl = document.getElementById('stat-total-users');
                var activeEl = document.getElementById('stat-active-users');
                var adminEl = document.getElementById('stat-admin-users');
                var deptEl = document.getElementById('stat-dept-count');

                if (totalEl) totalEl.textContent = data.total_users;
                if (activeEl) activeEl.textContent = data.active_users;
                if (adminEl) adminEl.textContent = data.admin_users;
                if (deptEl) deptEl.textContent = data.department_count;
            })
            .catch(function (err) {
                console.error("[users.js] Stats fetch failed:", err);
            });
        }

        function toggleUserStatus(userId) {
            if (!toggleUrlTemplate) return;

            var url = toggleUrlTemplate.replace('__ID__', userId);

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
                if (result.success) {
                    console.log("[users.js] Toggle result:", result.message);
                    fetchUsers();
                    fetchStats();
                }
            })
            .catch(function (err) {
                console.error("[users.js] Toggle failed:", err);
            });
        }

        function submitAddUser() {
            var form = document.getElementById('add-user-form');
            if (!form) return;

            var nameInput = document.getElementById('input-name');
            var emailInput = document.getElementById('input-email');
            var deptInput = document.getElementById('input-department');
            var roleInput = document.getElementById('input-role');
            var passwordInput = document.getElementById('input-password');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (formFeedback) {
                formFeedback.classList.add('d-none');
                formFeedback.classList.remove('alert-success', 'alert-danger');
            }

            var payload = {
                name: nameInput.value,
                email: emailInput.value,
                department_id: deptInput.value,
                role_id: roleInput.value,
                password: passwordInput.value
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
                if (result.success) {
                    if (formFeedback) {
                        formFeedback.textContent = result.message;
                        formFeedback.classList.remove('d-none', 'alert-danger');
                        formFeedback.classList.add('alert-success');
                    }

                    var modalEl = document.getElementById('addUserModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    form.reset();
                    fetchUsers();
                    fetchStats();
                } else {
                    if (formFeedback) {
                        formFeedback.textContent = result.message || 'Failed to create user.';
                        formFeedback.classList.remove('d-none', 'alert-success');
                        formFeedback.classList.add('alert-danger');
                    }
                }
            })
            .catch(function (err) {
                console.error("[users.js] Store failed:", err);
                if (formFeedback) {
                    formFeedback.textContent = 'Network error. Please try again.';
                    formFeedback.classList.remove('d-none', 'alert-success');
                    formFeedback.classList.add('alert-danger');
                }
            });
        }

        function openEditModal(userId) {
            var userData = cachedUsers.find(function (u) { return String(u.id) === String(userId); });
            if (!userData) {
                console.error("[users.js] User not found in cache:", userId);
                return;
            }

            document.getElementById('edit-user-id').value = userData.id;
            document.getElementById('edit-name').value = userData.name;
            document.getElementById('edit-email').value = userData.email;
            document.getElementById('edit-department').value = userData.department_id;
            document.getElementById('edit-role').value = userData.role_id;
            document.getElementById('edit-password').value = '';

            var fb = document.getElementById('edit-form-feedback');
            if (fb) {
                fb.classList.add('d-none');
                fb.classList.remove('alert-success', 'alert-danger');
            }

            var modalEl = document.getElementById('editUserModal');
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }

        function submitEditUser() {
            var form = document.getElementById('edit-user-form');
            if (!form) return;

            var userId = document.getElementById('edit-user-id').value;
            if (!userId) return;

            var nameInput = document.getElementById('edit-name');
            var emailInput = document.getElementById('edit-email');
            var deptInput = document.getElementById('edit-department');
            var roleInput = document.getElementById('edit-role');
            var passwordInput = document.getElementById('edit-password');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            var fb = document.getElementById('edit-form-feedback');
            if (fb) {
                fb.classList.add('d-none');
                fb.classList.remove('alert-success', 'alert-danger');
            }

            var payload = {
                name: nameInput.value,
                email: emailInput.value,
                department_id: deptInput.value,
                role_id: roleInput.value
            };

            if (passwordInput.value) {
                payload.password = passwordInput.value;
            }

            var url = updateUrlTemplate.replace('__ID__', userId);

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
                if (result.success) {
                    if (fb) {
                        fb.textContent = result.message;
                        fb.classList.remove('d-none', 'alert-danger');
                        fb.classList.add('alert-success');
                    }

                    var modalEl = document.getElementById('editUserModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    fetchUsers();
                    fetchStats();
                } else {
                    if (fb) {
                        fb.textContent = result.message || 'Failed to update user.';
                        fb.classList.remove('d-none', 'alert-success');
                        fb.classList.add('alert-danger');
                    }
                }
            })
            .catch(function (err) {
                console.error("[users.js] Update failed:", err);
                if (fb) {
                    fb.textContent = 'Network error. Please try again.';
                    fb.classList.remove('d-none', 'alert-success');
                    fb.classList.add('alert-danger');
                }
            });
        }

        function handleExport() {
            console.log("[users.js] Export triggered — no backend endpoint configured yet.");
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
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    });
})();
