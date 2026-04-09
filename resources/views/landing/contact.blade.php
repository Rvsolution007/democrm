@extends('landing.layout')
@section('title', 'Contact Us — VyaparCRM')
@section('meta_desc', 'Get in touch with VyaparCRM. We are here to help with demos, support, and questions.')

@section('content')
<div class="l-page-header">
    <div class="l-container">
        <div class="l-breadcrumb"><a href="{{ route('landing') }}">Home</a> <span>/</span> <span>Contact</span></div>
        <h1>Get In<br><span class="l-gradient-text">Touch</span></h1>
        <p>Have questions or need a demo? We'd love to hear from you.</p>
    </div>
</div>

<section class="l-section page-enter" style="padding-top:40px;">
    <div class="l-container">
        <div class="l-contact-grid">
            <div class="l-contact-info reveal-left">
                <h3>Let's Talk Business</h3>
                <p>Whether you're exploring CRM options or need help getting started, our team is here to assist you every step of the way.</p>

                <div class="l-contact-item">
                    <div class="l-contact-item-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    </div>
                    <div>
                        <h4>Email Us</h4>
                        <p>support@vyaparcrm.com</p>
                    </div>
                </div>
                <div class="l-contact-item">
                    <div class="l-contact-item-icon sky">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <h4>Call Us</h4>
                        <p>+91 98765 43210</p>
                    </div>
                </div>
                <div class="l-contact-item">
                    <div class="l-contact-item-icon orange">
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
                    <button type="submit" class="l-btn l-btn-blue l-btn-block">
                        Send Message
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
