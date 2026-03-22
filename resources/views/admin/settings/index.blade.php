@extends('admin.layouts.app')

@section('title', 'Settings')
@section('breadcrumb', 'Settings')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Settings</h1>
                <p class="page-description">Manage your account and application settings</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:260px 1fr;gap:24px">
        <!-- Settings Sidebar -->
        <div>
            <div class="card" style="position:sticky;top:20px">
                <div class="card-content" style="padding:12px">
                    <!-- Main Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            Main</div>
                        <button class="settings-nav-btn active" data-tab="company"
                            onclick="switchSettingsTab('company', this)">
                            <i data-lucide="building" style="width:16px;height:16px"></i> Company Info
                        </button>
                        <button class="settings-nav-btn" data-tab="leads-columns"
                            onclick="switchSettingsTab('leads-columns', this)">
                            <i data-lucide="users" style="width:16px;height:16px"></i> Leads
                        </button>
                        <button class="settings-nav-btn" data-tab="clients-columns"
                            onclick="switchSettingsTab('clients-columns', this)">
                            <i data-lucide="user-circle" style="width:16px;height:16px"></i> Clients
                        </button>
                        <button class="settings-nav-btn" data-tab="quotes-columns"
                            onclick="switchSettingsTab('quotes-columns', this)">
                            <i data-lucide="file-text" style="width:16px;height:16px"></i> Quotes
                        </button>
                    </div>

                    <!-- Catalog Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            Catalog</div>
                        <button class="settings-nav-btn" data-tab="products-columns"
                            onclick="switchSettingsTab('products-columns', this)">
                            <i data-lucide="package" style="width:16px;height:16px"></i> Products
                        </button>
                        <button class="settings-nav-btn" data-tab="categories-columns"
                            onclick="switchSettingsTab('categories-columns', this)">
                            <i data-lucide="folder" style="width:16px;height:16px"></i> Categories
                        </button>
                    </div>

                    <!-- Payments Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            Payments</div>
                        <button class="settings-nav-btn" data-tab="payments" onclick="switchSettingsTab('payments', this)">
                            <i data-lucide="credit-card" style="width:16px;height:16px"></i> Payment Types
                        </button>
                    </div>

                    <!-- Team Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            Team</div>
                        <button class="settings-nav-btn" data-tab="users-columns"
                            onclick="switchSettingsTab('users-columns', this)">
                            <i data-lucide="users-2" style="width:16px;height:16px"></i> Users
                        </button>
                        <button class="settings-nav-btn" data-tab="tasks-columns"
                            onclick="switchSettingsTab('tasks-columns', this)">
                            <i data-lucide="check-square" style="width:16px;height:16px"></i> Tasks
                        </button>
                    </div>

                    <!-- Analytics Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            Analytics</div>
                        <button class="settings-nav-btn" data-tab="profile" onclick="switchSettingsTab('profile', this)">
                            <i data-lucide="user" style="width:16px;height:16px"></i> Profile
                        </button>
                        <button class="settings-nav-btn" data-tab="notifications"
                            onclick="switchSettingsTab('notifications', this)">
                            <i data-lucide="bell" style="width:16px;height:16px"></i> Notifications
                        </button>
                        <button class="settings-nav-btn" data-tab="integrations"
                            onclick="switchSettingsTab('integrations', this)">
                            <i data-lucide="plug" style="width:16px;height:16px"></i> Integrations
                        </button>
                    </div>

                    <!-- Backup & Restore Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            Data</div>
                        <button class="settings-nav-btn" data-tab="backup-restore"
                            onclick="switchSettingsTab('backup-restore', this)">
                            <i data-lucide="database" style="width:16px;height:16px"></i> Backup & Restore
                        </button>
                    </div>

                    <!-- WhatsApp Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            WhatsApp</div>
                        <button class="settings-nav-btn" data-tab="whatsapp-api"
                            onclick="switchSettingsTab('whatsapp-api', this)">
                            <i data-lucide="message-circle" style="width:16px;height:16px"></i> WhatsApp API
                        </button>
                    </div>

                    <!-- AI Bot Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#999;padding:8px 12px">
                            AI Bot</div>
                        <button class="settings-nav-btn" data-tab="ai-bot"
                            onclick="switchSettingsTab('ai-bot', this)">
                            <i data-lucide="brain" style="width:16px;height:16px"></i> AI Bot Config
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div>
            <!-- Company Info Tab -->
            <div class="settings-tab" id="tab-company">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Company Information</h3>
                    </div>
                    <div class="card-content">
                        <form method="POST" action="{{ route('admin.settings.company.update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Logo Upload --}}
                            <div class="form-group" style="margin-bottom:20px">
                                <label class="form-label">Company Logo</label>
                                <div style="display:flex;align-items:center;gap:20px">
                                    @if($company && $company->logo)
                                        <div style="width:100px;height:100px;border:2px solid #e2e8f0;border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f8fafc;">
                                            <img src="{{ asset('storage/' . $company->logo) }}" alt="Company Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                                        </div>
                                    @else
                                        <div style="width:100px;height:100px;border:2px dashed #cbd5e1;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#94a3b8;font-size:12px;text-align:center;padding:8px;">
                                            No logo
                                        </div>
                                    @endif
                                    <div>
                                        <input type="file" name="logo" id="company-logo-input" accept="image/*" style="display:none" onchange="previewLogo(this)">
                                        <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('company-logo-input').click()">
                                            <i data-lucide="upload" style="width:14px;height:14px"></i> {{ $company && $company->logo ? 'Change Logo' : 'Upload Logo' }}
                                        </button>
                                        <p style="font-size:12px;color:#94a3b8;margin-top:6px">JPEG, PNG, GIF — max 2MB</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-input" name="name" value="{{ $company->name ?? 'RV CRM' }}">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">GST Number</label>
                                    <input type="text" class="form-input" name="gstin" value="{{ $company->gstin ?? '' }}">
                                </div>
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">Phone</label>
                                    <input type="tel" class="form-input" name="phone" value="{{ $company->phone ?? '' }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea class="form-textarea" name="address"
                                    rows="3">{{ is_array($company->address ?? null) ? implode(', ', array_filter($company->address)) : ($company->address ?? '') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Leads Column Visibility Tab -->
            <div class="settings-tab" id="tab-leads-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Leads — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which columns are visible on the
                                Leads page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('leads')">Reset to Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="leads" data-column="name"
                                    checked disabled> <span>Lead Name</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="leads" data-column="source"
                                    checked> <span>Source</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="leads" data-column="stage"
                                    checked> <span>Stage</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="leads" data-column="assigned"
                                    checked> <span>Assigned To</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="leads" data-column="location"
                                    checked> <span>Location</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="leads" data-column="actions"
                                    checked disabled> <span>Actions</span> <span
                                    class="badge badge-secondary">Required</span></label>
                        </div>
                    </div>
                </div>

                <!-- Lead Stages UI -->
                <div class="card" style="margin-top:24px">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Leads — Stages</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Manage custom stages for your leads</p>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="addStageRow('lead')">+ Add Stage</button>
                    </div>
                    <div class="card-content">
                        <div id="lead-stages-container"
                            style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
                            <!-- Populated dynamically -->
                        </div>
                        <button class="btn btn-primary" onclick="saveLeadStages(event)">Save Stages</button>
                    </div>
                </div>

                <!-- Lead Sources UI -->
                <div class="card" style="margin-top:24px">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Leads — Sources</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Manage custom sources for your leads (e.g.
                                Walk-in, Facebook)</p>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="addStageRow('source')">+ Add Source</button>
                    </div>
                    <div class="card-content">
                        <div id="source-stages-container"
                            style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
                            <!-- Populated dynamically -->
                        </div>
                        <button class="btn btn-primary" onclick="saveLeadSources(event)">Save Sources</button>
                    </div>
                </div>
            </div>

            <!-- Clients Column Visibility Tab -->
            <div class="settings-tab" id="tab-clients-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Clients — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which columns are visible on the
                                Clients page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('clients')">Reset to Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="clients" data-column="name"
                                    checked disabled> <span>Client Name</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="clients" data-column="gst"
                                    checked> <span>GST Number</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="clients" data-column="location"
                                    checked> <span>Location</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="clients"
                                    data-column="credit_limit" checked> <span>Credit Limit</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="clients"
                                    data-column="outstanding" checked> <span>Outstanding</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="clients" data-column="actions"
                                    checked disabled> <span>Actions</span> <span
                                    class="badge badge-secondary">Required</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quotes Column Visibility Tab -->
            <div class="settings-tab" id="tab-quotes-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Quotes — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which columns are visible on the
                                Quotes page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('quotes')">Reset to Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="quotes"
                                    data-column="quote_number" checked disabled> <span>Quote Number</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="quotes" data-column="client"
                                    checked> <span>Client</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="quotes" data-column="date"
                                    checked> <span>Date</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="quotes" data-column="amount"
                                    checked> <span>Amount</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="quotes" data-column="status"
                                    checked> <span>Status</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="quotes" data-column="actions"
                                    checked disabled> <span>Actions</span> <span
                                    class="badge badge-secondary">Required</span></label>
                        </div>
                    </div>
                </div>

                <!-- Quotes Taxes UI -->
                <div class="card" style="margin-top:24px">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Quotes — Tax Options</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Manage tax rates that can be applied to
                                quotes</p>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="addTaxRow()">+ Add Tax</button>
                    </div>
                    <div class="card-content">
                        <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
                            <thead>
                                <tr style="background:#f8f9fa;text-align:left">
                                    <th
                                        style="padding:10px 14px;border-bottom:1px solid #eee;font-size:13px;font-weight:600">
                                        Tax Name (e.g. GST 18%)</th>
                                    <th
                                        style="padding:10px 14px;border-bottom:1px solid #eee;font-size:13px;font-weight:600;width:150px">
                                        Rate (%)</th>
                                    <th style="padding:10px 14px;border-bottom:1px solid #eee;width:60px"></th>
                                </tr>
                            </thead>
                            <tbody id="taxes-tbody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                        <button class="btn btn-primary" onclick="saveTaxes()">Save Taxes</button>
                    </div>
                </div>
            </div>

            <!-- Products Column Visibility Tab -->
            <div class="settings-tab" id="tab-products-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Products — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which columns are visible on the
                                Products page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('products')">Reset to Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="name"
                                    checked disabled> <span>Product Name</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="sku"
                                    checked> <span>SKU</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products"
                                    data-column="category" checked> <span>Category</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="mrp"
                                    checked> <span>MRP (₹)</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products"
                                    data-column="sale_price" checked> <span>Sale Price (₹)</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="unit"
                                    checked> <span>Unit</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="gst"
                                    checked> <span>GST %</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="status"
                                    checked> <span>Status</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="products" data-column="actions"
                                    checked disabled> <span>Actions</span> <span
                                    class="badge badge-secondary">Required</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Column Visibility Tab -->
            <div class="settings-tab" id="tab-categories-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Categories — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which fields are visible on the
                                Categories page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('categories')">Reset to
                            Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="categories" data-column="name"
                                    checked disabled> <span>Category Name</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="categories"
                                    data-column="products_count" checked> <span>Products Count</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="categories"
                                    data-column="description" checked> <span>Description</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="categories"
                                    data-column="status" checked> <span>Status</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="categories"
                                    data-column="actions" checked disabled> <span>Actions</span> <span
                                    class="badge badge-secondary">Required</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Column Visibility Tab -->
            <div class="settings-tab" id="tab-users-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Users — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which columns are visible on the
                                Users page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('users')">Reset to Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="users" data-column="name"
                                    checked disabled> <span>User Name</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="users" data-column="email"
                                    checked> <span>Email</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="users" data-column="phone"
                                    checked> <span>Phone</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="users" data-column="role"
                                    checked> <span>Role</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="users" data-column="status"
                                    checked> <span>Status</span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks Column Visibility Tab -->
            <div class="settings-tab" id="tab-tasks-columns" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Tasks — Column Visibility</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Toggle which columns are visible on the
                                Tasks page</p>
                        </div>
                        <button class="btn btn-outline btn-sm" onclick="resetColumns('tasks')">Reset to Default</button>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" data-module="tasks" data-column="title"
                                    checked disabled> <span>Task Title</span> <span
                                    class="badge badge-secondary">Required</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="tasks"
                                    data-column="assigned_to" checked> <span>Assigned To</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="tasks" data-column="due_date"
                                    checked> <span>Due Date</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="tasks" data-column="priority"
                                    checked> <span>Priority</span></label>
                            <label class="column-toggle"><input type="checkbox" data-module="tasks" data-column="status"
                                    checked> <span>Status</span></label>
                        </div>
                    </div>
                </div>

                <!-- Task Statuses UI -->
                <div class="card" style="margin-top:24px">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Tasks — Statuses</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Manage custom statuses for your tasks</p>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="addStageRow('task')">+ Add Status</button>
                    </div>
                    <div class="card-content">
                        <div id="task-statuses-container"
                            style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
                            <!-- Populated dynamically -->
                        </div>
                        <button class="btn btn-primary" onclick="saveTaskStatuses(event)">Save Statuses</button>
                    </div>
                </div>
            </div>

            <!-- Payment Types Tab -->
            <div class="settings-tab" id="tab-payments" style="display:none">
                <div class="card">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title">Payments — Unified Types</h3>
                            <p class="text-sm text-muted" style="margin-top:4px">Manage custom payment types for both sales
                                and purchases</p>
                        </div>
                        <button class="btn btn-primary btn-sm" onclick="addStageRow('payment')">+ Add Type</button>
                    </div>
                    <div class="card-content">
                        <div id="payment-types-container"
                            style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px">
                            <!-- Populated dynamically -->
                        </div>
                        <button class="btn btn-primary" onclick="savePaymentTypes(event)">Save Types</button>
                    </div>
                </div>
            </div>

            <!-- Profile Tab -->
            <div class="settings-tab" id="tab-profile" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Profile Settings</h3>
                    </div>
                    <div class="card-content">
                        <!-- Profile Info Form -->
                        <form method="POST" action="{{ route('admin.profile.update') }}">
                            @csrf
                            @method('PUT')
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">Full Name</label>
                                    <input type="text" class="form-input" name="name" value="{{ auth()->user()->name }}"
                                        required>
                                </div>
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">Email</label>
                                    <input type="email" class="form-input" name="email" value="{{ auth()->user()->email }}"
                                        required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-input" name="phone" value="{{ auth()->user()->phone }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </form>
                        <hr style="margin:20px 0">
                        <!-- Password Change Form -->
                        <h4 style="font-weight:600;margin-bottom:12px">Change Password</h4>
                        <form method="POST" action="{{ route('admin.profile.password') }}">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-input" name="current_password" required>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">New Password</label>
                                    <input type="password" class="form-input" name="password" required minlength="6">
                                </div>
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">Confirm Password</label>
                                    <input type="password" class="form-input" name="password_confirmation" required
                                        minlength="6">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Notifications Tab -->
            <div class="settings-tab" id="tab-notifications" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Notification Preferences</h3>
                    </div>
                    <div class="card-content">
                        <div class="column-visibility-list">
                            <label class="column-toggle"><input type="checkbox" checked> <span>Email notifications for new
                                    leads</span></label>
                            <label class="column-toggle"><input type="checkbox" checked> <span>Email notifications for quote
                                    updates</span></label>
                            <label class="column-toggle"><input type="checkbox"> <span>Daily summary email</span></label>
                            <label class="column-toggle"><input type="checkbox" checked> <span>Browser
                                    notifications</span></label>
                        </div>
                        <button class="btn btn-primary" style="margin-top:16px">Save Preferences</button>
                    </div>
                </div>
            </div>

            <!-- Integrations Tab -->
            <div class="settings-tab" id="tab-integrations" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Integrations</h3>
                    </div>
                    <div class="card-content">
                        <div style="display:flex;flex-direction:column;gap:12px">
                            <div
                                style="display:flex;align-items:center;justify-content:space-between;padding:16px;border:1px solid #eee;border-radius:8px">
                                <div>
                                    <h4 style="font-weight:600;margin:0 0 4px">IndiaMART</h4>
                                    <p style="font-size:14px;color:#666;margin:0">Auto-import leads from IndiaMART</p>
                                </div>
                                <span class="badge badge-success">Connected</span>
                            </div>
                            <div
                                style="display:flex;align-items:center;justify-content:space-between;padding:16px;border:1px solid #eee;border-radius:8px">
                                <div>
                                    <h4 style="font-weight:600;margin:0 0 4px">Facebook Lead Ads</h4>
                                    <p style="font-size:14px;color:#666;margin:0">Sync leads from Facebook campaigns</p>
                                </div>
                                <button class="btn btn-outline btn-sm">Connect</button>
                            </div>
                            <div
                                style="display:flex;align-items:center;justify-content:space-between;padding:16px;border:1px solid #eee;border-radius:8px">
                                <div>
                                    <h4 style="font-weight:600;margin:0 0 4px">WhatsApp Business</h4>
                                    <p style="font-size:14px;color:#666;margin:0">Send messages via WhatsApp</p>
                                </div>
                                <button class="btn btn-outline btn-sm">Connect</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp API Configuration Tab -->
            <div class="settings-tab" id="tab-whatsapp-api" style="display:none">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="display:flex;align-items:center;gap:8px">
                            <i data-lucide="message-circle" style="width:20px;height:20px;color:#25D366"></i>
                            WhatsApp API Configuration
                        </h3>
                        <p class="text-sm text-muted" style="margin-top:4px">Configure your Evolution API credentials to
                            enable WhatsApp messaging</p>
                    </div>
                    <div class="card-content">
                        <div
                            style="background:linear-gradient(135deg,#dcfce7,#f0fdf4);border:1px solid #bbf7d0;border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
                            <i data-lucide="info"
                                style="width:18px;height:18px;color:#16a34a;flex-shrink:0;margin-top:1px"></i>
                            <div style="font-size:13px;color:#166534;line-height:1.5">
                                Enter your Evolution API server URL and global API key below. Each user will get their own
                                WhatsApp instance automatically when they scan QR from the WhatsApp Connect page.
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom:16px">
                            <label class="form-label" style="font-weight:600">API URL</label>
                            <input type="url" class="form-input" id="wa-api-url"
                                value="{{ $whatsappApiConfig['api_url'] ?? '' }}"
                                placeholder="https://your-evolution-api.com">
                            <small style="color:#999;font-size:12px;margin-top:4px;display:block">Your Evolution API server
                                URL (e.g. https://evo.yourdomain.com)</small>
                        </div>

                        <div class="form-group" style="margin-bottom:16px">
                            <label class="form-label" style="font-weight:600">Global API Key</label>
                            <div style="position:relative">
                                <input type="password" class="form-input" id="wa-api-key"
                                    value="{{ $whatsappApiConfig['api_key'] ?? '' }}"
                                    placeholder="Your Evolution API global key" style="padding-right:44px">
                                <button type="button" onclick="toggleApiKeyVisibility()" id="wa-key-toggle-btn"
                                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#888">
                                    <i data-lucide="eye" style="width:18px;height:18px" id="wa-key-icon"></i>
                                </button>
                            </div>
                            <small style="color:#999;font-size:12px;margin-top:4px;display:block">The master API key from
                                your Evolution API server</small>
                        </div>

                        <div class="form-group" style="margin-bottom:16px">
                            <label class="form-label" style="font-weight:600">Webhook Base URL <span style="color:#999;font-weight:400">(Your CRM Server URL)</span></label>
                            <input type="url" class="form-input" id="wa-webhook-url"
                                value="{{ $whatsappApiConfig['webhook_base_url'] ?? '' }}"
                                placeholder="https://your-crm-domain.com">
                            <small style="color:#999;font-size:12px;margin-top:4px;display:block">Your CRM server's public URL 
                                (e.g. https://crm.yourdomain.com). Evolution API ko webhook callbacks ke liye is URL ka zaroorat hai. Agar ye empty hai, to Auto-Reply kaam nahi karega.</small>
                        </div>

                        <button class="btn btn-primary" id="wa-save-btn" onclick="saveWhatsappApiConfig()">
                            <i data-lucide="save" style="width:16px;height:16px"></i> Save Configuration
                        </button>
                    </div>
                </div>
            </div>

            <!-- AI Bot Configuration Tab -->
            <div class="settings-tab" id="tab-ai-bot" style="display:none">
                <!-- AI Bot Toggle -->
                <div class="card" style="margin-bottom:24px">
                    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <h3 class="card-title" style="display:flex;align-items:center;gap:8px">
                                <i data-lucide="brain" style="width:20px;height:20px;color:#8b5cf6"></i>
                                AI Bot Status
                            </h3>
                            <p class="text-sm text-muted" style="margin-top:4px">AI Bot ON hone pe Auto-Reply rules auto disable ho jayenge</p>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                            <span id="ai-bot-label" style="font-weight:600;font-size:14px;color:{{ $aiBotEnabled ? '#16a34a' : '#dc2626' }}">{{ $aiBotEnabled ? 'ON' : 'OFF' }}</span>
                            <div style="position:relative;width:50px;height:28px">
                                <input type="checkbox" id="ai-bot-toggle" {{ $aiBotEnabled ? 'checked' : '' }}
                                    onchange="toggleAiBot(this.checked)"
                                    style="opacity:0;width:0;height:0;position:absolute">
                                <div id="ai-bot-slider" style="position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:{{ $aiBotEnabled ? '#22c55e' : '#ccc' }};border-radius:28px;transition:0.3s" onclick="document.getElementById('ai-bot-toggle').click()">
                                    <div style="position:absolute;content:'';height:22px;width:22px;left:{{ $aiBotEnabled ? '25px' : '3px' }};bottom:3px;background:white;border-radius:50%;transition:0.3s" id="ai-bot-knob"></div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Vertex AI Configuration -->
                <div class="card" style="margin-bottom:24px">
                    <div class="card-header">
                        <h3 class="card-title">Google Vertex AI Configuration</h3>
                        <p class="text-sm text-muted" style="margin-top:4px">Service Account credentials for Vertex AI (Gemini) API</p>
                    </div>
                    <div class="card-content">
                        <div style="background:linear-gradient(135deg,#ede9fe,#f5f3ff);border:1px solid #c4b5fd;border-radius:8px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
                            <i data-lucide="info" style="width:18px;height:18px;color:#7c3aed;flex-shrink:0;margin-top:1px"></i>
                            <div style="font-size:13px;color:#5b21b6;line-height:1.5">
                                Google Cloud Console se Service Account ka JSON file download karke niche paste karo. Project ID, Location aur Model bhi dal do.
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
                            <div>
                                <label style="display:block;margin-bottom:4px;font-weight:500">Project ID *</label>
                                <input type="text" class="form-input" id="ai-project-id" value="{{ $aiVertexConfig['project_id'] ?? '' }}" placeholder="my-gcp-project">
                            </div>
                            <div>
                                <label style="display:block;margin-bottom:4px;font-weight:500">Location</label>
                                <input type="text" class="form-input" id="ai-location" value="{{ $aiVertexConfig['location'] ?? 'us-central1' }}" placeholder="us-central1">
                            </div>
                            <div>
                                <label style="display:block;margin-bottom:4px;font-weight:500">Model</label>
                                <input type="text" class="form-input" id="ai-model" value="{{ $aiVertexConfig['model'] ?? 'gemini-1.5-flash-001' }}" placeholder="gemini-1.5-flash-001">
                            </div>
                        </div>

                        <div style="margin-bottom:16px">
                            <label style="display:block;margin-bottom:4px;font-weight:500">Service Account JSON *</label>
                            <textarea class="form-textarea" id="ai-service-account" rows="8" placeholder='{ "type": "service_account", "project_id": "...", "client_email": "...", "private_key": "..." }' style="font-family:monospace;font-size:12px">{{ !empty($aiVertexConfig['service_account']) ? json_encode($aiVertexConfig['service_account'], JSON_PRETTY_PRINT) : '' }}</textarea>
                            <small style="color:#999;font-size:12px;margin-top:4px;display:block">Google Cloud Console → IAM & Admin → Service Accounts → Keys → Add Key → JSON</small>
                        </div>

                        <button type="button" class="btn btn-primary" onclick="saveAiConfig()">
                            <i data-lucide="save" style="width:16px;height:16px"></i> Save AI Config
                        </button>
                    </div>
                </div>

                <!-- System Prompt -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">AI System Prompt</h3>
                        <p class="text-sm text-muted" style="margin-top:4px">Ye prompt AI ko batata hai ki wo kaise behave kare</p>
                    </div>
                    <div class="card-content">
                        <div style="margin-bottom:16px">
                            <textarea class="form-textarea" id="ai-system-prompt" rows="12" placeholder="Tum ek helpful WhatsApp AI assistant ho...">{{ $aiSystemPrompt ?? '' }}</textarea>
                            <small style="color:#999;font-size:12px;margin-top:4px;display:block">Hint: Batao ki bot Hindi/English me baat kare, customer ko kaise greet kare, products ke baare me kya bataye</small>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <button type="button" class="btn btn-primary" onclick="saveAiPrompt()">
                                <i data-lucide="save" style="width:16px;height:16px"></i> Save Prompt
                            </button>
                            
                            <a href="{{ route('admin.system-logs.index') }}" class="btn btn-outline" style="display:flex; align-items:center; gap:6px;">
                                <i data-lucide="terminal" style="width:16px;height:16px"></i> System Error Logs
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup & Restore Tab -->
            <div class="settings-tab" id="tab-backup-restore" style="display:none">
                @if(session('error'))
                    <div
                        style="background:#fee2e2;color:#dc2626;padding:14px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:600;border:1px solid #fecaca;display:flex;align-items:center;gap:8px;">
                        <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Full Database Restore -->
                <div class="card" style="margin-bottom:24px">
                    <div class="card-header" style="display:flex;align-items:center;gap:12px">
                        <div
                            style="width:38px;height:38px;background:linear-gradient(135deg,#fef08a,#fde047);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="database-zap" style="width:18px;height:18px;color:#ca8a04;"></i>
                        </div>
                        <div>
                            <h3 class="card-title" style="margin:0">Restore Full Database</h3>
                            <p class="text-sm text-muted" style="margin:2px 0 0">Upload the .zip backup file (from Google Drive) to restore all data</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div
                            style="background:linear-gradient(135deg,#fef9c3,#fef3c7);border:1px solid #fde68a;border-radius:10px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
                            <i data-lucide="alert-triangle"
                                style="width:18px;height:18px;color:#d97706;flex-shrink:0;margin-top:1px"></i>
                            <div style="font-size:13px;color:#92400e;line-height:1.5">
                                <strong>Warning:</strong> Ye backup upload karne se <strong>purana saara data replace</strong> ho jayega naaye backup data se. Sirf tabhi karo jab confirm ho ki ye sahi backup file hai.
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.backups.restore') }}" enctype="multipart/form-data" id="settings-restore-form">
                            @csrf
                            <div id="settings-restore-zone" onclick="document.getElementById('settings-restore-file').click();"
                                style="border:2px dashed #cbd5e1;border-radius:14px;padding:32px;text-align:center;transition:all 0.3s;cursor:pointer;background:#fafbfc;"
                                onmouseover="this.style.borderColor='#ca8a04';this.style.background='#fffbeb'"
                                onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fafbfc'">
                                <i data-lucide="upload-cloud" style="width:40px;height:40px;color:#94a3b8;margin-bottom:8px;"></i>
                                <p style="margin:0;font-size:14px;color:#64748b;"><strong style="color:#ca8a04">Click to browse</strong> for backup file</p>
                                <p style="margin:6px 0 0;font-size:12px;color:#94a3b8;">.zip or .sql file — Google Drive se downloaded backup</p>
                            </div>
                            <input type="file" name="backup_file" id="settings-restore-file" accept=".zip,.sql" style="display:none;"
                                onchange="settingsShowRestoreFile(this)">

                            <div id="settings-restore-selected" style="margin-top:12px;"></div>

                            <div style="margin-top:16px;">
                                <button type="submit" class="btn btn-primary" style="display:none;background:linear-gradient(135deg,#ca8a04,#a16207);border:none;padding:10px 24px;" id="settings-restore-btn"
                                    onclick="return confirm('⚠️ WARNING: Is se puura database overwrite ho jayega backup data se. Kya aap sure hain?');">
                                    <i data-lucide="alert-triangle" style="width:14px;height:14px;margin-right:6px;"></i> Restore Database
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Partial JSON Import -->
                <div class="card" style="margin-bottom:24px">
                    <div class="card-header" style="display:flex;align-items:center;gap:12px">
                        <div
                            style="width:38px;height:38px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="upload" style="width:18px;height:18px;color:#16a34a;"></i>
                        </div>
                        <div>
                            <h3 class="card-title" style="margin:0">Import JSON Backup</h3>
                            <p class="text-sm text-muted" style="margin:2px 0 0">Upload JSON backup files to merge/sync specific module data</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div
                            style="background:linear-gradient(135deg,#dcfce7,#f0fdf4);border:1px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px">
                            <i data-lucide="info"
                                style="width:18px;height:18px;color:#16a34a;flex-shrink:0;margin-top:1px"></i>
                            <div style="font-size:13px;color:#166534;line-height:1.5">
                                Ye method safer hai — ye existing data ke saath <strong>merge</strong> karta hai. Naya data add hota hai, existing data update hota hai.
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.backups.import') }}" enctype="multipart/form-data" id="settings-import-form">
                            @csrf
                            <div id="settings-import-zone" onclick="document.getElementById('settings-import-files').click();"
                                style="border:2px dashed #cbd5e1;border-radius:14px;padding:32px;text-align:center;transition:all 0.3s;cursor:pointer;background:#fafbfc;"
                                onmouseover="this.style.borderColor='#16a34a';this.style.background='#f0fdf4'"
                                onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#fafbfc'">
                                <i data-lucide="file-json" style="width:40px;height:40px;color:#94a3b8;margin-bottom:8px;"></i>
                                <p style="margin:0;font-size:14px;color:#64748b;"><strong style="color:#16a34a">Click to browse</strong> for JSON backup files</p>
                                <p style="margin:6px 0 0;font-size:12px;color:#94a3b8;">JSON files only • Multiple files allowed • Max 50MB each</p>
                            </div>
                            <input type="file" name="backup_files[]" id="settings-import-files" multiple accept=".json" style="display:none;"
                                onchange="settingsShowImportFiles(this)">

                            <div id="settings-import-selected" style="margin-top:12px;"></div>

                            <div style="margin-top:16px;">
                                <button type="submit" class="btn btn-primary" style="display:none;background:linear-gradient(135deg,#059669,#10b981);border:none;padding:10px 24px;" id="settings-import-btn">
                                    <i data-lucide="database" style="width:14px;height:14px;margin-right:6px;"></i> Import Files
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Download Backup Files -->
                <div class="card" style="margin-bottom:24px">
                    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:12px">
                            <div
                                style="width:38px;height:38px;background:linear-gradient(135deg,#e0e7ff,#c7d2fe);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="download" style="width:18px;height:18px;color:#4f46e5;"></i>
                            </div>
                            <div>
                                <h3 class="card-title" style="margin:0">Manage Backup Files</h3>
                                <p class="text-sm text-muted" style="margin:2px 0 0">JSON, ZIP aur SQL backup files jo server pe saved hain — download ya delete karo</p>
                            </div>
                        </div>
                        @if(count($backupFiles ?? []) > 0)
                            <span style="background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;font-size:11px;padding:4px 12px;border-radius:12px;font-weight:700;">
                                {{ count($backupFiles) }} Files
                            </span>
                        @endif
                    </div>
                    <div class="card-content" style="padding:0;">
                        @if(empty($backupFiles))
                            <div style="padding:40px;text-align:center;color:#94a3b8;">
                                <i data-lucide="inbox" style="width:36px;height:36px;display:block;margin:0 auto 8px;opacity:0.4;"></i>
                                <p style="margin:0;font-size:13px;">Abhi koi backup file nahi hai — pehle ek backup run karo.</p>
                            </div>
                        @else
                            <div style="max-height:400px;overflow-y:auto;">
                                @foreach($backupFiles as $file)
                                    @php
                                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                                        $icon = $ext === 'zip' ? 'package' : ($ext === 'sql' ? 'database' : 'file-json');
                                        $iconColor = $ext === 'zip' ? '#f59e0b' : ($ext === 'sql' ? '#10b981' : '#4f46e5');
                                        $bgGradient = $ext === 'zip' ? 'linear-gradient(135deg,#fef3c7,#fde68a)' : ($ext === 'sql' ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : 'linear-gradient(135deg,#e0e7ff,#c7d2fe)');
                                    @endphp
                                    <div style="display:flex;align-items:center;gap:14px;padding:12px 20px;border-bottom:1px solid rgba(0,0,0,0.04);transition:all 0.15s;"
                                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                        <div style="width:38px;height:38px;border-radius:10px;background:{{ $bgGradient }};color:{{ $iconColor }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="{{ $icon }}" style="width:16px;height:16px;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <p style="margin:0;font-size:13px;font-weight:600;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $file['name'] }}</p>
                                            <p style="margin:2px 0 0;font-size:11px;color:#94a3b8;">{{ strtoupper($ext) }} File • {{ $file['size'] }} KB • {{ $file['date'] }}</p>
                                        </div>
                                        <div style="display:flex; gap:8px; flex-shrink:0;">
                                            <a href="{{ route('admin.backups.download', $file['name']) }}" class="btn btn-outline btn-sm"
                                                style="padding:6px 14px;font-size:12px;display:flex;align-items:center;gap:4px;">
                                                <i data-lucide="download" style="width:13px;height:13px;"></i> Download
                                            </a>
                                            <button type="button" onclick="ajaxDelete('{{ route('admin.backups.destroy', $file['name']) }}')" class="btn btn-outline btn-sm"
                                                    style="padding:6px 14px;font-size:12px;display:flex;align-items:center;gap:4px;color:#ef4444;border-color:#fca5a5;background:transparent;"
                                                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                                                    <i data-lucide="trash-2" style="width:13px;height:13px;"></i> Delete
                                                </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Run Manual Backup -->
                <div class="card">
                    <div class="card-header" style="display:flex;align-items:center;gap:12px">
                        <div
                            style="width:38px;height:38px;background:linear-gradient(135deg,#e0e7ff,#c7d2fe);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="hard-drive" style="width:18px;height:18px;color:#4f46e5;"></i>
                        </div>
                        <div>
                            <h3 class="card-title" style="margin:0">Create Backup</h3>
                            <p class="text-sm text-muted" style="margin:2px 0 0">Manually run a backup to export all data</p>
                        </div>
                    </div>
                    <div class="card-content">
                        <div style="display:flex;gap:12px;flex-wrap:wrap;">
                            <form method="POST" action="{{ route('admin.backups.run') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="type" value="auto">
                                <button type="submit" class="btn btn-primary" style="padding:10px 22px;">
                                    <i data-lucide="play" style="width:14px;height:14px;margin-right:6px;"></i> Smart Backup
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.backups.run') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="type" value="full">
                                <button type="submit" class="btn btn-outline" style="padding:10px 22px;">
                                    <i data-lucide="refresh-cw" style="width:14px;height:14px;margin-right:6px;"></i> Full Backup
                                </button>
                            </form>
                        </div>
                        <p style="font-size:12px;color:#94a3b8;margin-top:12px;">
                            <i data-lucide="info" style="width:12px;height:12px;display:inline;vertical-align:middle;"></i>
                            Smart backup sirf changed data backup karta hai. Full backup sab data export karta hai.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .settings-nav-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            border-radius: 6px;
            font-size: 14px;
            color: #555;
            transition: all 0.15s ease;
        }

        .settings-nav-btn:hover {
            background: #f5f5f5;
            color: #111;
        }

        .settings-nav-btn.active {
            background: #e8f0fe;
            color: #1a73e8;
            font-weight: 600;
        }

        .column-visibility-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .column-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.15s ease;
            margin: 0;
        }

        .column-toggle:last-child {
            border-bottom: none;
        }

        .column-toggle:hover {
            background: #f9f9f9;
        }

        .column-toggle input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1a73e8;
            cursor: pointer;
            flex-shrink: 0;
        }

        .column-toggle input[type="checkbox"]:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .column-toggle span {
            font-size: 14px;
            color: #333;
        }

        .column-toggle .badge {
            margin-left: auto;
            font-size: 11px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ─── Database-backed column visibility settings ───
        // Settings loaded from controller (server-side), saved via AJAX to database
        const dbSettings = @json($columnVisibility ?? new \stdClass());
        let quoteTaxes = @json($quoteTaxes ?? []);
        let leadStages = @json($leadStages ?? []);
        let leadSources = @json($leadSources ?? []);
        let taskStatuses = @json($taskStatuses ?? []);
        let paymentTypes = {!! json_encode($paymentTypes ?? ['cash', 'online', 'cheque', 'upi', 'bank_transfer']) !!};
        const CSRF_TOKEN = '{{ csrf_token() }}';

        function switchSettingsTab(tabName, btn) {
            document.querySelectorAll('.settings-tab').forEach(tab => tab.style.display = 'none');
            document.querySelectorAll('.settings-nav-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tabName).style.display = 'block';
            if (btn) {
                btn.classList.add('active');
            } else {
                let targetBtn = document.querySelector(`.settings-nav-btn[data-tab="${tabName}"]`);
                if (targetBtn) targetBtn.classList.add('active');
            }
            window.location.hash = tabName;
        }

        // --- Lead Stages, Lead Sources, Task Statuses, Payment Types ---
        function renderStages(type) {
            let containerId, items;
            if (type === 'lead') {
                containerId = 'lead-stages-container';
                items = leadStages;
            } else if (type === 'source') {
                containerId = 'source-stages-container';
                items = leadSources;
            } else if (type === 'task') {
                containerId = 'task-statuses-container';
                items = taskStatuses;
            } else if (type === 'payment') {
                containerId = 'payment-types-container';
                items = paymentTypes;
            }
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = '';

            const itemName = type === 'payment' ? 'types' : (type === 'lead' ? 'stages' : (type === 'source' ? 'sources' : 'statuses'));

            if (items.length === 0) {
                container.innerHTML = `<div style="text-align:center;padding:24px;color:#999;font-size:13px">No ${itemName} added yet. Click &quot;+ Add&quot; to create one.</div>`;
                return;
            }

            items.forEach((item, idx) => {
                const placeholder = type === 'payment' ? 'e.g. PayPal' : (type === 'lead' ? 'e.g. New' : (type === 'source' ? 'e.g. Facebook' : 'e.g. Pending'));
                container.innerHTML += `
                                                        <div style="display:flex;align-items:center;gap:12px">
                                                            <i data-lucide="grip-vertical" style="color:#ccc;cursor:grab;width:16px;height:16px"></i>
                                                            <input type="text" class="form-input ${type}-stage-input" value="${escapeHtml(item)}" placeholder="${placeholder}" style="flex:1">
                                                            <button type="button" class="btn btn-icon btn-ghost btn-sm" style="color:var(--destructive)" onclick="removeStageRow('${type}', ${idx})" title="Remove">
                                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                            </button>
                                                        </div>
                                                    `;
            });
            lucide.createIcons();
        }

        function syncInputsToArray(type) {
            let containerId = type === 'lead' ? 'lead-stages-container' : (type === 'source' ? 'source-stages-container' : (type === 'task' ? 'task-statuses-container' : 'payment-types-container'));
            const inputs = document.querySelectorAll(`#${containerId} .${type}-stage-input`);
            let arr = [];
            inputs.forEach(input => arr.push(input.value));
            if (type === 'lead') leadStages = arr;
            else if (type === 'source') leadSources = arr;
            else if (type === 'task') taskStatuses = arr;
            else if (type === 'payment') paymentTypes = arr;
        }

        function addStageRow(type) {
            syncInputsToArray(type);
            if (type === 'lead') {
                leadStages.push('');
            } else if (type === 'source') {
                leadSources.push('');
            } else if (type === 'task') {
                taskStatuses.push('');
            } else if (type === 'payment') {
                paymentTypes.push('');
            }
            renderStages(type);
        }

        function removeStageRow(type, idx) {
            syncInputsToArray(type);

            // For lead stages, check if any leads are connected to this stage
            if (type === 'lead') {
                const stageName = leadStages[idx];
                if (!stageName) {
                    // Empty stage name, just remove
                    leadStages.splice(idx, 1);
                    renderStages(type);
                    return;
                }

                // Check server for leads on this stage
                fetch('{{ route("admin.settings.lead-stages.check") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ stage: stageName })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.count > 0) {
                        // Build transfer options (all other stages)
                        let otherStages = leadStages.filter((s, i) => i !== idx && s.trim() !== '');
                        if (otherStages.length === 0) {
                            alert('इस stage में ' + data.count + ' lead(s) हैं। कम से कम एक और stage होना चाहिए जिसमें leads को transfer किया जा सके।');
                            return;
                        }

                        let optionsHtml = otherStages.map(s => `<option value="${s}">${s.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</option>`).join('');

                        // Show transfer modal
                        showStageTransferModal(stageName, data.count, optionsHtml, idx);
                    } else {
                        // No leads, safe to remove
                        if (confirm('Are you sure you want to remove this stage?')) {
                            leadStages.splice(idx, 1);
                            renderStages(type);
                        }
                    }
                })
                .catch(err => {
                    console.error('Error checking stage leads:', err);
                    alert('Error checking stage. Please try again.');
                });
                return;
            }

            // For non-lead types, just remove directly
            if (type === 'source') {
                leadSources.splice(idx, 1);
            } else if (type === 'task') {
                taskStatuses.splice(idx, 1);
            } else if (type === 'payment') {
                paymentTypes.splice(idx, 1);
            }
            renderStages(type);
        }

        function showStageTransferModal(stageName, leadCount, optionsHtml, stageIdx) {
            // Remove existing modal if any
            let existingModal = document.getElementById('stage-transfer-modal');
            if (existingModal) existingModal.remove();

            let displayName = stageName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

            let modalHtml = `
                <div id="stage-transfer-modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
                    <div style="background:white;border-radius:16px;width:95%;max-width:480px;box-shadow:0 25px 60px rgba(0,0,0,0.2);overflow:hidden">
                        <div style="padding:20px 24px;border-bottom:1px solid #f0f0f0;background:linear-gradient(135deg,#fef2f2,#fff)">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#fca5a5,#f87171);display:flex;align-items:center;justify-content:center">
                                    <i data-lucide="alert-triangle" style="width:20px;height:20px;color:white"></i>
                                </div>
                                <div>
                                    <h3 style="margin:0;font-size:16px;font-weight:700;color:#1e293b">Stage Delete — Leads Found</h3>
                                    <p style="margin:2px 0 0;font-size:13px;color:#64748b">"${displayName}" stage me <b>${leadCount}</b> lead(s) hain</p>
                                </div>
                            </div>
                        </div>
                        <div style="padding:24px">
                            <p style="font-size:14px;color:#475569;margin:0 0 16px;line-height:1.6">
                                In leads ko kisi aur stage me transfer karna hoga, tab hi ye stage delete hoga.
                            </p>
                            <div>
                                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Transfer to Stage <span style="color:#ef4444">*</span></label>
                                <select id="transfer-stage-select" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;background:white">
                                    ${optionsHtml}
                                </select>
                            </div>
                        </div>
                        <div style="padding:16px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc">
                            <button type="button" onclick="closeStageTransferModal()" style="padding:9px 20px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b">Cancel</button>
                            <button type="button" onclick="executeStageTransfer('${stageName}', ${stageIdx})" id="btn-transfer-stage" style="padding:9px 20px;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(239,68,68,0.3)">Transfer & Delete</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeStageTransferModal() {
            let modal = document.getElementById('stage-transfer-modal');
            if (modal) modal.remove();
        }

        function executeStageTransfer(fromStage, stageIdx) {
            let toStage = document.getElementById('transfer-stage-select').value;
            let btn = document.getElementById('btn-transfer-stage');
            btn.textContent = 'Transferring...';
            btn.disabled = true;

            fetch('{{ route("admin.settings.lead-stages.transfer") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ from_stage: fromStage, to_stage: toStage })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeStageTransferModal();
                    // Remove the stage from local array
                    leadStages.splice(stageIdx, 1);
                    renderStages('lead');
                    
                    let displayTo = toStage.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    showSavedToast();
                    alert(data.transferred + ' lead(s) successfully transferred to "' + displayTo + '". Don\'t forget to click "Save Stages" to save the changes.');
                } else {
                    alert('Error transferring leads. Please try again.');
                    btn.textContent = 'Transfer & Delete';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Error transferring:', err);
                alert('Error transferring leads. Please try again.');
                btn.textContent = 'Transfer & Delete';
                btn.disabled = false;
            });
        }

        function saveLeadStages(event) {
            saveStagesData('lead', event.target);
        }

        function saveLeadSources(event) {
            saveStagesData('source', event.target);
        }

        function saveTaskStatuses(event) {
            saveStagesData('task', event.target);
        }

        function savePaymentTypes(event) {
            saveStagesData('payment', event.target);
        }

        function saveStagesData(type, btn) {
            let containerId;
            if (type === 'lead') containerId = 'lead-stages-container';
            else if (type === 'source') containerId = 'source-stages-container';
            else if (type === 'task') containerId = 'task-statuses-container';
            else if (type === 'payment') containerId = 'payment-types-container';

            const inputs = document.querySelectorAll(`#${containerId} .${type}-stage-input`);

            let newItems = [];
            inputs.forEach(input => {
                const val = input.value.trim();
                // Basic format check to be url-friendly (lowercase, no spaces)
                const formattedVal = val.toLowerCase().replace(/[^a-z0-9_-]/g, '_');
                if (formattedVal) newItems.push(formattedVal);
            });

            // Prevent empty list
            const itemName = type === 'payment' ? 'type' : (type === 'lead' ? 'stage' : (type === 'source' ? 'source' : 'status'));
            if (newItems.length === 0) {
                alert(`You must have at least one ${itemName}.`);
                return;
            }

            // Remove duplicates
            newItems = [...new Set(newItems)];

            let url, payload;
            if (type === 'lead') {
                leadStages = newItems;
                url = '{{ route("admin.settings.lead-stages.save") }}';
                payload = { stages: leadStages };
            } else if (type === 'source') {
                leadSources = newItems;
                url = '{{ route("admin.settings.lead-sources.save") }}';
                payload = { sources: leadSources };
            } else if (type === 'task') {
                taskStatuses = newItems;
                url = '{{ route("admin.settings.task-statuses.save") }}';
                payload = { statuses: taskStatuses };
            } else if (type === 'payment') {
                paymentTypes = newItems;
                url = '{{ route("admin.settings.payment-types.save") }}';
                payload = { types: paymentTypes };
            }

            const originalText = btn.textContent;
            btn.textContent = 'Saving...';
            btn.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSavedToast();
                        renderStages(type);
                    }
                })
                .catch(err => {
                    console.error('Error saving:', err);
                    alert('An error occurred while saving.');
                })
                .finally(() => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                });
        }

        // --- Quotes Taxes ---
        function renderTaxes() {
            var tbody = document.getElementById('taxes-tbody');
            if (!tbody) return;
            tbody.innerHTML = '';

            if (quoteTaxes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:24px;color:#999;font-size:13px">No taxes added yet. Click &quot;+ Add Tax&quot; to create one.</td></tr>';
                return;
            }

            quoteTaxes.forEach(function (tax, idx) {
                tbody.innerHTML += `
                                                                    <tr>
                                                                        <td style="padding:8px 14px;border-bottom:1px solid #f0f0f0"><input type="text" class="form-input tax-name" value="${escapeHtml(tax.name)}" placeholder="e.g. GST 18%"></td>
                                                                        <td style="padding:8px 14px;border-bottom:1px solid #f0f0f0"><input type="number" step="0.01" min="0" class="form-input tax-rate" value="${tax.rate}" placeholder="18"></td>
                                                                        <td style="padding:8px 14px;border-bottom:1px solid #f0f0f0;text-align:right"><button type="button" class="btn btn-icon btn-ghost btn-sm" style="color:var(--destructive)" onclick="removeTaxRow(${idx})" title="Remove"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button></td>
                                                                    </tr>
                                                                `;
            });
            lucide.createIcons();
        }

        function addTaxRow() {
            quoteTaxes.push({ name: '', rate: 0 });
            renderTaxes();
        }

        function removeTaxRow(idx) {
            quoteTaxes.splice(idx, 1);
            renderTaxes();
        }

        function saveTaxes() {
            var rows = document.querySelectorAll('#taxes-tbody tr');
            var newTaxes = [];
            rows.forEach(function (row) {
                var nameInput = row.querySelector('.tax-name');
                if (!nameInput) return;
                var name = nameInput.value.trim();
                var rate = parseFloat(row.querySelector('.tax-rate').value) || 0;
                if (name) {
                    newTaxes.push({ name: name, rate: rate });
                }
            });
            quoteTaxes = newTaxes;

            var btn = event.target;
            btn.textContent = 'Saving...';
            btn.disabled = true;

            fetch('{{ route("admin.settings.taxes.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ taxes: quoteTaxes })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showSavedToast();
                        renderTaxes();
                    }
                })
                .finally(function () {
                    btn.textContent = 'Save Taxes';
                    btn.disabled = false;
                });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Save a module's column settings to database via AJAX
        function saveModuleToDb(module) {
            var columns = {};
            document.querySelectorAll('input[data-module="' + module + '"][data-column]').forEach(function (cb) {
                if (!cb.disabled) {
                    columns[cb.dataset.column] = cb.checked;
                }
            });

            fetch('/admin/settings/column-visibility', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ module: module, columns: columns })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) showSavedToast();
                })
                .catch(function (err) {
                    console.error('Error saving settings:', err);
                });
        }

        function resetColumns(module) {
            // Re-check all checkboxes for this module
            document.querySelectorAll('input[data-module="' + module + '"]').forEach(function (cb) {
                if (!cb.disabled) cb.checked = true;
            });
            // Save reset state to database
            saveModuleToDb(module);
        }

        function showSavedToast() {
            var toast = document.getElementById('settings-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'settings-toast';
                toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1a73e8;color:white;padding:12px 24px;border-radius:8px;font-size:14px;font-weight:500;z-index:9999;opacity:0;transition:opacity 0.3s ease;box-shadow:0 4px 12px rgba(0,0,0,0.15)';
                document.body.appendChild(toast);
            }
            toast.textContent = '✓ Settings saved to database';
            toast.style.opacity = '1';
            setTimeout(function () { toast.style.opacity = '0'; }, 2000);
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Restore tab from hash if present
            if (window.location.hash) {
                let hashTab = window.location.hash.substring(1);
                if (document.getElementById('tab-' + hashTab)) {
                    switchSettingsTab(hashTab, null);
                }
            }

            // Load saved settings from DB (passed from controller) and apply to checkboxes
            document.querySelectorAll('input[data-module][data-column]').forEach(function (cb) {
                var module = cb.dataset.module;
                var column = cb.dataset.column;

                // If there are saved settings for this module from DB, apply them
                if (dbSettings[module] && dbSettings[module][column] !== undefined) {
                    cb.checked = dbSettings[module][column];
                }

                // Listen for changes — save to database
                cb.addEventListener('change', function () {
                    saveModuleToDb(this.dataset.module);
                });
            });

            renderTaxes(); // Initialize quotes taxes
            renderStages('lead'); // Initialize lead stages
            renderStages('source'); // Initialize lead sources
            renderStages('task'); // Initialize task statuses
            renderStages('payment'); // Initialize payment types

            // Initialize Drag & Drop for Lead Stages
            const leadStagesContainer = document.getElementById('lead-stages-container');
            if (leadStagesContainer && typeof Sortable !== 'undefined') {
                new Sortable(leadStagesContainer, {
                    animation: 150,
                    handle: '.lucide-grip-vertical',
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        // We don't save immediately, wait for user to click Save
                    }
                });
            }

            // Initialize Drag & Drop for Task Statuses
            const taskStatusesContainer = document.getElementById('task-statuses-container');
            if (taskStatusesContainer && typeof Sortable !== 'undefined') {
                new Sortable(taskStatusesContainer, {
                    animation: 150,
                    handle: '.lucide-grip-vertical',
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        // We don't save immediately, wait for user to click Save
                    }
                });
            }

            lucide.createIcons();
        });

        // --- WhatsApp API Configuration ---
        function toggleApiKeyVisibility() {
            var input = document.getElementById('wa-api-key');
            var icon = document.getElementById('wa-key-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function saveWhatsappApiConfig() {
            var apiUrl = document.getElementById('wa-api-url').value.trim();
            var apiKey = document.getElementById('wa-api-key').value.trim();
            var webhookUrl = document.getElementById('wa-webhook-url').value.trim();

            if (!apiUrl || !apiKey) {
                alert('Please fill in API URL and API Key.');
                return;
            }

            var btn = document.getElementById('wa-save-btn');
            btn.textContent = 'Saving...';
            btn.disabled = true;

            fetch('{{ route("admin.settings.whatsapp-api.save") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    api_url: apiUrl,
                    api_key: apiKey,
                    webhook_base_url: webhookUrl
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showSavedToast();
                    } else if (data.errors) {
                        alert(Object.values(data.errors).flat().join('\n'));
                    }
                })
                .catch(function (err) {
                    console.error('Error saving WhatsApp config:', err);
                    alert('An error occurred while saving.');
                })
                .finally(function () {
                    btn.innerHTML = '<i data-lucide="save" style="width:16px;height:16px"></i> Save Configuration';
                    btn.disabled = false;
                    lucide.createIcons();
                });
        }

        // Preview logo when selected
        function previewLogo(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    // Find the logo preview container (first child of the flex container)
                    var container = input.closest('.form-group').querySelector('div[style*="display:flex"] > div:first-child');
                    if (container) {
                        container.style.border = '2px solid #e2e8f0';
                        container.style.borderStyle = 'solid';
                        container.style.borderRadius = '12px';
                        container.style.overflow = 'hidden';
                        container.innerHTML = '<img src="' + e.target.result + '" alt="Logo Preview" style="max-width:100%;max-height:100%;object-fit:contain;">';
                    }
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

    <script>
        // ─── Backup & Restore Tab Handlers ───
        function settingsShowRestoreFile(input) {
            var container = document.getElementById('settings-restore-selected');
            var btn = document.getElementById('settings-restore-btn');
            if (input.files.length === 0) {
                container.innerHTML = '';
                btn.style.display = 'none';
                return;
            }
            var f = input.files[0];
            var sizeMB = (f.size / 1024 / 1024).toFixed(2);
            container.innerHTML = '<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">' +
                '<i data-lucide="file-archive" style="width:20px;height:20px;color:#ca8a04;"></i>' +
                '<div style="flex:1;min-width:0;">' +
                '<p style="margin:0;font-size:13px;font-weight:600;color:#92400e;">' + f.name + '</p>' +
                '<p style="margin:2px 0 0;font-size:11px;color:#a16207;">' + sizeMB + ' MB</p>' +
                '</div>' +
                '<button type="button" onclick="clearRestoreFile()" style="background:none;border:none;cursor:pointer;color:#dc2626;padding:4px;">' +
                '<i data-lucide="x" style="width:16px;height:16px;"></i></button>' +
                '</div>';
            btn.style.display = 'inline-flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function clearRestoreFile() {
            document.getElementById('settings-restore-file').value = '';
            document.getElementById('settings-restore-selected').innerHTML = '';
            document.getElementById('settings-restore-btn').style.display = 'none';
        }

        function settingsShowImportFiles(input) {
            var container = document.getElementById('settings-import-selected');
            var btn = document.getElementById('settings-import-btn');
            if (input.files.length === 0) {
                container.innerHTML = '';
                btn.style.display = 'none';
                return;
            }
            var html = '<div style="display:flex;flex-wrap:wrap;gap:8px;">';
            for (var i = 0; i < input.files.length; i++) {
                html += '<span style="background:#dcfce7;color:#166534;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;">' +
                    '<i data-lucide="file-json" style="width:12px;height:12px;"></i> ' + input.files[i].name + '</span>';
            }
            html += '</div>';
            container.innerHTML = html;
            btn.style.display = 'inline-flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Drag-drop for restore zone
        (function() {
            var rzone = document.getElementById('settings-restore-zone');
            if (rzone) {
                rzone.addEventListener('dragover', function(e) { e.preventDefault(); rzone.style.borderColor = '#ca8a04'; rzone.style.background = '#fffbeb'; });
                rzone.addEventListener('dragleave', function() { rzone.style.borderColor = '#cbd5e1'; rzone.style.background = '#fafbfc'; });
                rzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    rzone.style.borderColor = '#cbd5e1'; rzone.style.background = '#fafbfc';
                    var inp = document.getElementById('settings-restore-file');
                    inp.files = e.dataTransfer.files;
                    settingsShowRestoreFile(inp);
                });
            }
            var izone = document.getElementById('settings-import-zone');
            if (izone) {
                izone.addEventListener('dragover', function(e) { e.preventDefault(); izone.style.borderColor = '#16a34a'; izone.style.background = '#f0fdf4'; });
                izone.addEventListener('dragleave', function() { izone.style.borderColor = '#cbd5e1'; izone.style.background = '#fafbfc'; });
                izone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    izone.style.borderColor = '#cbd5e1'; izone.style.background = '#fafbfc';
                    var inp = document.getElementById('settings-import-files');
                    inp.files = e.dataTransfer.files;
                    settingsShowImportFiles(inp);
                });
            }
        })();

        // ═══════════════════════════════════════
        // AI Bot Settings Functions
        // ═══════════════════════════════════════
        function toggleAiBot(enabled) {
            var label = document.getElementById('ai-bot-label');
            var slider = document.getElementById('ai-bot-slider');
            var knob = document.getElementById('ai-bot-knob');
            label.textContent = enabled ? 'ON' : 'OFF';
            label.style.color = enabled ? '#16a34a' : '#dc2626';
            slider.style.background = enabled ? '#22c55e' : '#ccc';
            knob.style.left = enabled ? '25px' : '3px';

            fetch('{{ route("admin.settings.ai-toggle") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ enabled: enabled })
            }).then(r => r.json()).then(data => {
                if (data.success) alert(data.message);
                else alert('Error toggling AI bot');
            }).catch(() => alert('Request failed'));
        }

        function saveAiConfig() {
            fetch('{{ route("admin.settings.ai-config.save") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({
                    project_id: document.getElementById('ai-project-id').value,
                    location: document.getElementById('ai-location').value,
                    model: document.getElementById('ai-model').value,
                    service_account_json: document.getElementById('ai-service-account').value,
                })
            }).then(r => r.json()).then(data => {
                alert(data.message || 'Saved');
            }).catch(() => alert('Request failed'));
        }

        function saveAiPrompt() {
            fetch('{{ route("admin.settings.ai-prompt.save") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({
                    system_prompt: document.getElementById('ai-system-prompt').value,
                })
            }).then(r => r.json()).then(data => {
                alert(data.message || 'Saved');
            }).catch(() => alert('Request failed'));
        }
    </script>
@endpush