<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPackage;
use App\Models\SubscriptionPayment;
use App\Models\AiCreditWallet;
use App\Models\AiCreditTransaction;
use App\Models\AiCreditPack;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // ─── Revenue KPIs ───────────────────────────────────────────
        $monthlyRevenue = SubscriptionPayment::where('status', 'completed')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount');

        $lastMonthRevenue = SubscriptionPayment::where('status', 'completed')
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $totalRevenue = SubscriptionPayment::where('status', 'completed')->sum('amount');

        // AI Credit Revenue
        $monthlyAiRevenue = AiCreditTransaction::where('type', 'recharge')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount_paid');

        $totalAiRevenue = AiCreditTransaction::where('type', 'recharge')->sum('amount_paid');

        // ─── Business KPIs ──────────────────────────────────────────
        $totalCompanies = Company::count();
        $activeSubscriptions = Subscription::active()->count();
        $trialSubscriptions = Subscription::where('status', 'trial')
            ->where('expires_at', '>=', $now->toDateString())->count();
        $expiredSubscriptions = Subscription::expired()->count();
        $expiringSoon = Subscription::expiringSoon(7)->count();

        // New businesses this month
        $newBusinessesThisMonth = Company::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)->count();

        // ─── Package Distribution ───────────────────────────────────
        $packageStats = SubscriptionPackage::withCount(['subscriptions' => function ($q) {
            $q->whereIn('status', ['active', 'trial'])
              ->where('expires_at', '>=', now()->toDateString());
        }])->ordered()->get();

        // ─── AI Credit Stats ────────────────────────────────────────
        $totalCreditsInSystem = AiCreditWallet::sum('balance');
        $totalCreditsConsumed = AiCreditWallet::sum('total_consumed');
        $lowBalanceWallets = AiCreditWallet::where('balance', '<', 100)->count();

        // ─── Recent Activity ────────────────────────────────────────
        $recentPayments = SubscriptionPayment::where('status', 'completed')
            ->with(['company', 'subscription.package'])
            ->latest()->take(5)->get();

        $recentSubscriptions = Subscription::with(['company', 'package'])
            ->latest()->take(5)->get();

        // ─── Pending Upgrade Requests ───────────────────────────────
        $upgradeRequests = Subscription::with(['company', 'package'])
            ->where('notes', 'like', '%UPGRADE REQUEST:%')
            ->whereIn('status', ['active', 'trial'])
            ->where('expires_at', '>=', now()->toDateString())
            ->latest('updated_at')
            ->get();

        // ─── User Stats ─────────────────────────────────────────────
        $totalAdmins = User::where('user_type', 'admin')->count();
        $totalStaff = User::where('user_type', 'staff')->count();

        return view('superadmin.dashboard', compact(
            'monthlyRevenue', 'lastMonthRevenue', 'revenueGrowth', 'totalRevenue',
            'monthlyAiRevenue', 'totalAiRevenue',
            'totalCompanies', 'activeSubscriptions', 'trialSubscriptions',
            'expiredSubscriptions', 'expiringSoon', 'newBusinessesThisMonth',
            'packageStats',
            'totalCreditsInSystem', 'totalCreditsConsumed', 'lowBalanceWallets',
            'recentPayments', 'recentSubscriptions', 'upgradeRequests',
            'totalAdmins', 'totalStaff'
        ));
    }
}
