<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Document Tracking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}">
</head>
<body class="login-page">
    <main class="login-stage">
        <div class="login-wrap">

            <div class="login-frame">
                <div class="login-card">

                    <div class="login-card-head">
                        <span class="login-medallion" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                        <h1 class="login-title">Document Tracking System</h1>
                        <p class="login-sub">Secure Document Management</p>
                        <div class="login-trust-row">
                            <span class="login-trust-dot" aria-hidden="true"></span>
                            <span>Protected sign-in for authorized staff</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('login.submit') }}" autocomplete="on" class="login-form" onsubmit="let btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<span class=\'d-flex align-items-center justify-content-center gap-2\'><span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span> Signing In...</span>';">
                        @csrf

                        @unless (session('department_deactivated'))
                            @if ($errors->any())
                                <div class="alert alert-danger login-error mb-3" role="alert">
                                    <ul class="mb-0 ps-4">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endunless

                        <div class="mb-3">
                            <label for="email" class="form-label login-field-label">Email Address</label>
                            <div class="login-input-wrap">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="name@company.com" autocomplete="username" class="form-control login-input">
                            </div>
                        </div>

                        <div class="mb-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label login-field-label mb-0">Password</label>
                                <a href="#" id="forgotPasswordLink" class="login-forgot-link">Forgot Password?</a>
                            </div>
                            <div class="login-input-wrap">
                                <i class="bi bi-key" aria-hidden="true"></i>
                                <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password" class="form-control login-input login-input-pad-right">
                                <button id="togglePasswordBtn" class="login-pw-toggle" type="button" aria-label="Show password">
                                    <i id="toggleIcon" class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="login-signin mt-4">
                            <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign In
                        </button>
                    </form>

                    <div class="login-secure-strip">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        <span>Secure login — all access is logged</span>
                    </div>

                </div>
            </div>

            <div class="login-bottom-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Support</a>
            </div>
            <p class="login-caption">Internal system — authorized use only</p>
        </div>
    </main>

    <footer class="login-footer">
        <p>&copy; 2026 DocTrack Enterprise. Secure 256-bit SSL Encrypted Access.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/modules/login.js') }}?v={{ filemtime(public_path('js/modules/login.js')) }}"></script>
    <script src="{{ asset('js/modules/confirm-modal.js') }}?v={{ filemtime(public_path('js/modules/confirm-modal.js')) }}"></script>

    @if (session('department_deactivated'))
        <!-- Department Deactivated Error Modal -->
        <div class="modal fade" id="departmentDeactivatedModal" tabindex="-1" aria-labelledby="departmentDeactivatedLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="departmentDeactivatedLabel">
                            <i class="bi bi-shield-x"></i> Access Denied
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-1" aria-hidden="true"></i>
                            <h5 class="mt-3">Department Deactivated</h5>
                            <p class="text-muted">
                                Your department has been deactivated. Please contact an administrator to restore access.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    new bootstrap.Modal(document.getElementById('departmentDeactivatedModal')).show();
                }
            });
        </script>
    @endif
    @include('partials.confirm-modal')
</body>
</html>
