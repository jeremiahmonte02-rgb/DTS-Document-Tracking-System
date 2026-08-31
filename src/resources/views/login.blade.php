<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Document Tracking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,200">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .login-input:focus {
            border-color: #0d6efd !important;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2) !important;
            outline: none;
        }
        .btn-primary-brand {
            background-color: #0d6efd;
            transition: all 0.2s ease-in-out;
        }
        .btn-primary-brand:hover {
            background-color: #0b5ed7 !important;
        }
        .forgot-link {
            color: #0d6efd;
            transition: all 0.2s ease-in-out;
        }
        .forgot-link:hover {
            color: #0b5ed7;
            text-decoration: underline !important;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col relative overflow-hidden" style="font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; background-color: #1e3a8a;">

        <div class="absolute inset-0 z-0 pointer-events-none" style="background: linear-gradient(180deg, rgba(30, 58, 138, 1) 0%, rgba(30, 64, 175, 1) 100%);"></div>

        <main class="flex-grow flex items-center justify-center p-4 md:p-6 z-10">
            <div class="w-full max-w-[448px]">

                <div class="bg-white border border-gray-200 rounded-[28px] overflow-hidden transition-all duration-300"
                     style="box-shadow: 0px 24px 80px rgba(0, 0, 0, 0.05);">

                    <div class="px-12 pt-12 pb-6 text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full mb-4" style="background-color: rgba(13, 110, 253, 0.08);">
                            <span class="material-symbols-outlined text-3xl" style="color: #0d6efd; font-variation-settings: 'FILL' 1;">shield</span>
                        </div>
                        <h1 class="text-[24px] leading-[32px] font-bold text-gray-900 mb-1" style="font-family: 'Manrope', sans-serif;">
                            Document Tracking System
                        </h1>
                        <p class="text-sm text-gray-500">Secure Document Management</p>
                    </div>

                    <form method="POST" action="{{ route('login.submit') }}" autocomplete="on" class="px-12 pb-12 space-y-6" onsubmit="let btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<span class=\'flex items-center justify-center gap-2\'><span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span> Signing In...</span>';">
                        @csrf

                        @if ($errors->any())
                            <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-xl text-sm mb-2">
                                <ul class="mb-0 ps-4 list-disc space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="space-y-1">
                            <label for="email" class="block text-sm font-semibold text-gray-800">Email Address</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="name@company.com" autocomplete="one-time-code"
                                   class="login-input w-full rounded-[20px] border bg-white px-4 py-2 text-sm text-gray-900 transition-all" style="border-color: #d1d5db;" />
                        </div>

                        <div class="space-y-1">
                            <div class="flex justify-between items-center">
                                <label for="password" class="block text-sm font-semibold text-gray-800">Password</label>
                                <a href="#" id="forgotPasswordLink" class="forgot-link text-sm font-semibold" style="text-decoration: none;">Forgot Password?</a>
                            </div>
                            <div class="relative">
                                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="one-time-code"
                                       class="login-input w-full rounded-[20px] border bg-white px-4 py-2 text-sm text-gray-900 transition-all" style="border-color: #d1d5db;" />
                                <button id="togglePasswordBtn" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" type="button">
                                    <span id="toggleIcon" class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2 pt-1">
                            <input id="rememberMe" name="rememberMe" type="checkbox" class="h-4 w-4 rounded border-gray-300 cursor-pointer" style="color: #0d6efd;" />
                            <label for="rememberMe" class="text-sm text-gray-500 cursor-pointer select-none">Remember me</label>
                        </div>

                        <button type="submit" class="btn-primary-brand w-full rounded-[20px] text-white font-semibold py-2 text-sm active:scale-[0.99] transition-all shadow-md" style="border: none;">
                            <span class="flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">login</span>
                                Sign In
                            </span>
                        </button>
                    </form>

                    <div class="bg-gray-50 py-4 px-12 flex items-center justify-center space-x-2 border-t border-gray-100">
                        <span class="material-symbols-outlined text-[18px] text-gray-400" style="font-variation-settings: 'FILL' 1;">verified_user</span>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Secure Login — All access is logged</p>
                    </div>

                </div>

                <div class="mt-6 flex justify-center space-x-6 text-sm" style="color: rgba(255, 255, 255, 0.7);">
                    <a class="hover:underline transition-colors" href="#" style="color: rgba(255, 255, 255, 0.7);">Privacy Policy</a>
                    <a class="hover:underline transition-colors" href="#" style="color: rgba(255, 255, 255, 0.7);">Terms of Service</a>
                    <a class="hover:underline transition-colors" href="#" style="color: rgba(255, 255, 255, 0.7);">Contact Support</a>
                </div>
            </div>
        </main>

        <footer class="mt-auto py-6 text-center border-t" style="border-color: rgba(255, 255, 255, 0.1);">
            <p class="text-xs" style="color: rgba(255, 255, 255, 0.5);">&copy; 2026 DocTrack Enterprise. Secure 256-bit SSL Encrypted Access.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/modules/login.js') }}"></script>
</body>
</html>
