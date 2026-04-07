@extends('superadmin.layouts.app')
@section('title', 'Subscriptions')

@section('content')
<div class="page-header">
    <h1>Subscriptions</h1>
    <p>View and manage all active, trial, and expired subscriptions</p>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Active</div>
        <div style="font-size:28px;font-weight:800;color:hsl(var(--success));margin-top:4px;">{{ $stats['active'] }}</div>
    </div>
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Trials</div>
        <div style="font-size:28px;font-weight:800;color:hsl(var(--warning));margin-top:4px;">{{ $stats['trial'] }}</div>
    </div>
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Expired</div>
        <div style="font-size:28px;font-weight:800;color:hsl(var(--destructive));margin-top:4px;">{{ $stats['expired'] }}</div>
    </div>
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Expiring Soon</div>
        <div style="font-size:28px;font-weight:800;color:#f57c00;margin-top:4px;">{{ $stats['expiring_soon'] }}</div>
    </div>
</div>

{{-- Subscriptions Table --}}
<div class="card">
    <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:hsl(var(--muted)/0.3);">
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Business</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Package</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Status</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Cycle</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Period</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Days Left</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Paid</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                <tr style="border-bottom:1px solid hsl(var(--border)/0.5);">
                    <td style="padding:12px 16px;font-weight:600;">
                        @if($sub->company)
                            <a href="{{ route('superadmin.businesses.show', $sub->company_id) }}" style="color:hsl(var(--primary));text-decoration:none;">
                                {{ $sub->company->name }}
                            </a>
                        @else
                            <span style="color:hsl(var(--muted-foreground));">—</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;">
                        <span class="badge badge-outline" style="font-size:11px;">{{ $sub->package->name ?? '—' }}</span>
                    </td>
                    <td style="padding:12px 16px;">
                        @php
                            $statusColor = match($sub->status) {
                                'active' => 'hsl(var(--success))',
                                'trial' => 'hsl(var(--warning))',
                                'expired','cancelled' => 'hsl(var(--destructive))',
                                'suspended' => '#6b7280',
                                default => 'hsl(var(--muted-foreground))',
                            };
                        @endphp
                        <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;background:{{ $statusColor }}15;color:{{ $statusColor }};">
                            {{ strtoupper($sub->status) }}
                        </span>
                        @if($sub->isExpired())
                            <span style="display:inline-block;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:700;background:hsl(var(--destructive)/0.1);color:hsl(var(--destructive));margin-left:4px;">EXPIRED</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;color:hsl(var(--muted-foreground));">{{ ucfirst($sub->billing_cycle) }}</td>
                    <td style="padding:12px 16px;font-size:12px;color:hsl(var(--muted-foreground));">
                        {{ $sub->starts_at?->format('d M y') }} — {{ $sub->expires_at?->format('d M y') }}
                    </td>
                    <td style="padding:12px 16px;">
                        @php $days = $sub->daysRemaining(); @endphp
                        <span style="font-weight:700;color:{{ $days <= 7 ? '#ef4444' : ($days <= 30 ? '#f59e0b' : 'hsl(var(--foreground))') }};">
                            {{ $days }}d
                        </span>
                    </td>
                    <td style="padding:12px 16px;font-weight:600;">₹{{ number_format($sub->amount_paid, 0) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:hsl(var(--muted-foreground));">No subscriptions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscriptions->hasPages())
    <div style="padding:16px;border-top:1px solid hsl(var(--border));display:flex;justify-content:center;">
        {{ $subscriptions->links() }}
    </div>
    @endif
</div>
@endsection
