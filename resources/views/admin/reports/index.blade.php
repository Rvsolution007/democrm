@extends('admin.layouts.app')

@section('title', 'Reports')
@section('breadcrumb', 'Reports')
@section('has_charts', true)

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Reports & Analytics</h1>
                <p class="page-description">Business intelligence, insights & data exports</p>
            </div>
        </div>
    </div>

    <!-- Report Tabs Navigation -->
    <div class="report-tabs-nav" id="reportTabsNav">
        <button class="report-tab-btn active" data-tab="overview" onclick="switchReportTab('overview', this)">
            <i data-lucide="bar-chart-2" style="width:16px;height:16px"></i> Overview
        </button>
        <button class="report-tab-btn" data-tab="leads" onclick="switchReportTab('leads', this)">
            <i data-lucide="users" style="width:16px;height:16px"></i> Leads
        </button>
        <button class="report-tab-btn" data-tab="quotes" onclick="switchReportTab('quotes', this)">
            <i data-lucide="file-text" style="width:16px;height:16px"></i> Quotes & Revenue
        </button>
        <button class="report-tab-btn" data-tab="payments" onclick="switchReportTab('payments', this)">
            <i data-lucide="credit-card" style="width:16px;height:16px"></i> Payments
        </button>
        <button class="report-tab-btn" data-tab="projects" onclick="switchReportTab('projects', this)">
            <i data-lucide="briefcase" style="width:16px;height:16px"></i> Projects
        </button>
        <button class="report-tab-btn" data-tab="tasks" onclick="switchReportTab('tasks', this)">
            <i data-lucide="check-square" style="width:16px;height:16px"></i> Tasks
        </button>
        <button class="report-tab-btn" data-tab="team" onclick="switchReportTab('team', this)">
            <i data-lucide="users-2" style="width:16px;height:16px"></i> Team
        </button>
        <button class="report-tab-btn" data-tab="products" onclick="switchReportTab('products', this)">
            <i data-lucide="box" style="width:16px;height:16px"></i> Products
        </button>
        <button class="report-tab-btn" data-tab="vendors" onclick="switchReportTab('vendors', this)">
            <i data-lucide="truck" style="width:16px;height:16px"></i> Vendors
        </button>
    </div>

    <!-- ======================== TAB 1: OVERVIEW ======================== -->
    <div class="report-tab-content" id="tab-overview">
        <!-- KPI Stats -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(99,102,241,0.12);color:#6366f1"><i data-lucide="users" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Total Leads</div><div class="stats-card-value">{{ number_format($overview['total_leads']) }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981"><i data-lucide="user-check" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Total Clients</div><div class="stats-card-value">{{ number_format($overview['total_clients']) }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b"><i data-lucide="file-text" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Total Quotes</div><div class="stats-card-value">{{ number_format($overview['total_quotes']) }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(236,72,153,0.12);color:#ec4899"><i data-lucide="indian-rupee" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Total Revenue</div><div class="stats-card-value">₹{{ number_format($overview['total_revenue'], 0) }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(34,197,94,0.12);color:#22c55e"><i data-lucide="wallet" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Payments Received</div><div class="stats-card-value">₹{{ number_format($overview['total_payments'], 0) }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6"><i data-lucide="briefcase" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Open Projects</div><div class="stats-card-value">{{ $overview['open_projects'] }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(239,68,68,0.12);color:#ef4444"><i data-lucide="clock" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Pending Tasks</div><div class="stats-card-value">{{ $overview['pending_tasks'] }}</div></div></div>
            <div class="stats-card"><div class="stats-card-icon" style="background:rgba(168,85,247,0.12);color:#a855f7"><i data-lucide="trending-up" style="width:22px;height:22px"></i></div><div class="stats-card-content"><div class="stats-card-title">Conversion Rate</div><div class="stats-card-value">{{ $overview['conversion_rate'] }}%</div></div></div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Monthly Revenue Trend (Last 12 Months)</h3></div>
                <div class="card-content"><canvas id="overview-revenue-chart" height="300"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Lead Funnel</h3></div>
                <div class="card-content"><canvas id="overview-funnel-chart" height="300"></canvas></div>
            </div>
        </div>
    </div>

    <!-- ======================== TAB 2: LEADS ======================== -->
    <div class="report-tab-content" id="tab-leads" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip"><span class="report-stat-label">Total</span><span class="report-stat-val">{{ $leads['totalLeads'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#22c55e"><span class="report-stat-label">Won</span><span class="report-stat-val">{{ $leads['wonLeads'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#ef4444"><span class="report-stat-label">Lost</span><span class="report-stat-val">{{ $leads['lostLeads'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#3b82f6"><span class="report-stat-label">Open</span><span class="report-stat-val">{{ $leads['openLeads'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#a855f7"><span class="report-stat-label">Conversion</span><span class="report-stat-val">{{ $leads['leadsConversion'] }}%</span></div>
                <div class="report-stat-chip" style="--chip-color:#f59e0b"><span class="report-stat-label">Avg Aging</span><span class="report-stat-val">{{ $leads['leadAging'] }} days</span></div>
            </div>
            <a href="{{ route('admin.reports.export.leads') }}" class="btn btn-outline btn-sm"><i data-lucide="download" style="width:15px;height:15px"></i> Export Excel</a>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card"><div class="card-header"><h3 class="card-title">Leads by Source</h3></div><div class="card-content"><canvas id="leads-source-chart" height="300"></canvas></div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">Leads by Stage</h3></div><div class="card-content"><canvas id="leads-stage-chart" height="300"></canvas></div></div>
        </div>
        <div class="card mb-6">
            <div class="card-header"><h3 class="card-title">Daily Lead Trend (Last 30 Days)</h3></div>
            <div class="card-content"><canvas id="leads-trend-chart" height="200"></canvas></div>
        </div>
    </div>

    <!-- ======================== TAB 3: QUOTES & REVENUE ======================== -->
    <div class="report-tab-content" id="tab-quotes" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip"><span class="report-stat-label">Total Quotes</span><span class="report-stat-val">{{ $quotes['quotesTotal'] }}</span></div>
                @foreach($quotes['quotesByStatus'] as $status => $data)
                    <div class="report-stat-chip" style="--chip-color:{{ $status === 'accepted' ? '#22c55e' : ($status === 'rejected' ? '#ef4444' : ($status === 'sent' ? '#3b82f6' : ($status === 'expired' ? '#f59e0b' : '#6b7280'))) }}">
                        <span class="report-stat-label">{{ ucfirst($status) }}</span>
                        <span class="report-stat-val">{{ $data->count }} (₹{{ number_format($data->total / 100, 0) }})</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.reports.export.quotes') }}" class="btn btn-outline btn-sm"><i data-lucide="download" style="width:15px;height:15px"></i> Export Excel</a>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card"><div class="card-header"><h3 class="card-title">Quotes by Status</h3></div><div class="card-content"><canvas id="quotes-status-chart" height="300"></canvas></div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">Monthly Revenue (Current FY)</h3></div><div class="card-content"><canvas id="quotes-revenue-chart" height="300"></canvas></div></div>
        </div>

        <!-- Top 10 Quotes -->
        <div class="card mb-6">
            <div class="card-header"><h3 class="card-title">Top 10 Quotes by Value</h3></div>
            <div class="card-content" style="padding:0">
                <table class="data-table" style="width:100%">
                    <thead><tr><th>SR</th><th>Quote No</th><th>Client / Lead</th><th>Grand Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($quotes['topQuotes'] as $i => $q)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $q->quote_no }}</strong></td>
                                <td>{{ $q->client ? ($q->client->business_name ?: $q->client->contact_name) : ($q->lead->name ?? '-') }}</td>
                                <td><strong>₹{{ number_format($q->grand_total / 100, 0) }}</strong></td>
                                <td><span class="badge badge-{{ $q->status === 'accepted' ? 'success' : ($q->status === 'rejected' ? 'destructive' : ($q->status === 'sent' ? 'primary' : 'secondary')) }}">{{ ucfirst($q->status) }}</span></td>
                                <td>{{ $q->date ? $q->date->format('d M Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;padding:24px;color:#999">No quotes found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================== TAB 4: PAYMENTS ======================== -->
    <div class="report-tab-content" id="tab-payments" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip" style="--chip-color:#22c55e"><span class="report-stat-label">Total Collected</span><span class="report-stat-val">₹{{ number_format($payments['paymentsTotal'], 0) }}</span></div>
                @foreach($payments['paymentsByType'] as $type => $data)
                    <div class="report-stat-chip">
                        <span class="report-stat-label">{{ ucfirst(str_replace('_', ' ', $type ?? 'Other')) }}</span>
                        <span class="report-stat-val">₹{{ number_format($data->total / 100, 0) }} ({{ $data->count }})</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.reports.export.payments') }}" class="btn btn-outline btn-sm"><i data-lucide="download" style="width:15px;height:15px"></i> Export Excel</a>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card"><div class="card-header"><h3 class="card-title">Payments by Type</h3></div><div class="card-content"><canvas id="payments-type-chart" height="300"></canvas></div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">Monthly Collection (Last 12 Months)</h3></div><div class="card-content"><canvas id="payments-monthly-chart" height="300"></canvas></div></div>
        </div>

        <!-- Recent Payments Table -->
        <div class="card mb-6">
            <div class="card-header"><h3 class="card-title">Recent Payments</h3></div>
            <div class="card-content" style="padding:0">
                <table class="data-table" style="width:100%">
                    <thead><tr><th>SR</th><th>Quote No</th><th>Client</th><th>Amount</th><th>Type</th><th>Date</th><th>Recorded By</th></tr></thead>
                    <tbody>
                        @forelse($payments['recentPayments'] as $i => $p)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $p->quote->quote_no ?? '-' }}</td>
                                <td>{{ $p->quote && $p->quote->client ? ($p->quote->client->business_name ?: $p->quote->client->contact_name) : ($p->quote && $p->quote->lead ? $p->quote->lead->name : '-') }}</td>
                                <td><strong>₹{{ number_format($p->amount / 100, 0) }}</strong></td>
                                <td><span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $p->payment_type ?? '-')) }}</span></td>
                                <td>{{ $p->payment_date ? $p->payment_date->format('d M Y') : '-' }}</td>
                                <td>{{ $p->user->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align:center;padding:24px;color:#999">No payments found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================== TAB 5: PROJECTS ======================== -->
    <div class="report-tab-content" id="tab-projects" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip"><span class="report-stat-label">Total</span><span class="report-stat-val">{{ $projects['projectsTotal'] }}</span></div>
                @foreach($projects['projectsByStatus'] as $status => $count)
                    <div class="report-stat-chip" style="--chip-color:{{ $status === 'completed' ? '#22c55e' : ($status === 'in_progress' ? '#3b82f6' : ($status === 'on_hold' ? '#f59e0b' : ($status === 'cancelled' ? '#ef4444' : '#6b7280'))) }}">
                        <span class="report-stat-label">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                        <span class="report-stat-val">{{ $count }}</span>
                    </div>
                @endforeach
                <div class="report-stat-chip" style="--chip-color:#a855f7"><span class="report-stat-label">Completion</span><span class="report-stat-val">{{ $projects['projectCompletionRate'] }}%</span></div>
                <div class="report-stat-chip" style="--chip-color:#ec4899"><span class="report-stat-label">Total Budget</span><span class="report-stat-val">₹{{ number_format($projects['totalBudget'], 0) }}</span></div>
            </div>
            <a href="{{ route('admin.reports.export.projects') }}" class="btn btn-outline btn-sm"><i data-lucide="download" style="width:15px;height:15px"></i> Export Excel</a>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card"><div class="card-header"><h3 class="card-title">Projects by Status</h3></div><div class="card-content"><canvas id="projects-status-chart" height="300"></canvas></div></div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Overdue Projects</h3></div>
                <div class="card-content" style="padding:0">
                    <table class="data-table" style="width:100%">
                        <thead><tr><th>Project</th><th>Client</th><th>Due Date</th><th>Assigned To</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($projects['overdueProjects'] as $p)
                                <tr>
                                    <td><strong>{{ $p->name }}</strong></td>
                                    <td>{{ $p->client ? ($p->client->business_name ?: $p->client->contact_name) : '-' }}</td>
                                    <td style="color:#ef4444;font-weight:600">{{ $p->due_date->format('d M Y') }}</td>
                                    <td>{{ $p->assignedTo->name ?? 'Unassigned' }}</td>
                                    <td><span class="badge badge-warning">{{ ucfirst(str_replace('_', ' ', $p->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" style="text-align:center;padding:24px;color:#22c55e">🎉 No overdue projects!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================== TAB 6: TASKS ======================== -->
    <div class="report-tab-content" id="tab-tasks" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip"><span class="report-stat-label">Total</span><span class="report-stat-val">{{ $tasks['tasksTotal'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#22c55e"><span class="report-stat-label">Done</span><span class="report-stat-val">{{ $tasks['tasksDone'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#ef4444"><span class="report-stat-label">Overdue</span><span class="report-stat-val">{{ $tasks['tasksOverdue'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#f59e0b"><span class="report-stat-label">Pending</span><span class="report-stat-val">{{ $tasks['tasksPending'] }}</span></div>
                @foreach($tasks['tasksByPriority'] as $priority => $count)
                    <div class="report-stat-chip" style="--chip-color:{{ $priority === 'high' ? '#ef4444' : ($priority === 'medium' ? '#f59e0b' : '#6b7280') }}">
                        <span class="report-stat-label">{{ ucfirst($priority) }} Priority</span><span class="report-stat-val">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.reports.export.tasks') }}" class="btn btn-outline btn-sm"><i data-lucide="download" style="width:15px;height:15px"></i> Export Excel</a>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card"><div class="card-header"><h3 class="card-title">Tasks by Status</h3></div><div class="card-content"><canvas id="tasks-status-chart" height="300"></canvas></div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">Tasks by Priority</h3></div><div class="card-content"><canvas id="tasks-priority-chart" height="300"></canvas></div></div>
        </div>

        <!-- Overdue Tasks -->
        <div class="card mb-6">
            <div class="card-header"><h3 class="card-title">Overdue Tasks</h3></div>
            <div class="card-content" style="padding:0">
                <table class="data-table" style="width:100%">
                    <thead><tr><th>Task</th><th>Project</th><th>Due Date</th><th>Assigned To</th><th>Priority</th></tr></thead>
                    <tbody>
                        @forelse($tasks['overdueTasks'] as $t)
                            <tr>
                                <td><strong>{{ $t->title }}</strong></td>
                                <td>{{ $t->project->name ?? '-' }}</td>
                                <td style="color:#ef4444;font-weight:600">{{ $t->due_at->format('d M Y') }}</td>
                                <td>{{ $t->assignedTo->name ?? 'Unassigned' }}</td>
                                <td><span class="badge badge-{{ $t->priority === 'high' ? 'destructive' : ($t->priority === 'medium' ? 'warning' : 'secondary') }}">{{ ucfirst($t->priority) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:#22c55e">🎉 No overdue tasks!</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ======================== TAB 7: TEAM PERFORMANCE ======================== -->
    <div class="report-tab-content" id="tab-team" style="display:none">
        <div class="report-section-header">
            <div><h3 style="font-size:16px;font-weight:700;margin:0">Team Performance Overview</h3></div>
            <a href="{{ route('admin.reports.export.team') }}" class="btn btn-outline btn-sm"><i data-lucide="download" style="width:15px;height:15px"></i> Export Excel</a>
        </div>

        <div class="card mb-6">
            <div class="card-content" style="padding:0">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Team Member</th>
                            <th style="text-align:center">Leads Assigned</th>
                            <th style="text-align:center">Leads Won</th>
                            <th style="text-align:center">Win Rate</th>
                            <th style="text-align:center">Tasks</th>
                            <th style="text-align:center">Tasks Done</th>
                            <th style="text-align:center">Activities</th>
                            <th style="text-align:center">Quotes</th>
                            <th style="text-align:right">Revenue (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($team['teamData'] as $member)
                            <tr>
                                <td><strong>{{ $member['name'] }}</strong></td>
                                <td style="text-align:center">{{ $member['leads_assigned'] }}</td>
                                <td style="text-align:center"><span style="color:#22c55e;font-weight:600">{{ $member['leads_won'] }}</span></td>
                                <td style="text-align:center">{{ $member['leads_assigned'] > 0 ? round(($member['leads_won'] / $member['leads_assigned']) * 100, 1) : 0 }}%</td>
                                <td style="text-align:center">{{ $member['tasks_assigned'] }}</td>
                                <td style="text-align:center"><span style="color:#22c55e;font-weight:600">{{ $member['tasks_completed'] }}</span></td>
                                <td style="text-align:center">{{ $member['activities_logged'] }}</td>
                                <td style="text-align:center">{{ $member['quotes_created'] }}</td>
                                <td style="text-align:right;font-weight:600">₹{{ number_format($member['revenue_generated'], 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" style="text-align:center;padding:24px;color:#999">No team data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Performers Chart -->
        <div class="card mb-6">
            <div class="card-header"><h3 class="card-title">Top Performers — Revenue Generated</h3></div>
            <div class="card-content"><canvas id="team-revenue-chart" height="280"></canvas></div>
        </div>
    </div>

    <!-- ======================== TAB 8: PRODUCTS ======================== -->
    <div class="report-tab-content" id="tab-products" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip"><span class="report-stat-label">Total Products</span><span class="report-stat-val">{{ $products['productsTotal'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#22c55e"><span class="report-stat-label">Active</span><span class="report-stat-val">{{ $products['productsActive'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#ef4444"><span class="report-stat-label">Low Stock</span><span class="report-stat-val">{{ $products['lowStockProducts'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#6366f1"><span class="report-stat-label">Inventory Value</span><span class="report-stat-val">₹{{ number_format($products['inventoryValue'], 0) }}</span></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top Selling Products</h3></div>
                <div class="card-content" style="padding:0">
                    <table class="data-table" style="width:100%">
                        <thead><tr><th>Product Name</th><th style="text-align:center">Qty Sold</th><th style="text-align:right">Revenue (₹)</th></tr></thead>
                        <tbody>
                            @forelse($products['topProducts'] as $tp)
                                <tr>
                                    <td><strong>{{ $tp->product_name }}</strong></td>
                                    <td style="text-align:center">{{ $tp->qty_sold }}</td>
                                    <td style="text-align:right;font-weight:600">₹{{ number_format($tp->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" style="text-align:center;padding:24px;color:#999">No product sales data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card"><div class="card-header"><h3 class="card-title">Top Products by Revenue</h3></div><div class="card-content"><canvas id="products-revenue-chart" height="300"></canvas></div></div>
        </div>
    </div>

    <!-- ======================== TAB 9: VENDORS ======================== -->
    <div class="report-tab-content" id="tab-vendors" style="display:none">
        <div class="report-section-header">
            <div class="report-stats-row">
                <div class="report-stat-chip"><span class="report-stat-label">Total Vendors</span><span class="report-stat-val">{{ $vendors['vendorsTotal'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#22c55e"><span class="report-stat-label">Active</span><span class="report-stat-val">{{ $vendors['vendorsActive'] }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#3b82f6"><span class="report-stat-label">Total Purchases</span><span class="report-stat-val">₹{{ number_format($vendors['purchasesTotal'], 0) }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#10b981"><span class="report-stat-label">Total Paid</span><span class="report-stat-val">₹{{ number_format($vendors['purchasesPaid'], 0) }}</span></div>
                <div class="report-stat-chip" style="--chip-color:#ef4444"><span class="report-stat-label">Total Due</span><span class="report-stat-val">₹{{ number_format($vendors['purchasesDue'], 0) }}</span></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top Vendors by Purchase Volume</h3></div>
                <div class="card-content" style="padding:0">
                    <table class="data-table" style="width:100%">
                        <thead><tr><th>Vendor Name</th><th style="text-align:center">Purchases</th><th style="text-align:right">Total Amount (₹)</th><th style="text-align:right">Due (₹)</th></tr></thead>
                        <tbody>
                            @forelse($vendors['topVendors'] as $tv)
                                <tr>
                                    <td><strong>{{ $tv->name }}</strong></td>
                                    <td style="text-align:center">{{ $tv->purchases_count }}</td>
                                    <td style="text-align:right;font-weight:600">₹{{ number_format($tv->purchases_sum_total_amount / 100, 2) }}</td>
                                    <td style="text-align:right;color:#ef4444">₹{{ number_format(($tv->purchases_sum_total_amount - $tv->purchases_sum_paid_amount) / 100, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" style="text-align:center;padding:24px;color:#999">No vendors data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card"><div class="card-header"><h3 class="card-title">Top Vendors by Volume</h3></div><div class="card-content"><canvas id="vendors-volume-chart" height="300"></canvas></div></div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Report Tabs Navigation */
        .report-tabs-nav {
            display: flex;
            gap: 4px;
            padding: 6px;
            background: #f1f5f9;
            border-radius: 12px;
            margin-bottom: 24px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .report-tab-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 10px 18px;
            border: none;
            background: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .report-tab-btn:hover {
            background: #e2e8f0;
            color: #334155;
        }
        .report-tab-btn.active {
            background: #fff;
            color: #1e293b;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        /* Section Header with Export */
        .report-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .report-stats-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .report-stat-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            border-left: 3px solid var(--chip-color, #6366f1);
        }
        .report-stat-label {
            color: #64748b;
            font-weight: 500;
        }
        .report-stat-val {
            color: #1e293b;
            font-weight: 700;
        }

        /* Data Table */
        .data-table {
            border-collapse: collapse;
        }
        .data-table thead th {
            background: #f8fafc;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .data-table tbody td {
            padding: 12px 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table tbody tr:hover {
            background: #f8fafc;
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Grid helpers */
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-cols-4 { grid-template-columns: repeat(4, 1fr); }
        .gap-4 { gap: 16px; }
        .gap-6 { gap: 24px; }
        .mb-6 { margin-bottom: 24px; }

        @media (max-width: 1024px) {
            .grid-cols-4 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .grid-cols-2, .grid-cols-4 { grid-template-columns: 1fr; }
            .report-tabs-nav { flex-wrap: nowrap; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ===== Tab Switching =====
        function switchReportTab(tabName, btn) {
            document.querySelectorAll('.report-tab-content').forEach(t => t.style.display = 'none');
            document.querySelectorAll('.report-tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tabName).style.display = 'block';
            btn.classList.add('active');
            // Initialize charts on first show
            if (!window['__charts_init_' + tabName]) {
                window['__charts_init_' + tabName] = true;
                if (typeof window['initCharts_' + tabName] === 'function') {
                    window['initCharts_' + tabName]();
                }
            }
        }

        // ===== Chart Color Palettes =====
        const COLORS = ['#6366f1','#22c55e','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#a855f7','#f97316','#06b6d4'];
        const STAGE_COLORS = { new:'#3b82f6', contacted:'#14b8a6', qualified:'#22c55e', proposal:'#f59e0b', negotiation:'#a855f7', won:'#10b981', lost:'#ef4444' };

        // ===== Chart Defaults =====
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;

        // ===== Helper: create chart =====
        function makeChart(id, type, labels, datasets, opts = {}) {
            const ctx = document.getElementById(id);
            if (!ctx) return null;
            return new Chart(ctx, {
                type,
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: opts.legend !== false, position: opts.legendPos || 'top' },
                        ...opts.plugins
                    },
                    scales: opts.scales || (type === 'bar' || type === 'line' ? {
                        y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                        x: { ticks: { font: { size: 11 }, maxRotation: 45 } }
                    } : undefined),
                    ...opts.extra
                }
            });
        }

        // ===== OVERVIEW CHARTS (Init immediately) =====
        document.addEventListener('DOMContentLoaded', () => {
            window.__charts_init_overview = true;

            // Monthly Revenue Bar
            makeChart('overview-revenue-chart', 'bar',
                @json($monthLabels),
                [{ label: 'Revenue (₹)', data: @json($monthlyRevenue), backgroundColor: '#6366f1', borderRadius: 6, barPercentage: 0.6 }]
            );

            // Lead Funnel
            const stageData = @json($leadsByStage);
            const stages = @json($leadStages);
            const funnelLabels = stages.map(s => s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g,' '));
            const funnelValues = stages.map(s => stageData[s] || 0);
            const funnelColors = stages.map(s => STAGE_COLORS[s] || '#6b7280');
            makeChart('overview-funnel-chart', 'bar', funnelLabels,
                [{ label: 'Leads', data: funnelValues, backgroundColor: funnelColors, borderRadius: 6, barPercentage: 0.6 }],
                { extra: { indexAxis: 'y' } }
            );

            lucide.createIcons();
        });

        // ===== LEADS CHARTS =====
        window.initCharts_leads = function() {
            // By Source (Doughnut)
            const srcData = @json($leads['leadSources']);
            const srcLabels = Object.keys(srcData).map(s => s.charAt(0).toUpperCase() + s.slice(1).replace(/-/g,' '));
            makeChart('leads-source-chart', 'doughnut', srcLabels,
                [{ data: Object.values(srcData), backgroundColor: COLORS.slice(0, srcLabels.length) }],
                { legendPos: 'right' }
            );

            // By Stage (Bar)
            const stData = @json($leadsByStage);
            const stLabels = @json($leadStages);
            makeChart('leads-stage-chart', 'bar',
                stLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g,' ')),
                [{ label: 'Leads', data: stLabels.map(s => stData[s] || 0), backgroundColor: stLabels.map(s => STAGE_COLORS[s] || '#6b7280'), borderRadius: 6 }],
                { legend: false }
            );

            // Daily Trend (Line)
            const trendData = @json($leads['leadDailyTrend']);
            const trendDates = Object.keys(trendData);
            makeChart('leads-trend-chart', 'line', trendDates.map(d => { const dt = new Date(d); return dt.getDate() + ' ' + dt.toLocaleString('en', {month:'short'}); }),
                [{ label: 'New Leads', data: Object.values(trendData), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', fill: true, tension: 0.3, pointRadius: 3 }]
            );
            lucide.createIcons();
        };

        // ===== QUOTES CHARTS =====
        window.initCharts_quotes = function() {
            const qsData = @json($quotes['quotesByStatus']);
            const qsLabels = [], qsValues = [], qsColors = [];
            const statusColorMap = { draft:'#6b7280', sent:'#3b82f6', accepted:'#22c55e', rejected:'#ef4444', expired:'#f59e0b' };
            for (const [k, v] of Object.entries(qsData)) {
                qsLabels.push(k.charAt(0).toUpperCase() + k.slice(1));
                qsValues.push(v.count);
                qsColors.push(statusColorMap[k] || '#6b7280');
            }
            makeChart('quotes-status-chart', 'doughnut', qsLabels,
                [{ data: qsValues, backgroundColor: qsColors }], { legendPos: 'right' }
            );

            makeChart('quotes-revenue-chart', 'bar',
                @json($quotes['fyMonthLabels']),
                [{ label: 'Revenue (₹)', data: @json($quotes['fyMonthlyRevenue']), backgroundColor: '#22c55e', borderRadius: 6, barPercentage: 0.6 }]
            );
            lucide.createIcons();
        };

        // ===== PAYMENTS CHARTS =====
        window.initCharts_payments = function() {
            const ptData = @json($payments['paymentsByType']);
            const ptLabels = [], ptValues = [], ptColors = [];
            const typeColorMap = { cash:'#22c55e', online:'#3b82f6', cheque:'#f59e0b', upi:'#a855f7', bank_transfer:'#14b8a6' };
            for (const [k, v] of Object.entries(ptData)) {
                ptLabels.push(k ? k.charAt(0).toUpperCase() + k.slice(1).replace(/_/g,' ') : 'Other');
                ptValues.push(v.total / 100);
                ptColors.push(typeColorMap[k] || '#6b7280');
            }
            makeChart('payments-type-chart', 'doughnut', ptLabels,
                [{ data: ptValues, backgroundColor: ptColors }], { legendPos: 'right' }
            );

            makeChart('payments-monthly-chart', 'bar',
                @json($payments['paymentMonthLabels']),
                [{ label: 'Collected (₹)', data: @json($payments['monthlyPayments']), backgroundColor: '#14b8a6', borderRadius: 6, barPercentage: 0.6 }]
            );
            lucide.createIcons();
        };

        // ===== PROJECTS CHARTS =====
        window.initCharts_projects = function() {
            const psData = @json($projects['projectsByStatus']);
            const psLabels = [], psValues = [], psColors = [];
            const psColorMap = { pending:'#6b7280', in_progress:'#3b82f6', completed:'#22c55e', on_hold:'#f59e0b', cancelled:'#ef4444' };
            for (const [k, v] of Object.entries(psData)) {
                psLabels.push(k.charAt(0).toUpperCase() + k.slice(1).replace(/_/g,' '));
                psValues.push(v);
                psColors.push(psColorMap[k] || '#6b7280');
            }
            makeChart('projects-status-chart', 'doughnut', psLabels,
                [{ data: psValues, backgroundColor: psColors }], { legendPos: 'right' }
            );
            lucide.createIcons();
        };

        // ===== TASKS CHARTS =====
        window.initCharts_tasks = function() {
            const tsData = @json($tasks['tasksByStatus']);
            const tsLabels = [], tsValues = [], tsColors = [];
            const tsColorMap = { todo:'#6b7280', doing:'#3b82f6', done:'#22c55e' };
            for (const [k, v] of Object.entries(tsData)) {
                tsLabels.push(k.charAt(0).toUpperCase() + k.slice(1));
                tsValues.push(v);
                tsColors.push(tsColorMap[k] || '#6b7280');
            }
            makeChart('tasks-status-chart', 'doughnut', tsLabels,
                [{ data: tsValues, backgroundColor: tsColors }], { legendPos: 'right' }
            );

            const tpData = @json($tasks['tasksByPriority']);
            const tpColorMap = { low:'#6b7280', medium:'#f59e0b', high:'#ef4444' };
            makeChart('tasks-priority-chart', 'bar',
                Object.keys(tpData).map(k => k.charAt(0).toUpperCase() + k.slice(1)),
                [{ label: 'Tasks', data: Object.values(tpData), backgroundColor: Object.keys(tpData).map(k => tpColorMap[k] || '#6b7280'), borderRadius: 6, barPercentage: 0.5 }],
                { legend: false }
            );
            lucide.createIcons();
        };

        // ===== TEAM CHARTS =====
        window.initCharts_team = function() {
            const teamData = @json($team['teamData']);
            const sorted = [...teamData].sort((a, b) => b.revenue_generated - a.revenue_generated).slice(0, 10);
            makeChart('team-revenue-chart', 'bar',
                sorted.map(m => m.name),
                [{ label: 'Revenue (₹)', data: sorted.map(m => m.revenue_generated), backgroundColor: '#6366f1', borderRadius: 6, barPercentage: 0.5 }]
            );
            lucide.createIcons();
        };

        // ===== PRODUCTS CHARTS =====
        window.initCharts_products = function() {
            const topProducts = @json($products['topProducts']);
            makeChart('products-revenue-chart', 'bar',
                topProducts.map(p => p.product_name || 'Unknown Item'),
                [{ label: 'Revenue (₹)', data: topProducts.map(p => p.revenue), backgroundColor: '#10b981', borderRadius: 6, barPercentage: 0.5 }]
            );
            lucide.createIcons();
        };

        // ===== VENDORS CHARTS =====
        window.initCharts_vendors = function() {
            const topVendors = @json($vendors['topVendors']);
            makeChart('vendors-volume-chart', 'bar',
                topVendors.map(v => v.name || 'Unknown Vendor'),
                [{ label: 'Volume (₹)', data: topVendors.map(v => (v.purchases_sum_total_amount || 0) / 100), backgroundColor: '#f59e0b', borderRadius: 6, barPercentage: 0.5 }]
            );
            lucide.createIcons();
        };
    </script>
@endpush