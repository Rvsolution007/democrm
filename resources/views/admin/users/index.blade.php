@extends('admin.layouts.app')

@section('title', 'Users')
@section('breadcrumb', 'Users')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Users</h1>
                <p class="page-description">Manage team members and their access</p>
            </div>
            <div class="page-actions">
                @if(can('users.write'))
                    <button class="btn btn-primary" onclick="openAddUser()"><i data-lucide="plus"
                            style="width:16px;height:16px"></i> Add User</button>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
            <i data-lucide="check-circle" style="width:18px;height:18px"></i> {{ session('success') }}
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

    <div class="table-container">
        <div class="table-toolbar">
            <div class="table-search">
                <i data-lucide="search" class="table-search-icon" style="width:16px;height:16px"></i>
                <input type="text" class="table-search-input" id="users-search" placeholder="Search users...">
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table" id="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <div
                                        style="width:38px;height:38px;min-width:38px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:13px">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium" style="margin:0">{{ $user->name }}</p>
                                        <p class="text-xs text-muted" style="margin:0">Added
                                            {{ $user->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-sm">
                                    <i data-lucide="mail"
                                        style="width:13px;height:13px;display:inline;vertical-align:middle;margin-right:4px;color:#888"></i>
                                    {{ $user->email }}
                                </span>
                            </td>
                            <td>
                                <span class="text-sm">
                                    <i data-lucide="phone"
                                        style="width:13px;height:13px;display:inline;vertical-align:middle;margin-right:4px;color:#888"></i>
                                    {{ $user->phone ?? '—' }}
                                </span>
                            </td>
                            <td><span class="badge badge-primary">{{ $user->role->name ?? '—' }}</span></td>
                            <td>
                                <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                                    <span
                                        style="display:inline-block;width:7px;height:7px;border-radius:50%;background:{{ $user->status === 'active' ? '#22c55e' : '#94a3b8' }};margin-right:5px"></span>
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    @if(can('users.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" title="Edit"
                                            onclick="openEditUser({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->phone }}', {{ $user->role_id ?? 'null' }}, '{{ $user->status }}')">
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                    @if(can('users.delete'))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                                            style="display:inline"
                                            onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-icon btn-sm"
                                                style="color:var(--destructive)" title="Delete">
                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:60px;color:#888">
                                <i data-lucide="users" style="width:40px;height:40px;color:#ccc;margin-bottom:12px"></i>
                                <p style="font-weight:600;color:#555;margin-bottom:4px">No users found</p>
                                <p style="font-size:13px">Click "Add User" to create your first user.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing {{ $users->count() }} entries</span>
        </div>
    </div>

    <!-- Add/Edit User Drawer -->
    <div id="drawer-overlay" class="overlay" onclick="closeDrawer('user-drawer')"></div>
    <div id="user-drawer" class="drawer drawer-lg">
        <div class="drawer-header">
            <div>
                <h3 class="drawer-title" id="drawer-title">Add New User</h3>
            </div>
            <button class="drawer-close" onclick="closeDrawer('user-drawer')"><i data-lucide="x"></i></button>
        </div>
        <div class="drawer-body">
            <form id="user-form" method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">

                <div class="form-group">
                    <label class="form-label required">Full Name</label>
                    <input type="text" name="name" id="input-name" class="form-input" placeholder="Enter full name"
                        required>
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" id="input-email" class="form-input" placeholder="user@example.com"
                            required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Phone</label>
                        <input type="tel" name="phone" id="input-phone" class="form-input" placeholder="9876543210"
                            required>
                    </div>
                </div>

                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label" id="password-label">Password <span style="color:red">*</span></label>
                        <input type="password" name="password" id="input-password" class="form-input"
                            placeholder="Min 6 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Role</label>
                        <select name="role_id" id="input-role" class="form-select" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="input-status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Assign Products</label>
                    <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden">
                        <div
                            style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--muted);border-bottom:1px solid var(--border)">
                            <label class="form-check" style="margin:0">
                                <input type="checkbox" class="form-check-input" id="select-all-products"
                                    onchange="toggleAllProducts(this)">
                                <span class="form-check-label" style="font-weight:600;font-size:13px">Select All</span>
                            </label>
                            <span id="product-count" style="font-size:12px;color:#888;font-weight:500">0 selected</span>
                        </div>
                        <div style="padding:8px 14px;border-bottom:1px solid var(--border)">
                            <div style="display:flex;align-items:center;gap:8px">
                                <i data-lucide="search" style="width:14px;height:14px;color:#aaa"></i>
                                <input type="text" id="product-search" placeholder="Search products..."
                                    style="border:none;outline:none;width:100%;font-size:13px;background:transparent;padding:4px 0">
                            </div>
                        </div>
                        <div id="product-list" style="max-height:200px;overflow-y:auto;padding:6px 14px">
                            @forelse($products as $product)
                                <label class="form-check"
                                    style="display:flex;align-items:center;padding:6px 0;border-bottom:1px solid #f0f0f0">
                                    <input type="checkbox" class="form-check-input product-cb" name="products[]"
                                        value="{{ $product->id }}" onchange="updateProductCount()">
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
            <button class="btn btn-outline" onclick="closeDrawer('user-drawer')">Cancel</button>
            <button class="btn btn-primary" onclick="document.getElementById('user-form').submit()">Save User</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initTableSearch('users-table', 'users-search');
            lucide.createIcons();
        });

        function openAddUser() {
            document.getElementById('drawer-title').textContent = 'Add New User';
            document.getElementById('user-form').action = '{{ route("admin.users.store") }}';
            document.getElementById('form-method').value = 'POST';
            document.getElementById('input-name').value = '';
            document.getElementById('input-email').value = '';
            document.getElementById('input-phone').value = '';
            document.getElementById('input-password').value = '';
            document.getElementById('input-password').required = true;
            document.getElementById('password-label').innerHTML = 'Password <span style="color:red">*</span>';
            document.getElementById('input-role').value = '';
            document.getElementById('input-status').value = 'active';
            document.querySelectorAll('.product-cb').forEach(cb => cb.checked = false);
            document.getElementById('select-all-products').checked = false;
            updateProductCount();
            openDrawer('user-drawer');
        }

        function openEditUser(id, name, email, phone, roleId, status) {
            document.getElementById('drawer-title').textContent = 'Edit User';
            document.getElementById('user-form').action = '{{ url("admin/users") }}/' + id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('input-name').value = name;
            document.getElementById('input-email').value = email;
            document.getElementById('input-phone').value = phone || '';
            document.getElementById('input-password').value = '';
            document.getElementById('input-password').required = false;
            document.getElementById('password-label').innerHTML = 'Password <span style="color:#888;font-size:12px">(leave blank to keep current)</span>';
            document.getElementById('input-role').value = roleId || '';
            document.getElementById('input-status').value = status || 'active';
            openDrawer('user-drawer');
        }

        // Product search
        document.getElementById('product-search').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#product-list .form-check').forEach(el => {
                el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        function toggleAllProducts(toggle) {
            document.querySelectorAll('.product-cb').forEach(cb => {
                if (cb.closest('.form-check').style.display !== 'none') cb.checked = toggle.checked;
            });
            updateProductCount();
        }

        function updateProductCount() {
            const checked = document.querySelectorAll('.product-cb:checked').length;
            const total = document.querySelectorAll('.product-cb').length;
            document.getElementById('product-count').textContent = `${checked} selected`;
            document.getElementById('select-all-products').checked = checked === total && total > 0;
        }
    </script>
@endpush