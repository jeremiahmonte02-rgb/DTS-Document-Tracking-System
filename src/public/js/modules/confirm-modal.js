(function () {
    'use strict';

    var modalInstance = null;
    var previousConfirmHandler = null;

    function getModalEl() {
        return document.getElementById('globalConfirmModal');
    }

    function $(selector) {
        var el = getModalEl();
        return el ? el.querySelector(selector) : null;
    }

    function getVariantConfig(variant) {
        var variants = {
            success: { iconClass: 'bi bi-check-circle-fill', headerBg: 'bg-success-subtle', btnClass: 'btn-success' },
            warning: { iconClass: 'bi bi-exclamation-triangle-fill', headerBg: 'bg-warning-subtle', btnClass: 'btn-warning' },
            danger: { iconClass: 'bi bi-exclamation-triangle-fill', headerBg: 'bg-danger-subtle', btnClass: 'btn-danger' }
        };
        return variants[variant] || variants.success;
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function showConfirmModal(options) {
        var modalEl = getModalEl();
        if (!modalEl) return;
        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalEl);
        }

        var opts = Object.assign({
            title: 'Confirm',
            message: '',
            confirmLabel: 'Confirm',
            variant: 'success',
            onConfirm: null
        }, options);

        var config = getVariantConfig(opts.variant);

        var icon = $('.confirm-modal-icon');
        if (icon) {
            icon.className = 'confirm-modal-icon variant-' + opts.variant;
            var iconEl = icon.querySelector('i');
            if (iconEl) iconEl.className = config.iconClass;
        }

        var header = $('.confirm-modal-header');
        if (header) header.className = 'modal-header confirm-modal-header ' + config.headerBg;

        var titleEl = document.getElementById('globalConfirmModalLabel');
        if (titleEl) titleEl.textContent = opts.title;

        var body = $('.confirm-modal-body');
        if (body) body.className = 'modal-body confirm-modal-body variant-' + opts.variant;

        var msgEl = $('.confirm-modal-message');
        if (msgEl) msgEl.innerHTML = escapeHtml(opts.message);

        var btn = $('.confirm-modal-btn');
        if (btn) {
            btn.className = 'btn ' + config.btnClass + ' confirm-modal-btn';
            btn.textContent = opts.confirmLabel;
            if (previousConfirmHandler) btn.removeEventListener('click', previousConfirmHandler);
            var handler = function () {
                if (typeof opts.onConfirm === 'function') opts.onConfirm();
                if (modalInstance) modalInstance.hide();
            };
            btn.addEventListener('click', handler);
            previousConfirmHandler = handler;
        }

        modalInstance.show();
    }

    window.showConfirmModal = showConfirmModal;
})();