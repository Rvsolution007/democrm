@extends('superadmin.layouts.app')
@section('title', 'Wallets')

@section('content')
<div class="page-header">
    <h1>AI Credit Wallets</h1>
    <p>Monitor balances and add credits for all businesses</p>
</div>

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;">
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Total Wallets</div>
        <div style="font-size:28px;font-weight:800;margin-top:4px;">{{ $stats['total_wallets'] }}</div>
    </div>
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Total Balance</div>
        <div style="font-size:28px;font-weight:800;color:hsl(var(--success));margin-top:4px;">{{ number_format($stats['total_balance'], 0) }}</div>
    </div>
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Total Consumed</div>
        <div style="font-size:28px;font-weight:800;color:hsl(var(--warning));margin-top:4px;">{{ number_format($stats['total_consumed'], 0) }}</div>
    </div>
    <div class="card" style="padding:18px 20px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));font-weight:600;">Low Balance</div>
        <div style="font-size:28px;font-weight:800;color:hsl(var(--destructive));margin-top:4px;">{{ $stats['low_balance'] }}</div>
    </div>
</div>

{{-- Wallets Table --}}
<div class="card">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:hsl(var(--muted)/0.3);">
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Business</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Balance</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Purchased</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Consumed</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Status</th>
                    <th style="padding:12px 16px;text-align:center;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;color:hsl(var(--muted-foreground));">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wallets as $wallet)
                <tr style="border-bottom:1px solid hsl(var(--border)/0.5);{{ $wallet->isLowBalance() ? 'background:hsl(var(--destructive)/0.03);' : '' }}">
                    <td style="padding:12px 16px;font-weight:600;">
                        @if($wallet->company)
                            <a href="{{ route('superadmin.businesses.show', $wallet->company_id) }}" style="color:hsl(var(--primary));text-decoration:none;">
                                {{ $wallet->company->name }}
                            </a>
                        @else
                            <span style="color:hsl(var(--muted-foreground));">Unknown</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:right;font-weight:800;font-size:15px;{{ $wallet->isLowBalance() ? 'color:hsl(var(--destructive));' : '' }}">
                        {{ $wallet->getBalanceFormatted() }}
                    </td>
                    <td style="padding:12px 16px;text-align:right;color:hsl(var(--muted-foreground));">
                        {{ number_format($wallet->total_purchased, 0) }}
                    </td>
                    <td style="padding:12px 16px;text-align:right;color:hsl(var(--muted-foreground));">
                        {{ number_format($wallet->total_consumed, 0) }}
                    </td>
                    <td style="padding:12px 16px;text-align:center;">
                        @if($wallet->isLowBalance())
                            <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;background:hsl(var(--destructive)/0.1);color:hsl(var(--destructive));">LOW</span>
                        @else
                            <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;background:hsl(var(--success)/0.1);color:hsl(var(--success));">OK</span>
                        @endif
                    </td>
                    <td style="padding:12px 16px;text-align:center;">
                        <button class="btn btn-outline btn-sm" onclick="openAddCredits({{ $wallet->id }}, '{{ $wallet->company->name ?? 'Business' }}')">
                            <i data-lucide="plus" style="width:12px;height:12px;"></i> Add
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:hsl(var(--muted-foreground));">No wallets found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Credits Modal --}}
<div id="addCreditsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div class="card" style="max-width:420px;width:100%;">
        <div style="padding:20px 24px;border-bottom:1px solid hsl(var(--border));display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:16px;font-weight:700;margin:0;">Add Credits — <span id="addCreditsCompany"></span></h3>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('addCreditsModal').style.display='none'">&times;</button>
        </div>
        <form id="addCreditsForm" method="POST" style="padding:20px 24px;">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Credits</label>
                    <input name="credits" type="number" min="1" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Type</label>
                    <select name="type" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                        <option value="recharge">Recharge</option>
                        <option value="bonus">Bonus</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Amount Paid (₹)</label>
                <input name="amount_paid" type="number" step="0.01" min="0" value="0" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Description</label>
                <input name="description" placeholder="Optional note" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Add Credits</button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openAddCredits(walletId, companyName) {
    document.getElementById('addCreditsForm').action = '/crmdemo/public/superadmin/wallets/' + walletId + '/add-credits';
    document.getElementById('addCreditsCompany').textContent = companyName;
    document.getElementById('addCreditsModal').style.display = 'flex';
}
</script>
@endpush
