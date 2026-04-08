@extends('superadmin.layouts.app')
@section('title', 'Dashboard')
@section('has_charts', true)

@section('content')
<div class="page-header">
    <h1>Platform Dashboard</h1>
    <p>Overview of your SaaS platform performance</p>
</div>

{{-- Expiring Soon Alert --}}
@if($expiringSoon > 0)
<div style="margin-bottom:20px;padding:14px 18px;background:hsl(var(--warning)/0.08);border:1px solid hsl(var(--warning)/0.2);border-radius:10px;display:flex;align-items:center;gap:10px;">
    <i data-lucide="alert-triangle" style="width:20px;height:20px;color:hsl(var(--warning));flex-shrink:0;"></i>
    <div>
        <span style="font-weight:600;color:hsl(var(--warning));">{{ $expiringSoon }} subscription(s) expiring within 7 days</span>
        <span style="font-size:13px;color:hsl(var(--muted-foreground));margin-left:8px;">
            <a href="{{ route('superadmin.subscriptions.index') }}?filter=expiring" style="color:hsl(var(--primary));text-decoration:none;">View Details →</a>
        </span>
    </div>
</div>
@endif

{{-- Pending Upgrade Requests --}}
@if($upgradeRequests->count() > 0)
<div style="margin-bottom:20px;">
    <div style="padding:14px 18px;background:hsl(var(--primary)/0.06);border:1px solid hsl(var(--primary)/0.15);border-radius:10px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            <i data-lucide="arrow-up-circle" style="width:18px;height:18px;color:hsl(var(--primary));"></i>
            <span style="font-weight:700;font-size:14px;color:hsl(var(--primary));">{{ $upgradeRequests->count() }} Pending Upgrade Request(s)</span>
        </div>
        @foreach($upgradeRequests as $req)
        @php
            // Parse the latest upgrade request from notes
            $lines = array_filter(explode("\n", $req->notes ?? ''));
            $latestRequest = '';
            $requestedPlan = '';
            $requestDate = '';
            foreach (array_reverse($lines) as $line) {
                if (str_contains($line, 'UPGRADE REQUEST:')) {
                    $latestRequest = trim($line);
                    // Extract plan name: "... requested upgrade to PlanName (cycle) on date"
                    if (preg_match('/upgrade to (.+?) \(/', $line, $m)) {
                        $requestedPlan = $m[1];
                    }
                    if (preg_match('/on (\d{2} \w{3} \d{4})/', $line, $m)) {
                        $requestDate = $m[1];
                    }
                    break;
                }
            }
        @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:hsl(var(--background));border-radius:8px;margin-bottom:6px;border:1px solid hsl(var(--border));">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,hsl(var(--primary)),hsl(var(--accent)));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                    {{ strtoupper(substr($req->company->name ?? 'X', 0, 2)) }}
                </div>
                <div>
                    <div style="font-weight:600;font-size:14px;">{{ $req->company->name ?? '—' }}</div>
                    <div style="font-size:12px;color:hsl(var(--muted-foreground));">
                        Current: <span class="badge badge-secondary" style="font-size:10px;">{{ $req->package->name ?? '—' }}</span>
                        → Requested: <span class="badge badge-primary" style="font-size:10px;">{{ $requestedPlan ?: 'Unknown' }}</span>
                        @if($requestDate)
                            <span style="margin-left:6px;opacity:0.6;">{{ $requestDate }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <a href="{{ route('superadmin.businesses.show', $req->company_id) }}" class="btn btn-primary btn-sm" style="font-size:12px;white-space:nowrap;">
                    <i data-lucide="settings" style="width:14px;height:14px;"></i> Process
                </a>
                <form method="POST" action="{{ route('superadmin.businesses.dismiss-upgrade', $req->company_id) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="font-size:12px;white-space:nowrap;color:hsl(var(--muted-foreground));" onclick="return confirm('Dismiss this upgrade request?')">
                        ✕ Dismiss
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Revenue KPIs --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    {{-- MRR --}}
    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">Monthly Revenue</div>
                <div class="stats-card-value">₹{{ number_format((float)$monthlyRevenue, 0) }}</div>
                <div class="stats-card-description">
                    @if($revenueGrowth > 0)
                        <span style="color:hsl(var(--success));">↑ {{ $revenueGrowth }}%</span>
                    @elseif($revenueGrowth < 0)
                        <span style="color:hsl(var(--destructive));">↓ {{ abs($revenueGrowth) }}%</span>
                    @else
                        <span>—</span>
                    @endif
                    vs last month
                </div>
            </div>
            <div class="stats-card-icon">
                <i data-lucide="indian-rupee"></i>
            </div>
        </div>
    </div>

    {{-- Total Revenue --}}
    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">Total Revenue</div>
                <div class="stats-card-value">₹{{ number_format((float)$totalRevenue, 0) }}</div>
                <div class="stats-card-description">Subscription + AI Credits</div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(var(--success)/0.1);color:hsl(var(--success));">
                <i data-lucide="trending-up"></i>
            </div>
        </div>
    </div>

    {{-- Active Businesses --}}
    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">Active Businesses</div>
                <div class="stats-card-value">{{ $activeSubscriptions }}</div>
                <div class="stats-card-description">
                    <span style="color:hsl(var(--info));">{{ $trialSubscriptions }} on trial</span>
                    · {{ $newBusinessesThisMonth }} new this month
                </div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(var(--info)/0.1);color:hsl(var(--info));">
                <i data-lucide="building-2"></i>
            </div>
        </div>
    </div>

    {{-- AI Credit Economy --}}
    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">AI Credits Active</div>
                <div class="stats-card-value">{{ number_format((float)$totalCreditsInSystem, 0) }}</div>
                <div class="stats-card-description">
                    {{ number_format((float)$totalCreditsConsumed, 0) }} consumed
                    @if($lowBalanceWallets > 0)
                        · <span style="color:hsl(var(--destructive));">{{ $lowBalanceWallets }} low balance</span>
                    @endif
                </div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(168 76% 42%/0.1);color:hsl(168 76% 42%);">
                <i data-lucide="coins"></i>
            </div>
        </div>
    </div>
</div>

{{-- Second Row: Users + AI Revenue --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">Total Admins</div>
                <div class="stats-card-value">{{ $totalAdmins }}</div>
                <div class="stats-card-description">Business owners</div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(262 83% 58%/0.1);color:hsl(262 83% 58%);">
                <i data-lucide="user-check"></i>
            </div>
        </div>
    </div>

    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">Total Staff</div>
                <div class="stats-card-value">{{ $totalStaff }}</div>
                <div class="stats-card-description">Across all businesses</div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(var(--muted));color:hsl(var(--muted-foreground));">
                <i data-lucide="users"></i>
            </div>
        </div>
    </div>

    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">AI Credit Revenue</div>
                <div class="stats-card-value">₹{{ number_format((float)$totalAiRevenue, 0) }}</div>
                <div class="stats-card-description">₹{{ number_format((float)$monthlyAiRevenue, 0) }} this month</div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(var(--accent)/0.1);color:hsl(var(--accent));">
                <i data-lucide="sparkles"></i>
            </div>
        </div>
    </div>

    <div class="stats-card">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label">Expired / Suspended</div>
                <div class="stats-card-value" style="color:hsl(var(--destructive));">{{ $expiredSubscriptions }}</div>
                <div class="stats-card-description">Need attention</div>
            </div>
            <div class="stats-card-icon" style="background-color:hsl(var(--destructive)/0.1);color:hsl(var(--destructive));">
                <i data-lucide="alert-circle"></i>
            </div>
        </div>
    </div>
</div>

{{-- Package Distribution + Recent Payments --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
    {{-- Package Distribution --}}
    <div class="card">
        <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
            <div>
                <h3 class="card-title" style="font-size:16px;">Package Distribution</h3>
                <p class="card-description">Active subscriptions by plan</p>
            </div>
            <a href="{{ route('superadmin.packages.index') }}" class="btn btn-ghost btn-sm" style="font-size:12px;">
                Manage <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
            </a>
        </div>
        <div class="card-content">
            @foreach($packageStats as $pkg)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;{{ !$loop->last ? 'border-bottom:1px solid hsl(var(--border));' : '' }}">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:10px;height:10px;border-radius:50%;background:hsl(var(--chart-{{ $loop->iteration }}));"></div>
                    <div>
                        <div style="font-weight:600;font-size:14px;">{{ $pkg->name }}</div>
                        <div style="font-size:12px;color:hsl(var(--muted-foreground));">
                            ₹{{ number_format((float)$pkg->monthly_price,0) }}/mo · Max {{ $pkg->default_max_users }} users
                        </div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:18px;font-weight:700;">{{ $pkg->subscriptions_count }}</div>
                    <div style="font-size:11px;color:hsl(var(--muted-foreground));">active</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="card">
        <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
            <div>
                <h3 class="card-title" style="font-size:16px;">Recent Payments</h3>
                <p class="card-description">Latest subscription payments</p>
            </div>
        </div>
        <div class="card-content">
            @forelse($recentPayments as $payment)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid hsl(var(--border));' : '' }}">
                <div>
                    <div style="font-weight:500;font-size:14px;">{{ $payment->company->name ?? '—' }}</div>
                    <div style="font-size:12px;color:hsl(var(--muted-foreground));">
                        {{ $payment->subscription?->package?->name ?? '—' }}
                        · {{ $payment->getMethodLabel() }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:600;color:hsl(var(--success));">{{ $payment->getAmountFormatted() }}</div>
                    <div style="font-size:11px;color:hsl(var(--muted-foreground));">{{ $payment->created_at->diffForHumans() }}</div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:32px 0;color:hsl(var(--muted-foreground));font-size:14px;">
                <i data-lucide="inbox" style="width:32px;height:32px;margin-bottom:8px;opacity:0.5;"></i>
                <p>No payments yet</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Subscriptions --}}
<div class="card">
    <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
        <div>
            <h3 class="card-title" style="font-size:16px;">Recent Subscriptions</h3>
            <p class="card-description">Latest subscription activity</p>
        </div>
        <a href="{{ route('superadmin.subscriptions.index') }}" class="btn btn-ghost btn-sm" style="font-size:12px;">
            View All <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
        </a>
    </div>
    <div class="card-content">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Expires</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSubscriptions as $sub)
                    <tr>
                        <td style="font-weight:500;">{{ $sub->company->name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-primary" style="font-size:11px;">{{ $sub->package->name ?? '—' }}</span>
                        </td>
                        <td>
                            @php
                                $statusClass = match($sub->status) {
                                    'active' => 'badge-success',
                                    'trial' => 'badge-info',
                                    'expired' => 'badge-muted',
                                    'suspended' => 'badge-destructive',
                                    'cancelled' => 'badge-muted',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($sub->status) }}</span>
                        </td>
                        <td>{{ $sub->max_users ?? $sub->package?->default_max_users ?? '—' }}</td>
                        <td>
                            @if($sub->expires_at)
                                <span style="{{ $sub->isExpired() ? 'color:hsl(var(--destructive));' : '' }}">
                                    {{ $sub->expires_at->format('d M Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-weight:500;">₹{{ number_format((float)$sub->amount_paid, 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:hsl(var(--muted-foreground));">
                            No subscriptions yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
