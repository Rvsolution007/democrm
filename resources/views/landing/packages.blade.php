@extends('landing.layout')
@section('title', 'Packages & Pricing — VyaparCRM')
@section('meta_desc', 'Choose the perfect VyaparCRM plan for your business. Transparent pricing with free trial.')

@section('content')
<div class="l-page-header">
    <div class="l-page-header-orbits">
        <div class="l-orbit" style="width:400px;height:400px;top:50%;left:55%;transform:translate(-50%,-50%);animation:orbitSpin 25s linear infinite;border-color:rgba(37,99,235,0.06);"></div>
    </div>
    <div class="l-container">
        <div class="l-breadcrumb"><a href="{{ route('landing') }}">Home</a> <span>/</span> <span>Packages</span></div>
        <h1>Simple, Transparent<br><span class="l-gradient-text">Pricing</span></h1>
        <p>Choose the plan that fits your business. Start with a free trial — no credit card required.</p>
    </div>
</div>

<section class="l-section page-enter" style="padding-top:40px;">
    <div class="l-container">
        <div class="l-pricing-toggle reveal">
            <span class="l-pricing-toggle-label active" id="monthlyLabel">Monthly</span>
            <button class="l-toggle-switch" id="pricingToggle" onclick="togglePricing()"></button>
            <span class="l-pricing-toggle-label" id="yearlyLabel">Yearly</span>
            <span class="l-save-badge">Save 20%</span>
        </div>

        <div class="l-pricing-grid">
            @foreach($packages as $index => $pkg)
            <div class="l-pricing-card reveal stagger-{{ ($index % 3) + 1 }} {{ $index === 1 ? 'featured' : '' }}">
                <div class="l-pricing-name">{{ $pkg->name }}</div>
                <div class="l-pricing-desc">{{ $pkg->description ?? 'Perfect for growing businesses' }}</div>

                <div class="l-pricing-price" id="price-{{ $pkg->id }}">
                    @if($pkg->monthly_price <= 0)
                        <span class="l-pricing-price-free">Free</span>
                    @else
                        <span class="currency">₹</span><span class="price-value" data-monthly="{{ number_format((float)$pkg->monthly_price, 0) }}" data-yearly="{{ number_format((float)$pkg->yearly_price, 0) }}">{{ number_format((float)$pkg->monthly_price, 0) }}</span><span class="period" id="period-{{ $pkg->id }}">/month</span>
                    @endif
                </div>

                @if($pkg->trial_days > 0)
                <div class="l-pricing-trial">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ $pkg->trial_days }}-day free trial
                </div>
                @else
                <div style="height: 28px;"></div>
                @endif

                <ul class="l-pricing-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Up to {{ $pkg->default_max_users }} team members
                    </li>
                    @php $enabled = $pkg->getEnabledFeatures(); @endphp
                    @if(in_array('leads', $enabled))
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Lead & Sales Pipeline</li>
                    @endif
                    @if(in_array('quotes', $enabled))
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Quotes & Invoices</li>
                    @endif
                    @if(in_array('whatsapp_connect', $enabled))
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>WhatsApp Integration</li>
                    @else
                    <li class="disabled"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>WhatsApp Integration</li>
                    @endif
                    @if(in_array('ai_bot', $enabled))
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI Chatbot</li>
                    @else
                    <li class="disabled"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>AI Chatbot</li>
                    @endif
                    @if(in_array('reports', $enabled))
                    <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Reports & Analytics</li>
                    @endif
                </ul>

                <a href="{{ route('register', $pkg->slug) }}" class="l-btn {{ $index === 1 ? 'l-btn-blue' : 'l-btn-outline' }} l-pricing-btn">
                    {{ $pkg->trial_days > 0 ? 'Start Free Trial' : 'Get Started' }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA -->
<section class="l-cta-banner">
    <div class="l-container">
        <h2 class="reveal">Not Sure Which Plan Is Right?</h2>
        <p class="reveal stagger-1">Contact us for a personalized recommendation based on your business needs.</p>
        <a href="{{ route('landing.contact') }}" class="l-btn l-btn-white l-btn-lg reveal stagger-2">Contact Sales →</a>
    </div>
</section>
@endsection

@push('scripts')
<script>
let isYearly = false;
function togglePricing() {
    isYearly = !isYearly;
    document.getElementById('pricingToggle').classList.toggle('active', isYearly);
    document.getElementById('monthlyLabel').classList.toggle('active', !isYearly);
    document.getElementById('yearlyLabel').classList.toggle('active', isYearly);
    document.querySelectorAll('.price-value').forEach(el => {
        el.textContent = isYearly ? el.dataset.yearly : el.dataset.monthly;
    });
    document.querySelectorAll('[id^="period-"]').forEach(el => {
        el.textContent = isYearly ? '/year' : '/month';
    });
}
</script>
@endpush
