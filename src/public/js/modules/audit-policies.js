/**
 * Document Type Routing Policies Module - Audit Portal
 * Handles policy selection, immutable toggle, route builder, and form serialization.
 */
document.addEventListener('DOMContentLoaded', function () {
    const routeList = document.getElementById('routeList');
    const routePlaceholder = document.getElementById('routePlaceholder');
    const routeContainer = document.getElementById('routeListContainer');
    const routeHiddenInput = document.getElementById('policyPredefinedRoute');

    window.selectPolicyType = function (row) {
        document.querySelectorAll('#policyTableBody tr').forEach(function (tr) {
            tr.classList.remove('active-row');
        });
        row.classList.add('active-row');

        const typeId = row.getAttribute('data-type-id');
        const typeName = row.getAttribute('data-type-name');
        const isImmutable = row.getAttribute('data-is-immutable');
        const predefinedRoute = JSON.parse(row.getAttribute('data-predefined-route') || '[]');

        document.getElementById('policyDocumentTypeId').value = typeId;
        document.getElementById('editorTitle').textContent = typeName;
        document.getElementById('editorSubtitle').textContent = 'Set the routing behavior for ' + typeName + '.';

        const isImmutableBool = isImmutable === '1';
        document.getElementById('immutableToggle').checked = isImmutableBool;
        updateToggleLabel(isImmutableBool);

        document.getElementById('policyIsImmutable').value = isImmutableBool ? '1' : '0';

        renderRoute(predefinedRoute);

        document.getElementById('editorPlaceholder').style.display = 'none';
        document.getElementById('editorContent').style.display = 'block';
    };

    window.toggleImmutable = function (checkbox) {
        const isImmutable = checkbox.checked;
        document.getElementById('policyIsImmutable').value = isImmutable ? '1' : '0';
        updateToggleLabel(isImmutable);
    };

    function updateToggleLabel(immutable) {
        var label = document.getElementById('modeLabel');
        if (immutable) {
            label.textContent = 'Immutable — route path is locked and cannot be changed during upload';
            label.className = 'toggle-label immutable';
        } else {
            label.textContent = 'Mutable — departments can be reordered during upload';
            label.className = 'toggle-label mutable';
        }
    }

    function renderRoute(routeArray) {
        routeList.innerHTML = '';
        if (routeArray && routeArray.length > 0) {
            routeArray.forEach(function (step, index) {
                addRouteItem(step.department_id, step.department_name || 'Department #' + step.department_id, step.route_order || (index + 1));
            });
            routePlaceholder.classList.add('d-none');
            routeList.classList.remove('d-none');
        } else {
            routePlaceholder.classList.remove('d-none');
            routeList.classList.add('d-none');
        }
        synchronizeRouteInput();
    }

    window.addSelectedDepts = function () {
        const pool = document.getElementById('deptPool');
        const selected = Array.from(pool.selectedOptions);
        if (selected.length === 0) return;

        selected.forEach(function (opt) {
            const deptId = opt.value;
            const deptName = opt.getAttribute('data-name');
            if (document.querySelector('#routeList li[data-dept-id="' + deptId + '"]')) return;
            addRouteItem(deptId, deptName, routeList.children.length + 1);
        });

        routePlaceholder.classList.add('d-none');
        routeList.classList.remove('d-none');
        synchronizeRouteInput();
    };

    function addRouteItem(deptId, deptName, order) {
        var li = document.createElement('li');
        li.className = 'route-item';
        li.setAttribute('data-dept-id', deptId);
        li.innerHTML =
            '<div class="d-flex align-items-center gap-2">' +
                '<span class="route-order-badge">' + order + '</span>' +
                '<span class="fw-medium">' + escapeHtml(deptName) + '</span>' +
            '</div>' +
            '<div class="d-flex gap-1">' +
                '<button type="button" class="btn-outline-move" onclick="moveRouteItem(this, -1)" title="Move Up"><i class="bi bi-chevron-up"></i></button>' +
                '<button type="button" class="btn-outline-move" onclick="moveRouteItem(this, 1)" title="Move Down"><i class="bi bi-chevron-down"></i></button>' +
                '<button type="button" class="btn-outline-remove" onclick="removeRouteItem(this)" title="Remove"><i class="bi bi-trash3"></i></button>' +
            '</div>';
        routeList.appendChild(li);
    }

    window.moveRouteItem = function (btn, direction) {
        var li = btn.closest('li');
        if (!li) return;
        if (direction === -1 && li.previousElementSibling) {
            li.parentNode.insertBefore(li, li.previousElementSibling);
        } else if (direction === 1 && li.nextElementSibling) {
            li.parentNode.insertBefore(li.nextElementSibling, li);
        }
        renumberRouteItems();
        synchronizeRouteInput();
    };

    window.removeRouteItem = function (btn) {
        var li = btn.closest('li');
        if (!li) return;
        li.remove();
        renumberRouteItems();
        synchronizeRouteInput();

        if (routeList.children.length === 0) {
            routePlaceholder.classList.remove('d-none');
            routeList.classList.add('d-none');
        }
    };

    window.clearRoute = function () {
        routeList.innerHTML = '';
        routePlaceholder.classList.remove('d-none');
        routeList.classList.add('d-none');
        synchronizeRouteInput();
    };

    function renumberRouteItems() {
        var items = routeList.querySelectorAll('li');
        items.forEach(function (li, index) {
            var badge = li.querySelector('.route-order-badge');
            if (badge) badge.textContent = index + 1;
        });
    }

    function synchronizeRouteInput() {
        var items = routeList.querySelectorAll('li');
        var data = [];
        items.forEach(function (li, index) {
            data.push({
                department_id: parseInt(li.getAttribute('data-dept-id')),
                route_order: index + 1
            });
        });
        routeHiddenInput.value = data.length > 0 ? JSON.stringify(data) : '';
    }

    window.resetEditor = function () {
        document.getElementById('policyForm').reset();
        document.getElementById('policyIsImmutable').value = '0';
        document.getElementById('policyPredefinedRoute').value = '';
        document.getElementById('editorPlaceholder').style.display = 'block';
        document.getElementById('editorContent').style.display = 'none';
        document.querySelectorAll('#policyTableBody tr').forEach(function (tr) {
            tr.classList.remove('active-row');
        });
        clearRoute();
    };

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
