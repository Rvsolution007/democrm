@extends('admin.layouts.app')

@section('title', 'Purchases')
@section('breadcrumb', 'Purchases')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content"
            style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h1 class="page-title">Purchases</h1>
                <p class="page-description">Manage your purchase records</p>
            </div>

            <div style="flex:4;min-width:500px;max-width:900px">
                <div style="display:flex;gap:20px;">
                    <!-- Total Amount Card -->
                    <div
                        style="flex:1;background:linear-gradient(135deg,#4f46e5 0%,#3b82f6 100%);padding:14px 24px;border-radius:12px;box-shadow:0 4px 10px rgba(59,130,246,0.3);color:white;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <p id="summary-total-label"
                                style="margin:0 0 4px 0;font-size:12px;font-weight:600;color:#e0e7ff;text-transform:uppercase;letter-spacing:0.05em">
                                Total Amount (Purchases)</p>
                            <h3 id="summary-total" style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
                                ₹{{ number_format($allTotalAmount, 2) }}</h3>
                        </div>
                        <div
                            style="width:44px;height:44px;background:rgba(255,255,255,0.2);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="indian-rupee" style="width:24px;height:24px;stroke-width:2.5px;"></i>
                        </div>
                    </div>

                    <!-- Due Amount Card -->
                    <div
                        style="flex:1;background:linear-gradient(135deg,#ec4899 0%,#f43f5e 100%);padding:14px 24px;border-radius:12px;box-shadow:0 4px 10px rgba(244,63,94,0.3);color:white;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <p id="summary-due-label"
                                style="margin:0 0 4px 0;font-size:12px;font-weight:600;color:#fce7f3;text-transform:uppercase;letter-spacing:0.05em">
                                Due Amount (Purchases)</p>
                            <h3 id="summary-due" style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
                                ₹{{ number_format($allDueAmount, 2) }}</h3>
                        </div>
                        <div
                            style="width:44px;height:44px;background:rgba(255,255,255,0.2);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="clock" style="width:24px;height:24px;stroke-width:2.5px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-actions"
                style="display:flex;gap:12px;align-items:center;align-self:flex-end;margin-bottom:8px">
                @if(can('projects.write'))
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i data-lucide="plus" style="width:16px;height:16px"></i> Add Purchase
                    </button>
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
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif



    <div class="table-container">
        <!-- Filter Bar -->
        <div class="table-toolbar">
            <div style="display:flex;gap:12px;width:100%;flex-wrap:wrap">
                <div class="table-search" style="flex:1;min-width:250px">
                    <i data-lucide="search" class="table-search-icon" style="width:16px;height:16px"></i>
                    <input type="text" id="purchase-search" class="table-search-input"
                        placeholder="Search by Purchase No or Client Name...">
                </div>

                <select id="filter-status" class="form-select" style="width:150px;font-size:13px">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                </select>

                <div style="min-width:180px">
                    <div style="position:relative">
                        <i data-lucide="calendar"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                        <input type="text" id="purchase-date-range-picker" class="form-input"
                            placeholder="Select Date Range"
                            style="width:100%;padding-left:32px;font-size:13px;height:38px;border-color:#e2e8f0;background:#fff;cursor:pointer">
                        <input type="hidden" id="filter-date-from" value="">
                        <input type="hidden" id="filter-date-to" value="">
                    </div>
                </div>

                <button type="button" onclick="clearFilters()" class="btn btn-outline btn-sm"
                    style="display:flex;align-items:center;padding:0 12px;height:38px">
                    <i data-lucide="x" style="width:14px;height:14px;margin-right:6px"></i> Clear
                </button>

                <div style="display:flex;align-items:center;margin-left:auto;">
                    <span id="purchase-count-badge"
                        style="padding:4px 12px;background:#eff6ff;color:#3b82f6;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap">
                        {{ $allPurchases->count() }} Found
                    </span>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div style="padding:0 20px;border-bottom:1px solid #e2e8f0;display:flex;gap:24px;margin-bottom:16px;">
            <button class="purchase-tab-btn active" onclick="switchPurchaseTab('all', '')" id="tab-btn-all"
                style="background:none;border:none;padding:12px 4px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:2px solid #3b82f6;cursor:pointer;transition:all 0.2s;">All
                Purchases</button>
            @foreach($vendorSections as $idx => $section)
                <button class="purchase-tab-btn"
                    onclick="switchPurchaseTab('vendor_{{ $section['vendor']->id }}', '{{ $section['vendor']->id }}')"
                    id="tab-btn-vendor_{{ $section['vendor']->id }}"
                    style="background:none;border:none;padding:12px 4px;font-size:14px;font-weight:600;color:#64748b;border-bottom:2px solid transparent;cursor:pointer;transition:all 0.2s;">{{ $section['vendor']->name }}
                    Purchases</button>
            @endforeach
        </div>

        <!-- ALL PURCHASES TABLE -->
        <div class="table-wrapper purchase-tab-content" id="tab-content-all" style="overflow-x:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Purchase No</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody id="all-purchases-tbody">
                    @forelse($allPurchases as $purchase)
                        @include('admin.purchases._purchase_row', ['purchase' => $purchase, 'customFields' => []])
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-muted">No purchases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- DYNAMIC VENDOR SECTION TABLES (with custom field columns) -->
        @foreach($vendorSections as $idx => $section)
            <div class="table-wrapper purchase-tab-content" id="tab-content-vendor_{{ $section['vendor']->id }}"
                style="display:none;overflow-x:auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Purchase No</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Client</th>
                            @foreach($section['customFields'] as $cf)
                                <th>{{ $cf->field_name }}</th>
                            @endforeach
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th style="width:150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vendor_{{ $section['vendor']->id }}-purchases-tbody">
                        @forelse($section['purchases'] as $purchase)
                            @include('admin.purchases._purchase_row_vendor', ['purchase' => $purchase, 'customFields' => $section['customFields']])
                        @empty
                            <tr>
                                <td colspan="{{ 8 + count($section['customFields']) }}" class="text-center py-8 text-muted">No
                                    {{ $section['vendor']->name }} purchases found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <!-- Add/Edit Purchase Modal -->
    <div id="purchase-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:550px;max-height:92vh;overflow-y:auto">
            <div
                style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="modal-title" style="margin:0">Add New Purchase</h3>
                <button onclick="closeModal('purchase-modal')"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px">&times;</button>
            </div>
            <form id="purchase-form" method="POST" action="{{ route('admin.purchases.store') }}">
                @csrf
                <input type="hidden" id="form-method" name="_method" value="">
                <div style="padding:20px">
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Vendor *</label>
                        <select class="form-select" name="vendor_id" id="purchase-vendor" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"
                            onchange="onVendorChange()">
                            <option value="">Select Vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Client</label>
                        <select class="form-select" name="client_id" id="purchase-client"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="">Select Client (Optional)</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->business_name ?? $client->contact_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Purchase Date *</label>
                        <input type="date" class="form-input" name="purchase_date" id="purchase-date" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"
                            value="{{ date('Y-m-d') }}">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Total Amount *</label>
                        <input type="number" step="0.01" min="0" class="form-input" name="total_amount" id="purchase-amount"
                            required placeholder="Enter amount"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Status *</label>
                        <select class="form-select" name="status" id="purchase-status" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Notes</label>
                        <textarea class="form-textarea" name="notes" id="purchase-notes" rows="3" placeholder="Enter notes"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></textarea>
                    </div>
                    <!-- Dynamic Custom Fields Container -->
                    <div id="custom-fields-container"></div>
                </div>
                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeModal('purchase-modal')"
                        style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding:8px 16px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer">Save
                        Purchase</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div id="payment-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:500px;max-height:92vh;overflow-y:auto">
            <div
                style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0">Add Payment</h3>
                <button onclick="closeModal('payment-modal')"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px">&times;</button>
            </div>
            <form id="payment-form" method="POST" action="">
                @csrf
                <div style="padding:20px">
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Amount *</label>
                        <input type="number" step="0.01" min="1" class="form-input" name="amount" required
                            placeholder="Enter amount"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Payment Date *</label>
                        <input type="date" class="form-input" name="payment_date" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"
                            value="{{ date('Y-m-d') }}">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Payment Type</label>
                        <select class="form-select" name="payment_type" id="payment-type"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            @foreach($paymentTypes as $type)
                                <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Reference No</label>
                        <input type="text" class="form-input" name="reference_no" placeholder="e.g. Transaction ID"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Notes</label>
                        <textarea class="form-textarea" name="notes" rows="2" placeholder="Enter notes"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></textarea>
                    </div>
                </div>
                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeModal('payment-modal')"
                        style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding:8px 16px;background:#10b981;color:white;border:none;border-radius:4px;cursor:pointer">Save
                        Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Purchase Modal -->
    <div id="view-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:700px;max-height:92vh;overflow-y:auto">
            <div
                style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0">Purchase Details: <span id="view-purchase-no"></span></h3>
                <button onclick="closeModal('view-modal')"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px">&times;</button>
            </div>
            <div style="padding:20px" id="view-purchase-content">Loading...</div>
            <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('view-modal')"
                    style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer">Close</button>
            </div>
        </div>
    </div>
@endsection

<!-- Include Flatpickr for Date Range Selection -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // ====== State ======
    var currentTab = '{{ session('active_tab', 'all') }}';
    var currentVendorId = currentTab.startsWith('vendor_') ? currentTab.replace('vendor_', '') : '';
    var searchTimer = null;
    var canWrite = {{ can('projects.write') ? 'true' : 'false' }};
    var canDelete = {{ can('projects.delete') ? 'true' : 'false' }};
    var csrfToken = '{{ csrf_token() }}';
    var editingPurchaseId = null;
    var editingPurchaseCfValues = {}; // { fieldId: value }

    // Default summary values from server
    var defaultSummary = {
        'all': { total: {{ $allTotalAmount }}, due: {{ $allDueAmount }} },
        @foreach($vendorSections as $section)
            'vendor_{{ $section['vendor']->id }}': { total: {{ $section['total_amount'] }}, due: {{ $section['due_amount'] }} },
        @endforeach
                                    };

    // Vendor custom fields config from server (for initial load)
    var vendorCustomFieldsMap = {
        @foreach($vendorSections as $section)
                                        '{{ $section['vendor']->id }}': [
            @foreach($section['customFields'] as $cf)
                { id: {{ $cf->id }}, name: {!! json_encode($cf->field_name) !!}, type: '{{ $cf->field_type }}', options: {!! json_encode($cf->field_options ?? []) !!} },
            @endforeach
                                        ],
        @endforeach
                                    };

    // ====== Tab Switching ======
    function switchPurchaseTab(tab, vendorId) {
        currentTab = tab;
        currentVendorId = vendorId || '';

        document.querySelectorAll('.purchase-tab-btn').forEach(function (btn) {
            btn.style.color = '#64748b';
            btn.style.borderBottom = '2px solid transparent';
            btn.classList.remove('active');
        });
        var activeBtn = document.getElementById('tab-btn-' + tab);
        if (activeBtn) {
            activeBtn.style.color = '#0f172a';
            activeBtn.style.borderBottom = '2px solid #3b82f6';
            activeBtn.classList.add('active');
        }

        document.querySelectorAll('.purchase-tab-content').forEach(function (el) { el.style.display = 'none'; });
        var tabContent = document.getElementById('tab-content-' + tab);
        if (tabContent) tabContent.style.display = '';

        var hasFilters = document.getElementById('purchase-search').value.trim() ||
            document.getElementById('filter-date-from').value ||
            document.getElementById('filter-date-to').value ||
            document.getElementById('filter-status').value;

        if (hasFilters) {
            fetchPurchases();
        } else {
            var summary = defaultSummary[tab] || defaultSummary['all'];
            updateSummaryCards(summary.total, summary.due);
            var tbodyId = (tab === 'all') ? 'all-purchases-tbody' : tab + '-purchases-tbody';
            var tbody = document.getElementById(tbodyId);
            if (tbody) {
                var rows = tbody.querySelectorAll('tr');
                var count = 0;
                rows.forEach(function (r) { if (!r.querySelector('.text-muted')) count++; });
                document.getElementById('purchase-count-badge').textContent = count + ' Found';
            }
        }
    }

    function updateSummaryCards(total, due) {
        document.getElementById('summary-total').textContent = '₹' + parseFloat(total).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('summary-due').textContent = '₹' + parseFloat(due).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // ====== AJAX Search & Filters ======
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize Flatpickr for Date Range
        let dateFrom = document.getElementById('filter-date-from').value;
        let dateTo = document.getElementById('filter-date-to').value;
        let defaultDates = [];
        if (dateFrom && dateTo) defaultDates = [dateFrom, dateTo];

        flatpickr("#purchase-date-range-picker", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: defaultDates,
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    document.getElementById('filter-date-from').value = instance.formatDate(selectedDates[0], "Y-m-d");
                    document.getElementById('filter-date-to').value = instance.formatDate(selectedDates[1], "Y-m-d");
                    fetchPurchases();
                } else if (selectedDates.length === 0) {
                    document.getElementById('filter-date-from').value = '';
                    document.getElementById('filter-date-to').value = '';
                    fetchPurchases();
                }
            }
        });

        document.getElementById('purchase-search').addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(fetchPurchases, 300);
        });
        document.getElementById('filter-status').addEventListener('change', fetchPurchases);

        // Restore active tab from session
        if (currentTab !== 'all') {
            switchPurchaseTab(currentTab, currentVendorId);
        }
    });

    function fetchPurchases() {
        var search = document.getElementById('purchase-search').value.trim();
        var dateFrom = document.getElementById('filter-date-from').value;
        var dateTo = document.getElementById('filter-date-to').value;
        var status = document.getElementById('filter-status').value;

        var params = new URLSearchParams();
        if (search) params.append('search', search);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        if (status) params.append('status', status);
        if (currentVendorId) params.append('vendor_id', currentVendorId);

        fetch('{{ route("admin.purchases.search") }}?' + params.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var tbodyId = (currentTab === 'all') ? 'all-purchases-tbody' : currentTab + '-purchases-tbody';
                var customFields = data.custom_fields || [];

                if (currentVendorId && customFields.length > 0) {
                    // Update thead for vendor tab
                    updateVendorTableHead(currentTab, customFields);
                    renderVendorPurchasesTable(tbodyId, data.purchases, customFields);
                } else {
                    renderPurchasesTable(tbodyId, data.purchases);
                }
                document.getElementById('purchase-count-badge').textContent = data.count + ' Found';
                updateSummaryCards(data.total_amount, data.due_amount);
            })
            .catch(function (error) { console.error('Error fetching purchases:', error); });
    }

    function updateVendorTableHead(tab, customFields) {
        var tabContent = document.getElementById('tab-content-' + tab);
        if (!tabContent) return;
        var thead = tabContent.querySelector('thead tr');
        if (!thead) return;

        var html = '<th>Purchase No</th><th>Date</th><th>Vendor</th><th>Client</th>';
        customFields.forEach(function (cf) {
            html += '<th>' + escHtml(cf.field_name) + '</th>';
        });
        html += '<th>Amount</th><th>Paid</th><th>Status</th><th style="width:150px">Actions</th>';
        thead.innerHTML = html;
    }

    function renderPurchasesTable(tbodyId, purchases) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        if (!purchases || purchases.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-muted">No purchases found.</td></tr>';
            return;
        }
        var html = '';
        purchases.forEach(function (p) { html += buildPurchaseRow(p, []); });
        tbody.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function renderVendorPurchasesTable(tbodyId, purchases, customFields) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        if (!purchases || purchases.length === 0) {
            tbody.innerHTML = '<tr><td colspan="' + (8 + customFields.length) + '" class="text-center py-8 text-muted">No purchases found.</td></tr>';
            return;
        }
        var html = '';
        purchases.forEach(function (p) { html += buildPurchaseRow(p, customFields); });
        tbody.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function buildPurchaseRow(p, customFields) {
        var badgeClass = p.status === 'completed' ? 'success' : (p.status === 'active' ? 'primary' : 'secondary');
        var vendorName = p.vendor ? p.vendor.name : 'N/A';
        var clientName = p.client ? (p.client.business_name || p.client.contact_name || 'N/A') : 'N/A';
        var dateFormatted = new Date(p.date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        var amount = '₹' + (p.total_amount / 100).toFixed(2);
        var paid = '₹' + (p.paid_amount / 100).toFixed(2);
        var editDate = p.date ? p.date.substring(0, 10) : '';
        var notes = p.notes || '';
        notes = notes.replace(/"/g, '&quot;').replace(/'/g, '&#39;');

        // Build cf_values JSON for edit button
        var cfValues = p.cf_values || {};
        var cfValuesStr = JSON.stringify(cfValues).replace(/"/g, '&quot;');

        var html = '<tr>';
        html += '<td><p class="font-medium">' + escHtml(p.purchase_no) + '</p></td>';
        html += '<td>' + dateFormatted + '</td>';
        html += '<td>' + escHtml(vendorName) + '</td>';
        html += '<td>' + escHtml(clientName) + '</td>';

        // Custom field value columns
        if (customFields && customFields.length > 0) {
            customFields.forEach(function (cf) {
                var val = cfValues[cf.id] || cfValues[String(cf.id)] || '—';
                html += '<td>' + escHtml(val) + '</td>';
            });
        }

        html += '<td>' + amount + '</td>';
        html += '<td>' + paid + '</td>';
        html += '<td><span class="badge badge-' + badgeClass + '">' + ucfirst(p.status) + '</span></td>';

        html += '<td><div style="display:flex;gap:6px;align-items:center">';
        html += '<button onclick="viewPurchase(' + p.id + ')" style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all 0.15s" title="View" onmouseover="this.style.background=\'#dbeafe\'" onmouseout="this.style.background=\'#eff6ff\'"><i data-lucide="eye" style="width:16px;height:16px"></i></button>';
        if (canWrite) {
            html += '<button class="edit-purchase-btn" data-id="' + p.id + '" data-vendor="' + (p.vendor_id || '') + '" data-client="' + (p.client_id || '') + '" data-date="' + editDate + '" data-amount="' + (p.total_amount / 100) + '" data-notes="' + notes + '" data-status="' + p.status + '" data-cf-values="' + cfValuesStr + '" style="width:32px;height:32px;border-radius:8px;background:#fffbeb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#f59e0b;transition:all 0.15s" title="Edit" onmouseover="this.style.background=\'#fef3c7\'" onmouseout="this.style.background=\'#fffbeb\'"><i data-lucide="edit" style="width:16px;height:16px"></i></button>';
            html += '<button onclick="openPaymentModal(' + p.id + ')" style="width:32px;height:32px;border-radius:8px;background:#f0fdf4;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#16a34a;transition:all 0.15s" title="Add Payment" onmouseover="this.style.background=\'#dcfce7\'" onmouseout="this.style.background=\'#f0fdf4\'"><i data-lucide="indian-rupee" style="width:16px;height:16px"></i></button>';
            if (canDelete) {
                html += '<form action="{{ url("admin/purchases") }}/' + p.id + '" method="POST" style="display:inline;margin:0" onsubmit="return confirm(\'Are you sure?\')"><input type="hidden" name="_token" value="' + csrfToken + '"><input type="hidden" name="_method" value="DELETE"><button type="submit" style="width:32px;height:32px;border-radius:8px;background:#fef2f2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;transition:all 0.15s" title="Delete" onmouseover="this.style.background=\'#fee2e2\'" onmouseout="this.style.background=\'#fef2f2\'"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button></form>';
            }
        }
        html += '</div></td></tr>';
        return html;
    }

    function clearFilters() {
        document.getElementById('purchase-search').value = '';
        document.getElementById('filter-date-from').value = '';
        document.getElementById('filter-date-to').value = '';
        document.getElementById('filter-status').value = '';
        window.location.href = '{{ route("admin.purchases.index") }}';
    }

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    function ucfirst(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

    // ====== Vendor Custom Fields in Modal ======
    function onVendorChange() {
        var vendorId = document.getElementById('purchase-vendor').value;
        var container = document.getElementById('custom-fields-container');
        container.innerHTML = '';
        if (!vendorId) return;

        fetch('{{ url("admin/purchases/vendor-fields") }}/' + vendorId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                renderModalCustomFields(data.fields, editingPurchaseCfValues);
            })
            .catch(err => console.error('Error loading vendor fields:', err));
    }

    function renderModalCustomFields(fields, cfValues) {
        var container = document.getElementById('custom-fields-container');
        if (!fields || fields.length === 0) { container.innerHTML = ''; return; }

        var html = '<div style="border-top:1px solid #e2e8f0;padding-top:16px;margin-top:8px"><p style="font-size:13px;font-weight:600;color:#475569;margin:0 0 12px 0"><i data-lucide="settings-2" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:4px"></i> Custom Fields</p>';
        fields.forEach(function (f) {
            var val = (cfValues && cfValues[f.id]) ? cfValues[f.id] : ((cfValues && cfValues[String(f.id)]) ? cfValues[String(f.id)] : '');
            html += '<div style="margin-bottom:16px">';
            html += '<label style="display:block;margin-bottom:4px;font-weight:500">' + escHtml(f.field_name) + '</label>';
            if (f.field_type === 'select') {
                html += '<select name="custom_fields[' + f.id + ']" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">';
                html += '<option value="">Select...</option>';
                var options = f.field_options || [];
                options.forEach(function (opt) {
                    html += '<option value="' + escHtml(opt) + '"' + (val === opt ? ' selected' : '') + '>' + escHtml(opt) + '</option>';
                });
                html += '</select>';
            } else if (f.field_type === 'date') {
                html += '<input type="date" name="custom_fields[' + f.id + ']" value="' + escHtml(val) + '" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">';
            } else {
                html += '<input type="text" name="custom_fields[' + f.id + ']" value="' + escHtml(val) + '" placeholder="Enter ' + escHtml(f.field_name) + '" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">';
            }
            html += '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ====== Modals ======
    function openAddModal() {
        editingPurchaseId = null;
        editingPurchaseCfValues = {};
        document.getElementById('modal-title').textContent = 'Add New Purchase';
        document.getElementById('purchase-form').action = '{{ route("admin.purchases.store") }}';
        document.getElementById('form-method').value = '';
        document.getElementById('purchase-form').reset();
        document.getElementById('purchase-date').value = '{{ date("Y-m-d") }}';
        document.getElementById('purchase-status').value = 'draft';
        document.getElementById('custom-fields-container').innerHTML = '';
        document.getElementById('purchase-modal').style.display = 'flex';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.edit-purchase-btn');
        if (!btn) return;

        editingPurchaseId = btn.dataset.id;
        editingPurchaseCfValues = {};
        try { editingPurchaseCfValues = JSON.parse(btn.dataset.cfValues || '{}'); } catch (e) { }

        document.getElementById('modal-title').textContent = 'Edit Purchase';
        document.getElementById('purchase-form').action = '{{ url("admin/purchases") }}/' + btn.dataset.id;
        document.getElementById('form-method').value = 'PUT';
        document.getElementById('purchase-vendor').value = btn.dataset.vendor;
        document.getElementById('purchase-client').value = btn.dataset.client || '';
        document.getElementById('purchase-date').value = btn.dataset.date;
        document.getElementById('purchase-amount').value = btn.dataset.amount;
        document.getElementById('purchase-notes').value = btn.dataset.notes;
        document.getElementById('purchase-status').value = btn.dataset.status;
        document.getElementById('purchase-modal').style.display = 'flex';

        // Load custom fields for the selected vendor
        onVendorChange();
    });

    function openPaymentModal(id) {
        document.getElementById('payment-form').action = '{{ url("admin/purchases") }}/' + id + '/payment';
        document.getElementById('payment-form').reset();
        document.getElementById('payment-modal').style.display = 'flex';
    }

    function viewPurchase(id) {
        document.getElementById('view-modal').style.display = 'flex';
        document.getElementById('view-purchase-content').innerHTML = 'Loading...';
        document.getElementById('view-purchase-no').textContent = '';

        fetch('{{ url("admin/purchases") }}/' + id)
            .then(response => response.json())
            .then(data => {
                document.getElementById('view-purchase-no').textContent = data.purchase_no;
                var statusBadge = '<span class="badge badge-' + (data.status === 'completed' ? 'success' : (data.status === 'active' ? 'primary' : 'secondary')) + '">' + ucfirst(data.status) + '</span>';

                var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">';
                html += '<div><p style="margin:0 0 5px 0;color:#666;font-size:12px;text-transform:uppercase">Vendor</p><p style="margin:0;font-weight:500">' + (data.vendor ? data.vendor.name : 'N/A') + '</p></div>';
                html += '<div><p style="margin:0 0 5px 0;color:#666;font-size:12px;text-transform:uppercase">Client</p><p style="margin:0;font-weight:500">' + (data.client ? (data.client.business_name || data.client.contact_name) : 'N/A') + '</p></div>';
                html += '<div><p style="margin:0 0 5px 0;color:#666;font-size:12px;text-transform:uppercase">Date</p><p style="margin:0;font-weight:500">' + new Date(data.date).toLocaleDateString() + '</p></div>';
                html += '<div><p style="margin:0 0 5px 0;color:#666;font-size:12px;text-transform:uppercase">Status</p><p style="margin:0;font-weight:500">' + statusBadge + '</p></div>';
                html += '<div style="grid-column:span 2"><p style="margin:0 0 5px 0;color:#666;font-size:12px;text-transform:uppercase">Amounts</p>';
                html += '<p style="margin:0;font-weight:500">Total: ₹' + (data.total_amount / 100).toFixed(2) + ' | Paid: ₹' + (data.paid_amount / 100).toFixed(2) + ' | <span style="color:' + (data.total_amount > data.paid_amount ? 'var(--destructive)' : 'inherit') + '">Balance: ₹' + ((data.total_amount - data.paid_amount) / 100).toFixed(2) + '</span></p></div>';
                html += '</div>';

                // Custom field values
                if (data.custom_field_values && data.custom_field_values.length > 0) {
                    html += '<div style="margin-bottom:20px"><p style="margin:0 0 8px 0;color:#666;font-size:12px;text-transform:uppercase;font-weight:bold">Custom Fields</p>';
                    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
                    data.custom_field_values.forEach(function (cfv) {
                        var fieldName = cfv.custom_field ? cfv.custom_field.field_name : 'Field #' + cfv.vendor_custom_field_id;
                        html += '<div style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px"><p style="margin:0 0 2px 0;color:#666;font-size:11px;text-transform:uppercase">' + escHtml(fieldName) + '</p><p style="margin:0;font-weight:500">' + escHtml(cfv.value || '—') + '</p></div>';
                    });
                    html += '</div></div>';
                }

                html += '<div style="margin-bottom:20px"><p style="margin:0 0 5px 0;color:#666;font-size:12px;text-transform:uppercase">Notes</p>';
                html += '<div style="padding:10px;background:#f9f9f9;border:1px solid #eee;border-radius:4px">' + (data.notes || 'No notes provided.') + '</div></div>';

                if (data.payments && data.payments.length > 0) {
                    html += '<p style="margin:10px 0 5px 0;color:#666;font-size:12px;text-transform:uppercase;font-weight:bold">Payment History</p>';
                    html += '<table style="width:100%;border-collapse:collapse;font-size:14px"><thead><tr style="border-bottom:2px solid #eee;text-align:left"><th style="padding:8px">Date</th><th style="padding:8px">Amount</th><th style="padding:8px">Type</th><th style="padding:8px">Reference</th></tr></thead><tbody>';
                    data.payments.forEach(function (payment) {
                        html += '<tr style="border-bottom:1px solid #eee"><td style="padding:8px">' + new Date(payment.payment_date).toLocaleDateString() + '</td><td style="padding:8px">₹' + (payment.amount / 100).toFixed(2) + '</td><td style="padding:8px">' + (payment.payment_type || payment.payment_method || '-') + '</td><td style="padding:8px">' + (payment.reference_no || '-') + '</td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p style="margin:10px 0;color:#666;font-size:14px;font-style:italic">No payments recorded yet.</p>';
                }
                document.getElementById('view-purchase-content').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('view-purchase-content').innerHTML = '<p style="color:red">Failed to load details.</p>';
            });
    }

    function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }

    window.addEventListener('click', function (e) {
        if (e.target.id === 'purchase-modal') closeModal('purchase-modal');
        if (e.target.id === 'payment-modal') closeModal('payment-modal');
        if (e.target.id === 'view-modal') closeModal('view-modal');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeModal('purchase-modal'); closeModal('payment-modal'); closeModal('view-modal'); }
    });
</script>
@endpush