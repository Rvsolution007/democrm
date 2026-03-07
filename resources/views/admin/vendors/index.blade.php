@extends('admin.layouts.app')

@section('title', 'Vendors')
@section('breadcrumb', 'Vendors')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Vendors</h1>
                <p class="page-description">Manage your vendors and suppliers</p>
            </div>
            <div class="page-actions">
                @if(can('projects.global') || can('quotes.global'))
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i data-lucide="plus" style="width:16px;height:16px"></i> Add Vendor
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="table-container">
        <div class="table-wrapper">
            <table class="table" id="vendors-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Purchase Section</th>
                        <th>Status</th>
                        <th style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $vendor->name }}</p>
                            </td>
                            <td>{{ $vendor->phone ?? 'N/A' }}</td>
                            <td>{{ $vendor->email ?? 'N/A' }}</td>
                            <td>{{ Str::limit($vendor->address, 30, '...') }}</td>
                            <td>
                                @if($vendor->has_purchase_section)
                                    <span class="badge badge-primary" style="font-size:11px">
                                        <i data-lucide="check"
                                            style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:2px"></i>
                                        Enabled
                                    </span>
                                @else
                                    <span style="color:#94a3b8;font-size:13px">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $vendor->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($vendor->status) }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:8px">
                                    @if(can('projects.global') || can('quotes.global'))
                                        <button class="btn btn-ghost btn-icon btn-sm edit-vendor-btn" title="Edit"
                                            data-id="{{ $vendor->id }}" data-name="{{ $vendor->name }}"
                                            data-phone="{{ $vendor->phone }}" data-email="{{ $vendor->email }}"
                                            data-address="{{ $vendor->address }}" data-status="{{ $vendor->status }}"
                                            data-purchase-section="{{ $vendor->has_purchase_section ? '1' : '0' }}">
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                        <form action="{{ route('admin.vendors.destroy', $vendor->id) }}" method="POST"
                                            style="display:inline;margin:0"
                                            onsubmit="return confirm('Are you sure you want to delete this vendor?')">
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
                            <td colspan="7" class="text-center py-8 text-muted">No vendors found. Click "Add Vendor" to create
                                one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            {{ $vendors->links() }}
        </div>
    </div>

    <!-- Add/Edit Vendor Modal -->
    <div id="vendor-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:550px;max-height:92vh;overflow-y:auto">
            <div
                style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="modal-title" style="margin:0">Add New Vendor</h3>
                <button onclick="closeModal()"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px">&times;</button>
            </div>
            <form id="vendor-form" method="POST" action="{{ route('admin.vendors.store') }}">
                @csrf
                <input type="hidden" id="form-method" name="_method" value="">
                <div style="padding:20px">
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Vendor Name *</label>
                        <input type="text" class="form-input" name="name" id="vend-name" required
                            placeholder="Enter vendor name"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Phone</label>
                        <input type="text" class="form-input" name="phone" id="vend-phone" placeholder="Enter phone number"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Email</label>
                        <input type="email" class="form-input" name="email" id="vend-email"
                            placeholder="Enter email address"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Address</label>
                        <textarea class="form-textarea" name="address" id="vend-address" rows="3"
                            placeholder="Enter address"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></textarea>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Status *</label>
                        <select class="form-select" name="status" id="vend-status" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none">
                            <input type="checkbox" name="has_purchase_section" id="vend-purchase-section" value="1"
                                style="width:18px;height:18px;accent-color:#3b82f6;cursor:pointer"
                                onchange="toggleCustomFieldsSection()">
                            <span style="font-weight:500">Show as Purchase Section</span>
                        </label>
                        <p style="margin:4px 0 0 28px;font-size:12px;color:#94a3b8">When enabled, this vendor will have its
                            own dedicated tab on the Purchases page.</p>
                    </div>

                    <!-- Custom Fields Builder (only visible when checkbox ticked + editing existing vendor) -->
                    <div id="custom-fields-section"
                        style="display:none;margin-top:8px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                            <h4 style="margin:0;font-size:14px;font-weight:600;color:#334155">
                                <i data-lucide="settings-2"
                                    style="width:16px;height:16px;display:inline;vertical-align:middle;margin-right:6px"></i>
                                Custom Fields
                            </h4>
                            <span id="custom-fields-count" style="font-size:12px;color:#94a3b8">0 fields</span>
                        </div>

                        <!-- Existing Fields List -->
                        <div id="custom-fields-list" style="margin-bottom:14px"></div>

                        <!-- Add Field Form -->
                        <div style="border-top:1px solid #e2e8f0;padding-top:14px;margin-top:6px">
                            <p style="font-size:13px;font-weight:600;color:#475569;margin:0 0 10px 0">Add New Field</p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <input type="text" id="new-field-name" placeholder="Field Name"
                                    style="flex:1;min-width:130px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px">
                                <select id="new-field-type"
                                    style="width:100px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px"
                                    onchange="toggleOptionsField()">
                                    <option value="text">Text</option>
                                    <option value="select">Select</option>
                                    <option value="date">Date</option>
                                </select>
                                <button type="button" onclick="addCustomField()"
                                    style="padding:7px 14px;background:#3b82f6;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;white-space:nowrap;transition:background 0.15s"
                                    onmouseover="this.style.background='#2563eb'"
                                    onmouseout="this.style.background='#3b82f6'">
                                    <i data-lucide="plus"
                                        style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:2px"></i>
                                    Add
                                </button>
                            </div>
                            <div id="options-field-wrapper" style="display:none;margin-top:8px">
                                <input type="text" id="new-field-options"
                                    placeholder="Options (comma separated, e.g. Facebook, Instagram, Google)"
                                    style="width:100%;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px">
                            </div>
                        </div>
                    </div>
                </div>
                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeModal()"
                        style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding:8px 16px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer">Save
                        Vendor</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var currentEditVendorId = null;
        var csrfToken = '{{ csrf_token() }}';

        function openAddModal() {
            currentEditVendorId = null;
            document.getElementById('modal-title').textContent = 'Add New Vendor';
            document.getElementById('vendor-form').action = '{{ route("admin.vendors.store") }}';
            document.getElementById('form-method').value = '';
            document.getElementById('vendor-form').reset();
            document.getElementById('vend-status').value = 'active';
            document.getElementById('custom-fields-section').style.display = 'none';
            document.getElementById('custom-fields-list').innerHTML = '';
            document.getElementById('vendor-modal').style.display = 'flex';
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.edit-vendor-btn');
            if (!btn) return;

            currentEditVendorId = btn.dataset.id;
            document.getElementById('modal-title').textContent = 'Edit Vendor';
            document.getElementById('vendor-form').action = '{{ url("admin/vendors") }}/' + btn.dataset.id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('vend-name').value = btn.dataset.name;
            document.getElementById('vend-phone').value = btn.dataset.phone;
            document.getElementById('vend-email').value = btn.dataset.email;
            document.getElementById('vend-address').value = btn.dataset.address;
            document.getElementById('vend-status').value = btn.dataset.status;
            document.getElementById('vend-purchase-section').checked = btn.dataset.purchaseSection === '1';
            toggleCustomFieldsSection();
            document.getElementById('vendor-modal').style.display = 'flex';
        });

        function toggleCustomFieldsSection() {
            var checked = document.getElementById('vend-purchase-section').checked;
            var section = document.getElementById('custom-fields-section');
            if (checked && currentEditVendorId) {
                section.style.display = 'block';
                loadCustomFields();
            } else if (checked && !currentEditVendorId) {
                // For NEW vendors, show section but with a note
                section.style.display = 'block';
                document.getElementById('custom-fields-list').innerHTML =
                    '<p style="font-size:12px;color:#94a3b8;font-style:italic;margin:0">Save the vendor first, then edit to add custom fields.</p>';
                document.getElementById('custom-fields-count').textContent = '';
            } else {
                section.style.display = 'none';
            }
            // Re-init lucide icons
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function toggleOptionsField() {
            var type = document.getElementById('new-field-type').value;
            document.getElementById('options-field-wrapper').style.display = type === 'select' ? 'block' : 'none';
        }

        function loadCustomFields() {
            if (!currentEditVendorId) return;
            fetch('{{ url("admin/vendors") }}/' + currentEditVendorId + '/custom-fields', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    renderCustomFieldsList(data.fields);
                })
                .catch(err => console.error('Error loading fields:', err));
        }

        function renderCustomFieldsList(fields) {
            var list = document.getElementById('custom-fields-list');
            document.getElementById('custom-fields-count').textContent = fields.length + ' field' + (fields.length !== 1 ? 's' : '');

            if (!fields || fields.length === 0) {
                list.innerHTML = '<p style="font-size:12px;color:#94a3b8;font-style:italic;margin:0">No custom fields yet. Add one below.</p>';
                return;
            }

            var html = '';
            fields.forEach(function (f) {
                var typeLabel = f.field_type === 'select' ? 'Select' : (f.field_type === 'date' ? 'Date' : 'Text');
                var typeBadge = '';
                if (f.field_type === 'select') {
                    typeBadge = '<span style="padding:2px 8px;background:#eff6ff;color:#3b82f6;border-radius:4px;font-size:11px;font-weight:500">Select</span>';
                } else if (f.field_type === 'date') {
                    typeBadge = '<span style="padding:2px 8px;background:#fef3c7;color:#d97706;border-radius:4px;font-size:11px;font-weight:500">Date</span>';
                } else {
                    typeBadge = '<span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:11px;font-weight:500">Text</span>';
                }
                var optionsStr = '';
                if (f.field_type === 'select' && f.field_options && f.field_options.length > 0) {
                    optionsStr = '<span style="font-size:11px;color:#94a3b8;margin-left:6px">(' + f.field_options.join(', ') + ')</span>';
                }
                html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:white;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:6px">';
                html += '<div style="display:flex;align-items:center;gap:8px">';
                html += '<span style="font-size:13px;font-weight:500;color:#334155">' + escHtml(f.field_name) + '</span>';
                html += typeBadge + optionsStr;
                html += '</div>';
                html += '<button type="button" onclick="deleteCustomField(' + f.id + ')" style="width:26px;height:26px;border-radius:6px;background:#fef2f2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;transition:all 0.15s" title="Delete"'
                    + ' onmouseover="this.style.background=\'#fee2e2\'" onmouseout="this.style.background=\'#fef2f2\'">'
                    + '<i data-lucide="trash-2" style="width:13px;height:13px"></i></button>';
                html += '</div>';
            });
            list.innerHTML = html;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function addCustomField() {
            if (!currentEditVendorId) {
                alert('Please save the vendor first, then edit to add custom fields.');
                return;
            }
            var name = document.getElementById('new-field-name').value.trim();
            var type = document.getElementById('new-field-type').value;
            var options = document.getElementById('new-field-options').value.trim();

            if (!name) { alert('Please enter a field name'); return; }
            if (type === 'select' && !options) { alert('Please enter options for select field'); return; }

            var formData = new FormData();
            formData.append('vendor_id', currentEditVendorId);
            formData.append('field_name', name);
            formData.append('field_type', type);
            if (type === 'select') formData.append('field_options', options);

            fetch('{{ route("admin.vendors.custom-fields.store") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('new-field-name').value = '';
                        document.getElementById('new-field-options').value = '';
                        document.getElementById('new-field-type').value = 'text';
                        document.getElementById('options-field-wrapper').style.display = 'none';
                        loadCustomFields();
                    }
                })
                .catch(err => console.error('Error adding field:', err));
        }

        function deleteCustomField(id) {
            if (!confirm('Delete this custom field? All purchase data for this field will be lost.')) return;

            fetch('{{ url("admin/vendors/custom-fields") }}/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) loadCustomFields();
                })
                .catch(err => console.error('Error deleting field:', err));
        }

        function escHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function closeModal() {
            document.getElementById('vendor-modal').style.display = 'none';
            document.getElementById('vendor-form').reset();
            currentEditVendorId = null;
        }

        document.getElementById('vendor-modal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
@endpush