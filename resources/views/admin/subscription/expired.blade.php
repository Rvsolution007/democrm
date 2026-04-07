<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired - RV CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 50%, #fef2f2 100%);
            color: #1f2937;
        }
        .container {
            width: 100%; max-width: 520px; padding: 24px; text-align: center;
        }
        .card {
            background: white;
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.08), 0 1px 3px rgba(0,0,0,0.06);
            border: 1px solid rgba(239, 68, 68, 0.1);
        }
        .icon-wrapper {
            width: 80px; height: 80px; margin: 0 auto 24px;
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            animation: pulseIcon 2s ease-in-out infinite;
        }
        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .icon-wrapper svg { width: 36px; height: 36px; color: #ef4444; }
        h1 { font-size: 24px; font-weight: 700; color: #991b1b; margin-bottom: 12px; }
        .subtitle { font-size: 15px; color: #6b7280; line-height: 1.6; margin-bottom: 32px; }

        .info-box {
            background: #fef2f2; border: 1px solid #fee2e2;
            border-radius: 12px; padding: 16px; margin-bottom: 28px;
            text-align: left;
        }
        .info-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; font-size: 13px;
        }
        .info-row:not(:last-child) { border-bottom: 1px solid #fee2e2; }
        .info-label { color: #6b7280; font-weight: 500; }
        .info-value { color: #1f2937; font-weight: 600; }
        .info-value.expired { color: #ef4444; }

        .reason {
            font-size: 14px; color: #dc2626; font-weight: 500;
            margin-bottom: 24px; padding: 12px;
            background: rgba(239, 68, 68, 0.05);
            border-radius: 8px;
        }

        .btn-contact {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 32px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white; border: none; border-radius: 12px;
            font-size: 15px; font-weight: 600; font-family: inherit;
            cursor: pointer; text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .btn-logout {
            display: inline-block; margin-top: 16px;
            font-size: 13px; color: #6b7280; text-decoration: none;
            font-weight: 500;
        }
        .btn-logout:hover { color: #374151; }

        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>

            <h1>
                @if(session('reason') === 'suspended')
                    Account Suspended
                @else
                    Subscription Expired
                @endif
            </h1>

            <p class="subtitle">
                @if(session('reason') === 'suspended')
                    Your account has been suspended by the platform administrator. Please contact support for assistance.
                @else
                    Your subscription has expired. Please contact the platform administrator to renew your subscription and regain access to all features.
                @endif
            </p>

            @if($subscription ?? false)
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Company</span>
                    <span class="info-value">{{ $company->name ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Package</span>
                    <span class="info-value">{{ $subscription->package->name ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value expired">{{ ucfirst($subscription->status) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Expired On</span>
                    <span class="info-value expired">{{ $subscription->expires_at?->format('d M Y') ?? '—' }}</span>
                </div>
            </div>
            @endif

            <a href="mailto:support@rvcrm.com" class="btn-contact">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
                Contact Administrator
            </a>

            <br>
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn-logout" style="background:none;border:none;cursor:pointer;">← Sign Out & Use Different Account</button>
            </form>
        </div>
        <p class="footer">&copy; {{ date('Y') }} RV CRM. All rights reserved.</p>
    </div>
</body>
</html>
