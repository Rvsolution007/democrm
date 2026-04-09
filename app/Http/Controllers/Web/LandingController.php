<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    // ─── Landing Pages ─────────────────────────────────────────────────
    public function index()
    {
        return view('landing.home');
    }

    public function features()
    {
        return view('landing.features');
    }

    public function about()
    {
        return view('landing.about');
    }

    public function packages()
    {
        $packages = SubscriptionPackage::active()->ordered()->get();
        return view('landing.packages', compact('packages'));
    }

    public function faq()
    {
        return view('landing.faq');
    }

    public function reviews()
    {
        return view('landing.reviews');
    }

    public function contact()
    {
        return view('landing.contact');
    }

    // ─── Registration ──────────────────────────────────────────────────
    public function showRegister($packageSlug)
    {
        $package = SubscriptionPackage::where('slug', $packageSlug)->active()->firstOrFail();
        return view('register', compact('package'));
    }

    public function checkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $exists = User::where('email', $request->email)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function register(Request $request)
    {
        // Rate limit: 5 registrations per hour per IP
        $key = 'register:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Too many attempts. Please try again later.'])->withInput();
        }

        $validated = $request->validate([
            'package_id'     => 'required|exists:subscription_packages,id',
            'business_name'  => 'required|string|max:255',
            'admin_name'     => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'required|string|max:20',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        $package = SubscriptionPackage::findOrFail($validated['package_id']);

        DB::beginTransaction();
        try {
            // 1. Create Company
            $company = Company::create([
                'name'                => $validated['business_name'],
                'email'               => $validated['email'],
                'phone'               => $validated['phone'],
                'default_gst_percent' => 18,
                'quote_prefix'        => 'Q',
                'quote_fy_format'     => 'YY-YY',
                'status'              => 'active',
            ]);

            // 2. Create Admin Role
            $adminRole = Role::create([
                'company_id'  => $company->id,
                'name'        => 'Admin',
                'slug'        => 'admin',
                'description' => 'Full access to all features',
                'permissions' => ['all'],
                'is_system'   => true,
            ]);

            // 3. Create Sales Role
            Role::create([
                'company_id'  => $company->id,
                'name'        => 'Sales',
                'slug'        => 'sales',
                'description' => 'Access to leads, clients, quotes',
                'permissions' => ['leads.read','leads.write','clients.read','clients.write','quotes.read','quotes.write','products.read','categories.read','activities.read','activities.write','tasks.read','tasks.write'],
                'is_system'   => true,
            ]);

            // 4. Create Admin User
            $user = User::create([
                'company_id'        => $company->id,
                'role_id'           => $adminRole->id,
                'user_type'         => 'admin',
                'name'              => $validated['admin_name'],
                'email'             => $validated['email'],
                'phone'             => $validated['phone'],
                'password'          => $validated['password'],
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);

            // 5. Set owner
            $company->update(['owner_user_id' => $user->id]);

            // 6. Create Subscription
            $trialDays = $package->trial_days ?? 0;
            $startsAt  = now();
            $status    = $trialDays > 0 ? 'trial' : 'active';
            $expiresAt = $trialDays > 0
                ? now()->addDays($trialDays)
                : now()->addMonth();

            Subscription::create([
                'company_id'    => $company->id,
                'package_id'    => $package->id,
                'status'        => $status,
                'billing_cycle' => 'monthly',
                'amount_paid'   => 0,
                'max_users'     => $package->default_max_users,
                'starts_at'     => $startsAt,
                'expires_at'    => $expiresAt,
                'trial_ends_at' => $trialDays > 0 ? $expiresAt : null,
                'created_by'    => $user->id,
                'notes'         => 'Self-registration from landing page',
            ]);

            DB::commit();

            RateLimiter::hit($key, 3600);

            // Auto-login
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard')->with('success', 'Welcome! Your account has been created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['email' => 'Registration failed. Please try again.'])->withInput();
        }
    }

    // ─── Forgot Password ───────────────────────────────────────────────
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Rate limit: 3 codes per hour per email
        $key = 'reset-code:' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Too many attempts. Please try again later.'])->withInput();
        }

        $user = User::where('email', $request->email)->where('status', 'active')->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No active account found with this email address.'])->withInput();
        }

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code (delete old ones first)
        DB::table('password_reset_codes')->where('email', $request->email)->delete();
        DB::table('password_reset_codes')->insert([
            'email'      => $request->email,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        // Send email
        try {
            Mail::raw("Your password reset code is: {$code}\n\nThis code will expire in 15 minutes.\n\nIf you didn't request this, please ignore this email.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Password Reset Code - VyaparCRM');
            });
        } catch (\Exception $e) {
            // Log the error but don't expose it
            \Log::error('Failed to send reset email: ' . $e->getMessage());
        }

        // Also store in DB for contact form
        DB::table('password_reset_codes')->where('email', $request->email)->update([]);

        RateLimiter::hit($key, 3600);

        return redirect()->route('password.reset', ['email' => $request->email])
            ->with('success', 'A 6-digit reset code has been sent to your email.');
    }

    public function showResetPassword(Request $request)
    {
        $email = $request->query('email', '');
        return view('auth.reset-password', compact('email'));
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'code'     => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $validated['email'])
            ->first();

        if (!$record) {
            return back()->withErrors(['code' => 'Invalid or expired reset code.'])->withInput();
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table('password_reset_codes')->where('email', $validated['email'])->delete();
            return back()->withErrors(['code' => 'Reset code has expired. Please request a new one.'])->withInput();
        }

        if (!Hash::check($validated['code'], $record->code)) {
            return back()->withErrors(['code' => 'Invalid reset code.'])->withInput();
        }

        // Reset password
        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No account found.'])->withInput();
        }

        $user->update([
            'password'            => $validated['password'],
            'password_changed_at' => now(),
        ]);

        // Cleanup
        DB::table('password_reset_codes')->where('email', $validated['email'])->delete();

        return redirect()->route('login')->with('success', 'Password reset successfully! Please login with your new password.');
    }

    // ─── Contact Form ──────────────────────────────────────────────────
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Store in DB
        DB::table('contact_messages')->insert([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'subject'    => $validated['subject'],
            'message'    => $validated['message'],
            'created_at' => now(),
        ]);

        // Send email
        try {
            $body = "New Contact Form Submission\n\n"
                . "Name: {$validated['name']}\n"
                . "Email: {$validated['email']}\n"
                . "Phone: " . ($validated['phone'] ?? 'N/A') . "\n"
                . "Subject: {$validated['subject']}\n\n"
                . "Message:\n{$validated['message']}";

            Mail::raw($body, function ($message) use ($validated) {
                $message->to(config('mail.from.address'))
                    ->replyTo($validated['email'], $validated['name'])
                    ->subject('Contact Form: ' . $validated['subject']);
            });
        } catch (\Exception $e) {
            \Log::error('Contact form email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Thank you! Your message has been sent successfully.');
    }
}
