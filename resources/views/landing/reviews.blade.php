@extends('landing.layout')
@section('title', 'Client Reviews — VyaparCRM')
@section('meta_desc', 'See what our clients say about VyaparCRM. Real reviews from businesses across India.')

@section('content')
<div class="l-page-header">
    <div class="l-container">
        <div class="l-breadcrumb"><a href="{{ route('landing') }}">Home</a> <span>/</span> <span>Reviews</span></div>
        <h1>What Our Clients<br><span class="l-gradient-text">Say About Us</span></h1>
        <p>Trusted by businesses across India to streamline their operations and boost revenue.</p>
    </div>
</div>

<section class="l-section page-enter" style="padding-top:40px;">
    <div class="l-container">
        <div class="l-testimonials-grid">
            <div class="l-testimonial-card reveal stagger-1">
                <div class="l-testimonial-stars">
                    @for($i=0;$i<5;$i++)<svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>@endfor
                </div>
                <p class="l-testimonial-text">"VyaparCRM transformed how we manage our sales pipeline. We went from scattered spreadsheets to a unified system that tracks every lead. Our conversion rate improved by 40% in just 3 months!"</p>
                <div class="l-testimonial-author">
                    <div class="l-testimonial-avatar blue">RK</div>
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
                    <div class="l-testimonial-avatar sky">PS</div>
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
                    <div class="l-testimonial-avatar orange">AM</div>
                    <div>
                        <div class="l-testimonial-name">Amit Mehta</div>
                        <div class="l-testimonial-role">CEO, Precision Steel Works</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="l-cta-banner">
    <div class="l-container">
        <h2 class="reveal">Join 500+ Happy Businesses</h2>
        <p class="reveal stagger-1">Start your free trial and see why businesses love VyaparCRM.</p>
        <a href="{{ route('landing.packages') }}" class="l-btn l-btn-white l-btn-lg reveal stagger-2">Get Started Free →</a>
    </div>
</section>
@endsection
