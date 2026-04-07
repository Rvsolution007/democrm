<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditWallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        $wallets = AiCreditWallet::with('company')
            ->orderBy('balance', 'asc')
            ->get();

        $stats = [
            'total_wallets' => $wallets->count(),
            'total_balance' => $wallets->sum('balance'),
            'total_consumed' => $wallets->sum('total_consumed'),
            'low_balance' => $wallets->filter(fn($w) => $w->isLowBalance())->count(),
        ];

        return view('superadmin.wallets.index', compact('wallets', 'stats'));
    }

    public function addCredits(Request $request, AiCreditWallet $wallet)
    {
        $data = $request->validate([
            'credits' => 'required|numeric|min:1',
            'type' => 'required|in:recharge,bonus',
            'description' => 'nullable|string|max:255',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $wallet->addCredits(
            credits: $data['credits'],
            type: $data['type'],
            description: $data['description'] ?? null,
            amountPaid: $data['amount_paid'] ?? null,
            paymentMethod: 'manual',
        );

        $companyName = $wallet->company->name ?? 'Business';
        return back()->with('success', "{$data['credits']} credits added to {$companyName}.");
    }
}
