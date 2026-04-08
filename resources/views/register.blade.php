<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — {{ $package->name }} | VyaparCRM</title>
    <meta name="description" content="Create your VyaparCRM account with the {{ $package->name }} plan. Start managing your business smarter today.">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="landing-page">

<div class="l-auth-page">
    <!-- Background effects -->
    <div class="l-auth-blobs">
        <div class="l-blob l-blob-1"></div>
        <div class="l-blob l-blob-2"></div>
    </div>

    <div class="l-auth-card" style="max-width:560px;">
        <div class="l-auth-header">
            <a href="{{ route('landing') }}" class="l-auth-logo">
                <div class="l-navbar-logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <span style="font-size:1.35rem;font-weight:800;letter-spacing:-0.5px;">VyaparCRM</span>
            </a>
            <h1 class="l-auth-title">Create Your Account</h1>
            <p class="l-auth-subtitle">Set up your business in under a minute</p>
        </div>

        <!-- Selected Package -->
        <div class="l-auth-package">
            <div class="l-auth-package-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div>
                <div class="l-auth-package-name">{{ $package->name }}</div>
                <div class="l-auth-package-price">
                    {{ $package->getPriceLabel('monthly') }}/month
                    @if($package->trial_days > 0)
                        · {{ $package->trial_days }}-day free trial
                    @endif
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="l-auth-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" id="registerForm">
            @csrf
            <input type="hidden" name="package_id" value="{{ $package->id }}">

            <div class="l-form-group">
                <label class="l-form-label">Business Name *</label>
                <input type="text" name="business_name" class="l-form-input" placeholder="Your Company Pvt Ltd" required value="{{ old('business_name') }}">
            </div>

            <div class="l-form-group">
                <label class="l-form-label">Your Name *</label>
                <input type="text" name="admin_name" class="l-form-input" placeholder="Rajesh Kumar" required value="{{ old('admin_name') }}">
            </div>

            <div class="l-form-row">
                <div class="l-form-group">
                    <label class="l-form-label">Email Address *</label>
                    <input type="email" name="email" id="regEmail" class="l-form-input" placeholder="you@company.com" required value="{{ old('email') }}" oninput="checkEmailAvailability()">
                    <div id="emailStatus" style="font-size:0.78rem;margin-top:4px;"></div>
                </div>
                <div class="l-form-group">
                    <label class="l-form-label">Phone *</label>
                    <input type="text" name="phone" class="l-form-input" placeholder="+91 98765 43210" required value="{{ old('phone') }}">
                </div>
            </div>

            <div class="l-form-row">
                <div class="l-form-group">
                    <label class="l-form-label">Password *</label>
                    <input type="password" name="password" id="regPassword" class="l-form-input" placeholder="Min 8 characters" required minlength="8">
                    <div id="passwordStrength" style="font-size:0.78rem;margin-top:4px;"></div>
                </div>
                <div class="l-form-group">
                    <label class="l-form-label">Confirm Password *</label>
                    <input type="password" name="password_confirmation" id="regPasswordConfirm" class="l-form-input" placeholder="Re-enter password" required>
                    <div id="passwordMatch" style="font-size:0.78rem;margin-top:4px;"></div>
                </div>
            </div>

            <button type="submit" class="l-btn l-btn-primary l-pricing-btn" id="registerBtn" style="margin-top:12px;">
                Create Account & Start
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </form>

        <div class="l-auth-footer">
            Already have an account? <a href="{{ route('login') }}">Sign In</a>
        </div>
    </div>
</div>

<script>
// Email availability check (debounced)
let emailTimeout;
function checkEmailAvailability() {
    clearTimeout(emailTimeout);
    const email = document.getElementById('regEmail').value;
    const status = document.getElementById('emailStatus');
    if (!email || !email.includes('@')) { status.textContent = ''; return; }
    emailTimeout = setTimeout(() => {
        fetch('{{ route("register.check-email") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ email })
        }).then(r => r.json()).then(data => {
            if (data.exists) {
                status.innerHTML = '<span style="color:#dc2626;">✕ Email already registered</span>';
            } else {
                status.innerHTML = '<span style="color:#16a34a;">✓ Email available</span>';
            }
        }).catch(() => { status.textContent = ''; });
    }, 500);
}

// Password strength
document.getElementById('regPassword').addEventListener('input', function() {
    const val = this.value;
    const el = document.getElementById('passwordStrength');
    if (val.length < 8) { el.innerHTML = '<span style="color:#dc2626;">Too short (min 8)</span>'; return; }
    const hasUpper = /[A-Z]/.test(val), hasLower = /[a-z]/.test(val), hasNum = /[0-9]/.test(val), hasSpec = /[^A-Za-z0-9]/.test(val);
    const score = [hasUpper, hasLower, hasNum, hasSpec].filter(Boolean).length;
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['', '#dc2626', '#f59e0b', '#16a34a', '#059669'];
    el.innerHTML = `<span style="color:${colors[score]};">${labels[score]} password</span>`;
});

// Password match
document.getElementById('regPasswordConfirm').addEventListener('input', function() {
    const el = document.getElementById('passwordMatch');
    if (this.value === document.getElementById('regPassword').value) {
        el.innerHTML = '<span style="color:#16a34a;">✓ Passwords match</span>';
    } else {
        el.innerHTML = '<span style="color:#dc2626;">✕ Passwords don\'t match</span>';
    }
});
</script>
</body>
</html>
