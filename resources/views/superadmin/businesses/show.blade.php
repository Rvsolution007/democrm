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

{{-- ═══ Bot Trace Viewer ═══ --}}
<div class="card" style="margin-top:24px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid hsl(var(--border));">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,hsl(var(--primary)/0.15),hsl(var(--accent)/0.15));display:flex;align-items:center;justify-content:center;">
                <i data-lucide="activity" style="width:18px;height:18px;color:hsl(var(--primary));"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600;">Bot Node Traces</h3>
                <p style="margin:0;font-size:12px;color:hsl(var(--muted-foreground));">Diagnostic trace viewer for bot execution flows</p>
            </div>
        </div>
        {{-- Tab Switcher --}}
        <div style="display:flex;gap:4px;padding:3px;border-radius:8px;background:hsl(var(--muted));">
            <button type="button" class="btn btn-sm trace-tab active" data-bot-type="ai_bot" onclick="switchTraceTab(this, 'ai_bot')" style="border-radius:6px;font-size:12px;padding:6px 14px;transition:all .2s;">
                🤖 AI Bot
            </button>
            <button type="button" class="btn btn-sm trace-tab" data-bot-type="list_bot" onclick="switchTraceTab(this, 'list_bot')" style="border-radius:6px;font-size:12px;padding:6px 14px;transition:all .2s;">
                📋 Bot List
            </button>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <div id="traceSessionsContainer" style="min-height:200px;">
            <div style="text-align:center;padding:40px;color:hsl(var(--muted-foreground));">
                <i data-lucide="loader-2" class="spin" style="width:24px;height:24px;margin-bottom:8px;"></i>
                <p style="font-size:13px;">Loading sessions...</p>
            </div>
        </div>
        {{-- Pagination --}}
        <div id="tracePagination" style="display:none;padding:12px 20px;border-top:1px solid hsl(var(--border));display:flex;align-items:center;justify-content:space-between;">
        </div>
    </div>
</div>

{{-- Trace Flow Modal (inline expand) --}}
<style>
    .trace-tab { background:transparent; border:none; color:hsl(var(--muted-foreground)); cursor:pointer; }
    .trace-tab.active { background:hsl(var(--background)); color:hsl(var(--foreground)); box-shadow:0 1px 3px rgba(0,0,0,0.1); font-weight:600; }
    .trace-session-row { padding:14px 20px; border-bottom:1px solid hsl(var(--border)/0.5); display:flex; align-items:center; justify-content:space-between; cursor:pointer; transition:background .15s; }
    .trace-session-row:hover { background:hsl(var(--muted)/0.5); }
    .trace-session-row:last-child { border-bottom:none; }
    .trace-flow-container { display:none; background:hsl(var(--muted)/0.3); border-bottom:1px solid hsl(var(--border)); padding:16px 20px; }
    .trace-flow-container.open { display:block; }
    .trace-msg-group { margin-bottom:12px; }
    .trace-msg-header { font-size:12px; font-weight:600; color:hsl(var(--primary)); padding:6px 10px; background:hsl(var(--primary)/0.08); border-radius:6px; margin-bottom:6px; display:inline-block; }
    .trace-node { display:flex; align-items:flex-start; gap:12px; padding:8px 0; margin-left:20px; position:relative; }
    .trace-node::before { content:''; position:absolute; left:14px; top:28px; bottom:-8px; width:2px; background:hsl(var(--border)); }
    .trace-node:last-child::before { display:none; }
    .trace-node-dot { width:28px; height:28px; min-width:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid; z-index:1; background:hsl(var(--background)); }
    .trace-node-body { flex:1; min-width:0; }
    .trace-node-title { font-size:13px; font-weight:600; display:flex; align-items:center; gap:6px; }
    .trace-node-meta { font-size:11px; color:hsl(var(--muted-foreground)); margin-top:2px; }
    .trace-node-data { font-size:11px; padding:6px 8px; background:hsl(var(--muted)); border-radius:4px; margin-top:4px; overflow-x:auto; max-height:120px; overflow-y:auto; font-family:'SF Mono',Monaco,monospace; }
    .trace-node-data summary { cursor:pointer; font-weight:600; color:hsl(var(--foreground)); }
    .trace-badge { font-size:10px; padding:2px 6px; border-radius:4px; font-weight:600; }
    .trace-badge-success { background:hsl(142 71% 45% / 0.15); color:hsl(142 71% 45%); }
    .trace-badge-error { background:hsl(0 84% 60% / 0.15); color:hsl(0 84% 60%); }
    .trace-badge-warning { background:hsl(38 92% 50% / 0.15); color:hsl(38 92% 50%); }
    .trace-empty { text-align:center; padding:40px 20px; color:hsl(var(--muted-foreground)); }
    .trace-empty i { margin-bottom:8px; opacity:0.5; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .spin { animation: spin 1s linear infinite; }
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
// BOT TRACE VIEWER
// ═══════════════════════════════════════════════════════

let currentTraceTab = 'ai_bot';
let currentTracePage = 1;
const traceBaseUrl = '{{ route("superadmin.businesses.bot-traces", $company->id) }}';

function switchTraceTab(btn, botType) {
    document.querySelectorAll('.trace-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    currentTraceTab = botType;
    currentTracePage = 1;
    loadTraceSessions();
}

function loadTraceSessions() {
    const container = document.getElementById('traceSessionsContainer');
    container.innerHTML = '<div style="text-align:center;padding:40px;color:hsl(var(--muted-foreground));"><div class="spin" style="display:inline-block;width:24px;height:24px;border:2px solid hsl(var(--border));border-top-color:hsl(var(--primary));border-radius:50%;"></div><p style="font-size:13px;margin-top:8px;">Loading sessions...</p></div>';

    fetch(`${traceBaseUrl}?bot_type=${currentTraceTab}&page=${currentTracePage}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.sessions || data.sessions.length === 0) {
            const label = currentTraceTab === 'ai_bot' ? 'AI Bot' : 'Bot List';
            container.innerHTML = `<div class="trace-empty"><i data-lucide="inbox" style="width:32px;height:32px;display:block;margin:0 auto 8px;"></i><p style="font-size:13px;">No ${label} sessions found for this business.</p></div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.getElementById('tracePagination').style.display = 'none';
            return;
        }
        renderTraceSessions(data.sessions, container);
        renderTracePagination(data);
    })
    .catch(err => {
        container.innerHTML = `<div class="trace-empty"><i data-lucide="alert-circle" style="width:32px;height:32px;display:block;margin:0 auto 8px;color:hsl(var(--destructive));"></i><p style="font-size:13px;color:hsl(var(--destructive));">Failed to load sessions.</p></div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
}

function renderTraceSessions(sessions, container) {
    let html = '';
    sessions.forEach(s => {
        const statusBadge = s.status === 'active' ? '<span class="trace-badge trace-badge-success">Active</span>' : '<span class="trace-badge" style="background:hsl(var(--muted));color:hsl(var(--muted-foreground));">' + (s.status || 'done') + '</span>';
        const errorBadge = s.error_traces_count > 0 ? `<span class="trace-badge trace-badge-error">${s.error_traces_count} error${s.error_traces_count > 1 ? 's' : ''}</span>` : '<span class="trace-badge trace-badge-success">Healthy</span>';
        const leadsInfo = s.lead_id ? `<span style="font-size:11px;color:hsl(var(--muted-foreground));">L#${s.lead_id}</span>` : '';
        const quoteInfo = s.quote_id ? `<span style="font-size:11px;color:hsl(var(--muted-foreground));">Q#${s.quote_id}</span>` : '';

        html += `
        <div>
            <div class="trace-session-row" onclick="toggleTraceFlow(${s.id}, this)">
                <div style="display:flex;align-items:center;gap:12px;flex:1;">
                    <div style="width:32px;height:32px;border-radius:50%;background:hsl(var(--primary)/0.1);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="smartphone" style="width:14px;height:14px;color:hsl(var(--primary));"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;">${s.phone_number}</div>
                        <div style="font-size:11px;color:hsl(var(--muted-foreground));display:flex;gap:8px;align-items:center;">
                            ${statusBadge}
                            <span>${s.conversation_state || '—'}</span>
                            ${leadsInfo} ${quoteInfo}
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:16px;">
                    <div style="text-align:right;">
                        <div style="font-size:11px;color:hsl(var(--muted-foreground));">
                            <span title="Messages">💬 ${s.messages_count}</span> ·
                            <span title="Traces">📊 ${s.traces_count}</span>
                        </div>
                        <div style="display:flex;gap:4px;align-items:center;justify-content:flex-end;margin-top:2px;">
                            ${errorBadge}
                        </div>
                    </div>
                    <div style="font-size:11px;color:hsl(var(--muted-foreground));min-width:80px;text-align:right;" title="${s.last_message_at_full || ''}">
                        ${s.last_message_at || '—'}
                    </div>
                    <i data-lucide="chevron-down" style="width:16px;height:16px;color:hsl(var(--muted-foreground));transition:transform .2s;" class="trace-chevron"></i>
                </div>
            </div>
            <div class="trace-flow-container" id="traceFlow_${s.id}"></div>
        </div>`;
    });
    container.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderTracePagination(data) {
    const pag = document.getElementById('tracePagination');
    if (data.pages <= 1) { pag.style.display = 'none'; return; }
    pag.style.display = 'flex';
    let btns = '';
    for (let i = 1; i <= data.pages; i++) {
        const active = i === data.current_page ? 'background:hsl(var(--primary));color:white;' : '';
        btns += `<button class="btn btn-sm" style="min-width:32px;${active}" onclick="currentTracePage=${i};loadTraceSessions()">${i}</button>`;
    }
    pag.innerHTML = `<span style="font-size:12px;color:hsl(var(--muted-foreground));">${data.total} sessions</span><div style="display:flex;gap:4px;">${btns}</div>`;
}

function toggleTraceFlow(sessionId, rowEl) {
    const container = document.getElementById('traceFlow_' + sessionId);
    const chevron = rowEl.querySelector('.trace-chevron');

    if (container.classList.contains('open')) {
        container.classList.remove('open');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
        return;
    }

    // Close all others
    document.querySelectorAll('.trace-flow-container.open').forEach(c => {
        c.classList.remove('open');
        const prevChevron = c.previousElementSibling?.querySelector('.trace-chevron');
        if (prevChevron) prevChevron.style.transform = 'rotate(0deg)';
    });

    if (chevron) chevron.style.transform = 'rotate(180deg)';
    container.innerHTML = '<div style="text-align:center;padding:20px;"><div class="spin" style="display:inline-block;width:20px;height:20px;border:2px solid hsl(var(--border));border-top-color:hsl(var(--primary));border-radius:50%;"></div></div>';
    container.classList.add('open');

    fetch(`${traceBaseUrl}/${sessionId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        renderTraceFlow(container, data);
    })
    .catch(() => {
        container.innerHTML = '<div style="padding:16px;color:hsl(var(--destructive));font-size:13px;">Failed to load traces.</div>';
    });
}

function renderTraceFlow(container, data) {
    if (!data.messages || data.messages.length === 0) {
        container.innerHTML = '<div style="padding:20px;text-align:center;font-size:13px;color:hsl(var(--muted-foreground));">No traces recorded for this session.</div>';
        return;
    }

    let html = `<div style="padding:8px 0 4px;font-size:11px;color:hsl(var(--muted-foreground));display:flex;gap:12px;align-items:center;">
        <span>📊 ${data.total_traces} total nodes</span>
        <span>📱 ${data.session.phone_number}</span>
        ${data.session.lead_id ? '<span>🎯 Lead #' + data.session.lead_id + '</span>' : ''}
        ${data.session.quote_id ? '<span>📄 Quote #' + data.session.quote_id + '</span>' : ''}
    </div>`;

    data.messages.forEach(msg => {
        const msgLabel = msg.message_id ? ('💬 ' + (msg.user_message || '').substring(0, 80)) : '⚙️ System';
        html += `<div class="trace-msg-group">`;
        html += `<div class="trace-msg-header">${escHtml(msgLabel)}</div>`;

        msg.traces.forEach(trace => {
            const dotColor = trace.status_color || '#6b7280';
            const groupIcons = { 'git-branch': '🔀', 'brain': '🧠', 'database': '🗄️', 'send': '📤', 'image': '🖼️', 'bell': '🔔', 'circle': '⚪' };
            const icon = groupIcons[trace.group_icon] || '⚪';
            const statusCls = 'trace-badge-' + trace.status;
            const timeStr = trace.execution_time_ms > 0 ? `${trace.execution_time_ms}ms` : '';

            html += `<div class="trace-node">
                <div class="trace-node-dot" style="border-color:${dotColor};color:${dotColor};font-size:12px;">${icon}</div>
                <div class="trace-node-body">
                    <div class="trace-node-title">
                        ${escHtml(trace.node_name)}
                        <span class="trace-badge ${statusCls}">${trace.status}</span>
                        ${timeStr ? '<span style="font-size:10px;color:hsl(var(--muted-foreground));">' + timeStr + '</span>' : ''}
                    </div>
                    <div class="trace-node-meta">${trace.node_group} · ${trace.created_at}</div>`;

            if (trace.error_message) {
                html += `<div class="trace-node-data" style="border-left:3px solid hsl(0 84% 60%);">❌ ${escHtml(trace.error_message)}</div>`;
            }
            if (trace.input_data && Object.keys(trace.input_data).length) {
                html += `<div class="trace-node-data"><details><summary>📥 Input</summary><pre style="margin:4px 0 0;white-space:pre-wrap;word-break:break-all;">${escHtml(JSON.stringify(trace.input_data, null, 2))}</pre></details></div>`;
            }
            if (trace.output_data && Object.keys(trace.output_data).length) {
                html += `<div class="trace-node-data"><details><summary>📤 Output</summary><pre style="margin:4px 0 0;white-space:pre-wrap;word-break:break-all;">${escHtml(JSON.stringify(trace.output_data, null, 2))}</pre></details></div>`;
            }

            html += `</div></div>`;
        });

        html += `</div>`;
    });

    container.innerHTML = html;
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Load traces on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePrice();
    togglePaymentInfo();
    // Load trace sessions after a small delay
    setTimeout(loadTraceSessions, 500);
});
</script>
@endpush
