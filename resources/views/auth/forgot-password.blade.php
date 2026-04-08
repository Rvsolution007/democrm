<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — VyaparCRM</title>
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="landing-page">

<div class="l-auth-page">
    <div class="l-auth-blobs">
        <div class="l-blob l-blob-1"></div>
        <div class="l-blob l-blob-2"></div>
    </div>

    <div class="l-auth-card" style="max-width:460px;">
        <div class="l-auth-header">
            <a href="{{ route('landing') }}" class="l-auth-logo">
                <div class="l-navbar-logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <span style="font-size:1.35rem;font-weight:800;letter-spacing:-0.5px;">VyaparCRM</span>
            </a>
            <h1 class="l-auth-title">Forgot Password?</h1>
            <p class="l-auth-subtitle">Enter your email and we'll send you a 6-digit reset code</p>
        </div>

        @if($errors->any())
        <div class="l-auth-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.send-code') }}">
            @csrf
            <div class="l-form-group">
                <label class="l-form-label">Email Address</label>
                <input type="email" name="email" class="l-form-input" placeholder="you@company.com" required autofocus value="{{ old('email') }}">
            </div>

            <button type="submit" class="l-btn l-btn-primary l-pricing-btn">
                Send Reset Code
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </button>
        </form>

        <div class="l-auth-footer">
            Remember your password? <a href="{{ route('login') }}">Sign In</a>
        </div>
    </div>
</div>
</body>
</html>
