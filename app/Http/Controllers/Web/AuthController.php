<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    // ─── Admin / Staff Login ────────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByUserType(Auth::user());
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        // Rate limiting: 15 attempts per 5 minutes
        $throttleKey = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 15)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$minutes} minute(s).",
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);

            $user = Auth::user();

            // Check if user account is active
            if ($user->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Contact your administrator.',
                ])->onlyInput('email');
            }

            // Block super_admin from using regular login
            if ($user->user_type === 'super_admin') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Invalid email or password.',
                ])->onlyInput('email');
            }

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Check subscription for admin/staff
            $company = $user->company;
            if ($company) {
                $subscription = $company->activeSubscription();
                if (!$subscription) {
                    $latest = $company->latestSubscription();
                    if (!$latest || !$latest->isInGracePeriod()) {
                        // Subscription fully expired — let them login but redirect to expired page
                        return redirect()->route('subscription.expired');
                    }
                }
            }

            return $this->redirectByUserType($user);
        }

        RateLimiter::hit($throttleKey, 300); // 5 minutes = 300 seconds

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    // ─── Super Admin Login (Hidden URL) ─────────────────────────────────

    public function showSaLogin()
    {
        if (Auth::check() && Auth::user()->user_type === 'super_admin') {
            return redirect()->route('superadmin.dashboard');
        }
        return view('superadmin.auth.login');
    }

    public function saLogin(Request $request)
    {
        // Rate limiting: 10 attempts per 10 minutes (stricter for SA)
        $throttleKey = 'sa-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);
            return back()->withErrors([
                'email' => "Account locked. Try again in {$minutes} minute(s).",
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // ONLY super_admin can login from SA portal
            if ($user->user_type !== 'super_admin') {
                Auth::logout();
                RateLimiter::hit($throttleKey, 600);
                return back()->withErrors([
                    'email' => 'Invalid credentials.',
                ])->onlyInput('email');
            }

            if ($user->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Account deactivated.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);
            $user->update(['last_login_at' => now()]);

            return redirect()->route('superadmin.dashboard');
        }

        RateLimiter::hit($throttleKey, 600); // 10 minutes

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->onlyInput('email');
    }

    // ─── Logout ─────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $userType = Auth::user()?->user_type;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // SA logout → SA login URL, Admin/Staff → regular login
        if ($userType === 'super_admin') {
            $slug = config('app.sa_login_slug', env('SA_LOGIN_SLUG', 'sa-portal'));
            return redirect("/{$slug}/login");
        }

        return redirect()->route('login');
    }

    // ─── Subscription Expired Page ──────────────────────────────────────

    public function subscriptionExpired()
    {
        $user = Auth::user();
        $company = $user?->company;
        $subscription = $company?->latestSubscription();

        return view('admin.subscription.expired', compact('user', 'company', 'subscription'));
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function redirectByUserType($user)
    {
        return match ($user->user_type) {
            'super_admin' => redirect()->route('superadmin.dashboard'),
            default => redirect()->route('admin.dashboard'),
        };
    }
}

