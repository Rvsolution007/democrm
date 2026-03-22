@extends('admin.layouts.app')

@section('title', 'Roles')
@section('breadcrumb', 'Roles')

@section('content')
    <style>
        .global-checkbox {
            accent-color: #2563eb !important;
        }

        .global-checkbox:checked {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
        }
    </style>
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Roles & Permissions</h1>
                <p class="page-description">Manage user roles and their permissions</p>
            </div>
            <div class="page-actions">
                @if(can('roles.write'))
                    <button class="btn btn-primary" onclick="openAddRole()"><i data-lucide="plus"
                            style="width:16px;height:16px"></i> Add Role</button>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:8px;margin-bottom:20px">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:8px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4" id="roles-grid">
        @forelse($roles as $role)
            <div class="card">
                <div class="card-content">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="stats-card-icon"><i data-lucide="shield" style="width:20px;height:20px"></i></div>
                            <div>
                                <h3 class="font-semibold">{{ $role->name }}</h3>
                                <p class="text-xs text-muted">{{ $role->users_count }} users</p>
                            </div>
                        </div>
                        <div class="table-actions">
                            <button class="btn btn-ghost btn-icon btn-sm" title="Edit" onclick='openEditRole(@json($role))'>
                                <i data-lucide="edit" style="width:16px;height:16px"></i>
                            </button>
                            @if($role->users_count == 0)
                                <button type="button" onclick="ajaxDelete('{{ route('admin.roles.destroy', $role->id) }}')" class="btn btn-ghost btn-icon btn-sm" style="color:var(--destructive)"
                                        title="Delete">
                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                    </button>
                            @endif
                        </div>
                    </div>
                    <p class="text-sm text-muted mb-3">{{ $role->description ?? 'No description' }}</p>
                    <div class="flex flex-wrap gap-1">
                        @if(is_array($role->permissions))
                            @foreach($role->permissions as $perm)
                                <span class="badge badge-secondary text-xs">{{ $perm }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column:span 2">
                <div class="card-content" style="padding:60px;text-align:center">
                    <i data-lucide="shield" style="width:48px;height:48px;color:#ccc;margin-bottom:16px"></i>
                    <h3 style="font-weight:600;margin-bottom:8px;color:#333">No Roles Yet</h3>
                    <p style="color:#888;font-size:14px">Create your first role to get started.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Add/Edit Role Drawer -->
    <div id="drawer-overlay" class="overlay" onclick="closeDrawer('role-drawer')"></div>
    <div id="role-drawer" class="drawer drawer-lg">
        <div class="drawer-header">
            <div>
                <h3 class="drawer-title" id="role-drawer-title">Add New Role</h3>
            </div>
            <button class="drawer-close" onclick="closeDrawer('role-drawer')"><i data-lucide="x"></i></button>
        </div>
        <div class="drawer-body">
            <form id="role-form" method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <input type="hidden" name="_method" id="role-form-method" value="POST">
                <div class="form-group">
                    <label class="form-label required">Role Name</label>
                    <input type="text" name="name" id="role-name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="role-description" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Permissions</label>

                    <!-- Sales Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:6px;margin-bottom:8px">
                            <span
                                style="font-weight:600;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:0.03em">Sales</span>
                            <label class="form-check" style="margin:0"><input type="checkbox"
                                    class="form-check-input section-toggle" data-section="sales"
                                    onchange="toggleSection(this, 'sales')"> <span class="form-check-label"
                                    style="font-size:12px">Select All</span></label>
                        </div>
                        <div style="padding-left:8px">
                            <table style="width:100%;font-size:13px;border-collapse:collapse">
                                <thead>
                                    <tr style="border-bottom:1px solid #e0e0e0">
                                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:#666">Module</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Read</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Write</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Delete</th>
                                        <th
                                            style="text-align:center;padding:8px 4px;font-weight:600;color:#2563eb;width:70px">
                                            Global</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Leads</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="leads.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="leads.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="leads.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="sales"
                                                name="permissions[]" value="leads.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Clients</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="clients.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="clients.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="clients.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="sales"
                                                name="permissions[]" value="clients.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Quotes</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="quotes.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="quotes.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="quotes.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="sales"
                                                name="permissions[]" value="quotes.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Invoices</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="invoices.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="invoices.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="invoices.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="sales"
                                                name="permissions[]" value="invoices.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Payments</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="payments.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="payments.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="payments.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="sales"
                                                name="permissions[]" value="payments.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Follow-ups</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="followups.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="followups.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="sales"
                                                name="permissions[]" value="followups.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="sales"
                                                name="permissions[]" value="followups.global"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- WhatsApp Bulk Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:6px;margin-bottom:8px">
                            <span
                                style="font-weight:600;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:0.03em">WhatsApp Bulk</span>
                            <label class="form-check" style="margin:0"><input type="checkbox"
                                    class="form-check-input section-toggle" data-section="whatsapp"
                                    onchange="toggleSection(this, 'whatsapp')"> <span class="form-check-label"
                                    style="font-size:12px">Select All</span></label>
                        </div>
                        <div style="padding-left:8px">
                            <table style="width:100%;font-size:13px;border-collapse:collapse">
                                <thead>
                                    <tr style="border-bottom:1px solid #e0e0e0">
                                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:#666">Module</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">Read</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">Write</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">Delete</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#2563eb;width:70px">Global</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">WhatsApp Connect</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-connect.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-connect.write"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Chrome Extension</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-extension.read"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Bulk Sender</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-campaigns.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-campaigns.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-campaigns.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Templates</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="edit_whatsapp" name="permissions[]" value="whatsapp-templates.read" {{ in_array('whatsapp-templates.read', $role->permissions ?? []) ? 'checked' : '' }}></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="edit_whatsapp" name="permissions[]" value="whatsapp-templates.write" {{ in_array('whatsapp-templates.write', $role->permissions ?? []) ? 'checked' : '' }}></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="edit_whatsapp" name="permissions[]" value="whatsapp-templates.delete" {{ in_array('whatsapp-templates.delete', $role->permissions ?? []) ? 'checked' : '' }}></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="edit_whatsapp" name="permissions[]" value="whatsapp-templates.global" {{ in_array('whatsapp-templates.global', $role->permissions ?? []) ? 'checked' : '' }}></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Auto-Reply Rules</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-auto-reply.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-auto-reply.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-auto-reply.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Reply Analytics</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox" class="form-check-input perm-checkbox" data-section="whatsapp" name="permissions[]" value="whatsapp-analytics.read"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Catalog Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:6px;margin-bottom:8px">
                            <span
                                style="font-weight:600;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:0.03em">Catalog</span>
                            <label class="form-check" style="margin:0"><input type="checkbox"
                                    class="form-check-input section-toggle" data-section="catalog"
                                    onchange="toggleSection(this, 'catalog')"> <span class="form-check-label"
                                    style="font-size:12px">Select All</span></label>
                        </div>
                        <div style="padding-left:8px">
                            <table style="width:100%;font-size:13px;border-collapse:collapse">
                                <thead>
                                    <tr style="border-bottom:1px solid #e0e0e0">
                                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:#666">Module</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Read</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Write</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Delete</th>
                                        <th
                                            style="text-align:center;padding:8px 4px;font-weight:600;color:#2563eb;width:70px">
                                            Global</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Products</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="products.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="products.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="products.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="catalog" name="permissions[]" value="products.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Categories</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="categories.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="categories.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="categories.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="catalog" name="permissions[]" value="categories.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Vendors</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="vendors.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="vendors.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="vendors.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="catalog" name="permissions[]" value="vendors.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Purchases</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="purchases.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="purchases.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="purchases.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="catalog" name="permissions[]" value="purchases.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Purchase Payments</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="purchase-payments.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="purchase-payments.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="catalog"
                                                name="permissions[]" value="purchase-payments.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="catalog" name="permissions[]" value="purchase-payments.global"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Production Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:6px;margin-bottom:8px">
                            <span
                                style="font-weight:600;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:0.03em">Production</span>
                            <label class="form-check" style="margin:0"><input type="checkbox"
                                    class="form-check-input section-toggle" data-section="production"
                                    onchange="toggleSection(this, 'production')"> <span class="form-check-label"
                                    style="font-size:12px">Select All</span></label>
                        </div>
                        <div style="padding-left:8px">
                            <table style="width:100%;font-size:13px;border-collapse:collapse">
                                <thead>
                                    <tr style="border-bottom:1px solid #e0e0e0">
                                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:#666">Module</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Read</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Write</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Delete</th>
                                        <th
                                            style="text-align:center;padding:8px 4px;font-weight:600;color:#2563eb;width:70px">
                                            Global</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Projects</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="projects.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="projects.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="projects.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="production" name="permissions[]" value="projects.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Tasks</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="tasks.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="tasks.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="tasks.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="production" name="permissions[]" value="tasks.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Micro Tasks</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="micro-tasks.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="micro-tasks.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="micro-tasks.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="production" name="permissions[]" value="micro-tasks.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Micro Task Follow-ups</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="task-followups.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="task-followups.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="task-followups.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox"
                                                data-section="production" name="permissions[]" value="task-followups.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Service Templates</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="service-templates.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="service-templates.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="production"
                                                name="permissions[]" value="service-templates.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Team Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:6px;margin-bottom:8px">
                            <span
                                style="font-weight:600;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:0.03em">Team</span>
                            <label class="form-check" style="margin:0"><input type="checkbox"
                                    class="form-check-input section-toggle" data-section="team"
                                    onchange="toggleSection(this, 'team')"> <span class="form-check-label"
                                    style="font-size:12px">Select All</span></label>
                        </div>
                        <div style="padding-left:8px">
                            <table style="width:100%;font-size:13px;border-collapse:collapse">
                                <thead>
                                    <tr style="border-bottom:1px solid #e0e0e0">
                                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:#666">Module</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Read</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Write</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px">
                                            Delete</th>
                                        <th
                                            style="text-align:center;padding:8px 4px;font-weight:600;color:#2563eb;width:70px">
                                            Global</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Users</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="users.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="users.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="users.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="team"
                                                name="permissions[]" value="users.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Roles</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="roles.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="roles.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="roles.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="team"
                                                name="permissions[]" value="roles.global"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Activities</td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="activities.read"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="activities.write"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="team"
                                                name="permissions[]" value="activities.delete"></td>
                                        <td style="text-align:center;padding:8px 4px"><input type="checkbox"
                                                class="form-check-input perm-checkbox global-checkbox" data-section="team"
                                                name="permissions[]" value="activities.global"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Analytics Section -->
                    <div style="margin-bottom:16px">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8f9fa;border-radius:6px;margin-bottom:8px">
                            <span
                                style="font-weight:600;font-size:13px;color:#555;text-transform:uppercase;letter-spacing:0.03em">Analytics</span>
                            <label class="form-check" style="margin:0"><input type="checkbox"
                                    class="form-check-input section-toggle" data-section="analytics"
                                    onchange="toggleSection(this, 'analytics')"> <span class="form-check-label"
                                    style="font-size:12px">Select All</span></label>
                        </div>
                        <div style="padding-left:8px">
                            <table style="width:100%;font-size:13px;border-collapse:collapse">
                                <thead>
                                    <tr style="border-bottom:1px solid #e0e0e0">
                                        <th style="text-align:left;padding:8px 4px;font-weight:600;color:#666">Module</th>
                                        <th style="text-align:center;padding:8px 4px;font-weight:600;color:#666;width:70px"
                                            colspan="3">Access</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Reports</td>
                                        <td style="text-align:center;padding:8px 4px" colspan="3"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="analytics"
                                                name="permissions[]" value="reports.read"></td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f0f0f0">
                                        <td style="padding:8px 4px">Settings</td>
                                        <td style="text-align:center;padding:8px 4px" colspan="3"><input type="checkbox"
                                                class="form-check-input perm-checkbox" data-section="analytics"
                                                name="permissions[]" value="settings.manage"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Assign Products</label>
                    <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--muted);border-bottom:1px solid var(--border)">
                            <label class="form-check" style="margin:0">
                                <input type="checkbox" class="form-check-input" id="role-select-all-products"
                                    onchange="toggleAllRoleProducts(this)">
                                <span class="form-check-label" style="font-weight:600;font-size:13px">Select All</span>
                            </label>
                            <span id="role-product-count" style="font-size:12px;color:#888;font-weight:500">0
                                selected</span>
                        </div>
                        <div style="padding:8px 14px;border-bottom:1px solid var(--border)">
                            <div style="display:flex;align-items:center;gap:8px">
                                <i data-lucide="search" style="width:14px;height:14px;color:#aaa"></i>
                                <input type="text" id="role-product-search" placeholder="Search products..."
                                    style="border:none;outline:none;width:100%;font-size:13px;background:transparent;padding:4px 0">
                            </div>
                        </div>
                        <div id="role-product-list" style="max-height:200px;overflow-y:auto;padding:6px 14px">
                            @forelse($products as $product)
                                <label class="form-check"
                                    style="display:flex;align-items:center;padding:6px 0;border-bottom:1px solid #f0f0f0">
                                    <input type="checkbox" class="form-check-input role-product-cb" name="role_products[]"
                                        value="{{ $product->id }}" onchange="updateRoleProductCount()">
                                    <span class="form-check-label" style="font-size:13px">{{ $product->name }} <span
                                            style="color:#999;font-size:11px;margin-left:4px">{{ $product->sku }}</span></span>
                                </label>
                            @empty
                                <p style="color:#999;font-size:13px;text-align:center;padding:16px 0">No products available</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="drawer-footer">
            <button class="btn btn-outline" onclick="closeDrawer('role-drawer')">Cancel</button>
            <button class="btn btn-primary" onclick="document.getElementById('role-form').submit()">Save Role</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openAddRole() {
            document.getElementById('role-drawer-title').textContent = 'Add New Role';
            document.getElementById('role-form').action = '{{ route("admin.roles.store") }}';
            document.getElementById('role-form-method').value = 'POST';
            document.getElementById('role-name').value = '';
            document.getElementById('role-description').value = '';
            // Uncheck all permissions
            document.querySelectorAll('.perm-checkbox, .section-toggle').forEach(cb => cb.checked = false);
            // Uncheck all products
            document.querySelectorAll('.role-product-cb').forEach(cb => cb.checked = false);
            document.getElementById('role-select-all-products').checked = false;
            updateRoleProductCount();
            openDrawer('role-drawer');
        }

        function openEditRole(role) {
            document.getElementById('role-drawer-title').textContent = 'Edit Role';
            document.getElementById('role-form').action = '{{ url("admin/roles") }}/' + role.id;
            document.getElementById('role-form-method').value = 'PUT';
            document.getElementById('role-name').value = role.name;
            document.getElementById('role-description').value = role.description || '';

            // Uncheck all first
            document.querySelectorAll('.perm-checkbox, .section-toggle').forEach(cb => cb.checked = false);

            // Check saved permissions
            const perms = role.permissions || [];
            perms.forEach(p => {
                const cb = document.querySelector(`.perm-checkbox[value="${p}"]`);
                if (cb) cb.checked = true;
            });

            // Update section toggles
            document.querySelectorAll('.section-toggle').forEach(toggle => {
                const section = toggle.dataset.section;
                const all = document.querySelectorAll(`.perm-checkbox[data-section="${section}"]`);
                toggle.checked = all.length > 0 && [...all].every(cb => cb.checked);
            });

            openDrawer('role-drawer');
        }

        function toggleSection(toggle, section) {
            const checkboxes = document.querySelectorAll(`.perm-checkbox[data-section="${section}"]`);
            checkboxes.forEach(cb => cb.checked = toggle.checked);
        }

        // Update "Select All" state when individual checkboxes change
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('perm-checkbox')) {
                const section = e.target.dataset.section;
                const all = document.querySelectorAll(`.perm-checkbox[data-section="${section}"]`);
                const toggle = document.querySelector(`.section-toggle[data-section="${section}"]`);
                if (toggle) {
                    toggle.checked = [...all].every(cb => cb.checked);
                }
            }
        });

        // Product functions for roles
        document.getElementById('role-product-search').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#role-product-list .form-check').forEach(el => {
                el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        function toggleAllRoleProducts(toggle) {
            document.querySelectorAll('.role-product-cb').forEach(cb => {
                if (cb.closest('.form-check').style.display !== 'none') cb.checked = toggle.checked;
            });
            updateRoleProductCount();
        }

        function updateRoleProductCount() {
            const checked = document.querySelectorAll('.role-product-cb:checked').length;
            const total = document.querySelectorAll('.role-product-cb').length;
            document.getElementById('role-product-count').textContent = `${checked} selected`;
            document.getElementById('role-select-all-products').checked = checked === total && total > 0;
        }
    </script>
@endpush