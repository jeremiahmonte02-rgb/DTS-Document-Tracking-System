// Shared route-step status presentation for live timeline rendering.
//
// Single implementation of the status → color mapping already used by
// document-details.blade.php (server render) and scan.js Part B — any future
// live renderer must use this module instead of inventing another copy.
(function () {
    'use strict';

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function styles(status) {
        var s = String(status || '').toLowerCase();
        if (s === 'current') {
            return { badge: 'bg-warning text-dark fw-bold', row: 'border-warning bg-warning-subtle bg-opacity-10' };
        }
        if (s === 'received' || s === 'completed') {
            return { badge: 'bg-success text-white', row: 'border-success-subtle' };
        }
        if (s === 'next') {
            return { badge: 'bg-info text-white', row: 'border-info-subtle' };
        }
        return { badge: 'bg-secondary text-white', row: 'border-light-subtle bg-white' };
    }

    // Build one timeline row matching the Blade markup in
    // document-details.blade.php (order circle, department name, uppercase
    // status tag, data-dept-id / data-dept-name hooks).
    function rowElement(route) {
        var st = styles(route.status);
        var row = document.createElement('div');
        row.className = 'p-2 border rounded d-flex align-items-center justify-content-between ' + st.row;
        row.setAttribute('data-dept-id', route.department_id);
        row.setAttribute('data-dept-name', route.department_name || '');
        row.innerHTML = ''
            + '<div class="d-flex align-items-center">'
            + '<span class="badge ' + st.badge + ' rounded-circle me-2 font-mono d-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">'
            + esc(route.route_order) + '</span>'
            + '<span class="fw-semibold text-xs text-dark">' + esc(route.department_name) + '</span>'
            + '</div>'
            + '<span class="badge text-uppercase text-xxs px-2 py-1 ' + st.badge + '">'
            + esc(String(route.status || '').toUpperCase()) + '</span>';
        return row;
    }

    function renderInto(container, routes) {
        while (container.firstChild) container.removeChild(container.firstChild);
        (routes || []).forEach(function (route) {
            container.appendChild(rowElement(route));
        });
    }

    function isUserNext(routes, departmentId) {
        if (!departmentId) return false;
        return (routes || []).some(function (route) {
            return String(route.status || '').toLowerCase() === 'next'
                && String(route.department_id) === String(departmentId);
        });
    }

    window.RouteStatus = {
        styles: styles,
        rowElement: rowElement,
        renderInto: renderInto,
        isUserNext: isUserNext
    };
})();
