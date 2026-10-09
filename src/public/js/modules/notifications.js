// Notifications history page: mark read without removing rows.
// Uses its own data-feed-* attributes so the dropdown's remove-on-success
// delegated handlers (which key off data-notification-id /
// data-announcement-id) never fire here.
(function () {
    'use strict';

    function unboldRow(row) {
        if (!row) return;
        row.classList.remove('notif-unread');
        row.querySelectorAll('.feed-title').forEach(function (t) {
            t.classList.remove('fw-bold');
            t.classList.add('fw-normal');
        });
    }

    function markRead(url) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            keepalive: true
        })
        .then(function (response) {
            if (!response.ok) throw new Error('mark-as-read failed with status ' + response.status);
            return response.json();
        })
        .then(function (data) {
            if (!data.success) throw new Error('mark-as-read returned success=false');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var list = document.getElementById('notificationsList');
        if (!list || typeof csrfToken !== 'function') return;

        list.querySelectorAll('[data-feed-notification-id], [data-feed-announcement-id]').forEach(function (el) {
            el.addEventListener('click', function (event) {
                var notifId = el.getAttribute('data-feed-notification-id');
                var annId = el.getAttribute('data-feed-announcement-id');
                var url = notifId
                    ? '/notifications/' + encodeURIComponent(notifId) + '/read'
                    : '/announcements/' + encodeURIComponent(annId) + '/read';
                var targetUrl = el.getAttribute('href');
                var navigate = targetUrl && targetUrl !== '#' && targetUrl !== window.location.href;

                if (navigate) event.preventDefault();

                markRead(url).then(function () {
                    unboldRow(el.closest('.list-group-item') || el);
                }).catch(function (error) {
                    console.error('Page mark-as-read failed:', error);
                });

                if (navigate) window.location.href = targetUrl;
            });
        });

        var markAllBtn = document.getElementById('pageMarkAllRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (markAllBtn.disabled) return;
                markAllBtn.disabled = true;

                var headers = {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                };

                Promise.all([
                    fetch('/notifications/read-all', { method: 'POST', headers: headers, keepalive: true }),
                    fetch('/announcements/read-all', { method: 'POST', headers: headers, keepalive: true })
                ])
                .then(function (responses) {
                    if (!responses[0].ok || !responses[1].ok) throw new Error('Dismiss all failed');
                    return Promise.all([responses[0].json(), responses[1].json()]);
                })
                .then(function () {
                    list.querySelectorAll('.list-group-item').forEach(unboldRow);
                })
                .catch(function (error) {
                    console.error('Page mark-all-read failed:', error);
                })
                .finally(function () {
                    markAllBtn.disabled = false;
                });
            });
        }
    });
})();
