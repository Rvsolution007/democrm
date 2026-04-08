<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RV CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS reset and base */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f8fcfd 0%, #f1f9fc 100%);
            color: #1f2937;
            position: relative;
            overflow: hidden;
        }

        /* Animated background shapes */
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            animation: floatShape 10s ease-in-out infinite alternate;
        }

        /* Top Left Blue */
        .shape-1 {
            width: 250px;
            height: 250px;
            background: #56c2ff;
            border-radius: 40px 100px 100px 40px;
            top: -50px;
            left: -50px;
            opacity: 0.8;
            transform: rotate(-15deg);
        }

        /* Top Right Yellow */
        .shape-2 {
            width: 180px;
            height: 180px;
            background: #fbd84a;
            border-radius: 20px;
            top: 10%;
            right: 10%;
            opacity: 0.9;
            transform: rotate(20deg);
            animation-duration: 12s;
            animation-delay: -5s;
        }

        /* Bottom Left Yellow */
        .shape-3 {
            width: 200px;
            height: 200px;
            background: #fbd84a;
            border-radius: 40px 10px 40px 40px;
            bottom: 5%;
            left: 15%;
            opacity: 0.7;
            transform: rotate(-30deg);
            animation-duration: 15s;
        }

        /* Bottom Right Yellow Circle */
        .shape-4 {
            width: 150px;
            height: 150px;
            background: #fbd84a;
            border-radius: 50%;
            bottom: -20px;
            right: 20%;
            opacity: 0.8;
            animation-duration: 14s;
            animation-delay: -7s;
        }

        @keyframes floatShape {
            0% {
                transform: translateY(0) rotate(0deg) scale(1);
            }

            50% {
                transform: translateY(-20px) rotate(5deg) scale(1.05);
            }

            100% {
                transform: translateY(10px) rotate(-5deg) scale(0.95);
            }
        }

        /* Glassmorphism Card */
        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 28px;
            padding: 48px 40px 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
            animation: cardFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes cardFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header / Logo */
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            position: relative;
        }

        .logo-icon-part1 {
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 36px;
            background: #2db5ea;
            border-radius: 12px 12px 4px 12px;
        }

        .logo-icon-part2 {
            position: absolute;
            right: 0;
            top: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fbd84a;
            opacity: 0.9;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -0.5px;
        }

        .login-title {
            font-size: 26px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            animation: inputEnter 0.6s ease-out forwards;
            opacity: 0;
            transform: translateX(-10px);
        }

        .form-group:nth-child(2) .input-wrapper {
            animation-delay: 0.1s;
        }

        .form-group:nth-child(3) .input-wrapper {
            animation-delay: 0.2s;
        }

        @keyframes inputEnter {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .input-icon {
            position: absolute;
            left: 14px;
            width: 18px;
            height: 18px;
            color: #9ca3af;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 13px 40px 13px 42px;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            color: #111827;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .form-input:focus {
            border-color: #2db5ea;
            box-shadow: 0 0 0 3px rgba(45, 181, 234, 0.15);
        }

        .form-input:focus~.input-icon {
            color: #2db5ea;
        }

        .form-input.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #6b7280;
        }

        .form-extras {
            display: flex;
            justify-content: flex-end;
            margin-top: -8px;
            margin-bottom: 24px;
            opacity: 0;
            animation: inputEnter 0.6s 0.3s ease-out forwards;
        }

        .forgot-password {
            font-size: 13px;
            color: #2db5ea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-password:hover {
            color: #1e9ac9;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, #2db5ea, #38bdf8);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            opacity: 0;
            animation: inputEnter 0.6s 0.4s ease-out forwards;
            box-shadow: 0 4px 12px rgba(45, 181, 234, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(45, 181, 234, 0.4);
            background: linear-gradient(to right, #1ea5da, #29aeea);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login svg {
            transition: transform 0.3s ease;
        }

        .btn-login:hover svg {
            transform: translateX(4px);
        }

        /* Error Message */
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #dc2626;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        @keyframes shake {

            10%,
            90% {
                transform: translate3d(-1px, 0, 0);
            }

            20%,
            80% {
                transform: translate3d(2px, 0, 0);
            }

            30%,
            50%,
            70% {
                transform: translate3d(-4px, 0, 0);
            }

            40%,
            60% {
                transform: translate3d(4px, 0, 0);
            }
        }

        .footer-text {
            text-align: center;
            margin-top: 32px;
            font-size: 12px;
            color: #6b7280;
            opacity: 0;
            animation: inputEnter 0.6s 0.6s ease-out forwards;
        }
    </style>
</head>

<body>
    <!-- Background Animated Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    @php
                        $loginCompany = \App\Models\Company::first();
                    @endphp
                    @if($loginCompany && $loginCompany->logo)
                        <img src="{{ asset('storage/' . $loginCompany->logo) }}" alt="{{ $loginCompany->name ?? 'Company' }}" style="max-width:60px;max-height:60px;object-fit:contain;border-radius:8px;">
                    @else
                        <!-- Default generic logo -->
                        <div class="logo-icon">
                            <div class="logo-icon-part2"></div>
                            <div class="logo-icon-part1"></div>
                        </div>
                    @endif
                    <span class="logo-text">{{ $loginCompany->name ?? 'RV CRM' }}</span>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your {{ $loginCompany->name ?? 'RV CRM' }} account</p>
            </div>

            @if($errors->any())
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ url('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <!-- Custom focused border uses :focus in css -->
                        <input type="email" name="email" class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                            placeholder="you@company.com" value="{{ old('email') }}" required autofocus>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-input"
                            placeholder="Enter your password" required>
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-extras">
                    <a href="{{ route('password.forgot') }}" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">
                    Sign In
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>
        </div>
        <p class="footer-text">Don't have an account? <a href="{{ route('landing') }}#pricing" style="color:#2db5ea;font-weight:600;">Register Free</a></p>
        <p class="footer-text" style="margin-top:8px;">&copy; {{ date('Y') }} RV CRM. All rights reserved.</p>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" x2="23" y1="1" y2="23"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>

</html>