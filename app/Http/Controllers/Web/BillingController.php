<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiCreditPack;
use App\Models\AiCreditWallet;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * Billing overview — current plan, usage, upgrade options
     */
    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        // Current subscription
        $subscription = $company->activeSubscription();
        if ($subscription) {
            $subscription->load('package');
        }

        // All packages for upgrade comparison
        $packages = SubscriptionPackage::active()->ordered()->get();

        // Credit wallet
        $wallet = AiCreditWallet::where('company_id', $user->company_id)->first();

        // Credit packs for purchase
        $creditPacks = AiCreditPack::active()->ordered()->get();

        // Subscription history
        $subscriptionHistory = Subscription::with('package')
            ->where('company_id', $user->company_id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Recent credit transactions
        $recentTransactions = $wallet
            ? $wallet->transactions()->orderBy('created_at', 'desc')->take(15)->get()
            : collect();

        // User count
        $userCount = \App\Models\User::where('company_id', $user->company_id)
            ->where('status', 'active')
            ->where('user_type', '!=', 'super_admin')
            ->count();
        $maxUsers = $subscription ? $subscription->getMaxUsers() : 3;

        return view('admin.billing.index', compact(
            'subscription', 'packages', 'wallet', 'creditPacks',
            'subscriptionHistory', 'recentTransactions',
            'userCount', 'maxUsers', 'company'
        ));
    }

    /**
     * Request upgrade — creates a pending request for SA to process
     */
    public function requestUpgrade(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:subscription_packages,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $package = SubscriptionPackage::findOrFail($request->package_id);
        $user = auth()->user();

        // Store upgrade request as a note in the current subscription
        $subscription = $user->company->activeSubscription();

        if ($subscription) {
            $note = "UPGRADE REQUEST: {$user->name} requested upgrade to {$package->name} ({$request->billing_cycle}) on " . now()->format('d M Y H:i');
            $existingNotes = $subscription->notes ?? '';
            $subscription->update(['notes' => $existingNotes . "\n" . $note]);
        }

        return back()->with('success', "Upgrade request to {$package->name} submitted! Our team will contact you shortly.");
    }

    /**
     * Buy credit pack — adds credits to wallet immediately
     */
    public function requestCredits(Request $request)
    {
        $request->validate([
            'pack_id' => 'required|exists:ai_credit_packs,id',
        ]);

        $pack = AiCreditPack::findOrFail($request->pack_id);
        $user = auth()->user();
        $wallet = AiCreditWallet::where('company_id', $user->company_id)->first();

        if (!$wallet) {
            // Create wallet if it doesn't exist
            $wallet = AiCreditWallet::create([
                'company_id' => $user->company_id,
                'balance' => 0,
                'total_purchased' => 0,
                'total_consumed' => 0,
                'low_balance_threshold' => 50,
            ]);
        }

        // Add credits to wallet
        $wallet->balance += $pack->credits;
        $wallet->total_purchased += $pack->credits;
        $wallet->save();

        // Log the transaction
        $wallet->transactions()->create([
            'company_id' => $user->company_id,
            'type' => 'recharge',
            'credits' => $pack->credits,
            'balance_after' => $wallet->balance,
            'amount_paid' => $pack->price,
            'description' => "Purchased {$pack->name} ({$pack->getCreditsFormatted()} credits @ {$pack->getPriceFormatted()}) by {$user->name}",
            'reference_type' => 'credit_pack',
            'reference_id' => $pack->id,
            'payment_method' => 'direct',
        ]);

        return back()->with('success', "✅ {$pack->getCreditsFormatted()} credits added to your wallet from {$pack->name}!");
    }
}
