<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fcfd 0%, #fff7ed 50%, #f1f9fc 100%);
            color: #1f2937;
            position: relative;
            overflow: hidden;
        }

        /* Background floating shapes — matches CRM theme */
        .bg-shapes {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0; overflow: hidden; pointer-events: none;
        }
        .shape { position: absolute; animation: floatShape 10s ease-in-out infinite alternate; }

        .shape-1 {
            width: 280px; height: 280px; background: #56c2ff;
            border-radius: 40px 100px 100px 40px;
            top: -60px; left: -60px; opacity: 0.6; transform: rotate(-15deg);
        }
        .shape-2 {
            width: 200px; height: 200px; background: #f57c00;
            border-radius: 24px; top: 8%; right: 8%; opacity: 0.25;
            transform: rotate(20deg); animation-duration: 12s; animation-delay: -5s;
        }
        .shape-3 {
            width: 220px; height: 220px; background: #fbd84a;
            border-radius: 40px 10px 40px 40px;
            bottom: 3%; left: 12%; opacity: 0.5; transform: rotate(-30deg);
            animation-duration: 15s;
        }
        .shape-4 {
            width: 160px; height: 160px; background: #f57c00;
            border-radius: 50%; bottom: -30px; right: 18%; opacity: 0.2;
            animation-duration: 14s; animation-delay: -7s;
        }
        .shape-5 {
            width: 100px; height: 100px; background: #56c2ff;
            border-radius: 50%; top: 60%; left: 60%; opacity: 0.15;
            animation-duration: 18s; animation-delay: -3s;
        }

        @keyframes floatShape {
            0%   { transform: translateY(0) rotate(0deg) scale(1); }
            50%  { transform: translateY(-20px) rotate(5deg) scale(1.05); }
            100% { transform: translateY(10px) rotate(-5deg) scale(0.95); }
        }

        /* Login container */
        .login-container {
            width: 100%; max-width: 440px; padding: 20px;
            position: relative; z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.55);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 28px;
            padding: 48px 40px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), inset 0 0 0 1px rgba(255,255,255,0.5);
            animation: cardFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0; transform: translateY(30px);
        }

        @keyframes cardFadeIn { to { opacity: 1; transform: translateY(0); } }

        .login-header { text-align: center; margin-bottom: 32px; }

        .sa-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, rgba(245,124,0,0.12), rgba(251,216,74,0.15));
            border: 1px solid rgba(245,124,0,0.25);
            padding: 8px 18px; border-radius: 50px; margin-bottom: 20px;
            font-size: 11px; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; color: #e65100;
        }
        .sa-badge svg { width: 16px; height: 16px; color: #f57c00; }

        .login-title {
            font-size: 26px; font-weight: 700; color: #1a1a2e;
            margin-bottom: 6px; letter-spacing: -0.5px;
        }
        .login-subtitle { font-size: 14px; color: #6b7280; }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block; font-size: 13px; font-weight: 600;
            color: #374151; margin-bottom: 8px;
        }
        .form-input {
            width: 100%; padding: 14px 16px;
            background: rgba(255, 255, 255, 0.7);
            border: 1.5px solid #e5e7eb;
            border-radius: 14px; color: #1f2937; font-size: 14px;
            font-family: inherit; transition: all 0.3s ease; outline: none;
        }
        .form-input::placeholder { color: #9ca3af; }
        .form-input:focus {
            border-color: #f57c00;
            box-shadow: 0 0 0 3px rgba(245, 124, 0, 0.12);
            background: #fff;
        }

        .btn-login {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #f57c00, #e65100);
            border: none; border-radius: 14px; color: #fff;
            font-size: 15px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(245, 124, 0, 0.35);
            margin-top: 8px; letter-spacing: 0.2px;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(245, 124, 0, 0.4);
        }
        .btn-login:active { transform: translateY(0); }

        .error-message {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px; padding: 12px 16px; margin-bottom: 20px;
            font-size: 13px; color: #dc2626;
            display: flex; align-items: center; gap: 8px;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .footer-text {
            text-align: center; margin-top: 24px;
            font-size: 11px; color: #9ca3af;
        }

        /* ─── Responsive ─── */
        @media (max-width: 480px) {
            .login-card { padding: 36px 24px 28px; border-radius: 20px; }
            .login-title { font-size: 22px; }
            .shape-1 { width: 180px; height: 180px; }
            .shape-2 { width: 120px; height: 120px; }
            .shape-3 { width: 140px; height: 140px; }
        }
    </style>
</head>

<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="sa-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Platform Administration
                </div>
                <h1 class="login-title">Secure Access</h1>
                <p class="login-subtitle">Authorized personnel only</p>
            </div>

            @if($errors->any())
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ url(request()->segment(1) . '/login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="admin@platform.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">
                    Authenticate
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </form>
        </div>
        <p class="footer-text">&copy; {{ date('Y') }} Platform Administration. Secure Access.</p>
    </div>
</body>

</html>
