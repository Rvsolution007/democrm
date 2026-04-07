<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">RV</div>
        <span class="sidebar-title">RV CRM</span>
    </div>
    <nav class="sidebar-nav">
        {{-- Subscription Warning (if in grace period) --}}
        @if(session('subscription_warning'))
        <div style="margin:8px;padding:8px 10px;background:rgba(234,179,8,0.15);border:1px solid rgba(234,179,8,0.3);border-radius:8px;font-size:11px;color:#eab308;">
            <i data-lucide="alert-triangle" style="width:12px;height:12px;vertical-align:text-bottom;"></i>
            Subscription expiring soon
        </div>
        @endif

        {{-- ═══ MAIN — Always visible ═══ --}}
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="{{ route('admin.dashboard') }}"
                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
            </a>
        </div>

        {{-- ═══ SALES — Package 1+ (leads, clients, quotes, invoices, payments, followups) ═══ --}}
        @if(can('leads.read') || can('clients.read') || can('quotes.read'))
            <div class="nav-section">
                <div class="nav-section-title">Sales</div>
                @if(can('leads.read'))
                    <a href="{{ route('admin.leads.index') }}"
                        class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                        <i data-lucide="users"></i> <span>Leads</span>
                    </a>
                @endif
                @if(can('clients.read'))
                    <a href="{{ route('admin.clients.index') }}"
                        class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                        <i data-lucide="user-circle"></i> <span>Clients</span>
                    </a>
                @endif
                @if(can('quotes.read'))
                    <a href="{{ route('admin.quotes.index') }}"
                        class="nav-link {{ request()->routeIs('admin.quotes.*') ? 'active' : '' }}">
                        <i data-lucide="file-text"></i> <span>Quotes</span>
                    </a>
                    <a href="{{ route('admin.invoices.index') }}"
                        class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                        <i data-lucide="file-check"></i> <span>Invoices</span>
                    </a>
                    <a href="{{ route('admin.payments.index') }}"
                        class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                        <i data-lucide="credit-card"></i> <span>Payments</span>
                    </a>
                @endif
                @if(can('leads.read'))
                    <a href="{{ route('admin.followups.index') }}"
                        class="nav-link {{ request()->routeIs('admin.followups.*') ? 'active' : '' }}">
                        <i data-lucide="phone-call"></i> <span>Follow-ups</span>
                    </a>
                @endif

            </div>
        @endif

        {{-- ═══ WHATSAPP BULK — Package 2+ (whatsapp_connect, campaigns, templates, auto_reply) ═══ --}}
        @if(hasFeature('whatsapp_connect'))
            @if(can('whatsapp-connect.read') || can('whatsapp-connect.write') || can('whatsapp-extension.read') || can('whatsapp-campaigns.read') || can('whatsapp-templates.read') || can('whatsapp-auto-reply.read') || can('whatsapp-analytics.read'))
                <div class="nav-section">
                    <div class="nav-section-title"
                        style="margin-top: 15px; font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                        WhatsApp Bulk</div>
                    
                    @if(can('whatsapp-connect.read') || can('whatsapp-connect.write'))
                        <a href="{{ route('admin.whatsapp-connect.index') }}"
                            class="nav-link {{ request()->routeIs('admin.whatsapp-connect.*') ? 'active' : '' }}">
                            <i data-lucide="smartphone" style="color:#25D366"></i> <span>WhatsApp Connect</span>
                        </a>
                    @endif
                    @if(can('whatsapp-extension.read'))
                        <a href="{{ route('admin.whatsapp.extension') }}"
                            class="nav-link {{ request()->routeIs('admin.whatsapp.extension') ? 'active' : '' }}">
                            <i data-lucide="globe" style="color:#3b82f6"></i> <span>Chrome Extension</span>
                        </a>
                    @endif
                    @if(can('whatsapp-campaigns.read'))
                        <a href="{{ route('admin.whatsapp-campaigns.index') }}"
                            class="nav-link {{ request()->routeIs('admin.whatsapp-campaigns.*') ? 'active' : '' }}">
                            <i data-lucide="send"></i> <span>Bulk Sender</span>
                        </a>
                    @endif
                    @if(can('whatsapp-templates.read'))
                        <a href="{{ route('admin.whatsapp-templates.index') }}"
                            class="nav-link {{ request()->routeIs('admin.whatsapp-templates.*') ? 'active' : '' }}">
                            <i data-lucide="message-square"></i> <span>Templates</span>
                        </a>
                    @endif
                    @if(can('whatsapp-auto-reply.read'))
                        @php $aiBotOn = \App\Models\Setting::getValue('ai_bot', 'enabled', false); @endphp
                        @if(!$aiBotOn)
                        <a href="{{ route('admin.whatsapp-auto-reply.index') }}"
                            class="nav-link {{ request()->routeIs('admin.whatsapp-auto-reply.*') && !request()->routeIs('admin.whatsapp-auto-reply.analytics') ? 'active' : '' }}">
                            <i data-lucide="bot" style="color:#f59e0b"></i> <span>Auto-Reply Rules</span>
                        </a>
                        @endif
                    @endif
                    @if(can('whatsapp-analytics.read'))
                        @if(!($aiBotOn ?? false))
                        <a href="{{ route('admin.whatsapp-auto-reply.analytics') }}"
                            class="nav-link {{ request()->routeIs('admin.whatsapp-auto-reply.analytics') ? 'active' : '' }}">
                            <i data-lucide="bar-chart-3" style="color:#8b5cf6"></i> <span>Reply Analytics</span>
                        </a>
                        @endif
                    @endif

                </div>
            @endif
        @elseif(can('settings.manage'))
            {{-- Locked WhatsApp section — visible but locked for Package 1 admins --}}
            <div class="nav-section">
                <div class="nav-section-title" style="margin-top:15px;font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;opacity:0.8;">WhatsApp Bulk</div>
                <div class="nav-link" style="opacity:0.4;cursor:not-allowed;pointer-events:none;">
                    <i data-lucide="smartphone" style="color:#25D366"></i> <span>WhatsApp Connect</span>
                    <i data-lucide="lock" style="width:12px;height:12px;margin-left:auto;color:#f59e0b;"></i>
                </div>
                <a href="#" onclick="event.preventDefault();alert('This feature requires the Professional package or higher. Contact your platform administrator to upgrade.');" class="nav-link" style="opacity:0.5;">
                    <i data-lucide="sparkles" style="color:#f59e0b;width:14px;height:14px;"></i>
                    <span style="font-size:11px;color:#f59e0b;">Upgrade to unlock</span>
                </a>
            </div>
        @endif

        {{-- ═══ AI BOT — Catalogue=All packages, Chatflow+Token=Package 3 only ═══ --}}
        @if(can('settings.manage'))
            <div class="nav-section">
                <div class="nav-section-title"
                    style="margin-top: 15px; font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">
                    AI Bot</div>

                {{-- Catalogue Columns — Available in ALL packages --}}
                @if(hasFeature('catalogue_columns'))
                <a href="{{ route('admin.catalogue-columns.index') }}"
                    class="nav-link {{ request()->routeIs('admin.catalogue-columns.*') ? 'active' : '' }}">
                    <i data-lucide="columns" style="color:#06b6d4"></i> <span>Catalogue Columns</span>
                </a>
                @endif

                {{-- Chatflow Builder — Enterprise only --}}
                @if(hasFeature('chatflow'))
                <a href="{{ route('admin.chatflow.index') }}"
                    class="nav-link {{ request()->routeIs('admin.chatflow.*') ? 'active' : '' }}">
                    <i data-lucide="git-branch" style="color:#10b981"></i> <span>Chatflow Builder</span>
                </a>
                @else
                <div class="nav-link" style="opacity:0.4;cursor:not-allowed;pointer-events:none;">
                    <i data-lucide="git-branch" style="color:#10b981"></i> <span>Chatflow Builder</span>
                    <i data-lucide="lock" style="width:12px;height:12px;margin-left:auto;color:#f59e0b;"></i>
                </div>
                @endif

                {{-- Token Analytics — Enterprise only --}}
                @if(hasFeature('token_analytics'))
                <a href="{{ route('admin.ai-analytics.index') }}"
                    class="nav-link {{ request()->routeIs('admin.ai-analytics.index') ? 'active' : '' }}">
                    <i data-lucide="bar-chart-3" style="color:#f59e0b"></i> <span>Token Analytics</span>
                </a>
                @else
                <div class="nav-link" style="opacity:0.4;cursor:not-allowed;pointer-events:none;">
                    <i data-lucide="bar-chart-3" style="color:#f59e0b"></i> <span>Token Analytics</span>
                    <i data-lucide="lock" style="width:12px;height:12px;margin-left:auto;color:#f59e0b;"></i>
                </div>
                @endif

                {{-- Chat History — Enterprise only --}}
                @if(hasFeature('chat_history'))
                <a href="{{ route('admin.ai-analytics.chats') }}"
                    class="nav-link {{ request()->routeIs('admin.ai-analytics.chats') || request()->routeIs('admin.ai-analytics.chat-detail') ? 'active' : '' }}">
                    <i data-lucide="message-square" style="color:#8b5cf6"></i> <span>Chat History</span>
                </a>
                <a href="{{ route('admin.ai-analytics.tester') }}"
                    class="nav-link {{ request()->routeIs('admin.ai-analytics.tester') ? 'active' : '' }}">
                    <i data-lucide="stethoscope" style="color:#6366f1"></i> <span>AI Bot Tester</span>
                </a>
                <a href="{{ route('admin.ai-analytics.traces.index') }}"
                    class="nav-link {{ request()->routeIs('admin.ai-analytics.traces.*') ? 'active' : '' }}">
                    <i data-lucide="git-merge" style="color:#ec4899"></i> <span>Node Traces</span>
                </a>
                @endif
            </div>
        @endif

        {{-- ═══ CATALOG — Package 1+ ═══ --}}
        @if(can('products.read') || can('categories.read') || can('projects.global') || can('quotes.global'))
            <div class="nav-section">
                <div class="nav-section-title">Catalog</div>
                @if(can('products.read'))
                    <a href="{{ route('admin.products.index') }}"
                        class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                        <i data-lucide="package"></i> <span>Products</span>
                    </a>
                @endif

                @if(can('projects.global') || can('quotes.global'))
                    <a href="{{ route('admin.vendors.index') }}"
                        class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                        <i data-lucide="truck"></i> <span>Vendors</span>
                    </a>
                    <a href="{{ route('admin.purchases.index') }}"
                        class="nav-link {{ request()->routeIs('admin.purchases.*') ? 'active' : '' }}">
                        <i data-lucide="shopping-cart"></i> <span>Purchases</span>
                    </a>
                    <a href="{{ route('admin.purchase-payments.index') }}"
                        class="nav-link {{ request()->routeIs('admin.purchase-payments.*') ? 'active' : '' }}">
                        <i data-lucide="credit-card"></i> <span>Purchase Payments</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- ═══ PRODUCTION — Package 1+ ═══ --}}
        @if(can('projects.read') || can('tasks.read'))
            <div class="nav-section">
                <div class="nav-section-title">Production</div>
                @if(can('projects.read'))
                    <a href="{{ route('admin.projects.index') }}"
                        class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                        <i data-lucide="briefcase"></i> <span>Projects</span>
                    </a>
                @endif
                @if(can('tasks.read'))
                    <a href="{{ route('admin.tasks.index') }}"
                        class="nav-link {{ request()->routeIs('admin.tasks.*') ? 'active' : '' }}">
                        <i data-lucide="check-square"></i> <span>Tasks</span>
                    </a>
                @endif
                @if(can('tasks.read'))
                    <a href="{{ route('admin.micro-tasks.index') }}"
                        class="nav-link {{ request()->routeIs('admin.micro-tasks.*') ? 'active' : '' }}">
                        <i data-lucide="list-todo"></i> <span>Micro Tasks</span>
                    </a>
                @endif
                @if(can('tasks.read'))
                    <a href="{{ route('admin.task-followups.index') }}"
                        class="nav-link {{ request()->routeIs('admin.task-followups.*') ? 'active' : '' }}">
                        <i data-lucide="bell-ring"></i> <span>Micro Task Follow-ups</span>
                    </a>
                @endif
                @if(can('settings.manage'))
                    <a href="{{ route('admin.service-templates.index') }}"
                        class="nav-link {{ request()->routeIs('admin.service-templates.*') ? 'active' : '' }}">
                        <i data-lucide="clipboard-list"></i> <span>Service Templates</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- ═══ TEAM — Package 1+ ═══ --}}
        @if(can('users.read') || can('roles.read'))
            <div class="nav-section">
                <div class="nav-section-title">Team</div>
                @if(can('users.read'))
                    <a href="{{ route('admin.users.index') }}"
                        class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i data-lucide="users-2"></i> <span>Users</span>
                    </a>
                @endif
                @if(can('roles.read'))
                    <a href="{{ route('admin.roles.index') }}"
                        class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i data-lucide="shield"></i> <span>Roles</span>
                    </a>
                @endif
                @if(can('activities.read'))
                    <a href="{{ route('admin.activities.index') }}"
                        class="nav-link {{ request()->routeIs('admin.activities.*') ? 'active' : '' }}">
                        <i data-lucide="activity"></i> <span>Activities</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- ═══ ANALYTICS & SETTINGS — Package 1+ ═══ --}}
        <div class="nav-section">
            <div class="nav-section-title">Analytics</div>
            <a href="{{ route('admin.profile.index') }}"
                class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                <i data-lucide="user"></i> <span>My Profile</span>
            </a>
            @if(can('reports.read'))
                <a href="{{ route('admin.reports.index') }}"
                    class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i data-lucide="bar-chart-2"></i> <span>Reports</span>
                </a>
            @endif
            @if(can('settings.manage'))
                <a href="{{ route('admin.settings.index') }}"
                    class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i data-lucide="settings"></i> <span>Settings</span>
                </a>
            @endif
            @if(auth()->user()->user_type === 'admin')
                <a href="{{ route('admin.billing.index') }}"
                    class="nav-link {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
                    <i data-lucide="wallet" style="color:#f57c00;"></i> <span>Billing</span>
                </a>
            @endif
        </div>

        {{-- ═══ SUBSCRIPTION INFO — visible to admin users ═══ --}}
        @if(auth()->user()->user_type === 'admin')
        @php
            $mySub = auth()->user()->company?->activeSubscription();
            $myPkg = $mySub?->package;
        @endphp
        <div class="nav-section" style="margin-top:8px;border-top:1px solid rgba(255,255,255,0.08);padding-top:12px;">
            <div style="padding:8px 12px;background:rgba(249,115,22,0.1);border-radius:8px;margin:0 8px;">
                <div style="font-size:11px;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Your Plan</div>
                <div style="font-size:14px;font-weight:600;color:hsl(25 95% 53%);">{{ $myPkg->name ?? 'No Plan' }}</div>
                @if($mySub)
                <div style="font-size:11px;color:rgba(255,255,255,0.4);margin-top:2px;">
                    {{ $mySub->daysRemaining() }}d remaining · {{ auth()->user()->company->getActiveUserCount() }}/{{ $mySub->getMaxUsers() }} users
                </div>
                @endif
            </div>
        </div>
        @endif
    </nav>

</aside>