<style>
    /* ============ Modern 2026 Notification Bell & Dropdown ============ */
    .notification-wrapper {
        position: relative;
        overflow: visible;
    }

    .notification-bell {
        position: relative;
    }

    .notification-bell .noti-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        animation: notiBounce 2s ease-in-out infinite;
        pointer-events: none;
    }

    .notification-bell .noti-badge.hidden {
        display: none;
    }

    @keyframes notiBounce {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.15);
        }
    }

    /* Dropdown Panel */
    .notification-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 12px);
        right: -16px;
        width: 380px;
        background: #fff;
        backdrop-filter: blur(24px) saturate(180%);
        -webkit-backdrop-filter: blur(24px) saturate(180%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.05);
        z-index: 1000;
        overflow: hidden;
    }

    .notification-wrapper.active .notification-dropdown {
        display: block;
    }

    /* Arrow pointer */
    .notification-dropdown::before {
        content: '';
        position: absolute;
        top: -6px;
        right: 22px;
        width: 12px;
        height: 12px;
        background: rgba(255, 255, 255, 0.92);
        border-left: 1px solid rgba(255, 255, 255, 0.6);
        border-top: 1px solid rgba(255, 255, 255, 0.6);
        transform: rotate(45deg);
        z-index: 1;
    }

    /* Header */
    .noti-header {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.5) 100%);
    }

    .noti-header-title {
        font-weight: 700;
        font-size: 15px;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .noti-count-badge {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
    }

    .noti-mark-all {
        font-size: 12px;
        font-weight: 600;
        color: #4f46e5;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px 10px;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .noti-mark-all:hover {
        background: rgba(79, 70, 229, 0.08);
    }

    /* Body / List */
    .noti-body {
        max-height: 380px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(0, 0, 0, 0.08) transparent;
    }

    .noti-body::-webkit-scrollbar {
        width: 5px;
    }

    .noti-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .noti-body::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.08);
        border-radius: 10px;
    }

    /* Empty state */
    .noti-empty {
        padding: 40px 20px;
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
    }

    .noti-empty i {
        margin-bottom: 8px;
        display: block;
        opacity: 0.5;
    }

    /* Notification Item */
    .noti-item {
        padding: 14px 20px;
        display: flex;
        gap: 12px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        position: relative;
        background: transparent;
    }

    .noti-item:last-child {
        border-bottom: none;
    }

    .noti-item:hover {
        background: rgba(79, 70, 229, 0.04);
    }

    .noti-item.unread {
        background: rgba(79, 70, 229, 0.025);
    }

    .noti-item.unread::after {
        content: '';
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        width: 7px;
        height: 7px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    /* Icon */
    .noti-icon-box {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .noti-icon-box.project {
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4f46e5;
    }

    .noti-icon-box.task {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        color: #16a34a;
    }

    .noti-icon-box.lead {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #d97706;
    }

    .noti-icon-box.overdue {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #dc2626;
    }

    .noti-icon-box.micro_task {
        background: linear-gradient(135deg, #e0f2fe, #bae6fd);
        color: #0284c7;
    }

    /* Content */
    .noti-text-content {
        flex: 1;
        min-width: 0;
    }

    .noti-msg {
        font-size: 13px;
        color: #334155;
        margin: 0 0 3px 0;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .noti-msg strong {
        color: #0f172a;
        font-weight: 600;
    }

    .noti-time-label {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 500;
    }

    /* Footer */
    .noti-footer {
        padding: 10px;
        text-align: center;
        border-top: 1px solid rgba(0, 0, 0, 0.04);
        background: rgba(248, 250, 252, 0.5);
    }

    .noti-footer a {
        font-size: 12.5px;
        font-weight: 600;
        color: #4f46e5;
        text-decoration: none;
        padding: 4px 16px;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .noti-footer a:hover {
        background: rgba(79, 70, 229, 0.08);
    }

    /* Loading skeleton */
    .noti-skeleton {
        padding: 14px 20px;
        display: flex;
        gap: 12px;
    }

    .noti-skeleton .skel-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skelShimmer 1.5s infinite;
        flex-shrink: 0;
    }

    .noti-skeleton .skel-lines {
        flex: 1;
    }

    .noti-skeleton .skel-line {
        height: 10px;
        border-radius: 4px;
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: skelShimmer 1.5s infinite;
        margin-bottom: 6px;
    }

    .noti-skeleton .skel-line:last-child {
        width: 50%;
        margin-bottom: 0;
    }

    @keyframes skelShimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* ============ Header User Dropdown ============ */
    .header-user-dropdown-wrapper {
        position: relative;
    }

    .header-user-trigger {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 10px;
        transition: background 0.2s;
    }

    .header-user-trigger:hover {
        background: var(--muted, #f1f5f9);
    }

    .header-user-info {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .header-user-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--foreground, #0f172a);
    }

    .header-user-role {
        font-size: 11px;
        color: var(--muted-foreground, #64748b);
    }

    .header-user-arrow {
        width: 16px;
        height: 16px;
        color: var(--muted-foreground, #64748b);
        transition: transform 0.2s;
    }

    .header-dropdown-menu {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 220px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 16px 48px -12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        overflow: hidden;
    }

    .header-user-dropdown-wrapper.active .header-dropdown-menu {
        display: block;
    }

    .dropdown-header {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
    }

    .dropdown-user-name {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
    }

    .dropdown-user-email {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
        word-break: break-all;
    }

    .dropdown-divider {
        height: 1px;
        background: #f1f5f9;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 16px;
        font-size: 13px;
        color: #334155;
        cursor: pointer;
        transition: background 0.15s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .dropdown-item:hover {
        background: #f8fafc;
    }

    .dropdown-item i {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .dropdown-item-danger {
        color: #ef4444;
    }

    .dropdown-item-danger:hover {
        background: #fef2f2;
    }

    @media (max-width: 768px) {
        .hidden-mobile {
            display: none !important;
        }
    }

    /* ============ Desktop Toast Notifications ============ */
    .toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 99999;
        display: flex;
        flex-direction: column-reverse;
        gap: 12px;
        pointer-events: none;
        max-height: calc(100vh - 48px);
        overflow: hidden;
    }

    .toast-card {
        pointer-events: auto;
        width: 380px;
        max-width: calc(100vw - 48px);
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 16px;
        box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.18),
                    0 8px 20px -8px rgba(0, 0, 0, 0.08),
                    0 0 0 1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        cursor: pointer;
        animation: toastSlideIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        transform: translateX(120%);
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .toast-card:hover {
        box-shadow: 0 24px 56px -12px rgba(0, 0, 0, 0.22),
                    0 10px 24px -8px rgba(0, 0, 0, 0.1),
                    0 0 0 1px rgba(0, 0, 0, 0.06);
    }

    .toast-card.toast-exit {
        animation: toastSlideOut 0.35s cubic-bezier(0.55, 0, 1, 0.45) forwards;
    }

    @keyframes toastSlideIn {
        from {
            transform: translateX(120%) scale(0.9);
            opacity: 0;
        }
        to {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
    }

    @keyframes toastSlideOut {
        from {
            transform: translateX(0) scale(1);
            opacity: 1;
        }
        to {
            transform: translateX(120%) scale(0.9);
            opacity: 0;
        }
    }

    /* Accent bar */
    .toast-accent {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 4px 0 0 4px;
    }

    .toast-accent.accent-lead     { background: linear-gradient(180deg, #f59e0b, #d97706); }
    .toast-accent.accent-task     { background: linear-gradient(180deg, #22c55e, #16a34a); }
    .toast-accent.accent-micro_task { background: linear-gradient(180deg, #3b82f6, #2563eb); }
    .toast-accent.accent-project  { background: linear-gradient(180deg, #8b5cf6, #7c3aed); }
    .toast-accent.accent-default  { background: linear-gradient(180deg, #6366f1, #4f46e5); }

    /* Toast inner layout */
    .toast-inner {
        display: flex;
        gap: 12px;
        padding: 14px 16px 14px 20px;
        align-items: flex-start;
        position: relative;
    }

    .toast-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .toast-icon-box.icon-lead     { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .toast-icon-box.icon-task     { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a; }
    .toast-icon-box.icon-micro_task { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
    .toast-icon-box.icon-project  { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed; }
    .toast-icon-box.icon-default  { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }

    .toast-text {
        flex: 1;
        min-width: 0;
        padding-right: 20px;
    }

    .toast-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 0 0 4px 0;
        color: #94a3b8;
    }

    .toast-msg {
        font-size: 13.5px;
        font-weight: 500;
        color: #1e293b;
        margin: 0 0 4px 0;
        line-height: 1.45;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .toast-time {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 500;
    }

    .toast-close {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        padding: 0;
    }

    .toast-close:hover {
        background: #f1f5f9;
        color: #475569;
    }

    /* Progress bar */
    .toast-progress {
        height: 3px;
        background: #f1f5f9;
        overflow: hidden;
    }

    .toast-progress-bar {
        height: 100%;
        border-radius: 0 3px 3px 0;
        animation: toastCountdown 8s linear forwards;
    }

    .toast-progress-bar.bar-lead     { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .toast-progress-bar.bar-task     { background: linear-gradient(90deg, #22c55e, #16a34a); }
    .toast-progress-bar.bar-micro_task { background: linear-gradient(90deg, #3b82f6, #2563eb); }
    .toast-progress-bar.bar-project  { background: linear-gradient(90deg, #8b5cf6, #7c3aed); }
    .toast-progress-bar.bar-default  { background: linear-gradient(90deg, #6366f1, #4f46e5); }

    @keyframes toastCountdown {
        from { width: 100%; }
        to   { width: 0%; }
    }

    .toast-card:hover .toast-progress-bar {
        animation-play-state: paused;
    }

    /* Warning toast */
    .toast-card.toast-warning {
        cursor: default;
    }
    .toast-card.toast-warning .toast-accent {
        background: linear-gradient(180deg, #ef4444, #dc2626) !important;
    }
    .toast-card.toast-warning .toast-icon-box {
        background: linear-gradient(135deg, #fee2e2, #fecaca) !important;
        color: #dc2626 !important;
    }

    @media (max-width: 480px) {
        .toast-container {
            right: 12px;
            bottom: 12px;
        }
        .toast-card {
            width: calc(100vw - 24px);
        }
    }
</style>

<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menu-toggle"><i data-lucide="menu"></i></button>
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Home</a>
            <span class="breadcrumb-separator">/</span>
            <span>@yield('breadcrumb', 'Dashboard')</span>
        </div>
    </div>
    <div class="header-right">
        <button class="header-btn" id="dark-mode-toggle" title="Toggle Dark Mode">
            <i data-lucide="moon"></i>
        </button>

        <!-- Notification Bell -->
        <div class="notification-wrapper" id="notification-wrapper">
            <button class="header-btn notification-bell" title="Notifications" id="noti-bell-btn">
                <i data-lucide="bell"></i>
                <span class="noti-badge hidden" id="noti-badge">0</span>
            </button>
            <div class="notification-dropdown">
                <div class="noti-header">
                    <div class="noti-header-title">
                        Notifications
                        <span class="noti-count-badge hidden" id="noti-count-badge">0</span>
                    </div>
                    <button class="noti-mark-all" id="noti-mark-all-btn">Mark all read</button>
                </div>
                <div class="noti-body" id="noti-body">
                    <!-- Filled dynamically via JS -->
                    <div class="noti-skeleton">
                        <div class="skel-icon"></div>
                        <div class="skel-lines">
                            <div class="skel-line"></div>
                            <div class="skel-line"></div>
                        </div>
                    </div>
                    <div class="noti-skeleton">
                        <div class="skel-icon"></div>
                        <div class="skel-lines">
                            <div class="skel-line"></div>
                            <div class="skel-line"></div>
                        </div>
                    </div>
                    <div class="noti-skeleton">
                        <div class="skel-icon"></div>
                        <div class="skel-lines">
                            <div class="skel-line"></div>
                            <div class="skel-line"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-user-dropdown-wrapper" id="user-dropdown-wrapper">
            <div class="header-user-trigger" id="user-dropdown-trigger">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                </div>
                <div class="header-user-info hidden-mobile">
                    <span class="header-user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <span class="header-user-role">{{ auth()->user()->role->name ?? 'User' }}</span>
                </div>
                <i data-lucide="chevron-down" class="header-user-arrow"></i>
            </div>

            <div class="header-dropdown-menu" id="user-dropdown-menu">
                <div class="dropdown-header">
                    <p class="dropdown-user-name">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="dropdown-user-email">{{ auth()->user()->email ?? 'admin@example.com' }}</p>
                </div>
                <div class="dropdown-divider"></div>

                <a href="{{ route('admin.profile.index') }}" class="dropdown-item">
                    <i data-lucide="user"></i> My Profile
                </a>
                <a href="{{ route('admin.settings.index') }}" class="dropdown-item">
                    <i data-lucide="settings"></i> Settings
                </a>

                <div class="dropdown-divider"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item dropdown-item-danger">
                        <i data-lucide="log-out"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Desktop Toast Notification Container -->
<div class="toast-container" id="toast-container"></div>

@push('scripts')
    <script>
        (function () {
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const wrapper = document.getElementById('notification-wrapper');
            const bellBtn = document.getElementById('noti-bell-btn');
            const badge = document.getElementById('noti-badge');
            const countBadge = document.getElementById('noti-count-badge');
            const body = document.getElementById('noti-body');
            const markAllBtn = document.getElementById('noti-mark-all-btn');
            let loaded = false;

            const iconMap = {
                project: 'folder',
                task: 'check-square',
                lead: 'users',
                micro_task: 'list-checks'
            };

            function getIconClass(data) {
                if (data.type === 'overdue') return 'overdue';
                return data.icon || data.entity_type || 'task';
            }

            // Toggle dropdown
            bellBtn?.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                wrapper.classList.toggle('active');
                if (wrapper.classList.contains('active') && !loaded) {
                    loadNotifications();
                }
            });

            // Close on outside click
            document.addEventListener('click', function (e) {
                if (wrapper && wrapper.classList.contains('active') && !wrapper.contains(e.target)) {
                    wrapper.classList.remove('active');
                }
            });

            // Load notifications via AJAX
            function loadNotifications() {
                fetch("{{ route('admin.notifications.index') }}", { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(notifications => {
                        loaded = true;
                        if (!notifications.length) {
                            body.innerHTML = '<div class="noti-empty"><i data-lucide="bell-off" style="width:32px;height:32px;margin:0 auto 8px;"></i>No notifications yet</div>';
                            lucide.createIcons();
                            return;
                        }
                        body.innerHTML = '';
                        notifications.forEach(n => {
                            const d = n.data;
                            const isUnread = !n.read_at;
                            const iconClass = getIconClass(d);
                            const lucideIcon = iconMap[d.entity_type] || 'bell';

                            const item = document.createElement('div');
                            item.className = 'noti-item' + (isUnread ? ' unread' : '');
                            item.dataset.id = n.id;
                            item.innerHTML = `
                                <div class="noti-icon-box ${iconClass}">
                                    <i data-lucide="${lucideIcon}" style="width:16px;height:16px;"></i>
                                </div>
                                <div class="noti-text-content">
                                    <p class="noti-msg">${d.message || ''}</p>
                                    <span class="noti-time-label">${n.created_at}</span>
                                </div>
                            `;
                            item.addEventListener('click', () => markReadAndRedirect(n.id));
                            body.appendChild(item);
                        });
                        lucide.createIcons();
                    })
                    .catch(() => {
                        body.innerHTML = '<div class="noti-empty">Failed to load</div>';
                    });
            }

            // Mark read & redirect
            function markReadAndRedirect(id) {
                fetch("{{ url('admin/notifications') }}/" + id + "/read", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.url) window.location.href = data.url;
                    })
                    .catch(() => { });
            }

            // Mark all read
            markAllBtn?.addEventListener('click', function () {
                fetch("{{ route('admin.notifications.mark-all-read') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                })
                    .then(() => {
                        // Remove unread styling
                        body.querySelectorAll('.noti-item.unread').forEach(el => el.classList.remove('unread'));
                        updateBadge(0);
                    })
                    .catch(() => { });
            });

            // Update badge count
            function updateBadge(count) {
                if (count > 0) {
                    badge.textContent = count > 9 ? '9+' : count;
                    badge.classList.remove('hidden');
                    countBadge.textContent = count + ' New';
                    countBadge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                    countBadge.classList.add('hidden');
                }
            }

            // ═══════════════════════════════════════
            //  DESKTOP TOAST NOTIFICATIONS
            // ═══════════════════════════════════════
            let lastKnownCount = -1; // -1 means first load, skip toast
            const toastContainer = document.getElementById('toast-container');
            const TOAST_DURATION = 8000;
            const MAX_TOASTS = 4;

            const toastIconMap = {
                lead: { icon: 'users', label: 'Lead Follow-up' },
                task: { icon: 'check-square', label: 'Task Reminder' },
                micro_task: { icon: 'list-checks', label: 'Micro Task' },
                project: { icon: 'folder', label: 'Project Update' },
            };

            function getToastType(data) {
                return data?.entity_type || data?.icon || 'default';
            }

            function showToast(notification) {
                const data = notification.data || {};
                const entityType = getToastType(data);
                const meta = toastIconMap[entityType] || { icon: 'bell', label: 'Notification' };

                // Limit visible toasts
                const existing = toastContainer.querySelectorAll('.toast-card:not(.toast-exit)');
                if (existing.length >= MAX_TOASTS) {
                    dismissToast(existing[0]);
                }

                const card = document.createElement('div');
                card.className = 'toast-card';
                card.dataset.notiId = notification.id;
                card.dataset.url = data.url || '';
                card.innerHTML = `
                    <div class="toast-accent accent-${entityType}"></div>
                    <div class="toast-inner">
                        <div class="toast-icon-box icon-${entityType}">
                            <i data-lucide="${meta.icon}" style="width:18px;height:18px"></i>
                        </div>
                        <div class="toast-text">
                            <p class="toast-title">${meta.label}</p>
                            <p class="toast-msg">${escToast(data.message || 'New notification')}</p>
                            <span class="toast-time">${notification.created_at || 'Just now'}</span>
                        </div>
                        <button class="toast-close" title="Dismiss">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="toast-progress">
                        <div class="toast-progress-bar bar-${entityType}"></div>
                    </div>
                `;

                // Close button
                card.querySelector('.toast-close').addEventListener('click', function (e) {
                    e.stopPropagation();
                    dismissToast(card);
                });

                // Click → navigate (with dirty-form check)
                card.addEventListener('click', function () {
                    const url = card.dataset.url;
                    if (!url) { dismissToast(card); return; }

                    if (hasUnsavedFormData()) {
                        showWarningToast(getCurrentModuleName());
                        return;
                    }

                    // Mark as read then redirect
                    const notiId = card.dataset.notiId;
                    fetch("{{ url('admin/notifications') }}/" + notiId + "/read", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
                    }).finally(() => {
                        window.location.href = url;
                    });
                });

                toastContainer.appendChild(card);
                if (typeof lucide !== 'undefined') lucide.createIcons();

                // Auto-dismiss
                const timer = setTimeout(() => dismissToast(card), TOAST_DURATION);
                card._toastTimer = timer;

                // Pause on hover
                card.addEventListener('mouseenter', () => clearTimeout(card._toastTimer));
                card.addEventListener('mouseleave', () => {
                    card._toastTimer = setTimeout(() => dismissToast(card), 3000);
                });
            }

            function dismissToast(card) {
                if (!card || card.classList.contains('toast-exit')) return;
                clearTimeout(card._toastTimer);
                card.classList.add('toast-exit');
                setTimeout(() => card.remove(), 350);
            }

            function showWarningToast(moduleName) {
                // Check if warning toast already exists
                if (toastContainer.querySelector('.toast-warning')) return;

                const card = document.createElement('div');
                card.className = 'toast-card toast-warning';
                card.innerHTML = `
                    <div class="toast-accent"></div>
                    <div class="toast-inner">
                        <div class="toast-icon-box">
                            <i data-lucide="alert-triangle" style="width:18px;height:18px"></i>
                        </div>
                        <div class="toast-text">
                            <p class="toast-title">Unsaved Changes</p>
                            <p class="toast-msg">Please save this <strong>${escToast(moduleName)}</strong> first before navigating away.</p>
                        </div>
                        <button class="toast-close" title="Dismiss">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <div class="toast-progress">
                        <div class="toast-progress-bar bar-default" style="background:linear-gradient(90deg,#ef4444,#dc2626)"></div>
                    </div>
                `;
                card.querySelector('.toast-close').addEventListener('click', function (e) {
                    e.stopPropagation();
                    dismissToast(card);
                });
                toastContainer.appendChild(card);
                if (typeof lucide !== 'undefined') lucide.createIcons();
                card._toastTimer = setTimeout(() => dismissToast(card), 5000);
            }

            function hasUnsavedFormData() {
                const forms = document.querySelectorAll('form');
                for (const form of forms) {
                    // Skip search/filter forms and hidden forms
                    if (form.method === 'get' || form.id?.includes('filter') || form.id?.includes('search')) continue;
                    if (form.style.display === 'none') continue;
                    // Check if any input has been modified
                    const inputs = form.querySelectorAll('input:not([type=hidden]), textarea, select');
                    for (const input of inputs) {
                        if (input.type === 'submit' || input.type === 'button' || input.type === 'reset') continue;
                        if (input.tagName === 'SELECT') {
                            if (input.selectedIndex !== input.dataset.originalIndex) continue; // not tracked
                        }
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            if (input.checked !== input.defaultChecked) return true;
                        } else if ((input.value || '') !== (input.defaultValue || '')) {
                            return true;
                        }
                    }
                }
                return false;
            }

            function getCurrentModuleName() {
                const breadcrumb = document.querySelector('.breadcrumb span:last-child');
                return breadcrumb?.textContent?.trim() || 'page';
            }

            function escToast(str) {
                if (!str) return '';
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }

            // ═══════════════════════════════════════
            //  POLLING (enhanced with toast support)
            // ═══════════════════════════════════════
            function pollUnreadCount() {
                fetch("{{ route('admin.notifications.unread-count') }}", { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        const newCount = data.count || 0;
                        updateBadge(newCount);

                        // Detect NEW notifications (count went up)
                        if (lastKnownCount >= 0 && newCount > lastKnownCount) {
                            fetchAndShowNewToasts(newCount - lastKnownCount);
                        }
                        lastKnownCount = newCount;
                    })
                    .catch(() => { });
            }

            function fetchAndShowNewToasts(howMany) {
                fetch("{{ route('admin.notifications.index') }}", { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(notifications => {
                        // Show only the newest unread ones
                        const unread = notifications.filter(n => !n.read_at).slice(0, Math.min(howMany, MAX_TOASTS));
                        unread.reverse().forEach((n, i) => {
                            setTimeout(() => showToast(n), i * 300); // stagger
                        });
                        // Also refresh dropdown if already loaded
                        if (loaded) loadNotifications();
                    })
                    .catch(() => { });
            }

            // Initial poll + interval
            pollUnreadCount();
            setInterval(pollUnreadCount, 30000);
        })();

        // ===== User Dropdown Toggle =====
        (function () {
            const userWrapper = document.getElementById('user-dropdown-wrapper');
            const userTrigger = document.getElementById('user-dropdown-trigger');
            if (userTrigger && userWrapper) {
                userTrigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userWrapper.classList.toggle('active');
                    // Close notification dropdown if open
                    const notiWrapper = document.getElementById('notification-wrapper');
                    if (notiWrapper) notiWrapper.classList.remove('active');
                });
                document.addEventListener('click', function (e) {
                    if (userWrapper.classList.contains('active') && !userWrapper.contains(e.target)) {
                        userWrapper.classList.remove('active');
                    }
                });
            }
        })();
    </script>
@endpush