@extends('admin.layouts.app')

@section('title', 'Clients')
@section('breadcrumb', 'Clients')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Clients</h1>
                <p class="page-description">Manage your customers and their details</p>
            </div>
            <div class="page-actions">
                @if(can('clients.write'))
                    <button class="btn btn-primary" onclick="openDrawer('client-drawer')"><i data-lucide="plus"
                            style="width:16px;height:16px"></i> Add Client</button>
                @endif
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-toolbar">
            <form method="GET" action="{{ route('admin.clients.index') }}"
                style="display:flex;align-items:center;width:100%">
                <i data-lucide="search" class="table-search-icon" style="width:16px;height:16px"></i>
                <input type="text" name="search" class="table-search-input" id="clients-search"
                    value="{{ request('search') }}" placeholder="Search by name, phone, email..."
                    oninput="autoAjaxSearch(this.form)">
                @if(request('search'))
                    <a href="{{ route('admin.clients.index') }}" style="margin-left:8px;color:#999;text-decoration:none"
                        title="Clear Search">
                        <i data-lucide="x" style="width:16px;height:16px"></i>
                    </a>
                @endif
            </form>
        </div>
        <div class="table-wrapper">
            <table class="table" id="clients-table">
                <thead>
                    <tr>
                        <th class="sortable">Client Name</th>
                        <th>GST Number</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Business Category</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="clients-tbody">
                    @forelse($clients as $client)
                        <tr>
                            <td>
                                <div>
                                    <p class="font-medium">{{ $client->display_name }}</p>
                                    @if($client->business_name && $client->contact_name)
                                        <p class="text-xs text-muted">{{ $client->contact_name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td><span class="font-mono text-sm">{{ $client->gstin ?? '-' }}</span></td>
                            <td><span class="text-sm">{{ $client->phone ?? '-' }}</span></td>
                            <td><span class="text-sm">{{ $client->email ?? '-' }}</span></td>
                            <td>
                                <span class="badge badge-secondary" style="background:#f1f5f9;color:#475569;font-weight:500;">
                                    {{ $client->business_category ?: 'None' }}
                                </span>
                            </td>
                            <td><span
                                    class="badge badge-{{ $client->type === 'business' ? 'info' : 'secondary' }}">{{ ucfirst($client->type ?? 'Business') }}</span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('admin.clients.show', $client->id) }}"
                                        class="btn btn-ghost btn-icon btn-sm" title="View"><i data-lucide="eye"
                                            style="width:16px;height:16px"></i></a>
                                    @if(can('clients.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" title="Edit"
                                            onclick="editClient({{ $client->id }}, '{{ addslashes($client->contact_name) }}', '{{ addslashes($client->business_name) }}', '{{ addslashes($client->phone) }}', '{{ addslashes($client->email) }}', '{{ addslashes($client->gstin) }}', '{{ $client->type }}', '{{ addslashes($client->business_category) }}')"><i
                                                data-lucide="edit" style="width:16px;height:16px"></i></button>
                                    @endif
                                    @if(can('clients.delete'))
                                        <button class="btn btn-ghost btn-icon btn-sm" style="color:var(--destructive)"
                                            title="Delete" onclick="deleteClient({{ $client->id }})"><i data-lucide="trash-2"
                                                style="width:16px;height:16px"></i></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px 0;color:#999">
                                <i data-lucide="users" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                                <p style="margin:0;font-size:14px">No clients found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            <span>Showing {{ $clients->count() }} of {{ $clients->total() }} entries</span>
            {{ $clients->links() }}
        </div>
    </div>

    <!-- Client Drawer -->
    <div id="drawer-overlay" class="overlay" onclick="closeDrawer('client-drawer')"></div>
    <div id="client-drawer" class="drawer drawer-lg">
        <div class="drawer-header">
            <div>
                <h3 class="drawer-title">Add New Client</h3>
                <p class="drawer-description">Enter client details</p>
            </div>
            <button class="drawer-close" onclick="closeDrawer('client-drawer')"><i data-lucide="x"></i></button>
        </div>
        <div class="drawer-body">
            <form id="client-form" method="POST" action="{{ route('admin.clients.store') }}">
                @csrf
                <input type="hidden" name="_method" id="client-method" value="POST">
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label required">Contact Name</label>
                        <input type="text" name="contact_name" class="form-input" required
                            placeholder="Contact person name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Business Name</label>
                        <input type="text" name="business_name" class="form-input" placeholder="Business name">
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label required">Phone</label>
                        <input type="tel" name="phone" class="form-input" required placeholder="10-digit mobile">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="email@example.com">
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">GST Number</label>
                        <input type="text" name="gstin" class="form-input" placeholder="22AAAAA0000A1Z5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Business Category</label>
                        <select name="business_category" class="form-select">
                            <option value="">Select Category</option>
                            @foreach(\App\Models\Client::BUSINESS_CATEGORIES as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label required">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="business">Business</option>
                            <option value="individual">Individual</option>
                        </select>
                    </div>
                </div>

                <!-- Billing Address -->
                <div style="margin-top:20px;margin-bottom:12px">
                    <h4 style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:8px">Billing Address</h4>
                    <div class="form-group" style="margin-bottom:12px">
                        <input type="text" name="billing_address[street]" class="form-input" placeholder="Street Address">
                    </div>
                    <div class="form-row form-row-2" style="margin-bottom:12px">
                        <div class="form-group">
                            <input type="text" name="billing_address[city]" class="form-input" placeholder="City">
                        </div>
                        <div class="form-group">
                            <input type="text" name="billing_address[state]" class="form-input" placeholder="State">
                        </div>
                    </div>
                    <div class="form-row form-row-2">
                        <div class="form-group">
                            <input type="text" name="billing_address[pincode]" class="form-input" placeholder="Pincode">
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div style="margin-top:20px;margin-bottom:12px">
                    <h4 style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:8px">Shipping Address</h4>
                    <label
                        style="display:flex;align-items:center;gap:8px;font-size:13px;color:#666;margin-bottom:12px;cursor:pointer">
                        <input type="checkbox" id="same-as-billing" onchange="copyBillingToShipping(this.checked)"> Same as
                        Billing Address
                    </label>

                    <div id="shipping-address-fields"
                        style="transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out; max-height: 500px; opacity: 1; overflow: hidden;">
                        <div class="form-group" style="margin-bottom:12px">
                            <input type="text" name="shipping_address[street]" class="form-input"
                                placeholder="Street Address">
                        </div>
                        <div class="form-row form-row-2" style="margin-bottom:12px">
                            <div class="form-group">
                                <input type="text" name="shipping_address[city]" class="form-input" placeholder="City">
                            </div>
                            <div class="form-group">
                                <input type="text" name="shipping_address[state]" class="form-input" placeholder="State">
                            </div>
                        </div>
                        <div class="form-row form-row-2">
                            <div class="form-group">
                                <input type="text" name="shipping_address[pincode]" class="form-input"
                                    placeholder="Pincode">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="drawer-footer">
            <button class="btn btn-outline" onclick="closeDrawer('client-drawer')">Cancel</button>
            <button class="btn btn-primary" onclick="document.getElementById('client-form').submit()">Save Client</button>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Client search functionality (now relies primarily on server-side search for pagination)
            // But we keep this for local filtering on the current page if needed
            const searchInput = document.getElementById('clients-search');
            let timeout = null;
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    // We can also let the user press Enter, or auto-submit after typing
                    // but the standard form will handle Enter.
                    // This local JS filter works instantly on the current page before form submission:
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#clients-tbody tr');
                    let visibleCount = 0;
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        const match = text.includes(filter);
                        row.style.display = match ? '' : 'none';
                        if (match) visibleCount++;
                    });
                });
            }
        });

        function editClient(id, contact_name, business_name, phone, email, gstin, type, business_category, billing, shipping) {
            const drawerTitle = document.querySelector('#client-drawer .drawer-title');
            if (drawerTitle) drawerTitle.textContent = 'Edit Client';

            const form = document.getElementById('client-form');
            form.action = `/admin/clients/${id}`;
            document.getElementById('client-method').value = 'PUT';

            form.querySelector('input[name="contact_name"]').value = contact_name || '';
            form.querySelector('input[name="business_name"]').value = business_name || '';
            form.querySelector('input[name="phone"]').value = phone || '';
            form.querySelector('input[name="email"]').value = email || '';
            form.querySelector('input[name="gstin"]').value = gstin || '';
            form.querySelector('select[name="type"]').value = type || 'business';
            form.querySelector('select[name="business_category"]').value = business_category || '';

            // Billing
            let b = billing ? JSON.parse(billing) : {};
            form.querySelector('input[name="billing_address[street]"]').value = b.street || '';
            form.querySelector('input[name="billing_address[city]"]').value = b.city || '';
            form.querySelector('input[name="billing_address[state]"]').value = b.state || '';
            form.querySelector('input[name="billing_address[pincode]"]').value = b.pincode || '';

            // Shipping
            let s = shipping ? JSON.parse(shipping) : {};
            form.querySelector('input[name="shipping_address[street]"]').value = s.street || '';
            form.querySelector('input[name="shipping_address[city]"]').value = s.city || '';
            form.querySelector('input[name="shipping_address[state]"]').value = s.state || '';
            form.querySelector('input[name="shipping_address[pincode]"]').value = s.pincode || '';

            document.getElementById('same-as-billing').checked = false;
            let fieldsDiv = document.getElementById('shipping-address-fields');
            if (fieldsDiv) {
                fieldsDiv.style.maxHeight = '500px';
                fieldsDiv.style.opacity = '1';
                fieldsDiv.style.marginTop = '0';
            }

            openDrawer('client-drawer');
        }

        const addClientBtn = document.querySelector('button[onclick="openDrawer(\'client-drawer\')"]');
        if (addClientBtn) {
            const oldOnclick = addClientBtn.onclick;
            addClientBtn.onclick = function (e) {
                const drawerTitle = document.querySelector('#client-drawer .drawer-title');
                if (drawerTitle) drawerTitle.textContent = 'Add New Client';

                const form = document.getElementById('client-form');
                form.action = "{{ route('admin.clients.store') }}";
                document.getElementById('client-method').value = 'POST';
                form.reset();
                form.querySelector('select[name="type"]').value = 'business';
                form.querySelector('select[name="business_category"]').value = '';

                let fieldsDiv = document.getElementById('shipping-address-fields');
                if (fieldsDiv) {
                    fieldsDiv.style.maxHeight = '500px';
                    fieldsDiv.style.opacity = '1';
                }

                if (oldOnclick) oldOnclick.call(this, e);
            }
        }

        function deleteClient(id) {
            if (confirm('Are you sure you want to delete this client?')) {
                const form = document.createElement('form');
                form.action = `/admin/clients/${id}`;
                form.method = 'POST';
                form.style.display = 'none';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                document.body.appendChild(form);
                form.submit();
            }
        }
        function copyBillingToShipping(checked) {
            const form = document.getElementById('client-form');
            const fieldsDiv = document.getElementById('shipping-address-fields');

            if (checked) {
                form.querySelector('input[name="shipping_address[street]"]').value = form.querySelector('input[name="billing_address[street]"]').value;
                form.querySelector('input[name="shipping_address[city]"]').value = form.querySelector('input[name="billing_address[city]"]').value;
                form.querySelector('input[name="shipping_address[state]"]').value = form.querySelector('input[name="billing_address[state]"]').value;
                form.querySelector('input[name="shipping_address[pincode]"]').value = form.querySelector('input[name="billing_address[pincode]"]').value;

                // Hide fields with animation
                if (fieldsDiv) {
                    fieldsDiv.style.maxHeight = '0';
                    fieldsDiv.style.opacity = '0';
                }
            } else {
                form.querySelector('input[name="shipping_address[street]"]').value = '';
                form.querySelector('input[name="shipping_address[city]"]').value = '';
                form.querySelector('input[name="shipping_address[state]"]').value = '';
                form.querySelector('input[name="shipping_address[pincode]"]').value = '';

                // Show fields with animation
                if (fieldsDiv) {
                    fieldsDiv.style.maxHeight = '500px';
                    fieldsDiv.style.opacity = '1';
                }
            }
        }
    </script>
@endpush