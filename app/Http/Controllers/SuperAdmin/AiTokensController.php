<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditTransaction;
use App\Models\AiCreditWallet;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class AiTokensController extends Controller
{
    public function index()
    {
        // Global AI token stats
        $totalTokensUsed = AiCreditTransaction::where('type', 'consumption')
            ->sum(DB::raw('ABS(COALESCE(ai_tokens_used, 0))'));

        $totalCreditsConsumed = AiCreditTransaction::where('type', 'consumption')
            ->sum(DB::raw('ABS(credits)'));

        $totalCreditsRecharged = AiCreditTransaction::whereIn('type', ['recharge', 'bonus'])
            ->sum('credits');

        $totalRevenue = AiCreditTransaction::whereIn('type', ['recharge'])
            ->sum('amount_paid');

        // Cost calculation: Gemini pricing (approximate)
        // Input: $0.075 per 1M tokens, Output: $0.30 per 1M tokens
        // Average: ~$0.15 per 1M tokens mixed
        // 1 USD = ~84 INR
        $costPerMillionTokens = 0.15; // USD average
        $usdToInr = 84;
        $totalCostUSD = ($totalTokensUsed / 1000000) * $costPerMillionTokens;
        $totalCostINR = $totalCostUSD * $usdToInr;

        // Credits rate
        $creditsRate = Setting::getGlobalValue('ai_credits', 'credits_per_1k_tokens', 1.2);

        // Per-company breakdown
        $companyStats = AiCreditTransaction::where('type', 'consumption')
            ->select(
                'company_id',
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(ABS(COALESCE(ai_tokens_used, 0))) as total_tokens'),
                DB::raw('SUM(ABS(credits)) as total_credits'),
                DB::raw('MAX(created_at) as last_used_at')
            )
            ->groupBy('company_id')
            ->orderByDesc('total_tokens')
            ->get()
            ->map(function ($stat) use ($costPerMillionTokens, $usdToInr) {
                $company = Company::find($stat->company_id);
                $wallet = AiCreditWallet::where('company_id', $stat->company_id)->first();
                $costUSD = ($stat->total_tokens / 1000000) * $costPerMillionTokens;
                return [
                    'company_id' => $stat->company_id,
                    'company_name' => $company ? $company->name : 'Unknown',
                    'total_calls' => $stat->total_calls,
                    'total_tokens' => (int) $stat->total_tokens,
                    'total_credits' => round($stat->total_credits, 2),
                    'wallet_balance' => $wallet ? round($wallet->balance, 2) : 0,
                    'cost_inr' => round($costUSD * $usdToInr, 2),
                    'last_used_at' => $stat->last_used_at,
                ];
            });

        // Recent transactions (last 50)
        $recentTransactions = AiCreditTransaction::with('company')
            ->whereNotNull('ai_tokens_used')
            ->where('ai_tokens_used', '>', 0)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('superadmin.ai-tokens.index', compact(
            'totalTokensUsed', 'totalCreditsConsumed', 'totalCreditsRecharged',
            'totalRevenue', 'totalCostINR', 'totalCostUSD', 'creditsRate',
            'companyStats', 'recentTransactions'
        ));
    }
}
