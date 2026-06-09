/**
 * Authentication Layout Module - Document Tracking System
 * Manages client-side interaction toggles and assistance alerts for the login gateway.
 */
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const toggleIcon = document.getElementById('toggleIcon');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');

    // 1. Password Visibility Mask Toggle Trigger
    if (togglePasswordBtn && passwordInput && toggleIcon) {
        togglePasswordBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });
    }

    // 2. Administrator Communication Link Alert Prompt
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(event) {
            event.preventDefault();
            alert('Please contact your system administrator to reset your password.\n\nEmail: admin@company.com');
        });
    }
});
