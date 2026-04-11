@extends('superadmin.layouts.app')

@section('title', 'AI Credits & Tokens')
@section('breadcrumb', 'AI Tokens')

@section('content')
<div style="max-width:1200px;margin:0 auto">

    {{-- Page Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px">
        <div>
            <h1 style="font-size:24px;font-weight:700;color:hsl(var(--foreground));margin:0 0 6px 0;display:flex;align-items:center;gap:10px">
                <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center">
                    <i data-lucide="zap" style="width:22px;height:22px;color:white"></i>
                </div>
                AI Credits & Tokens
            </h1>
            <p style="color:hsl(var(--muted-foreground));font-size:14px;margin:0">Monitor AI token usage, costs, and credit consumption across all businesses</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
        {{-- Total Tokens --}}
        <div class="card" style="padding:20px;position:relative;overflow:hidden">
            <div style="position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(99,102,241,.05))"></div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#6366f1;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <i data-lucide="cpu" style="width:14px;height:14px"></i> Total Tokens Used
            </div>
            <div style="font-size:26px;font-weight:700;color:hsl(var(--foreground))">{{ number_format($totalTokensUsed) }}</div>
            <div style="font-size:11px;color:hsl(var(--muted-foreground));margin-top:4px">Across all businesses</div>
        </div>

        {{-- AI Cost INR --}}
        <div class="card" style="padding:20px;position:relative;overflow:hidden">
            <div style="position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(239,68,68,.1),rgba(239,68,68,.05))"></div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#ef4444;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <i data-lucide="indian-rupee" style="width:14px;height:14px"></i> AI Cost (Actual)
            </div>
            <div style="font-size:26px;font-weight:700;color:hsl(var(--foreground))">₹{{ number_format($totalCostINR, 2) }}</div>
            <div style="font-size:11px;color:hsl(var(--muted-foreground));margin-top:4px">${{ number_format($totalCostUSD, 4) }} USD</div>
        </div>

        {{-- Credits Consumed --}}
        <div class="card" style="padding:20px;position:relative;overflow:hidden">
            <div style="position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(245,158,11,.1),rgba(245,158,11,.05))"></div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#f59e0b;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <i data-lucide="flame" style="width:14px;height:14px"></i> Credits Consumed
            </div>
            <div style="font-size:26px;font-weight:700;color:hsl(var(--foreground))">{{ number_format($totalCreditsConsumed, 0) }}</div>
            <div style="font-size:11px;color:hsl(var(--muted-foreground));margin-top:4px">Rate: {{ $creditsRate }} credits/1K tokens</div>
        </div>

        {{-- Revenue --}}
        <div class="card" style="padding:20px;position:relative;overflow:hidden">
            <div style="position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(16,185,129,.05))"></div>
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;color:#10b981;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                <i data-lucide="trending-up" style="width:14px;height:14px"></i> Credit Revenue
            </div>
            <div style="font-size:26px;font-weight:700;color:hsl(var(--foreground))">₹{{ number_format($totalRevenue, 0) }}</div>
            <div style="font-size:11px;color:hsl(var(--muted-foreground));margin-top:4px">Total recharged: {{ number_format($totalCreditsRecharged, 0) }} credits</div>
        </div>
    </div>

    {{-- Profit/Loss Card --}}
    @php
        $profit = $totalRevenue - $totalCostINR;
        $isProfit = $profit >= 0;
    @endphp
    <div class="card" style="margin-bottom:28px;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;border-left:4px solid {{ $isProfit ? '#10b981' : '#ef4444' }}">
        <div style="display:flex;align-items:center;gap:12px">
            <i data-lucide="{{ $isProfit ? 'trending-up' : 'trending-down' }}" style="width:24px;height:24px;color:{{ $isProfit ? '#10b981' : '#ef4444' }}"></i>
            <div>
                <div style="font-size:13px;font-weight:600;color:hsl(var(--muted-foreground))">Net {{ $isProfit ? 'Profit' : 'Loss' }} (Revenue - AI Cost)</div>
                <div style="font-size:22px;font-weight:700;color:{{ $isProfit ? '#10b981' : '#ef4444' }}">{{ $isProfit ? '+' : '' }}₹{{ number_format($profit, 2) }}</div>
            </div>
        </div>
        <div style="text-align:right">
            <div style="font-size:11px;color:hsl(var(--muted-foreground))">Revenue: ₹{{ number_format($totalRevenue, 2) }}</div>
            <div style="font-size:11px;color:hsl(var(--muted-foreground))">AI Cost: ₹{{ number_format($totalCostINR, 2) }}</div>
        </div>
    </div>

    {{-- Per-Company Breakdown --}}
    <div class="card" style="margin-bottom:28px">
        <div class="card-content" style="padding:24px">
            <h3 style="font-size:16px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                <i data-lucide="building-2" style="width:18px;height:18px;color:#6366f1"></i>
                Token Usage by Business
            </h3>

            @if($companyStats->count() > 0)
            <div class="table-wrapper" style="overflow-x:auto">
                <table class="table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th style="text-align:right">API Calls</th>
                            <th style="text-align:right">Tokens Used</th>
                            <th style="text-align:right">Credits Used</th>
                            <th style="text-align:right">Wallet Balance</th>
                            <th style="text-align:right">AI Cost (INR)</th>
                            <th style="text-align:right">Last Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companyStats as $stat)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:white;font-size:11px;font-weight:700">
                                        {{ strtoupper(substr($stat['company_name'], 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600;font-size:13px">{{ $stat['company_name'] }}</div>
                                        <div style="font-size:11px;color:hsl(var(--muted-foreground))">ID: {{ $stat['company_id'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align:right;font-weight:500">{{ number_format($stat['total_calls']) }}</td>
                            <td style="text-align:right">
                                <span style="font-weight:600;color:#6366f1">{{ number_format($stat['total_tokens']) }}</span>
                            </td>
                            <td style="text-align:right">
                                <span style="font-weight:500;color:#f59e0b">{{ number_format($stat['total_credits'], 1) }}</span>
                            </td>
                            <td style="text-align:right">
                                <span class="badge {{ $stat['wallet_balance'] > 50 ? 'badge-success' : ($stat['wallet_balance'] > 10 ? 'badge-warning' : 'badge-destructive') }}" style="font-size:11px">
                                    {{ number_format($stat['wallet_balance'], 0) }}
                                </span>
                            </td>
                            <td style="text-align:right;font-weight:500;color:#ef4444">₹{{ number_format($stat['cost_inr'], 2) }}</td>
                            <td style="text-align:right;font-size:12px;color:hsl(var(--muted-foreground))">
                                {{ $stat['last_used_at'] ? \Carbon\Carbon::parse($stat['last_used_at'])->diffForHumans() : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align:center;padding:40px;color:hsl(var(--muted-foreground))">
                <i data-lucide="database" style="width:40px;height:40px;margin-bottom:10px;opacity:0.4"></i>
                <p>No AI token usage recorded yet</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Recent Token Transactions --}}
    <div class="card">
        <div class="card-content" style="padding:24px">
            <h3 style="font-size:16px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                <i data-lucide="activity" style="width:18px;height:18px;color:#f59e0b"></i>
                Recent AI Token Transactions
            </h3>

            @if($recentTransactions->count() > 0)
            <div class="table-wrapper" style="overflow-x:auto">
                <table class="table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Business</th>
                            <th>Description</th>
                            <th style="text-align:right">Tokens</th>
                            <th style="text-align:right">Credits</th>
                            <th style="text-align:right">Cost (INR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $txn)
                        @php
                            $txnCost = (($txn->ai_tokens_used ?? 0) / 1000000) * 0.15 * 84;
                        @endphp
                        <tr>
                            <td style="font-size:12px;color:hsl(var(--muted-foreground));white-space:nowrap">
                                {{ $txn->created_at->format('d M, h:i A') }}
                            </td>
                            <td>
                                <span style="font-weight:500;font-size:13px">{{ $txn->company->name ?? 'Unknown' }}</span>
                            </td>
                            <td style="font-size:12px;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $txn->description }}
                            </td>
                            <td style="text-align:right;font-weight:600;color:#6366f1;font-size:13px">
                                {{ number_format($txn->ai_tokens_used ?? 0) }}
                            </td>
                            <td style="text-align:right;font-weight:500;color:#f59e0b;font-size:13px">
                                {{ number_format(abs($txn->credits), 1) }}
                            </td>
                            <td style="text-align:right;font-size:12px;color:#ef4444">
                                ₹{{ number_format($txnCost, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align:center;padding:40px;color:hsl(var(--muted-foreground))">
                <i data-lucide="zap-off" style="width:40px;height:40px;margin-bottom:10px;opacity:0.4"></i>
                <p>No AI transactions yet</p>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
