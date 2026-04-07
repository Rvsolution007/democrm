@extends('superadmin.layouts.app')
@section('title', 'Add Business')

@section('content')
<div class="page-header" style="display:flex;align-items:center;gap:12px;">
    <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-ghost btn-icon btn-sm">
        <i data-lucide="arrow-left"></i>
    </a>
    <div>
        <h1>Add New Business</h1>
        <p>Onboard a new business with admin account and subscription</p>
    </div>
</div>

<form method="POST" action="{{ route('superadmin.businesses.store') }}">
    @csrf

    @if($errors->any())
    <div style="margin-bottom:16px;padding:14px 18px;background:hsl(var(--destructive)/0.08);border:1px solid hsl(var(--destructive)/0.2);border-radius:10px;font-size:13px;color:hsl(var(--destructive));">
        <strong>Please fix the following errors:</strong>
        <ul style="margin:8px 0 0 16px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        {{-- Business Info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">
                    <i data-lucide="building-2" style="width:18px;height:18px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>
                    Business Details
                </h3>
            </div>
            <div class="card-content">
                <div style="margin-bottom:16px;">
                    <label class="form-label">Company Name *</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" required placeholder="e.g. ABC Trading Pvt Ltd">
                </div>
            </div>
        </div>

        {{-- Owner Info --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title" style="font-size:16px;">
                    <i data-lucide="user-check" style="width:18px;height:18px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>
                    Admin Account
                </h3>
            </div>
            <div class="card-content">
                <div style="margin-bottom:16px;">
                    <label class="form-label">Admin Name *</label>
                    <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name') }}" required placeholder="Full name">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Email *</label>
                    <input type="email" name="owner_email" class="form-control" value="{{ old('owner_email') }}" required placeholder="admin@company.com">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="form-label">Phone *</label>
                        <input type="text" name="owner_phone" class="form-control" value="{{ old('owner_phone') }}" required placeholder="9876543210">
                    </div>
                    <div>
                        <label class="form-label">Password *</label>
                        <input type="text" name="owner_password" class="form-control" value="{{ old('owner_password', 'Welcome@123') }}" required>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Subscription --}}
    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <h3 class="card-title" style="font-size:16px;">
                <i data-lucide="credit-card" style="width:18px;height:18px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>
                Subscription Plan
            </h3>
        </div>
        <div class="card-content">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
                @foreach($packages as $pkg)
                <label style="cursor:pointer;">
                    <input type="radio" name="package_id" value="{{ $pkg->id }}" {{ old('package_id', $packages->first()->id) == $pkg->id ? 'checked' : '' }}
                        style="position:absolute;opacity:0;" onchange="this.closest('label').parentElement.querySelectorAll('.pkg-card').forEach(c=>c.classList.remove('selected'));this.closest('.pkg-card').classList.add('selected');">
                    <div class="pkg-card card {{ old('package_id', $packages->first()->id) == $pkg->id ? 'selected' : '' }}"
                         style="padding:20px;text-align:center;transition:all 0.2s;border:2px solid hsl(var(--border));">
                        <div style="font-size:16px;font-weight:700;margin-bottom:4px;">{{ $pkg->name }}</div>
                        <div style="font-size:13px;color:hsl(var(--muted-foreground));margin-bottom:12px;">{{ $pkg->description }}</div>
                        <div style="font-size:22px;font-weight:700;color:hsl(var(--primary));">
                            {{ $pkg->getPriceLabel('monthly') }}<span style="font-size:13px;font-weight:400;color:hsl(var(--muted-foreground));">/mo</span>
                        </div>
                        <div style="font-size:12px;color:hsl(var(--muted-foreground));margin-top:4px;">Max {{ $pkg->default_max_users }} users</div>
                    </div>
                </label>
                @endforeach
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                <div>
                    <label class="form-label">Billing Cycle *</label>
                    <select name="billing_cycle" class="form-select">
                        <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Duration (Days)</label>
                    <input type="number" name="subscription_days" class="form-control" value="{{ old('subscription_days', 30) }}" min="1" placeholder="30">
                </div>
                <div>
                    <label class="form-label">Max Users (Override)</label>
                    <input type="number" name="max_users" class="form-control" value="{{ old('max_users') }}" min="1" placeholder="Use package default">
                </div>
                <div>
                    <label class="form-label">Initial AI Credits</label>
                    <input type="number" name="initial_credits" class="form-control" value="{{ old('initial_credits', 500) }}" min="0" placeholder="500">
                </div>
            </div>
        </div>
    </div>

    {{-- Payment --}}
    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <h3 class="card-title" style="font-size:16px;">
                <i data-lucide="wallet" style="width:18px;height:18px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>
                Payment
            </h3>
        </div>
        <div class="card-content">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="form-label">Payment Method *</label>
                    <select name="payment_method" class="form-select">
                        <option value="free" {{ old('payment_method') === 'free' ? 'selected' : '' }}>Free Trial</option>
                        <option value="manual" {{ old('payment_method') === 'manual' ? 'selected' : '' }}>Manual (Bank/UPI/Cash)</option>
                        <option value="razorpay" {{ old('payment_method') === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Amount Paid (₹)</label>
                    <input type="number" name="amount_paid" class="form-control" value="{{ old('amount_paid', 0) }}" min="0" step="0.01" placeholder="0 for trial">
                </div>
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div style="display:flex;gap:12px;margin-top:20px;justify-content:flex-end;">
        <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i data-lucide="plus" style="width:16px;height:16px;"></i>
            Create Business
        </button>
    </div>
</form>

@push('styles')
<style>
    .pkg-card.selected { border-color: hsl(var(--primary)) !important; box-shadow: 0 0 0 3px hsl(var(--primary)/0.15); }
    .pkg-card:hover { border-color: hsl(var(--primary)/0.5); }
</style>
@endpush
@endsection
