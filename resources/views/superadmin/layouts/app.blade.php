<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Super Admin</title>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f59e0b' /%3E%3Cstop offset='100%25' stop-color='%2310b981' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='25' fill='url(%23g)' /%3E%3Ctext x='50' y='68' font-family='Arial, sans-serif' font-weight='900' font-size='52' fill='white' text-anchor='middle'%3ESA%3C/text%3E%3C/svg%3E">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    @hasSection('has_charts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif

    {{-- SA Sidebar overrides — match admin-style.css sidebar appearance --}}
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* ─── Sidebar header ─── */
        .sidebar-header {
            padding: 0 1.25rem;
            height: 64px;
        }
        .sidebar-logo {
            gap: 0.75rem;
        }
        .sidebar-logo-icon {
            background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--accent)));
        }
        .sidebar-logo-text {
            font-size: 1.125rem;
            font-weight: 600;
        }

        /* ─── Nav links — admin matching ─── */
        .sidebar-nav {
            padding: 1rem 0;
        }
        .sidebar-nav nav {
            padding: 0;
            gap: 2px;
        }
        .nav-link {
            gap: 0.75rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            border-radius: 0;
            opacity: 0.7;
            transition: all 150ms;
        }
        .nav-link:hover {
            opacity: 1;
            background-color: hsl(var(--sidebar-accent));
        }
        .nav-link.active {
            opacity: 1;
            background-color: hsl(var(--sidebar-accent));
            color: hsl(var(--primary));
            border-left: 3px solid hsl(var(--primary));
        }

        /* ─── Section titles — muted gray like admin ─── */
        .nav-divider {
            padding: 0.75rem 1.25rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            opacity: 1;
        }

        /* ─── Topbar — match admin header ─── */
        .topbar {
            background-color: hsl(var(--card));
            border-bottom: 1px solid hsl(var(--border));
        }

        /* ─── Collapsed sidebar states ─── */
        .sidebar.collapsed .nav-divider {
            display: none;
        }
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 10px 8px;
            border-left: none;
        }
    </style>
    @stack('styles')
</head>

<body>
    {{-- SA Sidebar — uses style.css classes, matches admin panel visual --}}
    <aside class="sidebar" id="sa-sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i data-lucide="shield-check"></i>
                </div>
                <span class="sidebar-logo-text">Super Admin</span>
            </div>
            <button class="sidebar-toggle" onclick="toggleSaSidebar()">
                <i data-lucide="panel-left-close" id="sa-sidebar-icon"></i>
            </button>
        </div>

        <div class="sidebar-nav">
            <nav>
                <div class="nav-divider">Main</div>

                <a href="{{ route('superadmin.dashboard') }}"
                   class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>

                <div class="nav-divider">Business Management</div>

                <a href="{{ route('superadmin.businesses.index') }}"
                   class="nav-link {{ request()->routeIs('superadmin.businesses.*') ? 'active' : '' }}">
                    <i data-lucide="building-2"></i>
                    <span>Businesses</span>
                </a>
                <a href="{{ route('superadmin.packages.index') }}"
                   class="nav-link {{ request()->routeIs('superadmin.packages.*') ? 'active' : '' }}">
                    <i data-lucide="package"></i>
                    <span>Packages</span>
                </a>
                <a href="{{ route('superadmin.subscriptions.index') }}"
                   class="nav-link {{ request()->routeIs('superadmin.subscriptions.*') ? 'active' : '' }}">
                    <i data-lucide="credit-card"></i>
                    <span>Subscriptions</span>
                </a>

                <div class="nav-divider">Token Economy</div>

                <a href="{{ route('superadmin.credit-packs.index') }}"
                   class="nav-link {{ request()->routeIs('superadmin.credit-packs.*') ? 'active' : '' }}">
                    <i data-lucide="coins"></i>
                    <span>Credit Packs</span>
                </a>
                <a href="{{ route('superadmin.wallets.index') }}"
                   class="nav-link {{ request()->routeIs('superadmin.wallets.*') ? 'active' : '' }}">
                    <i data-lucide="wallet"></i>
                    <span>Wallets</span>
                </a>

                <div class="nav-divider">Platform</div>

                <a href="{{ route('superadmin.settings') }}"
                   class="nav-link {{ request()->routeIs('superadmin.settings*') ? 'active' : '' }}">
                    <i data-lucide="settings"></i>
                    <span>Global Settings</span>
                </a>
            </nav>
        </div>
    </aside>

    <div class="main-wrapper" id="sa-main">
        {{-- SA Topbar — matches admin header structure --}}
        <header class="topbar" id="sa-topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" onclick="toggleSaSidebar()" style="display:none;" id="sa-menu-toggle">
                    <i data-lucide="menu"></i>
                </button>
                <div style="display:flex;align-items:center;gap:8px;">
                    <a href="{{ route('superadmin.dashboard') }}" style="font-size:14px;color:hsl(var(--muted-foreground));text-decoration:none;">Home</a>
                    <span style="opacity:0.5;font-size:14px;">/</span>
                    <span style="font-size:14px;">@yield('breadcrumb', 'Dashboard')</span>
                </div>
            </div>
            <div class="topbar-right">
                {{-- Subscription Warning Badge --}}
                @php
                    $expiringSoonCount = \App\Models\Subscription::expiringSoon()->count();
                @endphp
                @if($expiringSoonCount > 0)
                <div class="badge badge-destructive" style="font-size:11px;">
                    <i data-lucide="alert-triangle" style="width:12px;height:12px;margin-right:4px;"></i>
                    {{ $expiringSoonCount }} Expiring Soon
                </div>
                @endif

                <div style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:4px 8px;border-radius:10px;">
                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,hsl(var(--primary)),hsl(var(--accent)));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:14px;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'SA', 0, 2)) }}
                    </div>
                    <div style="line-height:1.2;">
                        <div style="font-size:13px;font-weight:600;color:hsl(var(--foreground));">{{ auth()->user()->name }}</div>
                        <div style="font-size:11px;color:hsl(var(--muted-foreground));">Super Admin</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-icon btn-sm" title="Sign Out"
                            style="width:36px;height:36px;border-radius:8px;color:hsl(var(--muted-foreground));">
                        <i data-lucide="log-out" style="width:18px;height:18px;"></i>
                    </button>
                </form>
            </div>
        </header>

        <div class="main-content">
            {{-- Flash Messages --}}
            @if(session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;background:hsl(var(--success)/0.1);border:1px solid hsl(var(--success)/0.2);border-radius:8px;color:hsl(var(--success));font-size:14px;display:flex;align-items:center;gap:8px;">
                <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;background:hsl(var(--destructive)/0.1);border:1px solid hsl(var(--destructive)/0.2);border-radius:8px;color:hsl(var(--destructive));font-size:14px;display:flex;align-items:center;gap:8px;">
                <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
                {{ session('error') }}
            </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script>
        function toggleSaSidebar() {
            const sidebar = document.getElementById('sa-sidebar');
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sa-sidebar-collapsed', sidebar.classList.contains('collapsed'));
        }
        // Restore sidebar state
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('sa-sidebar-collapsed') === 'true') {
                document.getElementById('sa-sidebar').classList.add('collapsed');
            }
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>

</html>
