<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VyaparCRM — Smart Business Management Platform</title>
    <meta name="description" content="VyaparCRM is a powerful all-in-one CRM platform. Manage leads, quotes, invoices, WhatsApp automation, AI chatbot, projects, and more. Start your free trial today.">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="landing-page">

<!-- ═══ NAVBAR ═══ -->
<nav class="l-navbar" id="navbar">
    <div class="l-container l-navbar-inner">
        <a href="#" class="l-navbar-logo">
            <div class="l-navbar-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <span class="l-navbar-logo-text">VyaparCRM</span>
        </a>
        <ul class="l-navbar-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#about">About Us</a></li>
            <li><a href="#pricing">Packages</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#reviews">Reviews</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <div class="l-navbar-cta">
            <a href="{{ route('login') }}" class="l-btn l-btn-outline">Sign In</a>
            <a href="#pricing" class="l-btn l-btn-primary">Get Started</a>
        </div>
        <button class="l-menu-toggle" onclick="toggleMobileMenu()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="l-mobile-menu" id="mobileMenu">
    <button class="l-mobile-menu-close" onclick="toggleMobileMenu()">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <a href="#home" onclick="toggleMobileMenu()">Home</a>
    <a href="#features" onclick="toggleMobileMenu()">Features</a>
    <a href="#about" onclick="toggleMobileMenu()">About Us</a>
    <a href="#pricing" onclick="toggleMobileMenu()">Packages</a>
    <a href="#faq" onclick="toggleMobileMenu()">FAQ</a>
    <a href="#reviews" onclick="toggleMobileMenu()">Reviews</a>
    <a href="#contact" onclick="toggleMobileMenu()">Contact</a>
    <a href="{{ route('login') }}" class="l-btn l-btn-primary" onclick="toggleMobileMenu()">Sign In</a>
</div>

<!-- ═══ HERO ═══ -->
<section class="l-hero" id="home">
    <!-- Background effects -->
    <div class="l-hero-orbits">
        <div class="l-orbit l-orbit-1"><div class="l-orbit-dot"></div></div>
        <div class="l-orbit l-orbit-2"><div class="l-orbit-dot l-orbit-dot-teal"></div></div>
        <div class="l-orbit l-orbit-3"><div class="l-orbit-dot"></div></div>
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
                <span style="background:linear-gradient(135deg,#2563eb,#0ea5e9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Smarter & Faster</span>
            </h1>
            <p class="l-hero-desc">
                All-in-one CRM platform to manage leads, automate WhatsApp, generate invoices, track projects, and close deals — powered with AI intelligence.
            </p>
            <div class="l-hero-actions">
                <a href="#pricing" class="l-btn l-btn-primary l-btn-lg">
                    Start Free Trial
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="#features" class="l-btn l-btn-outline l-btn-lg">Explore Features</a>
            </div>
        </div>
        <div class="l-hero-visual">
            <div class="l-hero-card">
                <div class="l-hero-stats">
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value orange">2.4K+</div>
                        <div class="l-hero-stat-label">Active Leads</div>
                    </div>
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value teal">₹18L</div>
                        <div class="l-hero-stat-label">Revenue Tracked</div>
                    </div>
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value purple">95%</div>
                        <div class="l-hero-stat-label">Task Completion</div>
                    </div>
                    <div class="l-hero-stat">
                        <div class="l-hero-stat-value blue">24/7</div>
                        <div class="l-hero-stat-label">AI Chatbot</div>
                    </div>
                </div>
                <!-- Floating cards -->
                <div class="l-float-card l-float-card-1">
                    <div class="l-float-icon orange">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <span>New deal closed!</span>
                </div>
                <div class="l-float-card l-float-card-2">
                    <div class="l-float-icon teal">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <span>WhatsApp lead</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FEATURES ═══ -->
<section class="l-section" id="features">
    <div class="l-container">
        <h2 class="l-section-title reveal">Everything You Need to <span class="l-gradient-text">Win More Deals</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Powerful modules designed to streamline every aspect of your business — from first contact to final invoice.</p>

        <div class="l-features-grid">
            <div class="l-feature-card reveal stagger-1">
                <div class="l-feature-icon orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3 class="l-feature-title">Lead Management</h3>
                <p class="l-feature-desc">Capture, assign, and track leads through every stage of your sales pipeline with visual Kanban boards.</p>
            </div>
            <div class="l-feature-card reveal stagger-2">
                <div class="l-feature-icon teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h3 class="l-feature-title">Quotes & Invoices</h3>
                <p class="l-feature-desc">Create professional GST-compliant quotes and invoices in seconds. Auto-convert quotes to orders with one click.</p>
            </div>
            <div class="l-feature-card reveal stagger-3">
                <div class="l-feature-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h3 class="l-feature-title">WhatsApp Automation</h3>
                <p class="l-feature-desc">Connect your WhatsApp Business, send bulk campaigns, set auto-replies, and capture leads directly from chats.</p>
            </div>
            <div class="l-feature-card reveal stagger-4">
                <div class="l-feature-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                </div>
                <h3 class="l-feature-title">AI-Powered Chatbot</h3>
                <p class="l-feature-desc">Smart 4-tier AI chatbot that handles customer queries, recommends products, and generates leads 24/7 on autopilot.</p>
            </div>
            <div class="l-feature-card reveal stagger-5">
                <div class="l-feature-icon rose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                </div>
                <h3 class="l-feature-title">Project & Task Tracking</h3>
                <p class="l-feature-desc">Manage projects, assign tasks to teams, track micro-tasks, set follow-ups, and monitor progress with visual boards.</p>
            </div>
            <div class="l-feature-card reveal stagger-6">
                <div class="l-feature-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <h3 class="l-feature-title">GST Billing & Finance</h3>
                <p class="l-feature-desc">Complete financial suite — payments, purchases, vendor management, profit & loss reports, and cash flow analytics.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══ ABOUT US ═══ -->
<section class="l-section l-section-light" id="about">
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
                <h3>We Build Software That <span class="l-gradient-text">Drives Growth</span></h3>
                <p>VyaparCRM was born from a simple idea — businesses shouldn't need 10 different tools to manage their operations. Our platform brings together lead management, invoicing, project tracking, WhatsApp automation, and AI-powered intelligence into one seamless experience.</p>
                <p>Built by a team with 20+ years of combined experience in frontend, backend development, and UX design — we understand what businesses truly need.</p>
                <div class="l-about-values">
                    <div class="l-about-value">
                        <div class="l-about-value-icon orange">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                        </div>
                        <div>
                            <h4>Lightning Fast</h4>
                            <p>Optimized for speed and performance</p>
                        </div>
                    </div>
                    <div class="l-about-value">
                        <div class="l-about-value-icon teal">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div>
                            <h4>Secure & Reliable</h4>
                            <p>Enterprise-grade data protection</p>
                        </div>
                    </div>
                    <div class="l-about-value">
                        <div class="l-about-value-icon orange">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                        </div>
                        <div>
                            <h4>Easy to Use</h4>
                            <p>Intuitive design, zero learning curve</p>
                        </div>
                    </div>
                    <div class="l-about-value">
                        <div class="l-about-value-icon teal">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <h4>Multi-User</h4>
                            <p>Teams, roles & permissions built-in</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ PACKAGES / PRICING ═══ -->
<section class="l-section" id="pricing">
    <div class="l-container">
        <h2 class="l-section-title reveal">Simple, Transparent <span class="l-gradient-text">Pricing</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Choose the plan that fits your business. Start with a free trial — no credit card required.</p>

        <div class="l-pricing-toggle reveal stagger-2">
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

                <a href="{{ route('register', $pkg->slug) }}" class="l-btn {{ $index === 1 ? 'l-btn-primary' : 'l-btn-blue' }} l-pricing-btn">
                    {{ $pkg->trial_days > 0 ? 'Start Free Trial' : 'Get Started' }}
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ═══ FAQ ═══ -->
<section class="l-section l-section-light" id="faq">
    <div class="l-container">
        <h2 class="l-section-title reveal">Frequently Asked <span class="l-gradient-text">Questions</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Got questions? We've got answers. If you don't see what you're looking for, reach out to us.</p>

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

<!-- ═══ CLIENT REVIEWS ═══ -->
<section class="l-section l-section-dark" id="reviews">
    <div class="l-container">
        <h2 class="l-section-title reveal" style="color:white;">What Our Clients <span class="l-gradient-text">Say</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Trusted by businesses across India to streamline their operations and boost revenue.</p>

        <div class="l-testimonials-grid">
            <div class="l-testimonial-card reveal stagger-1">
                <div class="l-testimonial-stars">
                    @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor
                </div>
                <p class="l-testimonial-text">"VyaparCRM transformed how we manage our sales pipeline. We went from scattered spreadsheets to a unified system that tracks every lead. Our conversion rate improved by 40% in just 3 months!"</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar orange">RK</div>
                    <div>
                        <div class="l-testimonial-name">Rajesh Kumar</div>
                        <div class="l-testimonial-role">MD, TechVista Solutions Pvt Ltd</div>
                    </div>
                </div>
            </div>
            <div class="l-testimonial-card reveal stagger-2">
                <div class="l-testimonial-stars">
                    @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor
                </div>
                <p class="l-testimonial-text">"The WhatsApp automation feature is a game-changer! We set up auto-replies and bulk campaigns that saved us 15+ hours per week. The AI chatbot handles customer queries brilliantly even at midnight."</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar teal">PS</div>
                    <div>
                        <div class="l-testimonial-name">Priya Sharma</div>
                        <div class="l-testimonial-role">Founder, GreenLeaf Organics</div>
                    </div>
                </div>
            </div>
            <div class="l-testimonial-card reveal stagger-3">
                <div class="l-testimonial-stars">
                    @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor
                </div>
                <p class="l-testimonial-text">"As a manufacturing business, tracking projects & tasks was chaotic. VyaparCRM gave us proper GST invoicing, purchase tracking, and vendor management — all in one place. Best investment we've made!"</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar purple">AM</div>
                    <div>
                        <div class="l-testimonial-name">Amit Mehta</div>
                        <div class="l-testimonial-role">CEO, Precision Steel Works</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ CTA BANNER ═══ -->
<section class="l-cta-banner">
    <div class="l-container">
        <h2 class="reveal">Ready to Supercharge Your Business?</h2>
        <p class="reveal stagger-1">Join 500+ businesses already using VyaparCRM. Start your free trial today.</p>
        <a href="#pricing" class="l-btn l-btn-white l-btn-lg reveal stagger-2">
            Get Started Free
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>
</section>

<!-- ═══ CONTACT US ═══ -->
<section class="l-section" id="contact">
    <div class="l-container">
        <h2 class="l-section-title reveal">Get In <span class="l-gradient-text">Touch</span></h2>
        <p class="l-section-subtitle reveal stagger-1">Have questions or need a demo? We'd love to hear from you.</p>

        <div class="l-contact-grid">
            <div class="l-contact-info reveal-left">
                <h3>Let's Talk Business</h3>
                <p>Whether you're exploring CRM options or need help getting started, our team is here to assist you every step of the way.</p>

                <div class="l-contact-item">
                    <div class="l-contact-item-icon orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                    <div>
                        <h4>Email Us</h4>
                        <p>support@vyaparcrm.com</p>
                    </div>
                </div>
                <div class="l-contact-item">
                    <div class="l-contact-item-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <h4>Call Us</h4>
                        <p>+91 98765 43210</p>
                    </div>
                </div>
                <div class="l-contact-item">
                    <div class="l-contact-item-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <h4>Visit Us</h4>
                        <p>Mumbai, Maharashtra, India</p>
                    </div>
                </div>
            </div>

            <div class="l-contact-form reveal-right">
                @if(session('success'))
                <div class="l-auth-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ session('success') }}
                </div>
                @endif
                <form method="POST" action="{{ route('contact.submit') }}">
                    @csrf
                    <div class="l-form-row">
                        <div class="l-form-group">
                            <label class="l-form-label">Your Name *</label>
                            <input type="text" name="name" class="l-form-input" placeholder="John Doe" required value="{{ old('name') }}">
                        </div>
                        <div class="l-form-group">
                            <label class="l-form-label">Email *</label>
                            <input type="email" name="email" class="l-form-input" placeholder="john@company.com" required value="{{ old('email') }}">
                        </div>
                    </div>
                    <div class="l-form-row">
                        <div class="l-form-group">
                            <label class="l-form-label">Phone</label>
                            <input type="text" name="phone" class="l-form-input" placeholder="+91 98765 43210" value="{{ old('phone') }}">
                        </div>
                        <div class="l-form-group">
                            <label class="l-form-label">Subject *</label>
                            <input type="text" name="subject" class="l-form-input" placeholder="How can we help?" required value="{{ old('subject') }}">
                        </div>
                    </div>
                    <div class="l-form-group">
                        <label class="l-form-label">Message *</label>
                        <textarea name="message" class="l-form-textarea" placeholder="Tell us about your requirements..." required>{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="l-btn l-btn-primary l-pricing-btn">
                        Send Message
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="l-footer">
    <div class="l-container">
        <div class="l-footer-grid">
            <div class="l-footer-brand">
                <div class="l-navbar-logo" style="margin-bottom:8px;">
                    <div class="l-navbar-logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    </div>
                    <span class="l-navbar-logo-text" style="color:white;">VyaparCRM</span>
                </div>
                <p>All-in-one business management platform trusted by 500+ companies across India. Manage leads, automate WhatsApp, and grow smarter.</p>
            </div>
            <div>
                <h4>Product</h4>
                <ul>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#pricing">Pricing</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="{{ route('login') }}">Sign In</a></li>
                </ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#reviews">Reviews</a></li>
                </ul>
            </div>
            <div>
                <h4>Support</h4>
                <ul>
                    <li><a href="#contact">Help Center</a></li>
                    <li><a href="#faq">FAQs</a></li>
                    <li><a href="mailto:support@vyaparcrm.com">Email Support</a></li>
                </ul>
            </div>
        </div>
        <div class="l-footer-bottom">
            <span>&copy; {{ date('Y') }} VyaparCRM. All rights reserved.</span>
            <div class="l-footer-social">
                <a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg></a>
                <a href="#" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
                <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
            </div>
        </div>
    </div>
</footer>

<script>
// ─── Navbar scroll effect ───
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});

// ─── Mobile menu ───
function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('open');
}

// ─── Smooth scroll ───
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ─── Scroll reveal (Intersection Observer) ───
const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => revealObserver.observe(el));

// ─── Pricing toggle ───
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

// ─── FAQ accordion ───
function toggleFaq(item) {
    const wasActive = item.classList.contains('active');
    document.querySelectorAll('.l-faq-item').forEach(i => i.classList.remove('active'));
    if (!wasActive) item.classList.add('active');
}
</script>

</body>
</html>
