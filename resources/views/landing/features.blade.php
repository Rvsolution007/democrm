@extends('landing.layout')
@section('title', 'Features — VyaparCRM')
@section('meta_desc', 'Explore VyaparCRM features: Lead Management, Quotes & Invoices, WhatsApp Automation, AI Chatbot, Project Tracking, and GST Billing.')

@section('content')
<!-- Page Header -->
<div class="l-page-header" style="background:linear-gradient(160deg, #ffffff 0%, #fffdf5 25%, #fefce8 50%, #fef9c3 80%, #fef08a 100%);">
    <div class="l-page-header-orbits">
        <div class="l-orbit" style="width:400px;height:400px;top:50%;left:60%;transform:translate(-50%,-50%);animation:orbitSpin 25s linear infinite;border-color:rgba(37,99,235,0.06);"></div>
        <div class="l-orbit" style="width:600px;height:600px;top:50%;left:40%;transform:translate(-50%,-50%);animation:orbitSpin 40s linear infinite reverse;border-color:rgba(14,165,233,0.06);"></div>
    </div>
    <div class="l-container">
        <div class="l-breadcrumb"><a href="{{ route('landing') }}">Home</a> <span>/</span> <span>Features</span></div>
        <h1>Powerful Features for<br><span class="l-gradient-warm">Modern Businesses</span></h1>
        <p>Everything you need to manage leads, close deals, automate WhatsApp, and grow your business — all in one platform.</p>
    </div>
</div>

<div class="l-container page-enter">

    <!-- ════ FEATURE 1: Lead Management ════ -->
    <div class="l-feature-block reveal">
        <div class="l-feature-text">
            <div class="l-feature-tag blue">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Lead Management
            </div>
            <h2>Capture, Track & Convert<br><span class="l-gradient-warm">Every Lead</span></h2>
            <p>Your complete sales pipeline in one view. Track leads from first contact to closed deal with visual Kanban boards, smart filters, and automated follow-up reminders.</p>
            <ul class="l-feature-points">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Visual Kanban board with drag-and-drop stages</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Multi-source lead capture (Walk-in, WhatsApp, Facebook, Website)</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Automated follow-up reminders & task assignments</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product-level tracking with amount pipeline view</li>
            </ul>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue">Try It Free →</a>
        </div>
        <div class="l-feature-images reveal-right">
            @if(file_exists(public_path('images/features/leads.png')))
                <div class="l-feature-img-main">
                    <img src="{{ asset('images/features/leads.png') }}" alt="Lead Management — Kanban Board View" loading="lazy">
                </div>
            @else
                <div class="l-screenshot-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Lead Management Screenshot</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ════ FEATURE 2: Quotes & Invoices ════ -->
    <div class="l-feature-block reverse reveal">
        <div class="l-feature-text">
            <div class="l-feature-tag sky">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Quotes & Invoices
            </div>
            <h2>Professional Billing<br><span class="l-gradient-warm">in Seconds</span></h2>
            <p>Create GST-compliant quotes and invoices with one click. Auto-convert quotes to invoices, track payments, and manage your entire billing lifecycle effortlessly.</p>
            <ul class="l-feature-points">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>GST-compliant quotes & invoice generation</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>One-click quote-to-invoice conversion</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Payment tracking with due amount alerts</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>PDF download & share via WhatsApp</li>
            </ul>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue">Try It Free →</a>
        </div>
        <div class="l-feature-images reveal-left">
            @if(file_exists(public_path('images/features/quotes.png')) || file_exists(public_path('images/features/invoices.png')))
                <div class="l-feature-gallery">
                    @if(file_exists(public_path('images/features/quotes.png')))
                    <div class="l-feature-gallery-item span-2">
                        <img src="{{ asset('images/features/quotes.png') }}" alt="Quotes Management" loading="lazy">
                    </div>
                    @endif
                    @if(file_exists(public_path('images/features/invoices.png')))
                    <div class="l-feature-gallery-item span-2">
                        <img src="{{ asset('images/features/invoices.png') }}" alt="Invoice Management" loading="lazy">
                    </div>
                    @endif
                </div>
            @else
                <div class="l-screenshot-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Quotes & Invoices Screenshot</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ════ FEATURE 3: WhatsApp Automation ════ -->
    <div class="l-feature-block reveal">
        <div class="l-feature-text">
            <div class="l-feature-tag orange">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                WhatsApp Automation
            </div>
            <h2>Automate WhatsApp<br><span class="l-orange-text">Like a Pro</span></h2>
            <p>Connect your WhatsApp Business, send bulk campaigns to thousands, set intelligent auto-replies, and capture leads directly from incoming messages — all from one dashboard.</p>
            <ul class="l-feature-points">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Bulk messaging with smart delay patterns</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Auto-reply rules with keyword matching</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Template management with media support</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Chrome extension for contact import</li>
            </ul>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-orange">Try It Free →</a>
        </div>
        <div class="l-feature-images reveal-right">
            @if(file_exists(public_path('images/features/whatsapp.png')))
                <div class="l-feature-img-main">
                    <img src="{{ asset('images/features/whatsapp.png') }}" alt="WhatsApp Automation Dashboard" loading="lazy">
                </div>
            @else
                <div class="l-screenshot-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>WhatsApp Automation Screenshot</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ════ FEATURE 4: AI Chatbot ════ -->
    <div class="l-feature-block reverse reveal">
        <div class="l-feature-text">
            <div class="l-feature-tag blue">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2"/></svg>
                AI-Powered Chatbot
            </div>
            <h2>Smart AI That<br><span class="l-gradient-warm">Never Sleeps</span></h2>
            <p>Our 4-tier AI chatbot handles customer queries, recommends products, and generates leads 24/7. Build custom chatflows with a visual builder — no coding required.</p>
            <ul class="l-feature-points">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>4-tier AI routing (Keywords → PHP → GPT → Fallback)</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Visual chatflow builder with drag-and-drop</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Product recommendation with catalogue sync</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Token usage analytics & cost tracking</li>
            </ul>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue">Try It Free →</a>
        </div>
        <div class="l-feature-images reveal-left">
            @if(file_exists(public_path('images/features/ai-chatbot.png')))
                <div class="l-feature-img-main">
                    <img src="{{ asset('images/features/ai-chatbot.png') }}" alt="AI Chatbot Builder" loading="lazy">
                </div>
            @else
                <div class="l-screenshot-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>AI Chatbot Screenshot</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ════ FEATURE 5: Project & Task Tracking ════ -->
    <div class="l-feature-block reveal">
        <div class="l-feature-text">
            <div class="l-feature-tag sky">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                Project & Task Tracking
            </div>
            <h2>Manage Projects<br><span class="l-gradient-warm">Effortlessly</span></h2>
            <p>Track projects, assign tasks to teams, manage micro-tasks with priorities, set follow-ups, and monitor progress — everything your team needs to stay productive.</p>
            <ul class="l-feature-points">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Project management with milestones</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Task & micro-task assignment with priorities</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Follow-up scheduling with reminders</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Team workload & progress dashboards</li>
            </ul>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue">Try It Free →</a>
        </div>
        <div class="l-feature-images reveal-right">
            @if(file_exists(public_path('images/features/projects.png')))
                <div class="l-feature-img-main">
                    <img src="{{ asset('images/features/projects.png') }}" alt="Project & Task Management" loading="lazy">
                </div>
            @else
                <div class="l-screenshot-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Project & Tasks Screenshot</span>
                </div>
            @endif
        </div>
    </div>

    <!-- ════ FEATURE 6: GST Billing & Finance ════ -->
    <div class="l-feature-block reverse reveal">
        <div class="l-feature-text">
            <div class="l-feature-tag orange">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                GST Billing & Finance
            </div>
            <h2>Complete Financial<br><span class="l-orange-text">Control</span></h2>
            <p>Go beyond basic invoicing — manage payments, purchases, vendor tracking, commission calculations, and get real-time profit & loss reports with cash flow analytics.</p>
            <ul class="l-feature-points">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Payment collection & purchase tracking</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Vendor management with advance wallets</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Profit & Loss reports with revenue breakdowns</li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Cash flow analytics & month-wise charts</li>
            </ul>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-orange">Try It Free →</a>
        </div>
        <div class="l-feature-images reveal-left">
            @if(file_exists(public_path('images/features/reports.png')))
                <div class="l-feature-img-main">
                    <img src="{{ asset('images/features/reports.png') }}" alt="Financial Reports & Analytics" loading="lazy">
                </div>
            @else
                <div class="l-screenshot-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Financial Reports Screenshot</span>
                </div>
            @endif
        </div>
    </div>

</div>

<!-- CTA -->
<section class="l-cta-banner" style="margin-top:60px;">
    <div class="l-container">
        <h2 class="reveal">Ready to Experience All Features?</h2>
        <p class="reveal stagger-1">Start your free trial today. No credit card required.</p>
        <a href="{{ route('landing.packages') }}" class="l-btn l-btn-white l-btn-lg reveal stagger-2">
            View Packages
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>
</section>
@endsection
