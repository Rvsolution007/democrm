@extends('admin.layouts.app')
@section('title', 'Billing & Subscription')

@section('content')
<style>
    /* ─── Billing Page Styles ─── */
    .billing-header h1 { font-size: 24px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px; }
    .billing-header p { font-size: 14px; color: #6b7280; margin: 0; }

    /* Overview Cards Grid */
    .overview-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }
    .overview-card {
        background: #fff;
        border-radius: 16px;
        padding: 20px 22px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: box-shadow 0.2s;
    }
    .overview-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .overview-card.plan-card {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #fff; border: none;
    }
    .overview-card.plan-card .card-label { color: rgba(255,255,255,0.5); }
    .overview-card.plan-card .card-value { color: #fff; }
    .overview-card.plan-card .card-sub { color: rgba(255,255,255,0.4); }
    .overview-card.low-balance { border-left: 3px solid #ef4444; }

    .card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .card-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; font-weight: 600; }
    .card-value { font-size: 30px; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
    .card-sub { font-size: 12px; color: #9ca3af; margin-top: 4px; }

    .badge-sm {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-active { background: rgba(34,197,94,0.12); color: #16a34a; }
    .badge-trial { background: rgba(245,158,11,0.12); color: #d97706; }
    .badge-expired { background: rgba(239,68,68,0.1); color: #dc2626; }
    .badge-warn { background: rgba(245,158,11,0.12); color: #d97706; }
    .badge-danger { background: rgba(239,68,68,0.1); color: #dc2626; }
    .badge-popular { background: #f57c00; color: #fff; font-size: 9px; padding: 2px 8px; border-radius: 4px; }

    /* Progress bar */
    .usage-bar { height: 6px; background: #f3f4f6; border-radius: 3px; margin-top: 8px; overflow: hidden; }
    .usage-bar-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }

    /* Section Card */
    .section-card {
        background: #fff; border-radius: 16px;
        border: 1px solid #f0f0f0; overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        margin-bottom: 24px;
    }
    .section-card-header {
        padding: 18px 24px; border-bottom: 1px solid #f5f5f5;
        display: flex; align-items: center; gap: 8px;
    }
    .section-card-header h3 { font-size: 16px; font-weight: 700; color: #1a1a2e; margin: 0; }
    .section-card-header i { color: #f57c00; }
    .section-card-body { padding: 20px 24px; }

    /* Package Cards */
    .packages-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .pkg-card {
        border: 1px solid #f0f0f0; border-radius: 14px;
        padding: 24px 20px; text-align: center;
        display: flex; flex-direction: column;
        transition: all 0.2s;
    }
    .pkg-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .pkg-card.is-current {
        border: 2px solid #f57c00;
        box-shadow: 0 4px 24px rgba(245,124,0,0.12);
    }
    .pkg-current-tag {
        background: #f57c00; color: #fff; text-align: center;
        padding: 5px; font-size: 10px; font-weight: 700; letter-spacing: 1px;
        margin: -24px -20px 16px; border-radius: 12px 12px 0 0;
    }
    .pkg-name { font-size: 20px; font-weight: 800; color: #1a1a2e; margin-bottom: 4px; }
    .pkg-desc { font-size: 12px; color: #6b7280; margin-bottom: 16px; min-height: 36px; line-height: 1.4; }
    .pkg-prices { display: flex; justify-content: center; gap: 20px; margin-bottom: 16px; }
    .pkg-price { text-align: center; }
    .pkg-price-val { font-size: 22px; font-weight: 800; color: #1a1a2e; }
    .pkg-price-val.yearly { color: #f57c00; }
    .pkg-price-cycle { font-size: 10px; color: #9ca3af; }
    .pkg-features { text-align: left; font-size: 12px; margin-bottom: 16px; flex-grow: 1; }
    .pkg-feature { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
    .pkg-feature .check { color: #22c55e; font-weight: 700; }
    .pkg-feature .cross { color: #d1d5db; }


    /* Credit packs + activity layout */
    .credits-layout {
        display: grid;
        grid-template-columns: 3fr 2fr;
        gap: 20px;
    }
    .credit-packs-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .credit-pack {
        border: 1px solid #f0f0f0; border-radius: 12px;
        padding: 16px; position: relative; transition: all 0.2s;
    }
    .credit-pack:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .credit-pack.is-popular { border: 2px solid #f57c00; }
    .credit-pack-name { font-size: 14px; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
    .credit-pack-desc { font-size: 11px; color: #6b7280; margin-bottom: 10px; line-height: 1.4; }
    .credit-pack-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
    .credit-pack-credits { font-size: 22px; font-weight: 800; color: #1a1a2e; line-height: 1; }
    .credit-pack-credits small { font-size: 10px; color: #9ca3af; font-weight: 400; display: block; margin-top: 2px; }
    .credit-pack-price { font-size: 18px; font-weight: 700; color: #f57c00; text-align: right; line-height: 1; }
    .credit-pack-price small { font-size: 10px; color: #9ca3af; font-weight: 400; display: block; margin-top: 2px; }

    .btn-buy {
        width: 100%; padding: 8px; border: none; border-radius: 8px;
        font-size: 12px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all 0.2s;
    }
    .btn-buy-primary { background: #f57c00; color: #fff; }
    .btn-buy-primary:hover { background: #e65100; }
    .btn-buy-default { background: #f5f5f5; color: #333; }
    .btn-buy-default:hover { background: #eee; }

    /* Transactions */
    .txn-list { max-height: 420px; overflow-y: auto; }
    .txn-item {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 16px; border-bottom: 1px solid #f9f9f9;
        font-size: 12px;
    }
    .txn-icon {
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }
    .txn-icon.debit { background: rgba(239,68,68,0.08); color: #ef4444; }
    .txn-icon.credit { background: rgba(34,197,94,0.08); color: #22c55e; }
    .txn-icon.pending { background: rgba(245,158,11,0.08); color: #f59e0b; }
    .txn-details { flex: 1; min-width: 0; }
    .txn-type { font-weight: 600; color: #1a1a2e; }
    .txn-desc { color: #9ca3af; font-size: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .txn-amount { text-align: right; }
    .txn-amount-val { font-weight: 700; }
    .txn-amount-val.pos { color: #22c55e; }
    .txn-amount-val.neg { color: #ef4444; }
    .txn-date { font-size: 10px; color: #9ca3af; }

    /* History table */
    .history-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .history-table thead { background: #fafafa; }
    .history-table th { padding: 10px 16px; font-weight: 600; color: #6b7280; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    .history-table td { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; color: #374151; }
    .history-table tr:hover { background: #fafafa; }

    /* Buttons */
    .btn-upgrade {
        width: 100%; padding: 10px; border: none; border-radius: 10px;
        font-size: 13px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all 0.2s;
        background: #f57c00; color: #fff;
    }
    .btn-upgrade:hover { background: #e65100; transform: translateY(-1px); }
    .btn-disabled {
        background: #f3f4f6; color: #9ca3af; cursor: default;
        width: 100%; padding: 10px; border: none; border-radius: 10px;
        font-size: 13px; font-weight: 600;
    }

    .empty-state { text-align: center; padding: 32px; color: #9ca3af; font-size: 13px; }

    /* ─── Responsive ─── */
    @media (max-width: 1200px) {
        .overview-grid { grid-template-columns: repeat(2, 1fr); }
        .packages-grid { grid-template-columns: repeat(2, 1fr); }
        .credits-layout { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .overview-grid { grid-template-columns: 1fr; }
        .packages-grid { grid-template-columns: 1fr; }
        .credit-packs-grid { grid-template-columns: 1fr; }
        .billing-header h1 { font-size: 20px; }
        .section-card-body { padding: 16px; }
        .section-card-header { padding: 14px 16px; }
    }
    @media (max-width: 480px) {
        .card-value { font-size: 24px; }
        .pkg-prices { flex-direction: column; gap: 8px; }
    }
</style>

<div class="container-fluid" style="padding: 24px 28px;">
    {{-- Page Header --}}
    <div class="billing-header" style="margin-bottom: 24px;">
        <h1>Billing & Subscription</h1>
        <p>Manage your plan, usage, and AI credits</p>
    </div>

    {{-- ═══ Overview Cards ═══ --}}
    <div class="overview-grid">
        {{-- Plan Card --}}
        <div class="overview-card plan-card">
            <div class="card-top">
                <span class="card-label" style="color:rgba(255,255,255,0.5);">Current Plan</span>
                @if($subscription)
                    <span class="badge-sm {{ $subscription->isTrial() ? 'badge-trial' : 'badge-active' }}">
                        {{ $subscription->isTrial() ? 'Trial' : 'Active' }}
                    </span>
                @else
                    <span class="badge-sm badge-expired">No Plan</span>
                @endif
            </div>
            <div class="card-value" style="font-size:26px;">
                {{ $subscription && $subscription->package ? $subscription->package->name : 'None' }}
            </div>
            <div class="card-sub" style="color:rgba(255,255,255,0.35);margin-top:6px;font-size:11px;">
                {{ $subscription && $subscription->package ? Str::limit($subscription->package->description, 60) : 'No active subscription' }}
            </div>
            @if($subscription)
            <div style="margin-top:10px;font-size:11px;color:rgba(255,255,255,0.45);">
                {{ ucfirst($subscription->billing_cycle) }} billing
            </div>
            @endif
        </div>

        {{-- Days Remaining --}}
        <div class="overview-card">
            <div class="card-top">
                <span class="card-label">Expires</span>
                @if($subscription && $subscription->isExpiringSoon())
                    <span class="badge-sm badge-warn">Expiring Soon</span>
                @endif
            </div>
            @if($subscription)
                <div class="card-value" style="{{ $subscription->daysRemaining() <= 7 ? 'color:#ef4444;' : '' }}">
                    {{ $subscription->daysRemaining() }}d
                </div>
                <div class="card-sub">Until {{ $subscription->expires_at ? $subscription->expires_at->format('d M Y') : '-' }}</div>
            @else
                <div class="card-value" style="color:#ef4444;">0d</div>
                <div class="card-sub">No active plan</div>
            @endif
        </div>

        {{-- Users --}}
        <div class="overview-card">
            <span class="card-label">Team Members</span>
            <div class="card-value" style="margin-top:10px;">
                {{ $userCount }}<span style="font-size:16px;color:#9ca3af;font-weight:400;">/{{ $maxUsers }}</span>
            </div>
            <div class="usage-bar">
                @php $usagePercent = $maxUsers > 0 ? min(100, ($userCount/$maxUsers)*100) : 0; @endphp
                <div class="usage-bar-fill" style="width:{{ $usagePercent }}%;background:{{ $usagePercent > 80 ? '#ef4444' : '#f57c00' }};"></div>
            </div>
            <div class="card-sub">{{ max(0, $maxUsers - $userCount) }} slots remaining</div>
        </div>

        {{-- AI Credits --}}
        <div class="overview-card {{ $wallet && $wallet->isLowBalance() ? 'low-balance' : '' }}">
            <div class="card-top">
                <span class="card-label">AI Credits</span>
                @if($wallet && $wallet->isLowBalance())
                    <span class="badge-sm badge-danger">Low</span>
                @endif
            </div>
            <div class="card-value" style="{{ $wallet && $wallet->isLowBalance() ? 'color:#ef4444;' : '' }}">
                {{ $wallet ? $wallet->getBalanceFormatted() : '0' }}
            </div>
            <div class="card-sub">{{ $wallet ? number_format($wallet->total_consumed, 0) : '0' }} used total</div>
        </div>
    </div>

    {{-- ═══ Available Plans ═══ --}}
    <div class="section-card">
        <div class="section-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 16.5 3-3m0 0L12 18m-4.5-4.5V21M21 3 8.5 15.5"/><path d="M21 3H15"/><path d="M21 3V9"/></svg>
            <h3>Available Plans</h3>
        </div>
        <div class="section-card-body">
            <div class="packages-grid">
                @foreach($packages as $pkg)
                @php
                    $isCurrent = $subscription && $subscription->package_id === $pkg->id;
                    $isUpgrade = $subscription && $pkg->sort_order > ($subscription->package->sort_order ?? 0);
                @endphp
                <div class="pkg-card {{ $isCurrent ? 'is-current' : '' }}">
                    @if($isCurrent)
                        <div class="pkg-current-tag">CURRENT PLAN</div>
                    @endif
                    <div class="pkg-name">{{ $pkg->name }}</div>
                    <div class="pkg-desc">{{ $pkg->description }}</div>
                    <div class="pkg-prices">
                        <div class="pkg-price">
                            <div class="pkg-price-val">{{ $pkg->getPriceLabel('monthly') }}</div>
                            <div class="pkg-price-cycle">/month</div>
                        </div>
                        <div style="width:1px;background:#eee;"></div>
                        <div class="pkg-price">
                            <div class="pkg-price-val yearly">{{ $pkg->getPriceLabel('yearly') }}</div>
                            <div class="pkg-price-cycle">/year</div>
                        </div>
                    </div>
                    <div class="pkg-features">
                        <div class="pkg-feature"><span class="check">✓</span> {{ $pkg->default_max_users }} users</div>
                        <div class="pkg-feature"><span class="check">✓</span> {{ $pkg->trial_days }} day trial</div>
                        @php $featureCount = count($pkg->getEnabledFeatures()); @endphp
                        <div class="pkg-feature"><span class="check">✓</span> {{ $featureCount }} modules included</div>
                        @if($pkg->hasFeature('whatsapp_connect'))
                            <div class="pkg-feature"><span class="check">✓</span> WhatsApp Integration</div>
                        @else
                            <div class="pkg-feature"><span class="cross">✗</span> <span style="color:#9ca3af;">WhatsApp</span></div>
                        @endif
                        @if($pkg->hasFeature('chatflow'))
                            <div class="pkg-feature"><span class="check">✓</span> AI Chatbot & Chatflow</div>
                        @else
                            <div class="pkg-feature"><span class="cross">✗</span> <span style="color:#9ca3af;">AI Chatbot</span></div>
                        @endif
                    </div>
                    <div>
                        @if($isCurrent)
                            <button class="btn-disabled" disabled>Current Plan</button>
                        @elseif($isUpgrade)
                            <form method="POST" action="{{ route('admin.billing.request-upgrade') }}">
                                @csrf
                                <input type="hidden" name="package_id" value="{{ $pkg->id }}">
                                <input type="hidden" name="billing_cycle" value="monthly">
                                <button type="submit" class="btn-upgrade" onclick="return confirm('Request upgrade to {{ $pkg->name }}?')">
                                    ↗ Upgrade
                                </button>
                            </form>
                        @else
                            <button class="btn-disabled" disabled>—</button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ Credit Packs + Activity ═══ --}}
    <div class="credits-layout">
        {{-- Credit Packs --}}
        <div class="section-card">
            <div class="section-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <h3>AI Credit Packs</h3>
            </div>
            <div class="section-card-body">
                <div class="credit-packs-grid">
                    @foreach($creditPacks as $pack)
                    <div class="credit-pack {{ $pack->is_popular ? 'is-popular' : '' }}">
                        @if($pack->is_popular)
                            <span class="badge-popular" style="position:absolute;top:-1px;right:12px;border-radius:0 0 4px 4px;">POPULAR</span>
                        @endif
                        <div class="credit-pack-name">{{ $pack->name }}</div>
                        <div class="credit-pack-desc">{{ $pack->description }}</div>
                        <div class="credit-pack-row">
                            <div class="credit-pack-credits">
                                {{ $pack->getCreditsFormatted() }}
                                <small>credits</small>
                            </div>
                            <div class="credit-pack-price">
                                {{ $pack->getPriceFormatted() }}
                                <small>{{ $pack->getPerCreditLabel() }}</small>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.billing.request-credits') }}" class="buy-credit-form">
                            @csrf
                            <input type="hidden" name="pack_id" value="{{ $pack->id }}">
                            <button type="submit" class="btn-buy {{ $pack->is_popular ? 'btn-buy-primary' : 'btn-buy-default' }}">
                                Buy Credits
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="section-card">
            <div class="section-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <h3>Recent Activity</h3>
            </div>
            @if($recentTransactions->isEmpty())
                <div class="empty-state">No transactions yet</div>
            @else
                <div class="txn-list">
                    @foreach($recentTransactions as $txn)
                    <div class="txn-item">
                        <div class="txn-icon {{ $txn->type === 'consumption' ? 'debit' : (in_array($txn->type, ['recharge','bonus']) ? 'credit' : 'pending') }}">
                            {{ $txn->type === 'consumption' ? '↓' : ($txn->type === 'recharge' || $txn->type === 'bonus' ? '↑' : '•') }}
                        </div>
                        <div class="txn-details">
                            <div class="txn-type">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</div>
                            <div class="txn-desc">{{ Str::limit($txn->description ?? '-', 45) }}</div>
                        </div>
                        <div class="txn-amount">
                            <div class="txn-amount-val {{ $txn->credits >= 0 ? 'pos' : 'neg' }}">
                                {{ $txn->credits >= 0 ? '+' : '' }}{{ number_format($txn->credits, 1) }}
                            </div>
                            <div class="txn-date">{{ $txn->created_at->format('d M') }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ Subscription History ═══ --}}
    <div class="section-card">
        <div class="section-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f57c00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>
            <h3>Subscription History</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="history-table">
                <thead>
                    <tr><th>Package</th><th>Status</th><th>Cycle</th><th>Period</th><th>Paid</th></tr>
                </thead>
                <tbody>
                    @forelse($subscriptionHistory as $sub)
                    <tr>
                        <td style="font-weight:600;">{{ $sub->package->name ?? '—' }}</td>
                        <td>
                            <span class="badge-sm {{ $sub->isActive() ? 'badge-active' : ($sub->isTrial() ? 'badge-trial' : 'badge-expired') }}">
                                {{ ucfirst($sub->status) }}
                            </span>
                        </td>
                        <td>{{ ucfirst($sub->billing_cycle) }}</td>
                        <td>{{ $sub->starts_at?->format('d M y') }} — {{ $sub->expires_at?->format('d M y') }}</td>
                        <td style="font-weight:600;">₹{{ number_format($sub->amount_paid, 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">No subscription history</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
