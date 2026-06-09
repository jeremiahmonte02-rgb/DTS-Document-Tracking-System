<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login application form.
     */
    public function showLogin()
    {
        return view('login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        // 1. Validate the input fields without ever returning password input to the view.
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('rememberMe');

        // 2. Attempt authentication using Laravel Auth
        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // 3. Block inactive employee records
            if ($user->status !== 'active') {
                Auth::logout();
                throw ValidationException::withMessages([
                    'email' => 'This account is currently deactivated. Please contact your administrator.',
                ]);
            }

            // 4. Securely regenerate the session identifier string
            $request->session()->regenerate();

            // 5. Safely redirect to your dashboard landing page
            return redirect()->intended(route('dashboard'));
        }

        // 6. Return standard validation errors on generic lookup mismatches.
        // Laravel's exception handler keeps old('email') available and excludes password fields.
        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Log the user out of the application safely.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Flush application tracking keys and recreate token seeds
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
