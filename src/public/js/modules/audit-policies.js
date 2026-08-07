/**
 * Document Type Routing Policies Module - Audit Portal
 * Handles policy selection, immutable toggle, route builder, and form serialization.
 */
document.addEventListener('DOMContentLoaded', function () {
    const routeList = document.getElementById('routeList');
    const routePlaceholder = document.getElementById('routePlaceholder');
    const routeContainer = document.getElementById('routeListContainer');
    const routeHiddenInput = document.getElementById('policyPredefinedRoute');
    const totalHiddenSla = document.getElementById('total_lifecycle_sla');
    const lifecycleTimeValue = document.getElementById('lifecycle_time_value');
    const lifecycleTimeUnit = document.getElementById('lifecycle_time_unit');
    const savePolicyBtn = document.querySelector('#policyForm button[type="submit"]');

    window.updateHiddenTotalSla = function () {
        var value = parseInt(lifecycleTimeValue.value);
        var multiplier = parseInt(lifecycleTimeUnit.value);
        if (isNaN(value) || value < 1 || isNaN(multiplier)) {
            totalHiddenSla.value = '';
            return;
        }
        totalHiddenSla.value = Math.floor(value * multiplier);
    };

    window.validateSlaTotals = function () {
        var errorMsg = document.getElementById('slaTotalError');
        if (!totalHiddenSla || !savePolicyBtn) return;

        var total = parseInt(totalHiddenSla.value);
        if (isNaN(total) || total < 1) {
            if (errorMsg) errorMsg.textContent = '';
            savePolicyBtn.disabled = false;
            return;
        }

        var sum = 0;
        var slaInputs = routeList.querySelectorAll('.route-sla-input');
        slaInputs.forEach(function (input) {
            var val = parseInt(input.value);
            if (!isNaN(val) && val > 0) sum += val;
        });

        if (sum > total) {
            if (!errorMsg) {
                errorMsg = document.createElement('div');
                errorMsg.id = 'slaTotalError';
                errorMsg.className = 'text-danger small mt-1';
                lifecycleTimeValue.closest('.mb-4').appendChild(errorMsg);
            }
            errorMsg.textContent = 'Error: Total step SLA (' + sum + ' mins) exceeds lifecycle SLA (' + total + ' mins).';
            savePolicyBtn.disabled = true;
        } else {
            if (errorMsg) errorMsg.textContent = '';
            savePolicyBtn.disabled = false;
        }
    };

    window.autoBalanceLastStep = function (changedInput) {
        var items = routeList.querySelectorAll('li');
        var count = items.length;
        if (count < 2) return;

        var lastLi = items[count - 1];
        if (changedInput === lastLi.querySelector('.route-sla-input')) return;

        var total = parseInt(totalHiddenSla.value);
        if (isNaN(total) || total < 1) return;

        var precedingSum = 0;
        for (var i = 0; i < count - 1; i++) {
            var val = parseInt(items[i].querySelector('.route-sla-input').value);
            if (!isNaN(val) && val > 0) precedingSum += val;
        }

        var remaining = total - precedingSum;
        var lastInput = lastLi.querySelector('.route-sla-input');
        if (remaining >= 1) {
            lastInput.value = remaining;
        } else {
            lastInput.value = '';
        }
        synchronizeRouteInput();
        validateSlaTotals();
    };

    function distributeAndValidate() {
        updateHiddenTotalSla();
        var total = parseInt(totalHiddenSla.value);
        var items = routeList.querySelectorAll('li');
        var stepCount = items.length;
        if (isNaN(total) || total < 1 || stepCount === 0) {
            synchronizeRouteInput();
            validateSlaTotals();
            return;
        }

        var base = Math.floor(total / stepCount);
        var remainder = total - (base * stepCount);

        items.forEach(function (li, index) {
            var slaInput = li.querySelector('.route-sla-input');
            if (slaInput) {
                var val = base + (index === stepCount - 1 ? remainder : 0);
                slaInput.value = val;
            }
        });
        synchronizeRouteInput();
        validateSlaTotals();
    }

    if (lifecycleTimeValue) {
        lifecycleTimeValue.addEventListener('input', distributeAndValidate);
    }
    if (lifecycleTimeUnit) {
        lifecycleTimeUnit.addEventListener('change', distributeAndValidate);
    }

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

    function resolveDeptName(deptId) {
        var opt = document.querySelector('#deptPool option[value="' + deptId + '"]');
        return opt ? opt.getAttribute('data-name') : 'Department #' + deptId;
    }

    function renderRoute(routeArray) {
        routeList.innerHTML = '';
        if (routeArray && routeArray.length > 0) {
            routeArray.forEach(function (step, index) {
                addRouteItem(step.department_id, step.department_name || resolveDeptName(step.department_id), step.route_order || (index + 1), step.sla_minutes);
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

    function addRouteItem(deptId, deptName, order, slaMinutes) {
        var li = document.createElement('li');
        li.className = 'route-item';
        li.setAttribute('data-dept-id', deptId);
        li.innerHTML =
            '<span class="route-order-badge">' + order + '</span>' +
            '<span class="fw-medium flex-grow-1 text-truncate">' + escapeHtml(deptName) + '</span>' +
            '<div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">' +
                '<input type="number" class="route-sla-input form-control form-control-sm" min="1" style="width: 80px;" placeholder="SLA" value="' + (slaMinutes || '') + '" oninput="autoBalanceLastStep(this); validateSlaTotals(); synchronizeRouteInput();">' +
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
            var slaInput = li.querySelector('.route-sla-input');
            var slaVal = slaInput ? parseInt(slaInput.value) : null;
            data.push({
                department_id: parseInt(li.getAttribute('data-dept-id')),
                route_order: index + 1,
                sla_minutes: isNaN(slaVal) || slaVal < 1 ? null : slaVal
            });
        });
        routeHiddenInput.value = data.length > 0 ? JSON.stringify(data) : '';
    }

    window.resetEditor = function () {
        document.getElementById('policyForm').reset();
        document.getElementById('policyIsImmutable').value = '0';
        document.getElementById('policyPredefinedRoute').value = '';
        document.getElementById('total_lifecycle_sla').value = '';
        if (lifecycleTimeValue) lifecycleTimeValue.value = '';
        if (lifecycleTimeUnit) lifecycleTimeUnit.selectedIndex = 0;
        var errorMsg = document.getElementById('slaTotalError');
        if (errorMsg) errorMsg.textContent = '';
        if (savePolicyBtn) savePolicyBtn.disabled = false;
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
