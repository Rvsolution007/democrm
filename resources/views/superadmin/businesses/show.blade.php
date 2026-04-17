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
        @php
            // Detect pending upgrade request
            $pendingUpgrade = null;
            $requestedPackageName = null;
            $requestedCycle = null;
            if ($subscription && $subscription->notes && str_contains($subscription->notes, 'UPGRADE REQUEST:')) {
                $lines = array_filter(explode("\n", $subscription->notes));
                foreach (array_reverse($lines) as $line) {
                    if (str_contains($line, 'UPGRADE REQUEST:')) {
                        $pendingUpgrade = trim($line);
                        if (preg_match('/upgrade to (.+?) \((\w+)\)/', $line, $m)) {
                            $requestedPackageName = $m[1];
                            $requestedCycle = $m[2];
                        }
                        break;
                    }
                }
            }
            $requestedPackage = $requestedPackageName ? $packages->firstWhere('name', $requestedPackageName) : null;
        @endphp
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="credit-card" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>Subscription</h3>
                @if($pendingUpgrade)
                    <span class="badge badge-warning" style="font-size:10px;animation:pulse 2s infinite;">⬆ Upgrade Requested</span>
                @endif
            </div>
            <div class="card-content">
                @if($pendingUpgrade)
                <div style="background:hsl(var(--warning)/0.08);border:1px solid hsl(var(--warning)/0.2);border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="arrow-up-circle" style="width:20px;height:20px;color:hsl(var(--warning));flex-shrink:0;"></i>
                        <div>
                            <div style="font-weight:600;font-size:13px;color:hsl(var(--warning));">Upgrade Requested</div>
                            <div style="font-size:12px;color:hsl(var(--muted-foreground));">
                                {{ $subscription->package->name ?? '—' }} → <strong>{{ $requestedPackageName }}</strong> ({{ $requestedCycle }})
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('superadmin.businesses.dismiss-upgrade', $company->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px;color:hsl(var(--muted-foreground));" onclick="return confirm('Dismiss this upgrade request?')">✕ Dismiss</button>
                    </form>
                </div>
                @endif
                <form method="POST" action="{{ route('superadmin.businesses.assign-subscription', $company->id) }}">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Package</label>
                            <select name="package_id" class="form-select" id="pkgSelect" onchange="updatePrice()">
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}"
                                        data-monthly="{{ $pkg->monthly_price }}"
                                        data-yearly="{{ $pkg->yearly_price }}"
                                        data-users="{{ $pkg->default_max_users }}"
                                        {{ ($requestedPackage && $requestedPackage->id == $pkg->id) ? 'selected' : ((!$requestedPackage && $subscription && $subscription->package_id == $pkg->id) ? 'selected' : '') }}>
                                        {{ $pkg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Billing Cycle</label>
                            <select name="billing_cycle" class="form-select" id="cycleSelect" onchange="updatePrice()">
                                <option value="monthly" {{ $requestedCycle === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ $requestedCycle === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Days</label>
                            <input type="number" name="days" class="form-control" id="daysInput" value="30" min="1">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Max Users</label>
                            <input type="number" name="max_users" class="form-control" id="maxUsersInput" value="{{ $requestedPackage ? $requestedPackage->default_max_users : ($subscription ? $subscription->getMaxUsers() : 5) }}" min="1">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Amount (₹)</label>
                            <input type="number" name="amount" class="form-control" id="amountInput" value="{{ $requestedPackage ? ($requestedCycle === 'yearly' ? $requestedPackage->yearly_price : $requestedPackage->monthly_price) : 0 }}" min="0" step="0.01">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Payment Method</label>
                            <select name="payment_method" class="form-select" id="payMethodSelect" onchange="togglePaymentInfo()">
                                <option value="free">Free / Trial</option>
                                <option value="manual" {{ $requestedPackage ? 'selected' : '' }}>Manual (Cash/UPI/Bank)</option>
                                <option value="razorpay">Razorpay</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Payment ref, txn ID, etc." value="{{ $requestedPackage ? 'Upgrade from ' . ($subscription->package->name ?? '') . ' to ' . $requestedPackageName : '' }}">
                        </div>
                    </div>
                    <div id="paymentInfoBox" style="display:{{ $requestedPackage ? 'block' : 'none' }};background:hsl(var(--info)/0.06);border:1px solid hsl(var(--info)/0.15);border-radius:8px;padding:12px 16px;margin-bottom:14px;">
                        <div style="font-size:12px;font-weight:600;color:hsl(var(--info));margin-bottom:4px;">💳 Payment Collection</div>
                        <div style="font-size:12px;color:hsl(var(--muted-foreground));line-height:1.6;">
                            1. Share payment link/UPI ID with the business admin<br>
                            2. Once payment is received, enter the amount above<br>
                            3. Add the transaction ID / payment reference in Notes<br>
                            4. Click "Assign / Renew" to activate the subscription
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('This will replace the current subscription. Continue?')">
                            <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Assign / Renew
                        </button>
                        <span id="priceHint" style="font-size:12px;color:hsl(var(--muted-foreground));"></span>
                    </div>
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
                        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($businessUsers as $usr)
                            <tr>
                                <td>
                                    <div style="font-weight:500;">{{ $usr->name }}</div>
                                </td>
                                <td>
                                    <div style="font-size:12px;color:hsl(var(--muted-foreground));">{{ $usr->email }}</div>
                                </td>
                                <td><span class="badge {{ $usr->role && str_contains(strtolower($usr->role->name), 'admin') ? 'badge-primary' : 'badge-secondary' }}" style="font-size:11px;">{{ $usr->role?->name ?? '—' }}</span></td>
                                <td><span class="badge {{ $usr->status === 'active' ? 'badge-success' : 'badge-muted' }}" style="font-size:11px;">{{ ucfirst($usr->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Admin Login Credentials --}}
        @php
            $adminUser = $businessUsers->where('user_type', 'admin')->first();
        @endphp
        <div class="card">
            <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                <h3 class="card-title" style="font-size:16px;"><i data-lucide="key-round" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--warning));vertical-align:text-bottom;"></i>Admin Login Credentials</h3>
            </div>
            <div class="card-content">
                @if($adminUser)
                {{-- Show admin details & reset form --}}
                <div style="background:hsl(var(--secondary));border-radius:8px;padding:16px;margin-bottom:16px;display:flex;align-items:center;gap:16px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,hsl(var(--primary)),hsl(var(--accent)));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:16px;flex-shrink:0;">
                        {{ strtoupper(substr($adminUser->name, 0, 2)) }}
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600;font-size:15px;">{{ $adminUser->name }}</div>
                        <div style="font-size:13px;color:hsl(var(--muted-foreground));">{{ $adminUser->email }}</div>
                    </div>
                    <span class="badge badge-success" style="font-size:11px;">{{ ucfirst($adminUser->status) }}</span>
                </div>
                <form method="POST" action="{{ route('superadmin.businesses.reset-credentials', $company->id) }}">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $adminUser->id }}">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">New Email <span style="color:hsl(var(--muted-foreground));font-size:11px;">(leave blank to keep current)</span></label>
                            <input type="email" name="new_email" class="form-control" id="resetEmailInput" placeholder="{{ $adminUser->email }}">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">New Password <span style="color:hsl(var(--muted-foreground));font-size:11px;">(leave blank to keep current)</span></label>
                            <div style="position:relative;">
                                <input type="text" name="new_password" class="form-control" id="resetPasswordInput" placeholder="New password (min 6 chars)">
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Are you sure you want to reset admin credentials?')">
                            <i data-lucide="shield-check" style="width:14px;height:14px;"></i> Reset Credentials
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="generatePassword()" title="Generate a random secure password">
                            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Generate Password
                        </button>
                    </div>
                </form>
                @else
                {{-- No admin user — Create Admin form --}}
                <div style="border:1px dashed hsl(var(--border));border-radius:8px;padding:20px;margin-bottom:12px;text-align:center;">
                    <i data-lucide="user-plus" style="width:24px;height:24px;margin-bottom:8px;color:hsl(var(--primary));opacity:0.7;"></i>
                    <p style="color:hsl(var(--muted-foreground));font-size:13px;margin-bottom:0;">No admin user found for this business. Create one to enable login.</p>
                </div>
                <form method="POST" action="{{ route('superadmin.businesses.create-admin', $company->id) }}" style="margin-top:16px;">
                    @csrf
                    <div style="display:grid;grid-template-columns:1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Admin Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Admin User">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label" style="font-size:12px;">Email</label>
                            <input type="email" name="email" class="form-control" required placeholder="admin@company.com">
                        </div>
                        <div>
                            <label class="form-label" style="font-size:12px;">Password</label>
                            <div style="position:relative;">
                                <input type="text" name="password" class="form-control" id="resetPasswordInput" required placeholder="Min 6 characters">
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i data-lucide="user-plus" style="width:14px;height:14px;"></i> Create Admin User
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="generatePassword()" title="Generate a random secure password">
                            <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Generate Password
                        </button>
                    </div>
                </form>
                @endif
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

{{-- ═══ Match Playground & Bot Config (Per-Business) ═══ --}}
<div style="margin-top:24px;">
    <div class="card">
        <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
            <h3 class="card-title" style="font-size:16px;">
                <i data-lucide="gamepad-2" style="width:16px;height:16px;margin-right:6px;color:#8b5cf6;vertical-align:text-bottom;"></i>
                🎮 Match Playground & Cache — {{ $company->name }}
            </h3>
        </div>
        <div class="card-content">
            <div style="background:linear-gradient(135deg,#ede9fe,#f5f3ff);border:1px solid #c4b5fd;border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
                <i data-lucide="info" style="width:18px;height:18px;color:#7c3aed;flex-shrink:0;margin-top:1px"></i>
                <div style="font-size:13px;color:#5b21b6;line-height:1.5">
                    Ye section <strong>{{ $company->name }}</strong> ke products ke liye match test karne ke liye hai. Confidence threshold aur cache controls per-business hain.
                </div>
            </div>

            {{-- Confidence Threshold --}}
            @php
                $matchConfidence = \App\Models\Setting::getValue('ai_bot', 'match_min_confidence', 60, $company->id);
            @endphp
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:8px;font-size:14px;">Match Confidence Threshold</label>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <input type="range" id="sa-match-slider" min="0" max="100" value="{{ $matchConfidence }}"
                            style="flex:1;accent-color:#8b5cf6;"
                            oninput="document.getElementById('sa-match-val').value=this.value">
                        <input type="number" id="sa-match-val" value="{{ $matchConfidence }}" min="0" max="100"
                            style="width:65px;text-align:center;border:1px solid hsl(var(--border));border-radius:6px;padding:4px 8px;font-weight:700;"
                            oninput="document.getElementById('sa-match-slider').value=this.value">
                        <span style="font-size:13px;color:hsl(var(--muted-foreground));">%</span>
                    </div>
                    <div style="font-size:12px;color:hsl(var(--muted-foreground));margin-top:6px;">Kam value = zyada matches (loose), Zyada value = strict matches</div>
                    <button type="button" class="btn btn-primary btn-sm" style="margin-top:10px;" onclick="saveMatchConfidence()">
                        <i data-lucide="save" style="width:14px;height:14px;"></i> Save Threshold
                    </button>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:8px;font-size:14px;">Cache Controls</label>
                    <div style="background:hsl(var(--secondary));border-radius:8px;padding:16px;">
                        <p style="font-size:13px;color:hsl(var(--muted-foreground));margin-bottom:12px;">Product match index cache clear karo jab products change ho.</p>
                        <button type="button" class="btn btn-outline btn-sm" onclick="clearMatchCache()">
                            <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Clear Product Cache
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Diagnostics System ═══ --}}
<div class="card" style="margin-top:24px;">
    <div class="card-header" style="display:flex;align-items:center;border-bottom:1px solid hsl(var(--border));">
        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,hsl(var(--primary)/0.15),hsl(var(--accent)/0.15));display:flex;align-items:center;justify-content:center;margin-right:10px;">
            <i data-lucide="wrench" style="width:18px;height:18px;color:hsl(var(--primary));"></i>
        </div>
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:600;">Customized Diagnostics</h3>
            <p style="margin:0;font-size:12px;color:hsl(var(--muted-foreground));">"Admin ne apne panel me kya-kya settings configure kiye hain"</p>
        </div>
    </div>
    <div class="card-body" style="padding:20px;">
        <button type="button" class="btn btn-primary" id="btnRunCustomized" onclick="runDiagnostics('customized')" style="transition:all 0.3s;">
            <i data-lucide="play" style="width:14px;height:14px;"></i> Run Customized Check
        </button>

        <div id="progressCustomized" style="display:none;margin-top:16px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;">
                <span id="progressTextCustomized" style="font-weight:600;color:hsl(var(--primary));">Initializing...</span>
                <span id="progressPercentCustomized">0%</span>
            </div>
            <div style="width:100%;height:8px;background:hsl(var(--muted));border-radius:4px;overflow:hidden;">
                <div id="progressBarCustomized" class="diag-progress-bar" style="width:0%;height:100%;"></div>
            </div>
        </div>

        <div id="resultsCustomized" style="margin-top:20px;"></div>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header" style="display:flex;align-items:center;border-bottom:1px solid hsl(var(--border));">
        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,hsl(var(--primary)/0.15),hsl(var(--accent)/0.15));display:flex;align-items:center;justify-content:center;margin-right:10px;">
            <i data-lucide="activity" style="width:18px;height:18px;color:hsl(var(--primary));"></i>
        </div>
        <div>
            <h3 style="margin:0;font-size:16px;font-weight:600;">Hardcoded Diagnostics</h3>
            <p style="margin:0;font-size:12px;color:hsl(var(--muted-foreground));">"System ke internal rules is business pe kaam kar rahe hain?"</p>
        </div>
    </div>
    <div class="card-body" style="padding:20px;">
        <button type="button" class="btn btn-primary" id="btnRunHardcoded" onclick="runDiagnostics('hardcoded')" style="transition:all 0.3s;">
            <i data-lucide="play" style="width:14px;height:14px;"></i> Run Hardcoded Check
        </button>

        <div id="progressHardcoded" style="display:none;margin-top:16px;">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;">
                <span id="progressTextHardcoded" style="font-weight:600;color:hsl(var(--primary));">Initializing...</span>
                <span id="progressPercentHardcoded">0%</span>
            </div>
            <div style="width:100%;height:8px;background:hsl(var(--muted));border-radius:4px;overflow:hidden;">
                <div id="progressBarHardcoded" class="diag-progress-bar" style="width:0%;height:100%;"></div>
            </div>
        </div>

        <div id="resultsHardcoded" style="margin-top:20px;"></div>
    </div>
</div>

<style>
    @keyframes diagFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes diagPopIn {
        0% { transform: scale(0); opacity: 0; }
        70% { transform: scale(1.3); }
        100% { transform: scale(1); opacity: 1; }
    }
    @keyframes diagPulseGlow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
        50% { box-shadow: 0 0 12px 4px rgba(239,68,68,0.2); }
    }
    @keyframes diagShimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .diag-row { animation: diagFadeIn 0.4s ease forwards; opacity: 0; background:hsl(var(--card)); box-shadow:0 1px 3px rgba(0,0,0,0.05); border-radius:8px; margin-bottom:8px; display:flex; border:1px solid hsl(var(--border)); overflow:hidden; }
    .diag-icon-pass { animation: diagPopIn 0.4s ease forwards; color:hsl(142, 71%, 45%); }
    .diag-icon-fail { animation: diagPopIn 0.4s ease forwards, diagPulseGlow 2s infinite; color:hsl(0, 84%, 60%); border-radius:50%; }
    .diag-icon-warn { animation: diagPopIn 0.4s ease forwards; color:hsl(38, 92%, 50%); }
    .diag-progress-bar {
        background: linear-gradient(90deg, #8b5cf6, #3b82f6, #10b981);
        background-size: 200% 100%;
        animation: diagShimmer 2s linear infinite;
        transition: width 0.3s ease;
    }
    .diag-btn-running { opacity:0.7; pointer-events:none; }
    .diag-col { padding:12px 16px; font-size:13px; display:flex; align-items:center; }
    .diag-col-header { padding:12px 16px; font-size:12px; font-weight:600; color:hsl(var(--muted-foreground)); background:hsl(var(--muted)/0.5); text-transform:uppercase; letter-spacing:0.5px; }
    .diag-summary { display:flex; justify-content:space-between; align-items:center; background:hsl(var(--card)); padding:16px; border-radius:8px; border:1px solid hsl(var(--border)); margin-top:20px; animation: diagFadeIn 0.5s ease forwards; font-weight:600; }
    .diag-badge { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:30px; font-size:12px; font-weight:600; }
</style>
@endsection

@push('scripts')
<script>
function updatePrice() {
    const pkg = document.getElementById('pkgSelect');
    const cycle = document.getElementById('cycleSelect');
    const opt = pkg.options[pkg.selectedIndex];
    const monthly = parseFloat(opt.dataset.monthly) || 0;
    const yearly = parseFloat(opt.dataset.yearly) || 0;
    const users = parseInt(opt.dataset.users) || 5;
    const isYearly = cycle.value === 'yearly';

    document.getElementById('amountInput').value = isYearly ? yearly : monthly;
    document.getElementById('maxUsersInput').value = users;
    document.getElementById('daysInput').value = isYearly ? 365 : 30;
    document.getElementById('priceHint').textContent = '₹' + (isYearly ? yearly : monthly).toLocaleString('en-IN') + ' / ' + cycle.value;
}

function togglePaymentInfo() {
    const method = document.getElementById('payMethodSelect').value;
    const box = document.getElementById('paymentInfoBox');
    box.style.display = (method === 'manual' || method === 'razorpay') ? 'block' : 'none';
}



// ─── Match Playground ───
function saveMatchConfidence() {
    const val = document.getElementById('sa-match-val').value;
    fetch('{{ route("superadmin.businesses.match-confidence", $company->id) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ confidence: parseInt(val) })
    }).then(r => r.json()).then(data => {
        alert(data.message || 'Saved');
    }).catch(() => alert('Request failed'));
}

function clearMatchCache() {
    if (!confirm('Is business ka product match cache clear karein?')) return;
    fetch('{{ route("superadmin.businesses.clear-cache", $company->id) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        alert(data.message || 'Cache cleared');
    }).catch(() => alert('Request failed'));
}

// ═══════════════════════════════════════════════════════
// DIAGNOSTICS SYSTEM
// ═══════════════════════════════════════════════════════

const diagRoutes = {
    customized: '{{ route("superadmin.businesses.diagnostics-customized", $company->id) }}',
    hardcoded: '{{ route("superadmin.businesses.diagnostics-hardcoded", $company->id) }}'
};

async function runDiagnostics(type) {
    const btn = document.getElementById('btnRun' + capitalize(type));
    const btnOriginalHTML = btn.innerHTML;
    const progressDiv = document.getElementById('progress' + capitalize(type));
    const progressBar = document.getElementById('progressBar' + capitalize(type));
    const progressText = document.getElementById('progressText' + capitalize(type));
    const progressPercent = document.getElementById('progressPercent' + capitalize(type));
    const resultsDiv = document.getElementById('results' + capitalize(type));

    // Disable button safely
    if (btn.classList.contains('diag-btn-running')) return;
    btn.classList.add('diag-btn-running');
    btn.innerHTML = `<i data-lucide="loader-2" class="spin" style="width:14px;height:14px;margin-right:6px;display:inline-block;"></i> Scanning...`;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    resultsDiv.innerHTML = '';
    progressDiv.style.display = 'block';
    
    progressBar.style.width = '5%';
    progressPercent.textContent = '5%';
    progressText.textContent = 'Fetching configuration data...';

    try {
        // Fetch data immediately
        const response = await fetch(diagRoutes[type], {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }
        
        progressBar.style.width = '20%';
        progressPercent.textContent = '20%';
        progressText.textContent = 'Preparing scan queue...';

        // 1. Initial Render: Render all items in "PENDING" mode
        renderPendingDiagnostics(type, data, resultsDiv);
        
        // 2. Animate and "Scan" sequentially
        await animateScan(type, data, progressBar, progressPercent, progressText);

        // Done
        btn.classList.remove('diag-btn-running');
        btn.innerHTML = `<i data-lucide="check" style="width:14px;height:14px;margin-right:6px;display:inline-block;"></i> Run Again`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        progressDiv.style.display = 'none';

    } catch (err) {
        console.error("DIAGNOSTIC ERROR:", err);
        btn.classList.remove('diag-btn-running');
        btn.innerHTML = btnOriginalHTML;
        progressText.textContent = 'Error: ' + err.message;
        progressText.style.color = 'red';
    }
}

// Renders the table layout and empty/pending rows
function renderPendingDiagnostics(type, data, container) {
    let html = '';
    
    // Header based on type
    if (type === 'customized') {
        html += `<div style="display:grid;grid-template-columns:30% 25% 45%;background:hsl(var(--muted)/0.5);border-radius:8px;margin-bottom:12px;border:1px solid hsl(var(--border));font-size:12px;font-weight:600;color:hsl(var(--muted-foreground));">
            <div style="padding:10px 16px;">Setting Name</div>
            <div style="padding:10px 16px;">Admin Set?</div>
            <div style="padding:10px 16px;">System Connected?</div>
        </div>`;
    } else {
        html += `<div style="display:grid;grid-template-columns:35% 20% 45%;background:hsl(var(--muted)/0.5);border-radius:8px;margin-bottom:12px;border:1px solid hsl(var(--border));font-size:12px;font-weight:600;color:hsl(var(--muted-foreground));">
            <div style="padding:10px 16px;">Rule Name</div>
            <div style="padding:10px 16px;">Working?</div>
            <div style="padding:10px 16px;">Connected To & Bot Flow</div>
        </div>`;
    }

    const categories = Object.keys(data);
    categories.forEach(category => {
        const rows = data[category];
        if(!rows || rows.length === 0) return;

        const catName = category.replace('_', ' ').toUpperCase();
        html += `<div class="diag-col-header" style="margin-top:16px;">📂 ${catName}</div>`;

        if (type === 'hardcoded' && category === 'catalogue_rules') {
            html += `<div style="display:grid;grid-template-columns:25% 25% 25% 25%;background:#f1f5f9;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em;border-radius:6px 6px 0 0;margin-top:10px;">
                <div style="padding:10px 16px;">Rule Name</div>
                <div style="padding:10px 16px;">Product Page</div>
                <div style="padding:10px 16px;">Chartflow</div>
                <div style="padding:10px 16px;">Lead/Quote</div>
            </div>`;
        }

        rows.forEach((r, idx) => {
            const rowId = `diag-${type}-${category}-${idx}`;
            
            // Render pending state
            if (type === 'customized') {
                html += `<div id="${rowId}" class="diag-row" style="display:grid;grid-template-columns:30% 25% 45%;opacity:0.6;background:#f8fafc;">
                    <div class="diag-col" style="font-weight:600;color:#64748b;">
                        <i data-lucide="loader-2" class="spin pending-spin" style="width:14px;height:14px;margin-right:6px;"></i> 
                        ${escHtml(r.name)}
                    </div>
                    <div class="diag-col diag-target-col1" style="color:#94a3b8;">Waiting...</div>
                    <div class="diag-col diag-target-col2" style="color:#94a3b8;">Waiting...</div>
                </div>`;
            } else if (type === 'hardcoded' && category === 'catalogue_rules') {
                html += `<div id="${rowId}" class="diag-row" style="display:grid;grid-template-columns:25% 25% 25% 25%;opacity:0.6;background:#f8fafc;">
                    <div class="diag-col" style="font-weight:600;color:#64748b;">
                        <i data-lucide="loader-2" class="spin pending-spin" style="width:14px;height:14px;margin-right:6px;"></i> 
                        ${escHtml(r.rule_name)}
                    </div>
                    <div class="diag-col diag-target-col1" style="color:#94a3b8;display:flex;align-items:center;">Waiting...</div>
                    <div class="diag-col diag-target-col2" style="color:#94a3b8;display:flex;align-items:center;">Waiting...</div>
                    <div class="diag-col diag-target-col3" style="color:#94a3b8;display:flex;align-items:center;">Waiting...</div>
                </div>`;
            } else {
                html += `<div id="${rowId}" class="diag-row" style="display:grid;grid-template-columns:35% 20% 45%;opacity:0.6;background:#f8fafc;">
                    <div class="diag-col" style="font-weight:600;color:#64748b;">
                        <i data-lucide="loader-2" class="spin pending-spin" style="width:14px;height:14px;margin-right:6px;"></i> 
                        ${escHtml(r.rule_name)}
                    </div>
                    <div class="diag-col diag-target-col1" style="color:#94a3b8;">Waiting...</div>
                    <div class="diag-col diag-target-col2" style="display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:10px 16px;color:#94a3b8;">
                       Waiting...
                    </div>
                </div>`;
            }
        });
    });

    // Summary Placeholder
    html += `<div id="diag-summary-${type}" class="diag-summary" style="display:none;"></div>`;

    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Sequentially animate the resolution of each scan row
function animateScan(type, data, progressBar, progressPercent, progressText) {
    return new Promise(resolve => {
        let totalRows = 0;
        const flatRows = [];
        
        // Flatten rows
        Object.keys(data).forEach(cat => {
            if(data[cat]) {
                data[cat].forEach((r, idx) => {
                    totalRows++;
                    flatRows.push({ category: cat, index: idx, data: r });
                });
            }
        });
        
        if (totalRows === 0) {
            resolve();
            return;
        }

        let totalPass = 0, totalWarn = 0, totalFail = 0;
        let currentIndex = 0;

        function processNext() {
            if (currentIndex >= flatRows.length) {
                // Done
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressText.textContent = 'Complete!';

                // Render Final Summary
                const summaryContainer = document.getElementById(`diag-summary-${type}`);
                summaryContainer.style.display = 'flex';
                
                let passBadge = `<span class="diag-badge" style="background:hsl(142 71% 45% / 0.15);color:hsl(142 71% 45%);"><i data-lucide="check-circle-2" style="width:14px;height:14px;"></i> ${totalPass} PASS</span>`;
                let warnBadge = totalWarn > 0 ? `<span class="diag-badge" style="background:hsl(38 92% 50% / 0.15);color:hsl(38 92% 50%);"><i data-lucide="alert-triangle" style="width:14px;height:14px;"></i> ${totalWarn} WARNING</span>` : '';
                let failBadge = totalFail > 0 ? `<span class="diag-badge" style="background:hsl(0 84% 60% / 0.15);color:hsl(0 84% 60%);"><i data-lucide="x-circle" style="width:14px;height:14px;"></i> ${totalFail} FAIL</span>` : '';
                let summaryBg = totalFail > 0 ? 'rgba(239,68,68,0.05)' : (totalWarn > 0 ? 'rgba(245,158,11,0.05)' : 'rgba(34,197,94,0.05)');

                summaryContainer.style.background = summaryBg;
                summaryContainer.innerHTML = `
                    <div style="display:flex;gap:8px;align-items:center;">
                        ${passBadge} ${warnBadge} ${failBadge}
                    </div>
                    <div style="color:hsl(var(--muted-foreground));font-size:13px;">
                        Total Rules Checked: <span style="font-weight:bold;color:hsl(var(--foreground));">${totalRows}</span>
                    </div>
                `;
                if (typeof lucide !== 'undefined') lucide.createIcons();

                resolve();
                return;
            }

            const item = flatRows[currentIndex];
            const r = item.data;
            const rowEl = document.getElementById(`diag-${type}-${item.category}-${item.index}`);

            if (rowEl) {
                // Update stats
                if(r.severity === 'success') totalPass++;
                else if(r.severity === 'warning') totalWarn++;
                else totalFail++;

                let iconCls = r.severity === 'success' ? 'diag-icon-pass' : (r.severity === 'warning' ? 'diag-icon-warn' : 'diag-icon-fail');
                let iconName = r.severity === 'success' ? 'check-circle-2' : (r.severity === 'warning' ? 'alert-triangle' : 'x-circle');

                // Animate row resolution
                rowEl.style.opacity = '1';
                rowEl.style.background = 'white';
                
                // Remove pending icon from name
                const nameCol = rowEl.querySelector('.diag-col');
                nameCol.style.color = '#111';
                const spinIcon = nameCol.querySelector('.pending-spin');
                if (spinIcon) spinIcon.remove();

                const col1 = rowEl.querySelector('.diag-target-col1');
                const col2 = rowEl.querySelector('.diag-target-col2');
                const col3 = rowEl.querySelector('.diag-target-col3');

                if (col1) col1.style.color = '#333';
                if (col2) col2.style.color = '#333';
                if (col3) col3.style.color = '#333';

                if (type === 'customized') {
                    col1.innerHTML = `<span style="display:flex;align-items:center;gap:6px;">
                            ${r.admin_set ? '<span>✅</span>' : '<span>❌</span>'} ${escHtml(r.admin_detail)}
                        </span>`;
                    
                    col2.innerHTML = `<span style="display:flex;align-items:center;gap:6px;width:100%;">
                            <i data-lucide="${iconName}" class="${iconCls}" style="width:16px;min-width:16px;"></i> <span style="flex:1;">${escHtml(r.connected_detail)}</span>
                        </span>`;

                    if (r.input_text || r.process_text) {
                        let extraHtml = `<div style="grid-column: 1 / -1; margin-top:10px; font-size:12px; background:hsl(var(--muted)/0.3); padding:12px 16px; border-radius:6px; border:1px dashed hsl(var(--border)); display:flex; flex-direction:column; gap:8px;">
                            ${r.input_text ? `<div style="color:hsl(var(--primary));line-height:1.4;"><strong>🤖 Bot Asks / Input:</strong> ${escHtml(r.input_text)}</div>` : ''}
                            ${r.process_text ? `<div style="color:hsl(var(--muted-foreground));line-height:1.4;"><strong>⚙️ Background Process:</strong> ${escHtml(r.process_text)}</div>` : ''}
                        </div>`;
                        rowEl.insertAdjacentHTML('beforeend', extraHtml);
                        rowEl.style.paddingBottom = '16px';
                    }
                } else if (type === 'hardcoded' && item.category === 'catalogue_rules') {
                    // Do not escape HTML here because it comes formatted from our backend safely
                    col1.innerHTML = `<span style="font-size:12px;color:hsl(var(--muted-foreground));line-height:1.4;">${r.product_page}</span>`;
                    col2.innerHTML = `<span style="font-size:12px;color:hsl(var(--muted-foreground));line-height:1.4;">${r.chatflow}</span>`;
                    col3.innerHTML = `<span style="font-size:12px;color:hsl(var(--muted-foreground));line-height:1.4;">${r.lead_quote}</span>`;
                } else {
                    col1.innerHTML = `<span style="display:flex;align-items:center;gap:6px;">
                            <i data-lucide="${iconName}" class="${iconCls}" style="width:16px;min-width:16px;"></i> ${r.working ? 'Working' : 'Not Working'}
                        </span>`;
                    col2.innerHTML = `<span style="font-weight:600;font-size:11px;color:hsl(var(--primary));background:hsl(var(--primary)/0.1);padding:2px 6px;border-radius:4px;margin-bottom:4px;">${escHtml(r.connected_to)}</span>
                        <span style="font-size:12px;color:hsl(var(--muted-foreground));line-height:1.4;">💬 ${escHtml(r.bot_flow)}</span>
                        ${r.detail !== 'N/A' && r.detail !== '' ? `<span style="font-size:11px;margin-top:4px;">${escHtml(r.detail)}</span>` : ''}`;
                }

                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

            // Update Progress
            let pct = 20 + Math.floor(((currentIndex + 1) / totalRows) * 80);
            progressBar.style.width = pct + '%';
            progressPercent.textContent = pct + '%';
            progressText.textContent = `Analyzing ${escHtml(r.name || r.rule_name)}...`;

            currentIndex++;
            setTimeout(processNext, 250); // Scan next row after 250ms delay
        }

        // Start scanning cycle
        setTimeout(processNext, 200);
    });
}

function capitalize(s) {
    if (typeof s !== 'string') return '';
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML.replace(/\n/g, '<br>');
}

// Load configurations on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePrice();
    togglePaymentInfo();

});
</script>
@endpush
