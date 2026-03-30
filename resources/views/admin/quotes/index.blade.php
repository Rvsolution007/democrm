@extends('admin.layouts.app')

@section('title', 'Quotes')
@section('breadcrumb', 'Quotes')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content"
            style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h1 class="page-title">Quotes</h1>
                <p class="page-description">Manage quotations</p>
            </div>

            <div style="flex:4;min-width:500px;max-width:900px">
                <div style="display:flex;gap:20px;">
                    <!-- Total Amount Card -->
                    <div
                        style="flex:1;background:linear-gradient(135deg,#4f46e5 0%,#3b82f6 100%);padding:14px 24px;border-radius:12px;box-shadow:0 4px 10px rgba(59,130,246,0.3);color:white;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <p id="summary-total-label"
                                style="margin:0 0 4px 0;font-size:12px;font-weight:600;color:#e0e7ff;text-transform:uppercase;letter-spacing:0.05em">
                                Total Amount (Quotes)</p>
                            <h3 id="summary-total" style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
                                ₹{{ number_format($leadTotalAmount, 2) }}</h3>
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
                                Due Amount (Quotes)</p>
                            <h3 id="summary-due" style="margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
                                ₹{{ number_format($leadDueAmount, 2) }}</h3>
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
                @if(can('quotes.write'))
                    <button class="btn btn-primary" onclick="openCreateQuoteDrawer()"><i data-lucide="plus"
                            style="width:16px;height:16px"></i> Create Quote</button>
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




    <div class="table-container">
        <div class="table-toolbar">
            <form method="GET" action="{{ route('admin.quotes.index') }}"
                style="display:flex;gap:12px;width:100%;flex-wrap:wrap">
                <div class="table-search" style="flex:1;min-width:250px">
                    <i data-lucide="search" class="table-search-icon" style="width:16px;height:16px"></i>
                    <input type="text" name="search" class="table-search-input" value="{{ request('search') }}"
                        placeholder="Search quote number, client..." oninput="autoAjaxSearch(this.form)">
                </div>

                @if(can('quotes.global') || auth()->user()->isAdmin())
                    <select name="assigned_to_user_id" class="form-select" style="width:180px;font-size:13px"
                        onchange="autoAjaxSearch(this.form)">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('assigned_to_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="status" class="form-select" style="width:150px;font-size:13px" onchange="autoAjaxSearch(this.form)">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>

                <div style="min-width:180px">
                    <div style="position:relative">
                        <i data-lucide="calendar"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                        <input type="text" id="quote-date-range-picker" class="form-input" placeholder="Select Date Range"
                            style="width:100%;padding-left:32px;font-size:13px;height:38px;border-color:#e2e8f0;background:#fff;cursor:pointer">
                        <input type="hidden" name="start_date" id="start-date" value="{{ request('start_date') }}">
                        <input type="hidden" name="due_date" id="due-date" value="{{ request('due_date') }}">
                    </div>
                </div>

                @if(request()->hasAny(['search', 'assigned_to_user_id', 'status', 'start_date', 'due_date']))
                    <a href="{{ route('admin.quotes.index') }}" class="btn btn-outline btn-sm"
                        style="display:flex;align-items:center;padding:0 12px;height:38px"><i data-lucide="x"
                            style="width:14px;height:14px;margin-right:6px"></i> Clear</a>
                @endif
                <!-- autoAjaxSearch removes need for physical submit button -->
            </form>
        </div>

        <!-- Quotes Table -->
        <div class="table-wrapper" id="tab-content-leads">
            <table class="table" id="lead-quotes-table">
                <thead>
                    <tr>
                        <th class="sortable">Quote Number</th>
                        <th>Quote For</th>
                        <th>Assigned To</th>
                        <th class="sortable">Status</th>
                        <th class="sortable">Total</th>
                        <th>Purchase</th>
                        <th class="sortable">Valid Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="quotes-tbody" id="lead-quotes-tbody">
                    @forelse($leadQuotes as $quote)
                        <tr data-status="{{ $quote->status }}">
                            <td>
                                <span class="font-medium">{{ $quote->quote_no }}</span>
                                @if($quote->gst_total > 0)
                                    <span style="margin-left:6px;font-size:11px;color:#0ea5e9;background:#e0f2fe;padding:2px 6px;border-radius:4px;font-weight:600">GST</span>
                                @endif
                                @if($quote->lead_id)
                                    <span
                                        style="margin-left:6px;font-size:11px;color:#6366f1;background:#eef2ff;padding:2px 6px;border-radius:4px">Lead
                                        #{{ $quote->lead_id }}</span>
                                @endif
                            </td>
                            <td>
                                @if($quote->client_id)
                                    <div style="display:flex;align-items:center;gap:4px">
                                        <i data-lucide="building" style="width:12px;height:12px;color:#64748b;"></i>
                                        <span>{{ $quote->client->display_name ?? $quote->client->name ?? '—' }}</span>
                                    </div>
                                @elseif($quote->lead_id)
                                    <div style="display:flex;align-items:center;gap:4px">
                                        <i data-lucide="user" style="width:12px;height:12px;color:#64748b;"></i>
                                        <span>{{ $quote->lead->name ?? '—' }}</span>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $quote->assignedUsers->isNotEmpty() ? $quote->assignedUsers->pluck('name')->implode(', ') : '—' }}</td>
                            <td>
                                <span
                                    class="badge badge-{{ $quote->status === 'accepted' ? 'success' : ($quote->status === 'rejected' ? 'destructive' : ($quote->status === 'sent' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($quote->status) }}
                                </span>
                            <td class="font-medium">
                                ₹{{ number_format($quote->grand_total_in_rupees, 2) }}
                                @if($quote->gst_total > 0)
                                    <span style="margin-left:6px;font-size:10px;color:#0ea5e9;background:#e0f2fe;padding:2px 5px;border-radius:4px;font-weight:600;display:inline-block">+GST</span>
                                @endif
                            </td>
                            <td>
                                @if($quote->total_purchase_amount_in_rupees > 0)
                                    <span style="font-weight:600;color:#f59e0b;">
                                        ₹{{ number_format($quote->total_purchase_amount_in_rupees, 2) }}
                                    </span>
                                @else
                                    <span style="color:#cbd5e1">—</span>
                                @endif
                            </td>
                            <td>{{ $quote->valid_till ? $quote->valid_till->format('d M Y') : '—' }}</td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-ghost btn-icon btn-sm" onclick="viewQuote({{ $quote->id }}, this)"
                                        title="View"><i data-lucide="eye" style="width:16px;height:16px"></i></button>
                                    <button class="btn btn-ghost btn-icon btn-sm" onclick="downloadQuote({{ $quote->id }})"
                                        title="Download"><i data-lucide="download" style="width:16px;height:16px"></i></button>
                                    @if(can('quotes.write') && $quote->client_id > 0)
                                        <button class="btn btn-ghost btn-icon btn-sm" style="color:#16a34a"
                                            onclick="openConvertInvoiceModal({{ $quote->id }})" title="Convert to Invoice"><i
                                                data-lucide="check-circle" style="width:16px;height:16px"></i></button>
                                    @elseif(can('quotes.write') && $quote->lead_id > 0)
                                        <button class="btn btn-ghost btn-icon btn-sm" style="color:#16a34a"
                                            onclick="convertLeadQuoteToClient({{ $quote->id }}, {{ $quote->lead_id }})" title="Convert to Client"><i
                                                data-lucide="user-check" style="width:16px;height:16px"></i></button>
                                    @endif
                                    @if(can('quotes.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" onclick="editQuote({{ $quote->id }}, this)"
                                            title="Edit"><i data-lucide="edit" style="width:16px;height:16px"></i></button>
                                    @endif
                                    @if(can('quotes.delete'))
                                        <button class="btn btn-ghost btn-icon btn-sm" style="color:var(--destructive)"
                                            onclick="deleteQuote({{ $quote->id }})" title="Delete"><i data-lucide="trash-2"
                                                style="width:16px;height:16px"></i></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px 0;color:#999">
                                <i data-lucide="file-text" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                                <p style="margin:0;font-size:14px">No Quotes found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="table-footer">
                <span>Showing {{ $leadQuotes->count() }} of {{ $leadQuotes->total() }} entries</span>
                {{ $leadQuotes->links() }}
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="payment-modal-overlay"
        style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9998;backdrop-filter:blur(2px);transition:opacity 0.3s;"
        onclick="closePaymentModal()"></div>
    <div id="payment-modal"
        style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.95);background:#fff;border-radius:16px;width:480px;max-width:90vw;z-index:9999;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);transition:transform 0.3s cubic-bezier(0.4,0,0.2,1),opacity 0.3s;opacity:0;">
        <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h3 style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">Record Payment</h3>
                <p id="payment-modal-quote-info" style="font-size:13px;color:#64748b;margin:4px 0 0;"></p>
            </div>
            <button onclick="closePaymentModal()" style="background:none;border:none;cursor:pointer;padding:4px;">
                <i data-lucide="x" style="width:20px;height:20px;color:#94a3b8"></i>
            </button>
        </div>
        <div style="padding:20px 28px 28px;">
            <div id="payment-error-container"
                style="display:none;padding:10px 14px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;margin-bottom:16px;font-size:13px;">
            </div>
            <form id="payment-form">
                <input type="hidden" id="payment-quote-id" name="quote_id">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-size:13px;font-weight:600;color:#374151;">Payment Date &
                        Time</label>
                    <input type="datetime-local" name="payment_date" id="payment-date" class="form-input"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;">
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-size:13px;font-weight:600;color:#374151;">Payment Type</label>
                    <select name="payment_type" id="payment-type" class="form-select"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;">
                        @foreach($paymentTypes as $type)
                            <option value="{{ $type }}">{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label" style="font-size:13px;font-weight:600;color:#374151;">Amount (₹)</label>
                    <input type="number" name="amount" id="payment-amount" class="form-input" step="0.01" min="0.01"
                        placeholder="Enter amount"
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:16px;font-weight:600;">
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label" style="font-size:13px;font-weight:600;color:#374151;">Notes (Optional)</label>
                    <textarea name="notes" id="payment-notes" class="form-textarea" rows="2"
                        placeholder="Payment reference or remarks..."
                        style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;resize:vertical;"></textarea>
                </div>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button type="button" class="btn btn-outline" onclick="closePaymentModal()"
                        style="padding:10px 20px;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitPayment()"
                        style="padding:10px 24px;background:linear-gradient(135deg,#059669,#10b981);border:none;">
                        <i data-lucide="check" style="width:16px;height:16px;margin-right:6px;"></i> Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="drawer-overlay" class="overlay" onclick="closeDrawer('quote-drawer')"
        style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9998;display:none;backdrop-filter:blur(2px)">
    </div>
    <div id="quote-drawer" class="drawer drawer-lg"
        style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.95);width:900px;max-width:95vw;max-height:90vh;background:#fff;border-radius:16px;z-index:9999;display:none;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,0.3);overflow:hidden;opacity:0;transition:transform 0.3s,opacity 0.3s">
        <div class="drawer-header"
            style="padding:20px 28px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0">
            <div>
                <h3 class="drawer-title" id="quote-drawer-title"
                    style="margin:0;font-size:18px;font-weight:700;color:#0f172a">Create New Quote</h3>
                <p class="drawer-description" style="margin:4px 0 0;font-size:13px;color:#64748b">Enter quote details</p>
            </div>
            <button class="drawer-close" onclick="closeDrawer('quote-drawer')"
                style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer"><i
                    data-lucide="x" style="width:18px;height:18px;color:#64748b"></i></button>
        </div>
        <div class="drawer-body" style="padding:24px 28px;overflow-y:auto;flex:1">
            <div id="quote-error-container"
                style="display:none;padding:12px 16px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:6px;margin-bottom:16px">
                <ul style="margin:0;padding-left:20px;font-size:13px" id="quote-error-list"></ul>
            </div>
            @if($errors->any())
                <div
                    style="padding:12px 16px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:6px;margin-bottom:16px">
                    <ul style="margin:0;padding-left:20px;font-size:13px">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form id="quote-form" method="POST" action="{{ route('admin.quotes.store') }}" data-turbo="false">
                @csrf
                <input type="hidden" name="_method" id="quote-form-method" value="POST">
                <input type="hidden" name="client_type" id="quote-client-type" value="client">
                <div class="form-group">
                    <label class="form-label required">Quote For</label>
                    <div
                        style="display:flex;gap:0;margin-bottom:10px;border:1px solid #ddd;border-radius:6px;overflow:hidden;width:fit-content">
                        <button type="button" id="btn-client-tab" class="quote-type-tab active"
                            onclick="switchQuoteClientType('client')"
                            style="padding:6px 18px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#4f46e5;color:#fff;transition:all .2s">
                            Client
                        </button>
                        <button type="button" id="btn-lead-tab" class="quote-type-tab"
                            onclick="switchQuoteClientType('lead')"
                            style="padding:6px 18px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#f3f4f6;color:#666;transition:all .2s">
                            New Lead
                        </button>
                    </div>

                    {{-- Client Dropdown --}}
                    <div id="quote-client-section" style="width:100%;">
                        <select name="client_id" id="quote-client-id" class="form-select" style="width:100%;">
                            <option value="">Select client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Lead Dropdown --}}
                    <div id="quote-lead-section" style="display:none; width:100%;">
                        <select name="lead_id" id="quote-lead-id" class="form-select" style="width:100%;">
                            <option value="">Select lead</option>
                            @foreach($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->name }}{{ $lead->phone ? ' — ' . $lead->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                <style>
                    /* Force Select2 inside Quote Drawer to be full width */
                    #quote-drawer .select2-container {
                        width: 100% !important;
                    }
                    /* Fix Select2 clear button overflow */
                    #quote-drawer .select2-selection__clear {
                        margin-right: 25px;
                        z-index: 10;
                    }
                    #quote-drawer .select2-selection__arrow {
                        z-index: 1;
                    }
                </style>
                @php
                    $showAssignDropdown = (can('quotes.global') || auth()->user()->isAdmin()) && $users->count() > 1;
                @endphp

                @if($showAssignDropdown)
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to_user_id" class="form-select">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>{{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    {{-- If only one user available (themselves) or no permission, show readonly text --}}
                    <input type="hidden" name="assigned_to_user_id" value="{{ auth()->id() }}">
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <input type="text" class="form-input" value="{{ auth()->user()->name }}" readonly
                            style="background-color:#f8fafc;color:#666">
                    </div>
                @endif
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Quote Date</label>
                        <input type="date" name="quote_date" class="form-input" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="date" name="valid_until" class="form-input"
                            value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>
                </div>
                <hr style="margin:16px 0;border:none;border-top:1px solid #eee">
                {{-- Product Selection --}}
                <div class="form-group">
                    <label class="form-label" style="font-size:14px;font-weight:600;color:#333">Products</label>
                    <div style="display:flex;gap:8px;margin-bottom:12px">
                        <select id="quote-product-selector" class="form-select" style="flex:1">
                            <option value="">-- Select Product --</option>
                            @foreach($products as $product)
                                @php $qDisplayPrice = ($product->mrp ?: $product->sale_price) / 100; @endphp
                                <option value="{{ $product->id }}" data-name="{{ $product->name }}"
                                    data-price="{{ $qDisplayPrice }}"
                                    data-desc="{{ Str::limit($product->description ?? '', 60) }}"
                                    data-purchase="{{ $product->is_purchase_enabled ? '1' : '0' }}">
                                    {{ $product->name }} — ₹{{ number_format($qDisplayPrice, 2) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline" onclick="addQuoteProduct()"
                            style="white-space:nowrap">+ Add</button>
                    </div>
                    <div id="quote-products-wrapper">
                        <table id="quote-products-table" style="width:100%;border-collapse:collapse;display:none">
                            <thead>
                                <tr style="background:#f8f9fa;text-align:left">
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px">Product Name</th>
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px;width:100px">Price
                                        (₹)</th>
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px">Description</th>
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px;width:70px">Qty</th>
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px;width:100px">Dis.
                                        Price</th>
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px;width:100px" title="Auto-Purchase Amount">Pur. Amt (₹)</th>
                                    <th style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px;width:50px"></th>
                                </tr>
                            </thead>
                            <tbody id="quote-products-body"></tbody>
                        </table>
                    </div>
                </div>

                <hr style="margin:16px 0;border:none;border-top:1px solid #eee">
                <h4 style="font-size:14px;font-weight:600;margin-bottom:12px;color:#333">Amount Details (₹)</h4>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Subtotal (₹)</label>
                        <input type="number" name="subtotal" id="q-subtotal" class="form-input" value="0" min="0"
                            step="0.01" readonly style="background:#f8fafc">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount (₹)</label>
                        <input type="number" name="discount" id="q-discount" class="form-input" value="0" min="0"
                            step="0.01" oninput="calcQuoteTotal()">
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">GST / Tax Rate</label>
                        <select id="q-tax-rate" class="form-select" onchange="calcQuoteTotal()">
                            <option value="0">No Tax</option>
                            @if(isset($quoteTaxes) && is_array($quoteTaxes))
                                @foreach($quoteTaxes as $tax)
                                    @if(isset($tax['rate']) && isset($tax['name']))
                                        <option value="{{ $tax['rate'] }}">{{ $tax['name'] }} ({{ $tax['rate'] }}%)</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Calculated Tax (₹)</label>
                        <input type="text" id="q-tax-display-input" class="form-input bg-gray-50" readonly value="₹0.00">
                        <input type="hidden" name="tax_amount" id="q-tax" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grand Total (₹)</label>
                        <input type="text" id="q-total" class="form-input" value="₹0.00" readonly
                            style="background:#f8fafc;font-weight:600;color:#16a34a">
                    </div>
                </div>
                <div class="form-group" id="quote-status-group" style="display:none">
                    <label class="form-label">Status</label>
                    <select name="status" id="quote-status" class="form-select">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="quote-notes-input" class="form-textarea" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="drawer-footer"
            style="display:flex;justify-content:space-between;align-items:center;padding:16px 28px;border-top:1px solid #e2e8f0;flex-shrink:0;background:#fafbfc">
            <div id="quote-drawer-left-actions">
                <button type="button" id="btn-convert-client" class="btn btn-outline"
                    style="display:none;color:#16a34a;border-color:#16a34a;align-items:center;"
                    onclick="convertLeadToClient()">
                    <i data-lucide="user-check" style="width:16px;height:16px;margin-right:6px"></i> Convert to Client
                </button>
            </div>
            <div style="display:flex; gap:12px;">
                <button class="btn btn-outline" onclick="closeDrawer('quote-drawer')">Cancel</button>
                @if(can('quotes.write'))
                    <button type="button" id="btn-edit-quote" class="btn btn-outline" style="display:none;align-items:center;">
                        <i data-lucide="edit" style="width:16px;height:16px;margin-right:6px"></i> <span id="btn-edit-text">Edit Quote</span>
                    </button>
                @endif
                <button type="button" onclick="submitQuoteForm()" class="btn btn-primary" id="btn-save-quote">Save Quote</button>
            </div>
        </div>
    </div>

    <!-- Description Edit Popup -->
    <div id="qdesc-edit-popup"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div
            style="background:white;border-radius:12px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden">
            <div
                style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:600;color:#1a1a2e">Edit Description</h3>
                <button onclick="closeQDescPopup()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b">&times;</button>
            </div>
            <div style="padding:20px">
                <textarea id="qdesc-edit-textarea" rows="6"
                    style="width:100%;padding:12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;resize:vertical;outline:none;font-family:inherit;line-height:1.6"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"
                    placeholder="Enter detailed description..."></textarea>
            </div>
            <div
                style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc">
                <button type="button" onclick="closeQDescPopup()"
                    style="padding:8px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b">Cancel</button>
                <button type="button" onclick="saveQDescPopup()"
                    style="padding:8px 18px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(37,99,235,0.3)">Save</button>
            </div>
        </div>
    </div>

    <!-- Convert to Client Modal -->
    <div id="convert-client-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10001;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div style="background:white;border-radius:12px;width:95%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f0fdf4,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:600;color:#16a34a;display:flex;align-items:center;gap:8px">
                    <i data-lucide="user-check" style="width:18px;height:18px"></i> Convert Lead to Client
                </h3>
                <button onclick="closeConvertModal()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b">&times;</button>
            </div>
            <div style="padding:20px">
                <input type="hidden" id="convert-lead-id">
                <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5">
                    A new client, project, and tasks will be auto-created. Select team members to assign the project to.
                </p>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-weight:600;font-size:13px;color:#334155;margin-bottom:6px">Assign Project To</label>
                    <select id="convert-assign-user" class="form-select" multiple style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <small style="color:#94a3b8;font-size:11px;margin-top:4px;display:block">Hold Ctrl/Cmd to select multiple users</small>
                </div>
            </div>
            <div style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc">
                <button type="button" onclick="closeConvertModal()"
                    style="padding:8px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b">Cancel</button>
                <button type="button" id="btn-confirm-convert" onclick="submitConvertToClient()"
                    style="padding:8px 18px;background:linear-gradient(135deg,#22c55e,#16a34a);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(22,163,74,0.3)">Convert & Assign</button>
            </div>
        </div>
    </div>

    <!-- Convert to Invoice Modal -->
    <div id="convert-invoice-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10001;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div style="background:white;border-radius:12px;width:95%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f0fdf4,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:600;color:#16a34a;display:flex;align-items:center;gap:8px">
                    <i data-lucide="file-check" style="width:18px;height:18px"></i> Convert to Invoice
                </h3>
                <button onclick="closeConvertInvoiceModal()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b">&times;</button>
            </div>
            <div style="padding:20px">
                <input type="hidden" id="convert-invoice-quote-id">
                <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5">
                    This will convert the quote to an invoice and auto-create a project. Select team members to assign the project to.
                </p>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-weight:600;font-size:13px;color:#334155;margin-bottom:6px">Assign Project To</label>
                    <select id="convert-invoice-assign-user" class="form-select" multiple style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <small style="color:#94a3b8;font-size:11px;margin-top:4px;display:block">Hold Ctrl/Cmd to select multiple users</small>
                </div>
            </div>
            <div style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc">
                <button type="button" onclick="closeConvertInvoiceModal()"
                    style="padding:8px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b">Cancel</button>
                <button type="button" id="btn-confirm-convert-invoice" onclick="submitConvertInvoice()"
                    style="padding:8px 18px;background:linear-gradient(135deg,#22c55e,#16a34a);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(22,163,74,0.3)">Convert & Assign</button>
            </div>
        </div>
    </div>

    <!-- Assign Project Modal (shown after auto-project creation) -->
    <div id="assign-project-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10002;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div style="background:white;border-radius:12px;width:95%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#eff6ff,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:600;color:#2563eb;display:flex;align-items:center;gap:8px">
                    <i data-lucide="folder-check" style="width:18px;height:18px"></i> Project Created
                </h3>
                <button onclick="closeAssignProjectModal()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b">&times;</button>
            </div>
            <div style="padding:20px">
                <p style="font-size:13px;color:#64748b;margin:0 0 8px 0;line-height:1.5">
                    A project has been auto-created. Assign team members below:
                </p>
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px 14px;margin-bottom:16px">
                    <span style="font-size:13px;font-weight:600;color:#0369a1" id="assign-project-name"></span>
                </div>
                <input type="hidden" id="assign-project-id">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-weight:600;font-size:13px;color:#334155;margin-bottom:6px">Assign To</label>
                    <select id="assign-project-users" class="form-select" multiple style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;min-height:80px">
                        @foreach($projectGlobalUsers ?? [] as $pUser)
                            <option value="{{ $pUser->id }}" {{ $pUser->id == auth()->id() ? 'selected' : '' }}>{{ $pUser->name }}</option>
                        @endforeach
                    </select>
                    <small style="color:#94a3b8;font-size:11px;margin-top:4px;display:block">Hold Ctrl/Cmd to select multiple users</small>
                </div>
            </div>
            <div style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:space-between;gap:10px;background:#fafbfc">
                <button type="button" onclick="skipAssignProject()"
                    style="padding:8px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b">Skip</button>
                <button type="button" id="btn-assign-project" onclick="submitAssignProject()"
                    style="padding:8px 18px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(37,99,235,0.3)">Assign & Continue</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Override openDrawer/closeDrawer for the centered quote modal
        (function () {
            var origOpen = window.openDrawer;
            var origClose = window.closeDrawer;

            window.openDrawer = function (id) {
                if (id === 'quote-drawer') {
                    var modal = document.getElementById('quote-drawer');
                    var overlay = document.getElementById('drawer-overlay');
                    if (overlay) { overlay.style.display = 'block'; }
                    if (modal) {
                        modal.style.display = 'flex';
                        setTimeout(function () {
                            modal.style.opacity = '1';
                            modal.style.transform = 'translate(-50%,-50%) scale(1)';
                        }, 10);
                    }
                    document.body.style.overflow = 'hidden';
                } else if (origOpen) {
                    origOpen(id);
                }
            };

            window.closeDrawer = function (id) {
                if (id === 'quote-drawer') {
                    var modal = document.getElementById('quote-drawer');
                    var overlay = document.getElementById('drawer-overlay');
                    if (modal) {
                        modal.style.opacity = '0';
                        modal.style.transform = 'translate(-50%,-50%) scale(0.95)';
                        setTimeout(function () { modal.style.display = 'none'; }, 300);
                    }
                    if (overlay) { setTimeout(function () { overlay.style.display = 'none'; }, 300); }
                    document.body.style.overflow = '';
                } else if (origClose) {
                    origClose(id);
                }
            };
        })();

        document.addEventListener('DOMContentLoaded', () => {
            // Watch quote lead selection to toggle convert button
            const quoteLeadSelect = document.getElementById('quote-lead-id');
            if (quoteLeadSelect) {
                quoteLeadSelect.addEventListener('change', function () {
                    const btn = document.getElementById('btn-convert-client');
                    if (btn && this.value && document.getElementById('quote-client-type').value === 'lead') {
                        btn.style.display = 'flex';
                    } else if (btn) {
                        btn.style.display = 'none';
                    }
                });
            }
            // Search
            const searchInput = document.getElementById('quotes-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const filter = this.value.toLowerCase();
                    document.querySelectorAll('.quotes-tbody tr').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
            }
        });

        function filterByStatus(status) {
            document.querySelectorAll('.quotes-tbody tr').forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function calcQuoteTotal() {
            var s = parseFloat(document.getElementById('q-subtotal').value) || 0;
            var d = parseFloat(document.getElementById('q-discount').value) || 0;
            var rate = parseFloat(document.getElementById('q-tax-rate').value) || 0;

            var taxable = s - d;
            if (taxable < 0) taxable = 0;

            var t = taxable * (rate / 100);

            document.getElementById('q-tax').value = t.toFixed(2);
            var taxDisplay = document.getElementById('q-tax-display-input');
            if (taxDisplay) taxDisplay.value = '₹' + t.toFixed(2);

            var total = taxable + t;
            document.getElementById('q-total').value = '₹' + total.toFixed(2);
        }

        // ====== Quote Product Selection Functions ======
        var qProductRowIndex = 0;

        function addQuoteProduct() {
            var sel = document.getElementById('quote-product-selector');
            if (!sel.value) return;
            var opt = sel.options[sel.selectedIndex];
            var pid = sel.value;
            var name = opt.getAttribute('data-name');
            var price = opt.getAttribute('data-price');
            var desc = opt.getAttribute('data-desc');
            var isPurchase = opt.getAttribute('data-purchase') === '1';
            addQuoteProductRow(pid, name, price, desc, 1, 0, isPurchase, 0);
            sel.value = '';
        }

        function addQuoteProductRow(pid, name, price, desc, qty, discount, isPurchase, purchaseAmt) {
            var existing = document.querySelectorAll('#quote-products-body input[data-qproduct-id="' + pid + '"]');
            if (existing.length > 0) {
                alert('This product is already added!');
                return;
            }

            var tbody = document.getElementById('quote-products-body');
            var table = document.getElementById('quote-products-table');
            var idx = qProductRowIndex++;
            discount = discount || 0;
            purchaseAmt = purchaseAmt || 0;

            var purchaseHtml = '';
            if (isPurchase) {
                purchaseHtml = '<div style="display:flex;align-items:center;justify-content:center;gap:4px">' +
                    '<input type="number" name="product_purchase_amounts[]" value="' + purchaseAmt + '" min="0" step="0.01" style="width:80px;padding:4px;border:1px solid #f59e0b;border-radius:4px;font-size:12px;text-align:center" placeholder="0">' +
                    '</div>';
            } else {
                purchaseHtml = '<span style="font-size:11px;color:#aaa;display:block;text-align:center">N/A</span><input type="hidden" name="product_purchase_amounts[]" value="0">';
            }

            var tr = document.createElement('tr');
            tr.id = 'qproduct-row-' + idx;
            tr.innerHTML = '<td style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px">' + escapeHtmlQ(name) + '</td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0"><input type="number" name="product_prices[]" value="' + parseFloat(price).toFixed(2) + '" min="0" step="0.01" oninput="calcQuoteTotalFromProducts()" style="width:100px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center;font-weight:600"></td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0">' +
                '<textarea name="product_descriptions[]" id="qdesc-store-' + idx + '" style="display:none">' + escapeHtmlQ(desc || '') + '</textarea>' +
                '<div style="display:flex;align-items:center;gap:4px">' +
                '<span id="qdesc-preview-' + idx + '" style="flex:1;padding:4px;font-size:13px;color:#666;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:200px;display:inline-block">' + escapeHtmlQ((desc || '').replace(/\n/g, ' ')) + '</span>' +
                '<button type="button" onclick="openQDescPopup(' + idx + ')" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;cursor:pointer;padding:4px 6px;display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0" title="Edit Description"><i data-lucide="pencil" style="width:12px;height:12px"></i></button>' +
                '</div>' +
                '</td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0"><input type="number" name="product_quantities[]" value="' + qty + '" min="1" oninput="calcQuoteTotalFromProducts()" style="width:60px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center"></td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0"><input type="number" name="product_discounts[]" value="' + discount + '" min="0" oninput="calcQuoteTotalFromProducts()" style="width:90px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center" placeholder="0"></td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0;text-align:center">' + purchaseHtml + '</td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0;text-align:center">' +
                '<button type="button" onclick="removeQuoteProductRow(' + idx + ')" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:16px" title="Remove">&times;</button>' +
                '<input type="hidden" name="product_ids[]" value="' + pid + '" data-qproduct-id="' + pid + '">' +
                '</td>';
            tbody.appendChild(tr);
            table.style.display = 'table';

            calcQuoteTotalFromProducts();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function togglePurchaseInput(idx) {
            var wrap = document.getElementById('purchase-input-wrap-' + idx);
            if (wrap) {
                wrap.style.display = wrap.style.display === 'none' ? 'flex' : 'none';
                if (wrap.style.display === 'flex') {
                    var inp = document.getElementById('purchase-amt-' + idx);
                    if (inp) inp.focus();
                }
            }
        }

        function removeQuoteProductRow(idx) {
            var row = document.getElementById('qproduct-row-' + idx);
            if (row) row.remove();
            var tbody = document.getElementById('quote-products-body');
            if (tbody.children.length === 0) {
                document.getElementById('quote-products-table').style.display = 'none';
            }
            calcQuoteTotalFromProducts();
        }

        function clearQuoteProducts() {
            document.getElementById('quote-products-body').innerHTML = '';
            document.getElementById('quote-products-table').style.display = 'none';
            qProductRowIndex = 0;
        }

        function updateQuoteDiscountedPrice(input) {
            // Price is now editable directly, no visual update needed
        }

        function calcQuoteTotalFromProducts() {
            var subtotal = 0;
            var rows = document.querySelectorAll('#quote-products-body tr');
            rows.forEach(function (row) {
                var priceInput = row.querySelector('input[name="product_prices[]"]');
                var qtyInput = row.querySelector('input[name="product_quantities[]"]');
                var discInput = row.querySelector('input[name="product_discounts[]"]');
                if (priceInput && qtyInput) {
                    var price = parseFloat(priceInput.value) || 0;
                    var qty = parseInt(qtyInput.value) || 1;
                    var disc = parseFloat(discInput.value) || 0;
                    subtotal += (price * qty) - disc;
                }
            });
            document.getElementById('q-subtotal').value = subtotal.toFixed(2);
            calcQuoteTotal();
        }

        function escapeHtmlQ(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // ====== Description Edit Popup ======
        var activeQDescIdx = null;

        function openQDescPopup(idx) {
            activeQDescIdx = idx;
            var textarea = document.getElementById('qdesc-store-' + idx);
            if (textarea) {
                document.getElementById('qdesc-edit-textarea').value = textarea.value;
            }
            document.getElementById('qdesc-edit-popup').style.display = 'flex';
            document.getElementById('qdesc-edit-textarea').focus();
        }

        function closeQDescPopup() {
            document.getElementById('qdesc-edit-popup').style.display = 'none';
            activeQDescIdx = null;
        }

        function saveQDescPopup() {
            if (activeQDescIdx !== null) {
                var val = document.getElementById('qdesc-edit-textarea').value;
                var textarea = document.getElementById('qdesc-store-' + activeQDescIdx);
                if (textarea) {
                    textarea.value = val;
                }
                var preview = document.getElementById('qdesc-preview-' + activeQDescIdx);
                if (preview) {
                    preview.textContent = val.replace(/\n/g, ' ');
                }
            }
            closeQDescPopup();
        }

        function openCreateQuoteDrawer() {
            setFormEditable(true);
            document.getElementById('quote-drawer-title').textContent = 'Add Quote';
            document.getElementById('btn-save-quote').textContent = 'Save Quote';
            document.getElementById('quote-form').reset();
            document.getElementById('quote-form').action = "{{ route('admin.quotes.store') }}";
            document.getElementById('quote-form-method').value = 'POST';
            document.getElementById('q-tax-rate').value = "0";
            document.getElementById('q-tax').value = "0";
            var taxDisplay = document.getElementById('q-tax-display-input');
            if (taxDisplay) taxDisplay.value = '₹0.00';
            document.getElementById('quote-status-group').style.display = 'none';
            switchQuoteClientType('client');
            clearQuoteProducts();
            document.getElementById('quote-error-container').style.display = 'none';
            openDrawer('quote-drawer');
        }

        function switchQuoteClientType(type) {
            document.getElementById('quote-client-type').value = type;
            var convertBtn = document.getElementById('btn-convert-client');

            if (type === 'client') {
                document.getElementById('quote-client-section').style.display = '';
                document.getElementById('quote-lead-section').style.display = 'none';
                document.getElementById('btn-client-tab').style.background = '#4f46e5';
                document.getElementById('btn-client-tab').style.color = '#fff';
                document.getElementById('btn-lead-tab').style.background = '#f3f4f6';
                document.getElementById('btn-lead-tab').style.color = '#666';
                if (convertBtn) convertBtn.style.display = 'none';
            } else {
                document.getElementById('quote-client-section').style.display = 'none';
                document.getElementById('quote-lead-section').style.display = '';
                document.getElementById('btn-lead-tab').style.background = '#4f46e5';
                document.getElementById('btn-lead-tab').style.color = '#fff';
                document.getElementById('btn-client-tab').style.background = '#f3f4f6';
                document.getElementById('btn-client-tab').style.color = '#666';

                if (convertBtn && document.getElementById('quote-lead-id').value) {
                    convertBtn.style.display = 'flex';
                } else if (convertBtn) {
                    convertBtn.style.display = 'none';
                }
            }
        }

        function convertLeadToClient() {
            var leadId = document.getElementById('quote-lead-id').value;
            if (!leadId) {
                alert('Please select a lead first.');
                return;
            }
            // Show assign modal instead of confirm dialog
            document.getElementById('convert-client-modal').style.display = 'flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // Action button handler for Lead Quotes from the table
        function convertLeadQuoteToClient(quoteId, leadId) {
            document.getElementById('quote-lead-id').value = leadId;
            document.getElementById('convert-client-modal').style.display = 'flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeConvertModal() {
            document.getElementById('convert-client-modal').style.display = 'none';
        }

        function submitConvertToClient() {
            var leadId = document.getElementById('quote-lead-id').value;
            var assignUserId = document.getElementById('convert-assign-user').value;
            var btn = document.getElementById('btn-confirm-convert');
            btn.disabled = true;
            btn.textContent = 'Converting...';

            fetch(`{{ url('admin/leads') }}/${leadId}/convert-to-client`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ assigned_to_users: [assignUserId] })
            })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    if (res.status === 200) {
                        alert(res.body.message);
                        closeConvertModal();
                        if (res.body.project_id) {
                            showAssignProjectModal(res.body.project_id, res.body.project_name);
                        } else {
                            window.location.reload();
                        }
                    } else {
                        alert(res.body.message || 'Error converting lead to client.');
                        btn.disabled = false;
                        btn.textContent = 'Convert & Assign';
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
                    btn.disabled = false;
                    btn.textContent = 'Convert & Assign';
                });
        }

        function setFormEditable(editable) {
            var form = document.getElementById('quote-form');
            var elements = form.querySelectorAll('input, select, textarea, button');
            elements.forEach(function (el) {
                if (el.id === 'btn-cancel-quote' || el.classList.contains('close-drawer')) return;
                el.disabled = !editable;
            });

            // Specific handling for product delete buttons and add product button
            var deleteBtns = document.querySelectorAll('.remove-product-btn'); // Assuming class
            deleteBtns.forEach(b => b.style.display = editable ? '' : 'none');

            var addProductBtn = document.querySelector('button[onclick="addQuoteProduct()"]');
            if (addProductBtn) addProductBtn.style.display = editable ? '' : 'none';

            var submitBtn = document.querySelector('#btn-save-quote');
            if (submitBtn) submitBtn.style.display = editable ? '' : 'none';

            var editBtn = document.querySelector('#btn-edit-quote');
            if (editBtn) editBtn.style.display = editable ? 'none' : 'flex';
        }

        function editQuote(id, btn) {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="spin" style="width:16px;height:16px"></i>';
                if (typeof lucide !== 'undefined') lucide.createIcons({'name': 'loader-2'});
            }

            // Fetch quote details
            fetch(`{{ url('admin/quotes') }}/${id}/edit`)
                .then(response => response.json())
                .then(quote => {
                    setFormEditable(true); // Ensure editable

                    var editBtn = document.getElementById('btn-edit-quote');
                    if (editBtn) editBtn.style.display = 'none';

                    document.getElementById('quote-drawer-title').textContent = 'Edit Quote';
                    document.getElementById('quote-form').action = `{{ url('admin/quotes') }}/${id}`;
                    document.getElementById('quote-form-method').value = 'PUT';

                    var isInvoice = quote.status === 'accepted';
                    document.getElementById('quote-drawer-title').textContent = isInvoice ? 'Edit Invoice' : 'Edit Quote';
                    document.getElementById('btn-save-quote').textContent = isInvoice ? 'Save Invoice' : 'Save Quote';

                    // Populate fields - detect client type
                    if (quote.lead_id) {
                        switchQuoteClientType('lead');
                        setInputValue('lead_id', quote.lead_id);
                        // Explicitly show Convert to Client button for lead quotes
                        var convertBtn = document.getElementById('btn-convert-client');
                        if (convertBtn) convertBtn.style.display = 'flex';
                    } else {
                        switchQuoteClientType('client');
                        setInputValue('client_id', quote.client_id);
                    }
                    setInputValue('assigned_to_user_id', quote.assigned_to_user_id);
                    setInputValue('quote_date', quote.date ? quote.date.substring(0, 10) : '');
                    setInputValue('valid_until', quote.valid_till ? quote.valid_till.substring(0, 10) : '');
                    setInputValue('subtotal', (quote.subtotal / 100).toFixed(2));
                    setInputValue('discount', (quote.discount / 100).toFixed(2));

                    var taxAmount = parseFloat(quote.tax_amount || quote.gst_total || 0) / 100;
                    var sub = parseFloat(quote.subtotal || 0) / 100;
                    var disc = parseFloat(quote.discount || 0) / 100;
                    var taxable = sub - disc;

                    if (taxable > 0 && taxAmount > 0) {
                        // Calculate rate percentage
                        var rate = (taxAmount / taxable) * 100;
                        var select = document.getElementById('q-tax-rate');
                        var found = false;
                        
                        for (var i = 0; i < select.options.length; i++) {
                            var optVal = parseFloat(select.options[i].value || 0);
                            // Match if within 0.1% to handle JS float quirks
                            if (Math.abs(optVal - rate) < 0.1) {
                                select.selectedIndex = i;
                                found = true;
                                break;
                            }
                        }
                        if (!found) select.value = "0";
                    } else {
                        document.getElementById('q-tax-rate').value = "0";
                    }

                    document.getElementById('q-tax').value = taxAmount.toFixed(2);
                    var taxDisplay = document.getElementById('q-tax-display-input');
                    if (taxDisplay) taxDisplay.value = '₹' + taxAmount.toFixed(2);

                    document.getElementById('quote-notes-input').value = quote.notes || '';

                    // Show and populate status
                    document.getElementById('quote-status-group').style.display = '';
                    setInputValue('status', quote.status);

                    // Load products
                    clearQuoteProducts();
                    if (quote.items && quote.items.length > 0) {
                        loadQuoteProducts(quote.items);
                    }

                    calcQuoteTotal();
                    openDrawer('quote-drawer');
                })
                .catch(error => {
                    console.error('Error fetching quote:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i data-lucide="edit" style="width:16px;height:16px"></i>';
                        if (typeof lucide !== 'undefined') lucide.createIcons({'name': 'edit'});
                    }
                });
        }

        function viewQuote(id, btn) {
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="spin" style="width:16px;height:16px"></i>';
                if (typeof lucide !== 'undefined') lucide.createIcons({'name': 'loader-2'});
            }

            fetch(`{{ url('admin/quotes') }}/${id}`)
                .then(response => response.json())
                .then(quote => {
                    setFormEditable(false); // Make read-only

                    var editBtn = document.getElementById('btn-edit-quote');
                    if (editBtn) {
                        editBtn.onclick = function () { 
                            var originalBtn = document.querySelector('button[onclick="editQuote('+id+', this)"]') || null;
                            editQuote(id, originalBtn); 
                        };
                    }

                    var isInvoice = quote.status === 'accepted';
                    document.getElementById('quote-drawer-title').textContent = isInvoice ? 'View Invoice' : 'View Quote';
                    var btnEditText = document.getElementById('btn-edit-text');
                    if (btnEditText) btnEditText.textContent = isInvoice ? 'Edit Invoice' : 'Edit Quote';

                    // Populate fields (same as edit)
                    if (quote.lead_id) {
                        switchQuoteClientType('lead');
                        setInputValue('lead_id', quote.lead_id);
                        // Explicitly show Convert to Client button for lead quotes
                        var convertBtn = document.getElementById('btn-convert-client');
                        if (convertBtn) convertBtn.style.display = 'flex';
                    } else {
                        switchQuoteClientType('client');
                        setInputValue('client_id', quote.client_id);
                    }
                    setInputValue('assigned_to_user_id', quote.assigned_to_user_id);
                    setInputValue('quote_date', quote.date ? quote.date.substring(0, 10) : '');
                    setInputValue('valid_until', quote.valid_till ? quote.valid_till.substring(0, 10) : '');
                    setInputValue('subtotal', (quote.subtotal / 100).toFixed(2));
                    setInputValue('discount', (quote.discount / 100).toFixed(2));

                    var taxAmount = parseFloat(quote.tax_amount || quote.gst_total || 0) / 100;
                    var sub = parseFloat(quote.subtotal || 0) / 100;
                    var disc = parseFloat(quote.discount || 0) / 100;
                    var taxable = sub - disc;

                    if (taxable > 0 && taxAmount > 0) {
                        var rate = (taxAmount / taxable) * 100;
                        var select = document.getElementById('q-tax-rate');
                        var found = false;
                        for (var i = 0; i < select.options.length; i++) {
                            var optVal = parseFloat(select.options[i].value || 0);
                            if (Math.abs(optVal - rate) < 0.1) {
                                select.selectedIndex = i;
                                found = true;
                                break;
                            }
                        }
                        if (!found) select.value = "0";
                    } else {
                        document.getElementById('q-tax-rate').value = "0";
                    }

                    document.getElementById('q-tax').value = taxAmount.toFixed(2);
                    var viewTaxDisplay = document.getElementById('q-tax-display-input');
                    if (viewTaxDisplay) viewTaxDisplay.value = '₹' + taxAmount.toFixed(2);

                    document.getElementById('quote-notes-input').value = quote.notes || '';
                    document.getElementById('quote-status-group').style.display = '';
                    setInputValue('status', quote.status);

                    clearQuoteProducts();
                    if (quote.items && quote.items.length > 0) {
                        loadQuoteProducts(quote.items);
                    }
                    calcQuoteTotal();
                    openDrawer('quote-drawer');
                })
                .catch(error => {
                    console.error('Error fetching quote:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i data-lucide="eye" style="width:16px;height:16px"></i>';
                        if (typeof lucide !== 'undefined') lucide.createIcons({'name': 'eye'});
                    }
                });
        }

        function downloadQuote(id) {
            window.open(`{{ url('admin/quotes') }}/${id}/download`, '_blank');
        }

        function loadQuoteProducts(items) {
            items.forEach(function (item) {
                // Determine if price includes discount
                // The item.unit_price is stored as integer paise.
                // We need to reconstruct the original price if possible or just show unit_price
                // Since we don't store original price in QuoteItem, we can fetch product details if needed
                // But for now let's use the stored unit_price as the price
                // Actually, QuoteItem unit_price is after discount ? No, let's check controller.
                // Controller: $unitPrice = ($product->mrp / 100) - $discountPerUnit;
                // So unit_price in DB is discounted price.
                // To restore the UI (Price, Dis. Price), we need original price. But we don't have it in QuoteItems directly unless we join products?
                // Wait, QuoteItem has product_id. We can try to find the product in the dropdown list to get its current price?
                // Limitation: If product price changed since quote creation, this might be off.
                // Better approach: QuoteItem should probably store discount amount or original price.
                // Checking QuoteItem model... it has unit_price, gst_amount, line_total. No discount column!
                // Ah, in controller store method: $unitPrice = ... - $discountPerUnit.
                // So the discount is baked into unit_price.
                // This means we CANNOT easily show "Price" and "Dis. Price" in the table separately for existing items unless we assume the current product price is valid.
                // Let's rely on finding the product in the global `products` list if available.

                var price = item.unit_price / 100;
                var discount = 0;

                // Try to use DB explicit rate and discount first (for newer quotes)
                if (item.rate !== undefined && item.discount !== undefined && item.rate > 0) {
                    price = item.rate / 100;
                    discount = item.discount / 100;
                } else {
                    // Fallback to legacy calculation for old quotes without explicit rate/discount columns
                    var option = document.querySelector(`#quote-product-selector option[value="${item.product_id}"]`);
                    if (option) {
                        var currentPrice = parseFloat(option.getAttribute('data-price'));
                        if (currentPrice > price) {
                            discount = currentPrice - price;
                            price = currentPrice;
                        }
                    }
                }

                // Determine if this product has purchase enabled
                var isPurchase = false;
                var purchaseAmt = 0;
                var option = document.querySelector('#quote-product-selector option[value="' + item.product_id + '"]');
                if (option && option.getAttribute('data-purchase') === '1') {
                    isPurchase = true;
                }
                if (item.purchase_amount !== undefined && item.purchase_amount > 0) {
                    purchaseAmt = item.purchase_amount / 100;
                }

                addQuoteProductRow(item.product_id, item.product_name, price, item.description, item.qty, discount, isPurchase, purchaseAmt);
            });
        }

        function setInputValue(name, value) {
            var form = document.getElementById('quote-form');
            var el = form ? form.querySelector(`[name="${name}"]`) : document.querySelector(`[name="${name}"]`);
            if (el) {
                el.value = value;
                if (el.tagName.toLowerCase() === 'select') {
                    $(el).trigger('change');
                }
            }
        }

        function deleteQuote(id) {
            var btn = event ? event.currentTarget : null;
            ajaxDelete('{{ url("admin/quotes") }}/' + id, btn, 'Quote');
        }

        function convertQuote(id) {
            // Keep this for backward compatibility or direct calls if needed, 
            // but the UI main button now calls openConvertInvoiceModal directly.
            openConvertInvoiceModal(id);
        }

        function openConvertInvoiceModal(quoteId) {
            document.getElementById('convert-invoice-quote-id').value = quoteId;
            document.getElementById('convert-invoice-modal').style.display = 'flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeConvertInvoiceModal() {
            document.getElementById('convert-invoice-modal').style.display = 'none';
        }

        function submitConvertInvoice() {
            var quoteId = document.getElementById('convert-invoice-quote-id').value;
            // Use jQuery to get Select2 multiple selected values
            var assignedUsers = $('#convert-invoice-assign-user').val() || [];

            if (assignedUsers.length === 0) {
                alert('Please select at least one user.');
                return;
            }

            var btn = document.getElementById('btn-confirm-convert-invoice');
            btn.disabled = true;
            btn.textContent = 'Converting...';

            fetch(`{{ url('admin/quotes') }}/${quoteId}/convert`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ assigned_to_users: assignedUsers })
            })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    if (res.status === 200) {
                        alert(res.body.message || 'Quote converted successfully.');
                        closeConvertInvoiceModal();
                        window.location.reload();
                    } else {
                        alert(res.body.message || 'An error occurred while converting the quote.');
                        btn.disabled = false;
                        btn.textContent = 'Convert & Assign';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while converting the quote.');
                    btn.disabled = false;
                    btn.textContent = 'Convert & Assign';
                });
        }

        function submitQuoteForm() {
            var form = document.getElementById('quote-form');
            var formData = new FormData(form);
            var url = form.action;

            // Clear errors
            document.getElementById('quote-error-container').style.display = 'none';
            document.getElementById('quote-error-list').innerHTML = '';

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json().then(data => ({ status: response.status, ok: response.ok, body: data })))
                .then(res => {
                    if (res.ok) {
                        closeDrawer('quote-drawer');
                        if (res.body.project_id) {
                            showAssignProjectModal(res.body.project_id, res.body.project_name);
                        } else {
                            alert(res.body.message || 'Quote saved successfully.');
                            window.location.reload();
                        }
                    } else {
                        if (res.body.errors) {
                            var list = document.getElementById('quote-error-list');
                            Object.values(res.body.errors).forEach(errs => {
                                errs.forEach(err => {
                                    var li = document.createElement('li');
                                    li.textContent = err;
                                    list.appendChild(li);
                                });
                            });
                            document.getElementById('quote-error-container').style.display = 'block';
                            document.querySelector('.drawer-body').scrollTop = 0;
                        } else {
                            alert(res.body.message || 'An error occurred');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the quote.');
                });
        }

        const leadTotalAmountFormatted = "{{ number_format($leadTotalAmount, 2) }}";
        const leadDueAmountFormatted = "{{ number_format($leadDueAmount, 2) }}";

        document.addEventListener('DOMContentLoaded', () => {
            // Check for edit open quote parameter
            const urlParams = new URLSearchParams(window.location.search);
            const openQuoteId = urlParams.get('open_quote');
            if (openQuoteId) {
                // Remove parameter from URL quietly
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: newUrl }, '', newUrl);

                // Open the quote view modal
                setTimeout(() => {
                    viewQuote(openQuoteId);
                }, 300); // Slight delay to ensure everything is ready
            }
        });

        // ====== Payment Modal Functions ======
        document.addEventListener('DOMContentLoaded', function () {
            var pmodal = document.getElementById('payment-modal-overlay');
            if (pmodal) {
                pmodal.addEventListener('click', function (e) {
                    if (e.target === pmodal) closePaymentModal();
                });
            }
        });

        // ====== Convert Lead Quote To Client ======
        function convertLeadQuoteToClient(quoteId, leadId) {
            document.getElementById('convert-lead-id').value = leadId;
            document.getElementById('convert-client-modal').style.display = 'flex';
        }

        function closeConvertModal() {
            document.getElementById('convert-client-modal').style.display = 'none';
        }

        function submitConvertToClient() {
            var leadId = document.getElementById('convert-lead-id').value;
            // Use jQuery to get Select2 multiple selected values
            var assignedUsers = $('#convert-assign-user').val() || [];

            if (assignedUsers.length === 0) {
                alert('Please select at least one user.');
                return;
            }

            var btn = document.getElementById('btn-confirm-convert');
            btn.disabled = true;
            btn.textContent = 'Converting...';

            fetch(`{{ url('admin/leads') }}/${leadId}/convert-to-client`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    assigned_to_users: assignedUsers
                })
            })
                .then(response => response.json())
                .then(data => {
                    closeConvertModal();
                    alert(data.message || 'Lead converted successfully.');
                    window.location.reload();
                })
                .catch(err => {
                    console.error('Error converting lead:', err);
                    alert('An error occurred during conversion.');
                    btn.disabled = false;
                    btn.textContent = 'Convert & Assign';
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            let startDt = document.getElementById('start-date').value;
            let dueDt = document.getElementById('due-date').value;
            let defDates = [];
            if (startDt && dueDt) defDates = [startDt, dueDt];

            flatpickr("#quote-date-range-picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: defDates,
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('start-date').value = instance.formatDate(selectedDates[0], "Y-m-d");
                        document.getElementById('due-date').value = instance.formatDate(selectedDates[1], "Y-m-d");
                        instance.element.closest('form').submit();
                    } else if (selectedDates.length === 0) {
                        document.getElementById('start-date').value = '';
                        document.getElementById('due-date').value = '';
                        instance.element.closest('form').submit();
                    }
                }
            });
        });

        // ====== Payment Modal Functions ======
        function openPaymentModal(quoteId, quoteNo, totalAmount) {
            document.getElementById('payment-quote-id').value = quoteId;
            document.getElementById('payment-modal-quote-info').textContent = quoteNo + ' — Total: ₹' + totalAmount.toFixed(2);
            document.getElementById('payment-error-container').style.display = 'none';
            document.getElementById('payment-amount').value = '';
            document.getElementById('payment-notes').value = '';
            document.getElementById('payment-type').value = '{{ $paymentTypes[0] ?? '' }}';

            // Auto-fill current date/time
            var now = new Date();
            var pad = n => n.toString().padStart(2, '0');
            var dateStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
            document.getElementById('payment-date').value = dateStr;

            // Show modal with animation
            document.getElementById('payment-modal-overlay').style.display = 'block';
            document.getElementById('payment-modal').style.display = 'block';
            setTimeout(function () {
                document.getElementById('payment-modal').style.opacity = '1';
                document.getElementById('payment-modal').style.transform = 'translate(-50%,-50%) scale(1)';
            }, 10);

            // Re-init lucide icons for the modal
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closePaymentModal() {
            var modal = document.getElementById('payment-modal');
            modal.style.opacity = '0';
            modal.style.transform = 'translate(-50%,-50%) scale(0.95)';
            setTimeout(function () {
                modal.style.display = 'none';
                document.getElementById('payment-modal-overlay').style.display = 'none';
            }, 200);
        }

        function submitPayment() {
            var quoteId = document.getElementById('payment-quote-id').value;
            var amount = document.getElementById('payment-amount').value;
            var paymentType = document.getElementById('payment-type').value;
            var paymentDate = document.getElementById('payment-date').value;
            var notes = document.getElementById('payment-notes').value;
            var errContainer = document.getElementById('payment-error-container');

            if (!amount || parseFloat(amount) <= 0) {
                errContainer.textContent = 'Please enter a valid amount.';
                errContainer.style.display = 'block';
                return;
            }
            if (!paymentDate) {
                errContainer.textContent = 'Please select a payment date.';
                errContainer.style.display = 'block';
                return;
            }

            errContainer.style.display = 'none';

            fetch('{{ route("admin.payments.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    quote_id: quoteId,
                    amount: amount,
                    payment_type: paymentType,
                    payment_date: paymentDate,
                    notes: notes
                })
            })
                .then(function (response) { return response.json().then(function (data) { return { status: response.status, body: data }; }); })
                .then(function (res) {
                    if (res.status === 200) {
                        // Update due amount cell
                        var dueCell = document.getElementById('due-amount-' + quoteId);
                        if (dueCell) {
                            var dueAmt = res.body.due_amount;
                            var color = dueAmt > 0 ? '#ef4444' : '#059669';
                            dueCell.innerHTML = '<span style="font-weight:700;color:' + color + ';">₹' + parseFloat(dueAmt).toFixed(2) + '</span>';
                        }
                        closePaymentModal();
                        alert('Payment recorded successfully!');
                    } else {
                        var msg = res.body.message || 'Error recording payment.';
                        if (res.body.errors) {
                            msg = Object.values(res.body.errors).flat().join(', ');
                        }
                        errContainer.textContent = msg;
                        errContainer.style.display = 'block';
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    errContainer.textContent = 'An error occurred. Please try again.';
                    errContainer.style.display = 'block';
                });
        }

        // ====== Assign Project Modal Functions ======
        function showAssignProjectModal(projectId, projectName) {
            document.getElementById('assign-project-id').value = projectId;
            document.getElementById('assign-project-name').textContent = projectName || 'New Project';
            document.getElementById('assign-project-modal').style.display = 'flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeAssignProjectModal() {
            document.getElementById('assign-project-modal').style.display = 'none';
        }

        function skipAssignProject() {
            closeAssignProjectModal();
            window.location.reload();
        }

        function submitAssignProject() {
            var projectId = document.getElementById('assign-project-id').value;
            var select = document.getElementById('assign-project-users');
            var selectedUsers = Array.from(select.selectedOptions).map(o => o.value);

            if (selectedUsers.length === 0) {
                alert('Please select at least one user.');
                return;
            }

            var btn = document.getElementById('btn-assign-project');
            btn.disabled = true;
            btn.textContent = 'Assigning...';

            fetch(`{{ url('admin/projects') }}/${projectId}/assign`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ assigned_to_users: selectedUsers })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Project assigned successfully.');
                    } else {
                        alert(data.message || 'Failed to assign project.');
                    }
                    closeAssignProjectModal();
                    window.location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
                    btn.disabled = false;
                    btn.textContent = 'Assign & Continue';
                });
        }
    </script>
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Include Select2 for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-dropdown {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #3b82f6;
        }

        /* Modern Select2 Multi-Select Styling (Professional Pill Design) */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--multiple {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            min-height: 44px;
            padding: 2px 4px;
            background-color: #fff;
            transition: all 0.2s ease;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #16a34a; /* CRM Green Theme */
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f0fdf4; /* Light green bg */
            border: 1px solid #bbf7d0; /* Soft green border */
            border-radius: 20px; /* Fully rounded pill shape */
            color: #166534; /* Dark green text */
            padding: 4px 12px 4px 26px; /* 26px left padding to provide space for the absolute X */
            margin: 4px;
            font-size: 13.5px;
            font-weight: 500;
            position: relative; /* For absolute positioning of the remove icon */
            display: inline-flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #166534;
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%); /* Centers the X vertically perfectly */
            border: none;
            background: transparent;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            margin: 0;
            line-height: 1;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #dc2626; /* Turn red when hovered */
            background: transparent;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            padding: 0;
            margin: 0;
            cursor: default;
        }
        .select2-search--inline .select2-search__field {
            margin-top: 6px;
            margin-left: 8px;
            font-family: inherit;
            color: #334155;
        }
    </style>
    <script>
        $(document).ready(function () {
            $('#quote-client-id').select2({
                placeholder: "Select client",
                allowClear: true,
                dropdownParent: $('#quote-drawer')
            });
            $('#quote-lead-id').select2({
                placeholder: "Select lead",
                allowClear: true,
                dropdownParent: $('#quote-drawer')
            });

            // Professional assignment multiselect
            $('#convert-invoice-assign-user').select2({
                placeholder: "Search and select team members",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#convert-invoice-modal')
            });

            $('#convert-assign-user').select2({
                placeholder: "Search and select team members",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#convert-client-modal')
            });
        });
    </script>
@endpush