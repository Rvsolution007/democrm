<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — VyaparCRM</title>
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="landing-page">

<div class="l-auth-page">
    <div class="l-auth-blobs">
        <div class="l-blob l-blob-1"></div>
        <div class="l-blob l-blob-2"></div>
    </div>

    <div class="l-auth-card" style="max-width:480px;">
        <div class="l-auth-header">
            <a href="{{ route('landing') }}" class="l-auth-logo">
                <div class="l-navbar-logo-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                </div>
                <span style="font-size:1.35rem;font-weight:800;letter-spacing:-0.5px;">VyaparCRM</span>
            </a>
            <h1 class="l-auth-title">Reset Password</h1>
            <p class="l-auth-subtitle">Enter the 6-digit code sent to your email</p>
        </div>

        @if(session('success'))
        <div class="l-auth-success">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="l-auth-error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.reset.store') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

            <div class="l-form-group" style="text-align:center;">
                <label class="l-form-label" style="text-align:center;margin-bottom:12px;">Verification Code</label>
                <div class="l-code-input">
                    <input type="text" maxlength="1" class="code-digit" data-index="0" oninput="handleCodeInput(this)" onkeydown="handleCodeKeydown(event, this)" autofocus>
                    <input type="text" maxlength="1" class="code-digit" data-index="1" oninput="handleCodeInput(this)" onkeydown="handleCodeKeydown(event, this)">
                    <input type="text" maxlength="1" class="code-digit" data-index="2" oninput="handleCodeInput(this)" onkeydown="handleCodeKeydown(event, this)">
                    <input type="text" maxlength="1" class="code-digit" data-index="3" oninput="handleCodeInput(this)" onkeydown="handleCodeKeydown(event, this)">
                    <input type="text" maxlength="1" class="code-digit" data-index="4" oninput="handleCodeInput(this)" onkeydown="handleCodeKeydown(event, this)">
                    <input type="text" maxlength="1" class="code-digit" data-index="5" oninput="handleCodeInput(this)" onkeydown="handleCodeKeydown(event, this)">
                </div>
                <input type="hidden" name="code" id="codeValue">
            </div>

            <div class="l-form-group">
                <label class="l-form-label">New Password</label>
                <input type="password" name="password" class="l-form-input" placeholder="Min 8 characters" required minlength="8">
            </div>
            <div class="l-form-group">
                <label class="l-form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" class="l-form-input" placeholder="Re-enter new password" required>
            </div>

            <button type="submit" class="l-btn l-btn-primary l-pricing-btn" style="margin-top:8px;">
                Reset Password
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </button>
        </form>

        <div class="l-auth-footer">
            <a href="{{ route('password.forgot') }}">Resend code</a> · <a href="{{ route('login') }}">Back to Sign In</a>
        </div>
    </div>
</div>

<script>
// 6-digit code input handler
function handleCodeInput(el) {
    const val = el.value.replace(/\D/g, '');
    el.value = val;
    if (val && el.dataset.index < 5) {
        const next = document.querySelector(`.code-digit[data-index="${parseInt(el.dataset.index)+1}"]`);
        if (next) next.focus();
    }
    updateCodeValue();
}
function handleCodeKeydown(e, el) {
    if (e.key === 'Backspace' && !el.value && el.dataset.index > 0) {
        const prev = document.querySelector(`.code-digit[data-index="${parseInt(el.dataset.index)-1}"]`);
        if (prev) { prev.focus(); prev.value = ''; }
    }
}
function updateCodeValue() {
    let code = '';
    document.querySelectorAll('.code-digit').forEach(d => code += d.value);
    document.getElementById('codeValue').value = code;
}

// Handle paste
document.querySelectorAll('.code-digit').forEach(input => {
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0,6);
        document.querySelectorAll('.code-digit').forEach((d, i) => { d.value = paste[i] || ''; });
        updateCodeValue();
        const lastIdx = Math.min(paste.length, 5);
        document.querySelector(`.code-digit[data-index="${lastIdx}"]`)?.focus();
    });
});
</script>
</body>
</html>
