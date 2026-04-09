@extends('landing.layout')
@section('title', 'VyaparCRM — Smart Business Management Platform')

@section('content')
<!-- ═══ HERO ═══ -->
<section class="l-hero" id="home">
    <div class="l-hero-orbits">
        <div class="l-orbit l-orbit-1"><div class="l-orbit-dot"></div></div>
        <div class="l-orbit l-orbit-2"><div class="l-orbit-dot l-orbit-dot-sky"></div></div>
        <div class="l-orbit l-orbit-3"><div class="l-orbit-dot l-orbit-dot-orange"></div></div>
    </div>
    <div class="l-blob l-blob-1"></div>
    <div class="l-blob l-blob-2"></div>
    <div class="l-blob l-blob-3"></div>
    <div class="l-hero-grid"></div>

    <div class="l-container l-hero-content">
        <div class="l-hero-text">
            <div class="l-hero-badge">
                <span class="l-hero-badge-dot"></span>
                Trusted by 500+ Businesses
            </div>
            <h1 class="l-hero-title">
                Grow Your Business<br>
                <span class="highlight">Smarter & Faster</span>
            </h1>
            <p class="l-hero-desc">
                All-in-one CRM platform to manage leads, automate WhatsApp, generate invoices, track projects, and close deals — powered with AI intelligence.
            </p>
            <div class="l-hero-actions">
                <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue l-btn-lg">
                    Start Free Trial
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="{{ route('landing.features') }}" class="l-btn l-btn-outline l-btn-lg">Explore Features</a>
            </div>
        </div>
        <div class="l-hero-visual">
            <div class="l-hero-card">
                <div class="l-hero-stats">
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value blue">2.4K+</div>
                        <div class="l-hero-stat-label">Active Leads</div>
                    </div>
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value sky">₹18L</div>
                        <div class="l-hero-stat-label">Revenue Tracked</div>
                    </div>
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value orange">95%</div>
                        <div class="l-hero-stat-label">Task Completion</div>
                    </div>
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value dark">24/7</div>
                        <div class="l-hero-stat-label">AI Chatbot</div>
                    </div>
                </div>
                <div class="l-float-card l-float-card-1">
                    <div class="l-float-icon blue">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <span>New deal closed!</span>
                </div>
                <div class="l-float-card l-float-card-2">
                    <div class="l-float-icon sky">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <span>WhatsApp lead</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ QUICK FEATURES STRIP ═══ -->
<section class="l-section-sm">
    <div class="l-container">
        <div class="l-quick-features">
            <div class="l-quick-feature reveal stagger-1">
                <div class="l-quick-feature-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Lead Management</h3>
                <p>Track & convert leads with Kanban boards</p>
            </div>
            <div class="l-quick-feature reveal stagger-2">
                <div class="l-quick-feature-icon sky">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3>Quotes & Invoices</h3>
                <p>GST-compliant billing in seconds</p>
            </div>
            <div class="l-quick-feature reveal stagger-3">
                <div class="l-quick-feature-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3>WhatsApp Automation</h3>
                <p>Bulk campaigns & auto-replies</p>
            </div>
            <div class="l-quick-feature reveal stagger-4">
                <div class="l-quick-feature-icon dark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                </div>
                <h3>AI Chatbot</h3>
                <p>24/7 smart customer engagement</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ ABOUT PREVIEW ═══ -->
<section class="l-section">
    <div class="l-container">
        <div class="l-about-grid">
            <div class="l-about-visual reveal-left">
                <div class="l-about-image">
                    <div class="l-orbit" style="width:200px;height:200px;top:50%;left:50%;transform:translate(-50%,-50%);animation:orbitSpin 20s linear infinite;"></div>
                    <div class="l-orbit" style="width:300px;height:300px;top:50%;left:50%;transform:translate(-50%,-50%);animation:orbitSpin 30s linear infinite reverse;"></div>
                    <div class="l-about-img-card">
                        <div class="l-about-img-card-value">10+</div>
                        <div class="l-about-img-card-label">Years of CRM Excellence</div>
                    </div>
                </div>
            </div>
            <div class="l-about-text reveal-right">
                <h2>We Build Software That <span class="l-gradient-text">Drives Growth</span></h2>
                <p>VyaparCRM was born from a simple idea — businesses shouldn't need 10 different tools to manage their operations. Our platform brings together lead management, invoicing, project tracking, WhatsApp automation, and AI-powered intelligence into one seamless experience.</p>
                <p>Built by a team with 20+ years of combined experience in frontend, backend development, and UX design.</p>
                <a href="{{ route('landing.about') }}" class="l-btn l-btn-blue" style="margin-top:16px;">Learn More About Us →</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FEATURES PREVIEW ═══ -->
<section class="l-section l-section-light">
    <div class="l-container">
        <h2 class="l-section-title reveal">Everything You Need to <span class="l-gradient-text">Win More Deals</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Powerful modules designed to streamline every aspect of your business — from first contact to final invoice.</p>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;">
            <div class="l-quick-feature reveal stagger-1" style="text-align:left;">
                <div class="l-quick-feature-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
                <h3>Lead Management</h3>
                <p>Capture, assign, and track leads through every stage of your sales pipeline with visual Kanban boards.</p>
            </div>
            <div class="l-quick-feature reveal stagger-2" style="text-align:left;">
                <div class="l-quick-feature-icon sky"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <h3>Quotes & Invoices</h3>
                <p>Create professional GST-compliant quotes and invoices in seconds. Auto-convert quotes to orders.</p>
            </div>
            <div class="l-quick-feature reveal stagger-3" style="text-align:left;">
                <div class="l-quick-feature-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                <h3>WhatsApp Automation</h3>
                <p>Send bulk campaigns, set auto-replies, and capture leads from WhatsApp messages automatically.</p>
            </div>
            <div class="l-quick-feature reveal stagger-4" style="text-align:left;">
                <div class="l-quick-feature-icon dark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2"/></svg></div>
                <h3>AI Chatbot</h3>
                <p>4-tier AI chatbot handles customer queries, recommends products, and generates leads 24/7.</p>
            </div>
            <div class="l-quick-feature reveal stagger-5" style="text-align:left;">
                <div class="l-quick-feature-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></div>
                <h3>Project & Task Tracking</h3>
                <p>Manage projects, assign tasks with priorities, set follow-ups, and monitor team progress.</p>
            </div>
            <div class="l-quick-feature reveal stagger-6" style="text-align:left;">
                <div class="l-quick-feature-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <h3>GST Billing & Finance</h3>
                <p>Complete financial suite — payments, purchases, vendor management, P&L reports, and analytics.</p>
            </div>
        </div>
        <div style="text-align:center;margin-top:40px;">
            <a href="{{ route('landing.features') }}" class="l-btn l-btn-blue l-btn-lg reveal">View All Features →</a>
        </div>
    </div>
</section>

<!-- ═══ PACKAGES PREVIEW ═══ -->
<section class="l-section">
    <div class="l-container">
        <h2 class="l-section-title reveal">Simple, Transparent <span class="l-gradient-text">Pricing</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Choose the plan that fits your business. Start with a free trial — no credit card required.</p>
        <div style="text-align:center;">
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue l-btn-lg reveal stagger-2">View Packages & Pricing →</a>
        </div>
    </div>
</section>

<!-- ═══ FAQ PREVIEW ═══ -->
<section class="l-section l-section-light">
    <div class="l-container">
        <h2 class="l-section-title reveal">Frequently Asked <span class="l-gradient-text">Questions</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Got questions? We've got answers.</p>

        <div class="l-faq-list">
            <div class="l-faq-item reveal stagger-1" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>What is VyaparCRM and who is it for?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">VyaparCRM is a comprehensive business management platform designed for small and medium businesses in India. It combines CRM, invoicing, project management, WhatsApp automation, and AI chatbot into one powerful tool.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-2" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>Is there a free trial available?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Yes! Most of our plans come with a free trial period. You can start using VyaparCRM without any payment or credit card and explore all features.</div></div>
            </div>
            <div class="l-faq-item reveal stagger-3" onclick="toggleFaq(this)">
                <div class="l-faq-question">
                    <span>Is my data secure?</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="l-faq-answer"><div class="l-faq-answer-inner">Yes, security is our top priority. All data is encrypted with industry-standard authentication, rate limiting, session management, and complete data isolation between tenants.</div></div>
            </div>
        </div>
        <div style="text-align:center;margin-top:32px;">
            <a href="{{ route('landing.faq') }}" class="l-btn l-btn-outline">View All FAQs →</a>
        </div>
    </div>
</section>

<!-- ═══ REVIEWS PREVIEW ═══ -->
<section class="l-section">
    <div class="l-container">
        <h2 class="l-section-title reveal">What Our Clients <span class="l-gradient-text">Say</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Trusted by businesses across India to streamline operations and boost revenue.</p>

        <div class="l-testimonials-grid">
            <div class="l-testimonial-card reveal stagger-1">
                <div class="l-testimonial-stars">@for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor</div>
                <p class="l-testimonial-text">"VyaparCRM transformed how we manage our sales pipeline. Our conversion rate improved by 40% in just 3 months!"</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar blue">RK</div>
                    <div><div class="l-testimonial-name">Rajesh Kumar</div><div class="l-testimonial-role">MD, TechVista Solutions Pvt Ltd</div></div>
                </div>
            </div>
            <div class="l-testimonial-card reveal stagger-2">
                <div class="l-testimonial-stars">@for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor</div>
                <p class="l-testimonial-text">"The WhatsApp automation feature is a game-changer! We saved 15+ hours per week. The AI chatbot handles queries brilliantly."</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar sky">PS</div>
                    <div><div class="l-testimonial-name">Priya Sharma</div><div class="l-testimonial-role">Founder, GreenLeaf Organics</div></div>
                </div>
            </div>
            <div class="l-testimonial-card reveal stagger-3">
                <div class="l-testimonial-stars">@for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor</div>
                <p class="l-testimonial-text">"GST invoicing, purchase tracking, and vendor management — all in one place. Best investment we've made!"</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar orange">AM</div>
                    <div><div class="l-testimonial-name">Amit Mehta</div><div class="l-testimonial-role">CEO, Precision Steel Works</div></div>
                </div>
            </div>
        </div>
        <div style="text-align:center;margin-top:32px;">
            <a href="{{ route('landing.reviews') }}" class="l-btn l-btn-outline">View All Reviews →</a>
        </div>
    </div>
</section>

<!-- ═══ CONTACT PREVIEW ═══ -->
<section class="l-section l-section-light">
    <div class="l-container" style="text-align:center;">
        <h2 class="l-section-title reveal">Get In <span class="l-gradient-text">Touch</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Have questions or need a demo? We'd love to hear from you.</p>
        <div class="reveal stagger-2" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('landing.contact') }}" class="l-btn l-btn-blue l-btn-lg">Contact Us →</a>
            <a href="mailto:support@vyaparcrm.com" class="l-btn l-btn-outline l-btn-lg">support@vyaparcrm.com</a>
        </div>
    </div>
</section>

<!-- ═══ CTA BANNER ═══ -->
<section class="l-cta-banner">
    <div class="l-container">
        <h2 class="reveal">Ready to Supercharge Your Business?</h2>
        <p class="reveal stagger-1">Join 500+ businesses already using VyaparCRM. Start your free trial today.</p>
        <a href="{{ route('landing.packages') }}" class="l-btn l-btn-white l-btn-lg reveal stagger-2">
            Get Started Free
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
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
