<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
        }
        .container { text-align: center; padding: 32px; }
        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 10px 20px; border-radius: 50px;
            font-size: 12px; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; color: #818cf8; margin-bottom: 24px;
        }
        h1 { font-size: 32px; font-weight: 700; margin-bottom: 12px; }
        p { color: #64748b; font-size: 16px; margin-bottom: 8px; }
        .info {
            background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px; padding: 24px 32px; margin-top: 32px;
            display: inline-block; text-align: left;
        }
        .info div { padding: 8px 0; font-size: 14px; display: flex; gap: 12px; }
        .info .label { color: #64748b; min-width: 120px; }
        .info .value { color: #e2e8f0; font-weight: 500; }
        .btn-logout {
            display: inline-flex; align-items: center; gap: 8px;
            margin-top: 28px; padding: 12px 28px;
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px; color: #f87171; font-size: 14px; font-weight: 500;
            cursor: pointer; font-family: inherit; transition: all 0.3s;
        }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.25); }
        .coming-soon {
            margin-top: 16px; font-size: 13px; color: #475569;
            background: rgba(99, 102, 241, 0.05); border-radius: 8px; padding: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Super Admin Panel
        </div>
        <h1>Welcome, {{ auth()->user()->name }}</h1>
        <p>Platform Administration Dashboard</p>
        <p style="font-size:14px; color:#818cf8;">You are logged in as Super Admin</p>

        <div class="info">
            <div><span class="label">Email</span><span class="value">{{ auth()->user()->email }}</span></div>
            <div><span class="label">User Type</span><span class="value">{{ auth()->user()->user_type }}</span></div>
            <div><span class="label">Last Login</span><span class="value">{{ auth()->user()->last_login_at?->format('d M Y, h:i A') ?? '—' }}</span></div>
            <div><span class="label">Total Packages</span><span class="value">{{ \App\Models\SubscriptionPackage::count() }}</span></div>
            <div><span class="label">Total Companies</span><span class="value">{{ \App\Models\Company::count() }}</span></div>
            <div><span class="label">Active Subscriptions</span><span class="value">{{ \App\Models\Subscription::active()->count() }}</span></div>
        </div>

        <div class="coming-soon">
            📋 Full SA Dashboard with KPIs, Business CRUD, Package Management, Token Economics will be built in Session 3 & 4
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign Out
            </button>
        </form>
    </div>
</body>
</html>
