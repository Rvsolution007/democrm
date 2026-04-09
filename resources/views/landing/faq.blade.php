@extends('landing.layout')
@section('title', 'Frequently Asked Questions — VyaparCRM')
@section('meta_desc', 'Got questions about VyaparCRM? Find answers about pricing, features, WhatsApp integration, security, and more.')

@section('content')
<div class="l-page-header">
    <div class="l-container">
        <div class="l-breadcrumb"><a href="{{ route('landing') }}">Home</a> <span>/</span> <span>FAQ</span></div>
        <h1>Frequently Asked<br><span class="l-gradient-text">Questions</span></h1>
        <p>Got questions? We've got answers. If you don't see what you're looking for, reach out to us.</p>
    </div>
</div>

<section class="l-section page-enter" style="padding-top:40px;">
    <div class="l-container">
        <div class="l-faq-list">
            <div class="l-faq-item reveal stagger-1" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>What is VyaparCRM and who is it for?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">VyaparCRM is a comprehensive business management platform designed for small and medium businesses in India. It combines CRM, invoicing, project management, WhatsApp automation, and AI chatbot into one powerful tool. Whether you're in sales, manufacturing, services, or trading — VyaparCRM adapts to your workflow.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-2" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>Is there a free trial available?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Yes! Most of our plans come with a free trial period. You can start using VyaparCRM without any payment or credit card. Explore all features during your trial and upgrade when you're ready.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-3" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>Can I manage my team with roles and permissions?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Absolutely! VyaparCRM supports multi-user access with granular role-based permissions. Create custom roles (Sales, Manager, Support) and control exactly what each team member can see and do — down to individual module access.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-4" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>How does the WhatsApp integration work?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Connect your WhatsApp Business number to VyaparCRM and unlock bulk messaging campaigns, automated auto-replies, AI-powered chatbot responses, and automatic lead capture from incoming messages. Set up custom templates and schedule campaigns with smart delay patterns.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-5" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>Is my data secure?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Yes, security is our top priority. All data is encrypted, we use industry-standard authentication with rate limiting, session management, and CSRF protection. Each business gets complete data isolation — your data is never shared or accessible by other tenants.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-6" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>Can I upgrade or downgrade my plan anytime?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Yes! You can request a plan upgrade or downgrade at any time from your admin dashboard. Our team will process the change and adjust your billing accordingly. No long-term contracts — stay because you love the product.</div></div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="l-cta-banner">
    <div class="l-container">
        <h2 class="reveal">Still Have Questions?</h2>
        <p class="reveal stagger-1">We're here to help. Get in touch with our team for personalized support.</p>
        <a href="{{ route('landing.contact') }}" class="l-btn l-btn-white l-btn-lg reveal stagger-2">Contact Us →</a>
    </div>
</section>
@endsection

@push('scripts')
<script>
function toggleFaq(item) {
    const wasActive = item.classList.contains('active');
    document.querySelectorAll('.l-faq-item').forEach(i => i.classList.remove('active'));
    if (!wasActive) item.classList.add('active');
}
</script>
@endpush
