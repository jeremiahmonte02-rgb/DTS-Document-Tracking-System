/**
 * Authentication Layout Module - Document Tracking System
 * Manages client-side interaction toggles and assistance alerts for the login gateway.
 */
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const toggleIcon = document.getElementById('toggleIcon');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');

    // 1. Password Visibility Mask Toggle Trigger (Bootstrap Icons; legacy Material Symbols fallback)
    if (togglePasswordBtn && passwordInput && toggleIcon) {
        togglePasswordBtn.addEventListener('click', function() {
            var show = passwordInput.type === 'password';
            passwordInput.type = show ? 'text' : 'password';
            if (toggleIcon.classList && toggleIcon.classList.contains('bi')) {
                toggleIcon.classList.toggle('bi-eye', !show);
                toggleIcon.classList.toggle('bi-eye-slash', show);
            } else {
                toggleIcon.textContent = show ? 'visibility_off' : 'visibility';
            }
            togglePasswordBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    }

    // 2. Administrator Communication Link — informational modal
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(event) {
            event.preventDefault();
            if (typeof window.showConfirmModal === 'function') {
                window.showConfirmModal({
                    title: 'Forgot Password',
                    message: 'Please contact your system administrator to reset your password. Email: admin@company.com',
                    confirmLabel: 'OK',
                    variant: 'success',
                    showCancel: false
                });
            } else {
                alert('Please contact your system administrator to reset your password.\n\nEmail: admin@company.com');
            }
        });
    }
});
