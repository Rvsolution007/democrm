<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use App\Models\SubscriptionPayment;
use App\Models\AiCreditWallet;
use App\Models\AiCreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BusinessController extends Controller
{
    // ─── List All Businesses ────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Company::with(['owner', 'users', 'subscriptions.package', 'wallet']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by subscription status
        if ($filter = $request->input('filter')) {
            switch ($filter) {
                case 'active':
                    $query->whereHas('subscriptions', fn($q) =>
                        $q->whereIn('status', ['active', 'trial'])->where('expires_at', '>=', now()->toDateString()));
                    break;
                case 'expired':
                    $query->whereHas('subscriptions', fn($q) =>
                        $q->where('expires_at', '<', now()->toDateString()));
                    break;
                case 'trial':
                    $query->whereHas('subscriptions', fn($q) =>
                        $q->where('status', 'trial')->where('expires_at', '>=', now()->toDateString()));
                    break;
                case 'suspended':
                    $query->where('status', 'suspended');
                    break;
                case 'expiring':
                    $query->whereHas('subscriptions', fn($q) =>
                        $q->whereIn('status', ['active', 'trial'])
                          ->where('expires_at', '>=', now()->toDateString())
                          ->where('expires_at', '<=', now()->addDays(7)->toDateString()));
                    break;
            }
        }

        // Filter by package
        if ($package = $request->input('package')) {
            $query->whereHas('subscriptions', fn($q) =>
                $q->where('package_id', $package)
                  ->whereIn('status', ['active', 'trial'])
                  ->where('expires_at', '>=', now()->toDateString()));
        }

        $businesses = $query->latest()->paginate(15)->withQueryString();
        $packages = SubscriptionPackage::active()->ordered()->get();

        // Stats
        $totalBusinesses = Company::count();
        $activeCount = Subscription::active()->distinct('company_id')->count('company_id');
        $expiredCount = Company::whereDoesntHave('subscriptions', fn($q) =>
            $q->whereIn('status', ['active', 'trial'])->where('expires_at', '>=', now()->toDateString())
        )->count();

        return view('superadmin.businesses.index', compact(
            'businesses', 'packages', 'totalBusinesses', 'activeCount', 'expiredCount'
        ));
    }

    // ─── Create Business Form ───────────────────────────────────────────

    public function create()
    {
        $packages = SubscriptionPackage::active()->ordered()->get();
        return view('superadmin.businesses.create', compact('packages'));
    }

    // ─── Store New Business ─────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => 'required|string|max:20',
            'owner_password' => 'required|string|min:6',
            'package_id' => 'required|exists:subscription_packages,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'subscription_days' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'payment_method' => 'required|in:manual,razorpay,free',
            'amount_paid' => 'nullable|numeric|min:0',
            'initial_credits' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Company
            $company = Company::create([
                'name' => $request->company_name,
                'phone' => $request->owner_phone,
                'email' => $request->owner_email,
                'status' => 'active',
            ]);

            // 2. Create Admin Role for this company
            $adminRole = Role::create([
                'company_id' => $company->id,
                'name' => 'Admin',
                'permissions' => json_encode(['all']),
                'is_system' => true,
            ]);

            // 3. Create Owner User
            $owner = User::create([
                'user_type' => 'admin',
                'company_id' => $company->id,
                'role_id' => $adminRole->id,
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'phone' => $request->owner_phone,
                'password' => $request->owner_password,
                'status' => 'active',
            ]);

            // 4. Link owner to company
            $company->update(['owner_user_id' => $owner->id]);

            // 5. Create Subscription
            $package = SubscriptionPackage::find($request->package_id);
            $days = (int) ($request->subscription_days ?? ($request->billing_cycle === 'yearly' ? 365 : 30));
            $amount = $request->amount_paid ?? ($request->billing_cycle === 'yearly' ? $package->yearly_price : $package->monthly_price);

            $subscription = Subscription::create([
                'company_id' => $company->id,
                'package_id' => $package->id,
                'status' => $request->payment_method === 'free' ? 'trial' : 'active',
                'billing_cycle' => $request->billing_cycle,
                'amount_paid' => $amount,
                'max_users' => $request->max_users ?? $package->default_max_users,
                'starts_at' => now(),
                'expires_at' => now()->addDays($days),
                'trial_ends_at' => $request->payment_method === 'free' ? now()->addDays($package->trial_days ?? 14) : null,
                'created_by' => auth()->id(),
            ]);

            // 6. Record Payment (if paid)
            if ($request->payment_method !== 'free' && $amount > 0) {
                SubscriptionPayment::create([
                    'subscription_id' => $subscription->id,
                    'company_id' => $company->id,
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'status' => 'completed',
                    'admin_notes' => 'Initial subscription payment by SA',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);
            }

            // 7. Create AI Credit Wallet
            $initialCredits = $request->initial_credits ?? 500;
            $wallet = AiCreditWallet::create([
                'company_id' => $company->id,
                'balance' => $initialCredits,
                'total_purchased' => $initialCredits,
                'total_consumed' => 0,
                'low_balance_threshold' => 100,
            ]);

            if ($initialCredits > 0) {
                AiCreditTransaction::create([
                    'company_id' => $company->id,
                    'wallet_id' => $wallet->id,
                    'type' => 'bonus',
                    'credits' => $initialCredits,
                    'balance_after' => $initialCredits,
                    'description' => 'Welcome bonus credits',
                ]);
            }

            DB::commit();

            return redirect()->route('superadmin.businesses.show', $company->id)
                ->with('success', "Business '{$company->name}' created successfully with {$package->name} subscription!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create business: ' . $e->getMessage());
        }
    }

    // ─── Show Business Detail ───────────────────────────────────────────

    public function show(Company $company)
    {
        $company->load(['owner', 'users.role', 'wallet']);
        
        // Filter out super_admin from business user list (SA is platform-level, not business-level)
        $businessUsers = $company->users->where('user_type', '!=', 'super_admin');
        
        $subscription = $company->activeSubscription();
        $latestSubscription = $company->latestSubscription();
        $allSubscriptions = $company->subscriptions()->with('package')->latest()->get();
        $payments = SubscriptionPayment::where('company_id', $company->id)
            ->with('subscription.package')
            ->latest()->get();
        $wallet = $company->wallet;
        $recentTransactions = $wallet
            ? AiCreditTransaction::where('wallet_id', $wallet->id)->latest()->take(10)->get()
            : collect();
        $packages = SubscriptionPackage::active()->ordered()->get();

        return view('superadmin.businesses.show', compact(
            'company', 'businessUsers', 'subscription', 'latestSubscription', 'allSubscriptions',
            'payments', 'wallet', 'recentTransactions', 'packages'
        ));
    }

    // ─── Toggle Business Status ─────────────────────────────────────────

    public function toggleStatus(Company $company)
    {
        $newStatus = $company->status === 'active' ? 'suspended' : 'active';
        $company->update(['status' => $newStatus]);

        // If suspending, also suspend active subscription
        if ($newStatus === 'suspended') {
            $company->subscriptions()
                ->whereIn('status', ['active', 'trial'])
                ->update(['status' => 'suspended']);
        }

        $label = $newStatus === 'active' ? 'activated' : 'suspended';
        return back()->with('success', "Business '{$company->name}' has been {$label}.");
    }

    // ─── Assign / Renew Subscription ────────────────────────────────────

    public function assignSubscription(Request $request, Company $company)
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'days' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:manual,razorpay,free',
            'notes' => 'nullable|string|max:500',
        ]);

        $package = SubscriptionPackage::findOrFail($request->package_id);
        $days = (int) ($request->days ?? ($request->billing_cycle === 'yearly' ? 365 : 30));
        $amount = $request->amount ?? ($request->billing_cycle === 'yearly' ? $package->yearly_price : $package->monthly_price);

        // Expire any existing active subscription
        $company->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->update(['status' => 'expired', 'expires_at' => now()]);

        // Create new subscription
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'status' => $request->payment_method === 'free' ? 'trial' : 'active',
            'billing_cycle' => $request->billing_cycle,
            'amount_paid' => $amount,
            'max_users' => $request->max_users ?? $package->default_max_users,
            'starts_at' => now(),
            'expires_at' => now()->addDays($days),
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        // Record payment
        if ($request->payment_method !== 'free' && $amount > 0) {
            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                'admin_notes' => $request->notes ?? 'Subscription assigned by SA',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        }

        // Reactivate company if suspended
        if ($company->status !== 'active') {
            $company->update(['status' => 'active']);
        }

        return back()->with('success', "Subscription updated to {$package->name} ({$request->billing_cycle}) for {$days} days!");
    }

    // ─── Dismiss Upgrade Request ────────────────────────────────────────

    public function dismissUpgrade(Company $company)
    {
        // Clear upgrade request notes from active subscriptions
        $subscriptions = $company->subscriptions()
            ->whereIn('status', ['active', 'trial'])
            ->where('notes', 'like', '%UPGRADE REQUEST:%')
            ->get();

        foreach ($subscriptions as $sub) {
            $lines = explode("\n", $sub->notes ?? '');
            $cleaned = array_filter($lines, fn($line) => !str_contains($line, 'UPGRADE REQUEST:'));
            $sub->update(['notes' => trim(implode("\n", $cleaned)) ?: null]);
        }

        return back()->with('success', "Upgrade request for '{$company->name}' dismissed.");
    }

    // ─── Add AI Credits ─────────────────────────────────────────────────

    public function addCredits(Request $request, Company $company)
    {
        $request->validate([
            'credits' => 'required|integer|min:1',
            'type' => 'required|in:bonus,adjustment',
            'description' => 'nullable|string|max:500',
        ]);

        $wallet = $company->wallet;
        if (!$wallet) {
            $wallet = AiCreditWallet::create([
                'company_id' => $company->id,
                'balance' => 0,
                'total_purchased' => 0,
                'total_consumed' => 0,
                'low_balance_threshold' => 100,
            ]);
        }

        $wallet->addCredits(
            $request->credits,
            $request->type,
            $request->description ?? "SA {$request->type}: {$request->credits} credits"
        );

        return back()->with('success', "{$request->credits} credits added to {$company->name}'s wallet!");
    }

    // ─── Update Max Users ───────────────────────────────────────────────

    public function updateMaxUsers(Request $request, Company $company)
    {
        $request->validate([
            'max_users' => 'required|integer|min:1|max:999',
        ]);

        $subscription = $company->activeSubscription();
        if ($subscription) {
            $subscription->update(['max_users' => $request->max_users]);
            return back()->with('success', "User limit updated to {$request->max_users} for {$company->name}.");
        }

        return back()->with('error', 'No active subscription to update.');
    }

    // ─── Reset Admin Credentials ───────────────────────────────────────

    public function resetAdminCredentials(Request $request, Company $company)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'new_email' => 'nullable|email|max:255',
            'new_password' => 'nullable|string|min:6|max:100',
        ]);

        $user = User::where('id', $request->user_id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        // Block resetting super_admin credentials from business panel
        if ($user->user_type === 'super_admin') {
            return back()->with('error', 'Cannot reset Super Admin credentials from here.');
        }

        $changes = [];

        // Update email if provided and different
        if ($request->filled('new_email') && $request->new_email !== $user->email) {
            // Check uniqueness (exclude current user)
            $exists = User::where('email', $request->new_email)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($exists) {
                return back()->with('error', "Email '{$request->new_email}' is already taken by another user.");
            }

            $user->email = $request->new_email;
            $changes[] = 'email';
        }

        // Update password if provided
        if ($request->filled('new_password')) {
            $user->password = $request->new_password; // User model auto-hashes
            $changes[] = 'password';
        }

        if (empty($changes)) {
            return back()->with('error', 'No changes provided. Please enter a new email or password.');
        }

        $user->save();

        $changeLabel = implode(' & ', $changes);
        return back()->with('success', "Admin {$changeLabel} updated successfully for {$user->name}!");
    }

    // ─── Create Admin User for a Business ─────────────────────────────

    public function createAdmin(Request $request, Company $company)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:100',
        ]);

        // Find or create an Admin role for this company
        $adminRole = Role::where('company_id', $company->id)
            ->where('slug', 'admin')
            ->first();

        if (!$adminRole) {
            $adminRole = Role::create([
                'company_id'  => $company->id,
                'name'        => 'Admin',
                'slug'        => 'admin',
                'description' => 'Full access to all features',
                'permissions' => json_encode(['all']),
                'is_system'   => true,
            ]);
        }

        $user = User::create([
            'company_id' => $company->id,
            'role_id'    => $adminRole->id,
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => $request->password, // auto-hashed by model cast
            'user_type'  => 'admin',
            'status'     => 'active',
        ]);

        return back()->with('success', "Admin user '{$user->name}' created successfully! They can now login at the admin portal.");
    }

    // ─── Delete Business ───────────────────────────────────────────────

    public function destroy(Company $company)
    {
        DB::beginTransaction();
        try {
            $companyName = $company->name;

            // Delete credit transactions & wallet
            $wallet = $company->wallet;
            if ($wallet) {
                AiCreditTransaction::where('wallet_id', $wallet->id)->delete();
                $wallet->delete();
            }

            // Delete subscription payments & subscriptions
            SubscriptionPayment::where('company_id', $company->id)->delete();
            $company->subscriptions()->delete();

            // Delete users (soft-delete) and roles
            User::where('company_id', $company->id)->forceDelete();
            Role::where('company_id', $company->id)->delete();

            // Finally delete company
            $company->delete();

            DB::commit();

            return redirect()->route('superadmin.businesses.index')
                ->with('success', "Business '{$companyName}' and all its data have been permanently deleted.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete business: ' . $e->getMessage());
        }
    }
}
