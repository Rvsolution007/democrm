@extends('landing.layout')
@section('title', 'About Us — VyaparCRM')
@section('meta_desc', 'Learn about VyaparCRM — built by a team with 20+ years of experience to help Indian businesses manage leads, invoices, WhatsApp, and more.')

@section('content')
<div class="l-page-header">
    <div class="l-page-header-orbits">
        <div class="l-orbit" style="width:350px;height:350px;top:50%;left:55%;transform:translate(-50%,-50%);animation:orbitSpin 20s linear infinite;border-color:rgba(37,99,235,0.06);"></div>
    </div>
    <div class="l-container">
        <div class="l-breadcrumb"><a href="{{ route('landing') }}">Home</a> <span>/</span> <span>About Us</span></div>
        <h1>We Build Software That<br><span class="l-gradient-text">Drives Growth</span></h1>
        <p>Born from a simple idea — businesses shouldn't need 10 different tools to manage their operations.</p>
    </div>
</div>

<!-- Stats -->
<section class="l-section-sm" style="margin-top:-30px;position:relative;z-index:10;">
    <div class="l-container">
        <div class="l-stats-strip">
            <div class="l-stat-card reveal stagger-1">
                <div class="l-stat-card-value blue">500+</div>
                <div class="l-stat-card-label">Active Businesses</div>
            </div>
            <div class="l-stat-card reveal stagger-2">
                <div class="l-stat-card-value orange">10+</div>
                <div class="l-stat-card-label">Years of Excellence</div>
            </div>
            <div class="l-stat-card reveal stagger-3">
                <div class="l-stat-card-value sky">15+</div>
                <div class="l-stat-card-label">Powerful Modules</div>
            </div>
            <div class="l-stat-card reveal stagger-4">
                <div class="l-stat-card-value dark">24/7</div>
                <div class="l-stat-card-label">AI-Powered Support</div>
            </div>
        </div>
    </div>
</section>

<!-- About Content -->
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
                <h2>Our <span class="l-gradient-text">Story</span></h2>
                <p>VyaparCRM was born from a simple idea — businesses shouldn't need 10 different tools to manage their operations. Our platform brings together lead management, invoicing, project tracking, WhatsApp automation, and AI-powered intelligence into one seamless experience.</p>
                <p>Built by a team with 20+ years of combined experience in frontend, backend development, and UX design — we understand what businesses truly need.</p>
                <div class="l-about-values">
                    <div class="l-about-value">
                        <div class="l-about-value-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                        <div><h4>Lightning Fast</h4><p>Optimized for speed and performance</p></div>
                    </div>
                    <div class="l-about-value">
                        <div class="l-about-value-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                        <div><h4>Secure & Reliable</h4><p>Enterprise-grade data protection</p></div>
                    </div>
                    <div class="l-about-value">
                        <div class="l-about-value-icon sky"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></div>
                        <div><h4>Easy to Use</h4><p>Intuitive design, zero learning curve</p></div>
                    </div>
                    <div class="l-about-value">
                        <div class="l-about-value-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                        <div><h4>Multi-User</h4><p>Teams, roles & permissions built-in</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="l-cta-banner">
    <div class="l-container">
        <h2 class="reveal">Want to See VyaparCRM in Action?</h2>
        <p class="reveal stagger-1">Start your free trial today and experience the difference.</p>
        <a href="{{ route('landing.packages') }}" class="l-btn l-btn-white l-btn-lg reveal stagger-2">Get Started Free →</a>
    </div>
</section>
@endsection
