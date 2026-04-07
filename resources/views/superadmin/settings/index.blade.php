@extends('superadmin.layouts.app')
@section('title', 'Global Settings')

@push('styles')
<style>
    /* ─── Settings Page Styles ─── */
    .settings-tabs {
        display: flex; gap: 4px; margin-bottom: 24px;
        border-bottom: 2px solid hsl(var(--border));
        overflow-x: auto; -webkit-overflow-scrolling: touch;
    }
    .settings-tab {
        padding: 12px 20px; border: none; background: none;
        font-size: 14px; font-weight: 600; color: hsl(var(--muted-foreground));
        cursor: pointer; border-bottom: 2px solid transparent;
        margin-bottom: -2px; transition: all 0.2s; white-space: nowrap;
        display: flex; align-items: center; gap: 6px;
        font-family: inherit;
    }
    .settings-tab:hover { color: hsl(var(--primary)); }
    .settings-tab.active { color: hsl(var(--primary)); border-bottom-color: hsl(var(--primary)); }
    .settings-tab i, .settings-tab svg { width: 16px; height: 16px; }

    .settings-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
    }
    .settings-card {
        background: hsl(var(--card)); border: 1px solid hsl(var(--border));
        border-radius: 16px; overflow: hidden;
    }
    .settings-card-header {
        padding: 16px 22px; border-bottom: 1px solid hsl(var(--border));
        display: flex; align-items: center; gap: 8px;
    }
    .settings-card-header h3 { font-size: 15px; font-weight: 700; margin: 0; }
    .settings-card-body { padding: 20px 22px; }

    .form-row { margin-bottom: 18px; }
    .form-row:last-child { margin-bottom: 0; }
    .form-row label.field-label {
        display: block; font-size: 13px; font-weight: 600;
        color: hsl(var(--foreground)); margin-bottom: 6px;
    }
    .form-row .form-control {
        width: 100%; padding: 10px 14px;
        background: hsl(var(--background)); border: 1px solid hsl(var(--border));
        border-radius: 10px; font-size: 14px; color: hsl(var(--foreground));
        font-family: inherit; transition: all 0.2s; outline: none;
    }
    .form-row .form-control:focus {
        border-color: hsl(var(--primary));
        box-shadow: 0 0 0 3px hsl(var(--primary) / 0.1);
    }
    .form-row .hint {
        font-size: 11px; color: hsl(var(--muted-foreground)); margin-top: 4px; line-height: 1.5;
    }

    .toggle-row {
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
        cursor: pointer; padding: 6px 0;
    }
    .toggle-row input[type="checkbox"] {
        width: 18px; height: 18px; accent-color: hsl(var(--primary));
        cursor: pointer; flex-shrink: 0;
    }
    .toggle-row span { font-size: 13px; font-weight: 500; }

    .info-box {
        background: hsl(var(--muted) / 0.15); padding: 14px 16px;
        border-radius: 10px; line-height: 1.7; font-size: 12px;
        color: hsl(var(--muted-foreground));
    }
    .info-box strong { font-weight: 700; color: hsl(var(--foreground)); }

    .calc-box {
        background: hsl(var(--muted) / 0.15); padding: 14px 16px;
        border-radius: 10px; margin-bottom: 18px;
    }
    .calc-box .calc-title {
        font-size: 11px; font-weight: 700; color: hsl(var(--muted-foreground));
        margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .calc-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
        font-size: 12px; color: hsl(var(--muted-foreground));
    }
    .calc-grid strong { color: hsl(var(--foreground)); }

    .save-row {
        display: flex; justify-content: flex-end; margin-top: 20px;
    }
    .save-row .btn { padding: 10px 24px; font-size: 14px; }

    /* ─── Responsive ─── */
    @media (max-width: 992px) {
        .settings-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .settings-tabs { gap: 0; }
        .settings-tab { padding: 10px 14px; font-size: 12px; }
        .settings-card-body { padding: 16px; }
        .settings-card-header { padding: 14px 16px; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>Global Settings</h1>
    <p>Platform-wide configuration for AI credits, payments, and system defaults</p>
</div>

{{-- Tab Navigation --}}
<div class="settings-tabs">
    <button class="settings-tab active" onclick="switchTab('credits')" id="tab-credits">
        <i data-lucide="coins"></i> AI Credits
    </button>
    <button class="settings-tab" onclick="switchTab('platform')" id="tab-platform">
        <i data-lucide="settings-2"></i> Platform
    </button>
    <button class="settings-tab" onclick="switchTab('payment')" id="tab-payment">
        <i data-lucide="credit-card"></i> Payment
    </button>
</div>

{{-- ═══ TAB 1: AI Credits ═══ --}}
<div class="settings-panel" id="panel-credits">
    <form method="POST" action="{{ route('superadmin.settings.save-credits') }}">
        @csrf
        <div class="settings-grid">
            <div class="settings-card">
                <div class="settings-card-header">
                    <i data-lucide="calculator" style="width:16px;height:16px;color:hsl(var(--primary));"></i>
                    <h3>Token → Credit Conversion</h3>
                </div>
                <div class="settings-card-body">
                    <div class="form-row">
                        <label class="field-label">Credits per 1,000 AI Tokens</label>
                        <input type="number" name="credits_per_1k_tokens" class="form-control" value="{{ $creditSettings['credits_per_1k_tokens'] }}" min="0.01" step="0.01" required>
                        <div class="hint">E.g., 1.2 means 1,000 tokens = 1.2 credits deducted. Gemini Flash costs ~$0.075/1M tokens (₹6.30/1M).</div>
                    </div>

                    <div class="calc-box">
                        <div class="calc-title">💡 Pricing Calculator</div>
                        <div class="calc-grid">
                            <div>1 credit ≈ <strong id="calc-tokens">833</strong> tokens</div>
                            <div>100 credits ≈ <strong id="calc-conversations">~33</strong> conversations</div>
                            <div>Avg. conversation: ~3,000 tokens</div>
                            <div>Cost per credit: <strong>₹<span id="calc-cost">0.005</span></strong></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <label class="field-label">Minimum Credits to Operate</label>
                        <input type="number" name="min_credits_to_operate" class="form-control" value="{{ $creditSettings['min_credits_to_operate'] }}" min="1" required>
                        <div class="hint">Bot will stop responding when balance drops below this number.</div>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-header">
                    <i data-lucide="bell" style="width:16px;height:16px;color:hsl(var(--warning));"></i>
                    <h3>Low Balance Alerts</h3>
                </div>
                <div class="settings-card-body">
                    <div class="form-row">
                        <label class="field-label">Low Balance Threshold (Credits)</label>
                        <input type="number" name="low_balance_threshold" class="form-control" value="{{ $creditSettings['low_balance_threshold'] }}" min="1" required>
                        <div class="hint">Alert triggers when a company's balance falls below this.</div>
                    </div>

                    <label class="toggle-row">
                        <input type="checkbox" name="alert_admin_on_low" value="1" {{ $creditSettings['alert_admin_on_low'] ? 'checked' : '' }}>
                        <span>Alert Business Admin on low balance</span>
                    </label>

                    <label class="toggle-row">
                        <input type="checkbox" name="alert_sa_on_low" value="1" {{ $creditSettings['alert_sa_on_low'] ? 'checked' : '' }}>
                        <span>Alert Super Admin on low balance</span>
                    </label>

                    <div class="info-box" style="margin-top:12px;">
                        <strong>ℹ️ How Credits Work</strong><br>
                        • Every AI call deducts credits based on tokens used<br>
                        • Credits = (tokens / 1000) × rate<br>
                        • When balance &lt; minimum, bot stops responding<br>
                        • Admin must recharge to resume AI functionality
                    </div>
                </div>
            </div>
        </div>

        <div class="save-row">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" style="width:16px;height:16px;"></i> Save Credit Settings
            </button>
        </div>
    </form>
</div>

{{-- ═══ TAB 2: Platform ═══ --}}
<div class="settings-panel" id="panel-platform" style="display:none;">
    <form method="POST" action="{{ route('superadmin.settings.save-platform') }}">
        @csrf
        <div class="settings-grid">
            <div class="settings-card">
                <div class="settings-card-header">
                    <i data-lucide="shield" style="width:16px;height:16px;color:hsl(var(--primary));"></i>
                    <h3>System Defaults</h3>
                </div>
                <div class="settings-card-body">
                    <div class="form-row">
                        <label class="field-label">Default Trial Days</label>
                        <input type="number" name="default_trial_days" class="form-control" value="{{ $platformSettings['default_trial_days'] }}" min="0" required>
                        <div class="hint">Days of free trial for new businesses (0 = no trial).</div>
                    </div>

                    <div class="form-row">
                        <label class="field-label">Grace Period After Expiry (Days)</label>
                        <input type="number" name="grace_period_days" class="form-control" value="{{ $platformSettings['grace_period_days'] }}" min="0" required>
                        <div class="hint">Days after expiry where access still works (with warnings).</div>
                    </div>

                    <label class="toggle-row" style="margin-top:8px;">
                        <input type="checkbox" name="maintenance_mode" value="1" {{ $platformSettings['maintenance_mode'] ? 'checked' : '' }}>
                        <span style="color:hsl(var(--destructive));font-weight:700;">🛑 Maintenance Mode</span>
                    </label>
                    <div class="hint" style="margin-left:28px;margin-top:-8px;">Block all admin logins. Only SA can access.</div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-header">
                    <i data-lucide="headphones" style="width:16px;height:16px;color:hsl(var(--accent));"></i>
                    <h3>Support Contact</h3>
                </div>
                <div class="settings-card-body">
                    <div class="form-row">
                        <label class="field-label">Support Email</label>
                        <input type="email" name="support_email" class="form-control" value="{{ $platformSettings['support_email'] }}" placeholder="support@company.com">
                    </div>
                    <div class="form-row">
                        <label class="field-label">Support Phone</label>
                        <input type="text" name="support_phone" class="form-control" value="{{ $platformSettings['support_phone'] }}" placeholder="+91 98XXXXXXXX">
                    </div>
                    <div class="info-box">
                        Shown to admins in subscription warnings, upgrade prompts, and error pages.
                    </div>
                </div>
            </div>
        </div>

        <div class="save-row">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" style="width:16px;height:16px;"></i> Save Platform Settings
            </button>
        </div>
    </form>
</div>

{{-- ═══ TAB 3: Payment ═══ --}}
<div class="settings-panel" id="panel-payment" style="display:none;">
    <form method="POST" action="{{ route('superadmin.settings.save-payment') }}">
        @csrf
        <div class="settings-grid">
            <div class="settings-card">
                <div class="settings-card-header">
                    <i data-lucide="landmark" style="width:16px;height:16px;color:hsl(var(--primary));"></i>
                    <h3>Razorpay Configuration</h3>
                </div>
                <div class="settings-card-body">
                    <label class="toggle-row">
                        <input type="checkbox" name="razorpay_enabled" value="1" {{ $razorpaySettings['enabled'] ? 'checked' : '' }}>
                        <span class="field-label" style="margin:0;">Enable Razorpay Payments</span>
                    </label>
                    <div class="hint" style="margin-left:28px;margin-top:-8px;margin-bottom:16px;">Allow admins to pay online for subscriptions and credit packs.</div>

                    <div class="form-row">
                        <label class="field-label">Razorpay Key ID</label>
                        <input type="text" class="form-control" value="{{ $razorpaySettings['key_id'] }}" disabled style="opacity:0.6;">
                        <div class="hint">Set in <code>.env</code> file: RAZORPAY_KEY_ID</div>
                    </div>

                    <div class="form-row">
                        <label class="field-label">Razorpay Key Secret</label>
                        <input type="text" class="form-control" value="{{ $razorpaySettings['key_secret'] ?: 'Not configured' }}" disabled style="opacity:0.6;">
                        <div class="hint">Set in <code>.env</code> file: RAZORPAY_KEY_SECRET</div>
                    </div>

                    <div class="form-row">
                        <label class="field-label">Webhook Secret</label>
                        <input type="text" class="form-control" value="{{ $razorpaySettings['webhook_secret'] ?: 'Not configured' }}" disabled style="opacity:0.6;">
                        <div class="hint">Set in <code>.env</code> file: RAZORPAY_WEBHOOK_SECRET</div>
                    </div>
                </div>
            </div>

            <div class="settings-card">
                <div class="settings-card-header">
                    <i data-lucide="wallet" style="width:16px;height:16px;color:hsl(var(--success));"></i>
                    <h3>Manual Payments</h3>
                </div>
                <div class="settings-card-body">
                    <div class="info-box" style="line-height:2;">
                        <strong>Manual payment flow:</strong><br>
                        1. Admin contacts you for subscription/credits<br>
                        2. Receive payment via UPI/bank transfer<br>
                        3. Go to <strong>Businesses → Detail → Subscription</strong><br>
                        4. Assign/renew subscription with Payment Method = "Manual"<br>
                        5. For credits: Use <strong>Add Credits</strong> section<br>
                        <br>
                        <em>Razorpay automates steps 2-5, but manual is always available.</em>
                    </div>
                </div>
            </div>
        </div>

        <div class="save-row">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save" style="width:16px;height:16px;"></i> Save Payment Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('panel-' + tab).style.display = 'block';
    document.getElementById('tab-' + tab).classList.add('active');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
switchTab('credits');

// Pricing calculator
const rateInput = document.querySelector('input[name="credits_per_1k_tokens"]');
if (rateInput) {
    function updateCalc() {
        const rate = parseFloat(rateInput.value) || 1;
        document.getElementById('calc-tokens').textContent = Math.round(1000 / rate).toLocaleString();
        document.getElementById('calc-conversations').textContent = '~' + Math.round(100 / (3 * rate));
        document.getElementById('calc-cost').textContent = ((6.30 / 1000000) * 1000 / rate * 1000).toFixed(3);
    }
    rateInput.addEventListener('input', updateCalc);
    updateCalc();
}
</script>
@endpush
