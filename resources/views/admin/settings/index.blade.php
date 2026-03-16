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
                        <form>
                            <div class="form-group">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-input" value="{{ $company->name ?? 'RV CRM' }}">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">GST Number</label>
                                    <input type="text" class="form-input" value="{{ $company->gstin ?? '' }}">
                                </div>
                                <div>
                                    <label style="display:block;margin-bottom:4px;font-weight:500">Phone</label>
                                    <input type="tel" class="form-input" value="{{ $company->phone ?? '' }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea class="form-textarea"
                                    rows="3">{{ is_array($company->address ?? null) ? implode(', ', array_filter($company->address)) : '' }}</textarea>
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
            btn.classList.add('active');
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
            if (type === 'lead') {
                leadStages.splice(idx, 1);
            } else if (type === 'source') {
                leadSources.splice(idx, 1);
            } else if (type === 'task') {
                taskStatuses.splice(idx, 1);
            } else if (type === 'payment') {
                paymentTypes.splice(idx, 1);
            }
            renderStages(type);
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
    </script>
@endpush