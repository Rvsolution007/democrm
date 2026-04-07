@extends('superadmin.layouts.app')
@section('title', $company->name)

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-ghost btn-icon btn-sm"><i data-lucide="arrow-left"></i></a>
        <div>
            <h1 style="display:flex;align-items:center;gap:8px;">
                {{ $company->name }}
                <span class="badge {{ $company->status === 'active' ? 'badge-success' : 'badge-destructive' }}" style="font-size:11px;">{{ ucfirst($company->status) }}</span>
            </h1>
            <p>{{ $company->email }} · {{ $company->phone }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('superadmin.businesses.toggle-status', $company->id) }}">
        @csrf
        @if($company->status === 'active')
            <button type="submit" class="btn btn-outline btn-sm" style="color:hsl(var(--destructive));border-color:hsl(var(--destructive)/0.3);" onclick="return confirm('Suspend this business?')">
                <i data-lucide="ban" style="width:14px;height:14px;"></i> Suspend
            </button>
        @else
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check-circle" style="width:14px;height:14px;"></i> Activate</button>
        @endif
    </form>
</div>

{{-- Overview Cards --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">Package</div>
                <div class="stats-card-value" style="font-size:18px;">{{ $subscription?->package?->name ?? 'None' }}</div>
                <div class="stats-card-description">{{ $subscription ? ucfirst($subscription->status) : 'No plan' }}</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;"><i data-lucide="package" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">Users</div>
                <div class="stats-card-value" style="font-size:18px;">{{ $company->getActiveUserCount() }}/{{ $subscription ? $subscription->getMaxUsers() : '—' }}</div>
                <div class="stats-card-description">{{ $company->getRemainingUserSlots() }} slots remaining</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;background:hsl(var(--info)/0.1);color:hsl(var(--info));"><i data-lucide="users" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">AI Credits</div>
                <div class="stats-card-value" style="font-size:18px;{{ $wallet && $wallet->isLowBalance() ? 'color:hsl(var(--destructive));' : '' }}">{{ $wallet ? $wallet->getBalanceFormatted() : '0' }}</div>
                <div class="stats-card-description">{{ $wallet ? number_format((float)$wallet->total_consumed, 0) : '0' }} consumed</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;background:hsl(var(--accent)/0.1);color:hsl(var(--accent));"><i data-lucide="coins" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">Expires</div>
                <div class="stats-card-value" style="font-size:18px;{{ $subscription && $subscription->isExpiringSoon() ? 'color:hsl(var(--warning));' : '' }}">
                    {{ $subscription ? $subscription->daysRemaining() . 'd' : '—' }}
                </div>
                <div class="stats-card-description">{{ $subscription?->expires_at?->format('d M Y') ?? 'N/A' }}</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;background:hsl(var(--warning)/0.1);color:hsl(var(--warning));"><i data-lucide="calendar" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    {{-- Left Column --}}
    <div>
        {{-- Assign Subscription --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="credit-card" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>Subscription</h3>
            </div>
            <div class="card-content">
                <form method="POST" action="{{ route('superadmin.businesses.assign-subscription', $company->id) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Package</label>
                            <select name="package_id" class="form-select">
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" {{ $subscription && $subscription->package_id == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Billing Cycle</label>
                            <select name="billing_cycle" class="form-select">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Days</label>
                            <input type="number" name="days" class="form-control" value="30" min="1">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Max Users</label>
                            <input type="number" name="max_users" class="form-control" value="{{ $subscription?->getMaxUsers() }}" min="1">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Amount (₹)</label>
                            <input type="number" name="amount" class="form-control" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="free">Free / Trial</option>
                                <option value="manual">Manual</option>
                                <option value="razorpay">Razorpay</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('This will replace the current subscription. Continue?')">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Assign / Renew
                    </button>
                </form>
            </div>
        </div>

        {{-- Add Credits --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="coins" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--accent));vertical-align:text-bottom;"></i>Add AI Credits</h3>
            </div>
            <div class="card-content">
                <form method="POST" action="{{ route('superadmin.businesses.add-credits', $company->id) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Credits</label>
                            <input type="number" name="credits" class="form-control" value="500" min="1" required>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Type</label>
                            <select name="type" class="form-select">
                                <option value="bonus">Bonus</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional reason">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <i data-lucide="plus" style="width:14px;height:14px;"></i> Add Credits
                    </button>
                </form>
            </div>
        </div>

        {{-- Users --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="users" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--info));vertical-align:text-bottom;"></i>Users ({{ $businessUsers->count() }})</h3>
                {{-- Update Max Users --}}
                <form method="POST" action="{{ route('superadmin.businesses.update-users', $company->id) }}" style="display:flex;gap:8px;align-items:center;">
                    @csrf
                    <input type="number" name="max_users" class="form-control" value="{{ $subscription?->getMaxUsers() ?? 3 }}" min="1" style="width:70px;height:32px;font-size:12px;">
                    <button type="submit" class="btn btn-ghost btn-xs" title="Update limit">
                        <i data-lucide="save" style="width:14px;height:14px;"></i>
                    </button>
                </form>
            </div>
            <div class="card-content" style="padding:0;">
                <div class="table-container" style="border:none;">
                    <table>
                        <thead><tr><th>Name</th><th>Role</th><th>Type</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($businessUsers as $usr)
                            <tr>
                                <td>
                                    <div style="font-weight:500;">{{ $usr->name }}</div>
                                    <div style="font-size:11px;color:hsl(var(--muted-foreground));">{{ $usr->email }}</div>
                                </td>
                                <td><span class="badge badge-secondary" style="font-size:11px;">{{ $usr->role?->name ?? '—' }}</span></td>
                                <td><span class="badge {{ $usr->user_type === 'admin' ? 'badge-primary' : 'badge-muted' }}" style="font-size:10px;">{{ $usr->user_type }}</span></td>
                                <td><span class="badge {{ $usr->status === 'active' ? 'badge-success' : 'badge-muted' }}" style="font-size:11px;">{{ ucfirst($usr->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Reset Admin Credentials --}}
        <div class="card">
            <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="key-round" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--warning));vertical-align:text-bottom;"></i>Reset Login Credentials</h3>
            </div>
            <div class="card-content">
                <form method="POST" action="{{ route('superadmin.businesses.reset-credentials', $company->id) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Select User</label>
                            <select name="user_id" class="form-select" required id="resetUserSelect" onchange="fillCurrentEmail(this)">
                                <option value="">— Choose a user —</option>
                                @foreach($businessUsers as $usr)
                                    <option value="{{ $usr->id }}" data-email="{{ $usr->email }}">{{ $usr->name }} ({{ $usr->user_type }}) — {{ $usr->email }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">New Email <span style="color:hsl(var(--muted-foreground));font-size:11px;">(leave blank to keep current)</span></label>
                            <input type="email" name="new_email" class="form-control" id="resetEmailInput" placeholder="new-email@example.com">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">New Password <span style="color:hsl(var(--muted-foreground));font-size:11px;">(leave blank to keep current)</span></label>
                            <div style="position:relative;">
                                <input type="text" name="new_password" class="form-control" id="resetPasswordInput" placeholder="New password (min 6 chars)">
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to reset credentials for this user?')">
                            <i data-lucide="shield-check" style="width:14px;height:14px;"></i> Reset Credentials
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="generatePassword()" title="Generate a random secure password">
                            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Generate Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <script>
        function fillCurrentEmail(select) {
            const option = select.options[select.selectedIndex];
            if (option && option.dataset.email) {
                document.getElementById('resetEmailInput').placeholder = option.dataset.email;
            }
        }
        function generatePassword() {
            const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
            let pw = '';
            for (let i = 0; i < 12; i++) pw += chars.charAt(Math.floor(Math.random() * chars.length));
            document.getElementById('resetPasswordInput').value = pw;
        }
        </script>
    </div>

    {{-- Right Column --}}
    <div>
        {{-- Subscription History --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="history" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>Subscription History</h3>
            </div>
            <div class="card-content" style="padding:0;">
                <div class="table-container" style="border:none;">
                    <table>
                        <thead><tr><th>Package</th><th>Status</th><th>Period</th><th>Paid</th></tr></thead>
                        <tbody>
                            @forelse($allSubscriptions as $sub)
                            <tr>
                                <td><span class="badge badge-primary" style="font-size:11px;">{{ $sub->package->name ?? '—' }}</span></td>
                                <td>
                                    @php $sc = match($sub->status) { 'active'=>'badge-success','trial'=>'badge-info','expired'=>'badge-muted','suspended'=>'badge-destructive',default=>'badge-secondary' }; @endphp
                                    <span class="badge {{ $sc }}">{{ ucfirst($sub->status) }}</span>
                                </td>
                                <td style="font-size:12px;">
                                    {{ $sub->starts_at?->format('d M') }} - {{ $sub->expires_at?->format('d M Y') }}
                                </td>
                                <td style="font-weight:500;">₹{{ number_format((float)$sub->amount_paid, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:hsl(var(--muted-foreground));">No history</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment History --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="receipt" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--success));vertical-align:text-bottom;"></i>Payment History</h3>
            </div>
            <div class="card-content" style="padding:0;">
                <div class="table-container" style="border:none;">
                    <table>
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($payments as $pay)
                            <tr>
                                <td style="font-size:12px;">{{ $pay->created_at->format('d M Y') }}</td>
                                <td style="font-weight:600;color:hsl(var(--success));">{{ $pay->getAmountFormatted() }}</td>
                                <td><span class="badge badge-secondary" style="font-size:11px;">{{ $pay->getMethodLabel() }}</span></td>
                                <td><span class="badge {{ $pay->status === 'completed' ? 'badge-success' : 'badge-muted' }}">{{ ucfirst($pay->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:hsl(var(--muted-foreground));">No payments</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Credit Transactions --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="sparkles" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--accent));vertical-align:text-bottom;"></i>Recent Credit Transactions</h3>
            </div>
            <div class="card-content" style="padding:0;">
                <div class="table-container" style="border:none;">
                    <table>
                        <thead><tr><th>Date</th><th>Type</th><th>Credits</th><th>Balance</th></tr></thead>
                        <tbody>
                            @forelse($recentTransactions as $txn)
                            <tr>
                                <td style="font-size:12px;">{{ $txn->created_at->format('d M') }}</td>
                                <td><span class="badge {{ $txn->getTypeBadgeClass() }}" style="font-size:11px;">{{ $txn->getTypeLabel() }}</span></td>
                                <td style="font-weight:600;{{ $txn->credits >= 0 ? 'color:hsl(var(--success));' : 'color:hsl(var(--destructive));' }}">{{ $txn->getCreditsFormatted() }}</td>
                                <td>{{ number_format((float)$txn->balance_after, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:hsl(var(--muted-foreground));">No transactions</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
