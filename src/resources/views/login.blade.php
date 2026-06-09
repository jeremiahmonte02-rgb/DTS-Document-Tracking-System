<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Document Tracking System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="login-card card shadow-sm">
                        <div class="login-header text-center py-4 bg-primary text-white rounded-top">
                            <i class="bi bi-file-earmark-lock2 fs-1"></i>
                            <h3 class="mt-3 mb-0">Document Tracking System</h3>
                            <p class="mb-0 mt-2 opacity-75 text-white-50">Secure Document Management</p>
                        </div>
                        <div class="card-body p-4">
                            
                            <form method="POST" action="{{ route('login.submit') }}" autocomplete="off">
                                @csrf

                                @if ($errors->any())
                                    <div class="alert alert-danger p-2 rounded text-sm mb-3">
                                        <ul class="mb-0 ps-3">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label for="email" class="form-label font-medium text-muted">Email Address</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}" 
                                           class="form-control" required autofocus placeholder="name@company.com">
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <label for="password" class="form-label font-medium text-muted">Password</label>
                                        <a href="#" id="forgotPasswordLink" class="text-sm text-primary text-decoration-none">Forgot Password?</a>
                                    </div>
                                    <div class="input-group">
                                        <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="new-password">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn">
                                            <i id="toggleIcon" class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3 form-check d-flex align-items-center">
                                    <input id="rememberMe" name="rememberMe" type="checkbox" class="form-check-input mt-0 me-2">
                                    <label for="rememberMe" class="form-check-label text-sm text-secondary">Remember me</label>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary w-full py-2 font-medium">
                                        Sign In
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center bg-light py-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check"></i> Secure Login - All access is logged
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="{{ asset('js/modules/login.js') }}"></script>
</body>
</html>
