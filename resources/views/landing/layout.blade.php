<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VyaparCRM — Smart Business Management Platform')</title>
    <meta name="description" content="@yield('meta_desc', 'VyaparCRM is a powerful all-in-one CRM platform. Manage leads, quotes, invoices, WhatsApp automation, AI chatbot, projects, and more.')">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @stack('styles')
</head>
<body class="landing-page">

<!-- ═══ NAVBAR ═══ -->
<nav class="l-navbar" id="navbar">
    <div class="l-container l-navbar-inner">
        <a href="{{ route('landing') }}" class="l-navbar-logo">
            <div class="l-navbar-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <span class="l-navbar-logo-text">VyaparCRM</span>
        </a>
        <ul class="l-navbar-links">
            <li><a href="{{ route('landing') }}" class="{{ request()->routeIs('landing') ? 'active' : '' }}">Home</a></li>
            <li><a href="{{ route('landing.features') }}" class="{{ request()->routeIs('landing.features') ? 'active' : '' }}">Features</a></li>
            <li><a href="{{ route('landing.about') }}" class="{{ request()->routeIs('landing.about') ? 'active' : '' }}">About Us</a></li>
            <li><a href="{{ route('landing.packages') }}" class="{{ request()->routeIs('landing.packages') ? 'active' : '' }}">Packages</a></li>
            <li><a href="{{ route('landing.faq') }}" class="{{ request()->routeIs('landing.faq') ? 'active' : '' }}">FAQ</a></li>
            <li><a href="{{ route('landing.reviews') }}" class="{{ request()->routeIs('landing.reviews') ? 'active' : '' }}">Reviews</a></li>
            <li><a href="{{ route('landing.contact') }}" class="{{ request()->routeIs('landing.contact') ? 'active' : '' }}">Contact</a></li>
        </ul>
        <div class="l-navbar-cta">
            <a href="{{ route('login') }}" class="l-btn l-btn-outline l-btn-sm">Sign In</a>
            <a href="{{ route('landing.packages') }}" class="l-btn l-btn-blue l-btn-sm">Get Started</a>
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
    <a href="{{ route('landing') }}" class="{{ request()->routeIs('landing') ? 'active' : '' }}" onclick="toggleMobileMenu()">Home</a>
    <a href="{{ route('landing.features') }}" class="{{ request()->routeIs('landing.features') ? 'active' : '' }}" onclick="toggleMobileMenu()">Features</a>
    <a href="{{ route('landing.about') }}" class="{{ request()->routeIs('landing.about') ? 'active' : '' }}" onclick="toggleMobileMenu()">About Us</a>
    <a href="{{ route('landing.packages') }}" class="{{ request()->routeIs('landing.packages') ? 'active' : '' }}" onclick="toggleMobileMenu()">Packages</a>
    <a href="{{ route('landing.faq') }}" class="{{ request()->routeIs('landing.faq') ? 'active' : '' }}" onclick="toggleMobileMenu()">FAQ</a>
    <a href="{{ route('landing.reviews') }}" class="{{ request()->routeIs('landing.reviews') ? 'active' : '' }}" onclick="toggleMobileMenu()">Reviews</a>
    <a href="{{ route('landing.contact') }}" class="{{ request()->routeIs('landing.contact') ? 'active' : '' }}" onclick="toggleMobileMenu()">Contact</a>
    <a href="{{ route('login') }}" class="l-btn l-btn-blue" onclick="toggleMobileMenu()">Sign In</a>
</div>

<!-- Page Content -->
@yield('content')

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
                    <li><a href="{{ route('landing.features') }}">Features</a></li>
                    <li><a href="{{ route('landing.packages') }}">Pricing</a></li>
                    <li><a href="{{ route('landing.faq') }}">FAQ</a></li>
                    <li><a href="{{ route('login') }}">Sign In</a></li>
                </ul>
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="{{ route('landing.about') }}">About Us</a></li>
                    <li><a href="{{ route('landing.contact') }}">Contact</a></li>
                    <li><a href="{{ route('landing.reviews') }}">Reviews</a></li>
                </ul>
            </div>
            <div>
                <h4>Support</h4>
                <ul>
                    <li><a href="{{ route('landing.contact') }}">Help Center</a></li>
                    <li><a href="{{ route('landing.faq') }}">FAQs</a></li>
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
// Navbar scroll
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => { navbar.classList.toggle('scrolled', window.scrollY > 50); });

// Mobile menu
function toggleMobileMenu() { document.getElementById('mobileMenu').classList.toggle('open'); }

// Scroll reveal
const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => { if (entry.isIntersecting) entry.target.classList.add('visible'); });
}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
revealEls.forEach(el => revealObserver.observe(el));
</script>
@stack('scripts')
</body>
</html>
