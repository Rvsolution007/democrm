<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">RV</div>
        <span class="sidebar-title">RV CRM</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="{{ route('admin.dashboard') }}"
                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
        </div>
        @if(can('leads.read') || can('clients.read') || can('quotes.read'))
            <div class="nav-section">
                <div class="nav-section-title">Sales</div>
                @if(can('leads.read'))
                    <a href="{{ route('admin.leads.index') }}"
                        class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                        <i data-lucide="users"></i> Leads
                    </a>
                @endif
                @if(can('clients.read'))
                    <a href="{{ route('admin.clients.index') }}"
                        class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                        <i data-lucide="user-circle"></i> Clients
                    </a>
                @endif
                @if(can('quotes.read'))
                    <a href="{{ route('admin.quotes.index') }}"
                        class="nav-link {{ request()->routeIs('admin.quotes.*') ? 'active' : '' }}">
                        <i data-lucide="file-text"></i> Quotes
                    </a>
                    <a href="{{ route('admin.payments.index') }}"
                        class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                        <i data-lucide="credit-card"></i> Payments
                    </a>
                @endif
                @if(can('leads.read'))
                    <a href="{{ route('admin.followups.index') }}"
                        class="nav-link {{ request()->routeIs('admin.followups.*') ? 'active' : '' }}">
                        <i data-lucide="phone-call"></i> Follow-ups
                    </a>
                @endif

                <div class="nav-section-title"
                    style="margin-top: 15px; font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                    WhatsApp Bulk</div>
                @if(can('leads.read'))
                    <a href="{{ route('admin.whatsapp-connect.index') }}"
                        class="nav-link {{ request()->routeIs('admin.whatsapp-connect.*') ? 'active' : '' }}">
                        <i data-lucide="smartphone" style="color:#25D366"></i> WhatsApp Connect
                    </a>
                    <a href="{{ route('admin.whatsapp-campaigns.index') }}"
                        class="nav-link {{ request()->routeIs('admin.whatsapp-campaigns.*') ? 'active' : '' }}">
                        <i data-lucide="send"></i> Bulk Sender
                    </a>
                    <a href="{{ route('admin.whatsapp-templates.index') }}"
                        class="nav-link {{ request()->routeIs('admin.whatsapp-templates.*') ? 'active' : '' }}">
                        <i data-lucide="message-square"></i> Templates
                    </a>
                @endif

            </div>
        @endif
        @if(can('products.read') || can('categories.read') || can('projects.global') || can('quotes.global'))
            <div class="nav-section">
                <div class="nav-section-title">Catalog</div>
                @if(can('products.read'))
                    <a href="{{ route('admin.products.index') }}"
                        class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                        <i data-lucide="package"></i> Products
                    </a>
                @endif
                @if(can('categories.read'))
                    <a href="{{ route('admin.categories.index') }}"
                        class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i data-lucide="folder"></i> Categories
                    </a>
                @endif
                @if(can('projects.global') || can('quotes.global'))
                    <a href="{{ route('admin.vendors.index') }}"
                        class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                        <i data-lucide="truck"></i> Vendors
                    </a>
                    <a href="{{ route('admin.purchases.index') }}"
                        class="nav-link {{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}">
                        <i data-lucide="shopping-cart"></i> Purchases
                    </a>
                    <a href="{{ route('admin.purchase-payments.index') }}"
                        class="nav-link {{ request()->routeIs('admin.purchase-payments.*') ? 'active' : '' }}">
                        <i data-lucide="credit-card"></i> Purchase Payments
                    </a>
                @endif
            </div>
        @endif
        @if(can('projects.read') || can('tasks.read'))
            <div class="nav-section">
                <div class="nav-section-title">Production</div>
                @if(can('projects.read'))
                    <a href="{{ route('admin.projects.index') }}"
                        class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                        <i data-lucide="briefcase"></i> Projects
                    </a>
                @endif
                @if(can('tasks.read'))
                    <a href="{{ route('admin.tasks.index') }}"
                        class="nav-link {{ request()->routeIs('admin.tasks.*') ? 'active' : '' }}">
                        <i data-lucide="check-square"></i> Tasks
                    </a>
                @endif
                @if(can('tasks.read'))
                    <a href="{{ route('admin.micro-tasks.index') }}"
                        class="nav-link {{ request()->routeIs('admin.micro-tasks.*') ? 'active' : '' }}">
                        <i data-lucide="list-todo"></i> Micro Tasks
                    </a>
                @endif
                @if(can('tasks.read'))
                    <a href="{{ route('admin.task-followups.index') }}"
                        class="nav-link {{ request()->routeIs('admin.task-followups.*') ? 'active' : '' }}">
                        <i data-lucide="bell-ring"></i> Micro Task Follow-ups
                    </a>
                @endif
                @if(can('settings.manage'))
                    <a href="{{ route('admin.service-templates.index') }}"
                        class="nav-link {{ request()->routeIs('admin.service-templates.*') ? 'active' : '' }}">
                        <i data-lucide="clipboard-list"></i> Service Templates
                    </a>
                @endif
            </div>
        @endif
        @if(can('users.read') || can('roles.read'))
            <div class="nav-section">
                <div class="nav-section-title">Team</div>
                @if(can('users.read'))
                    <a href="{{ route('admin.users.index') }}"
                        class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i data-lucide="users-2"></i> Users
                    </a>
                @endif
                @if(can('roles.read'))
                    <a href="{{ route('admin.roles.index') }}"
                        class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i data-lucide="shield"></i> Roles
                    </a>
                @endif
                @if(can('activities.read'))
                    <a href="{{ route('admin.activities.index') }}"
                        class="nav-link {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}">
                        <i data-lucide="activity"></i> Activities
                    </a>
                @endif
            </div>
        @endif
        <div class="nav-section">
            <div class="nav-section-title">Analytics</div>
            <a href="{{ route('admin.profile.index') }}"
                class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                <i data-lucide="user"></i> My Profile
            </a>
            @if(can('reports.read'))
                <a href="{{ route('admin.reports.index') }}"
                    class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i data-lucide="bar-chart-2"></i> Reports
                </a>
            @endif
            @if(can('settings.manage'))
                <a href="{{ route('admin.settings.index') }}"
                    class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i data-lucide="settings"></i> Settings
                </a>
            @endif
        </div>
    </nav>

</aside>