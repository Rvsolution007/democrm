@extends('admin.layouts.app')

@section('title', 'Quotes')
@section('breadcrumb', 'Quotes')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content"
            style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h1 class="page-title">Quotes & Invoices</h1>
                <p class="page-description">Manage quotations and invoices</p>
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

        <!-- TABS -->
        <div style="padding:0 20px;border-bottom:1px solid #e2e8f0;display:flex;gap:24px;margin-bottom:16px;">
            <button class="quote-tab-btn active" onclick="switchQuoteTab('leads')" id="tab-btn-leads"
                style="background:none;border:none;padding:12px 4px;font-size:14px;font-weight:600;color:#0f172a;border-bottom:2px solid #3b82f6;cursor:pointer;transition:all 0.2s;">Quotes</button>
            <button class="quote-tab-btn" onclick="switchQuoteTab('clients')" id="tab-btn-clients"
                style="background:none;border:none;padding:12px 4px;font-size:14px;font-weight:600;color:#64748b;border-bottom:2px solid transparent;cursor:pointer;transition:all 0.2s;">Invoices</button>
        </div>

        <!-- LEADS TABLE (Quotes) -->
        <div class="table-wrapper quote-tab-content" id="tab-content-leads">
            <table class="table" id="lead-quotes-table">
                <thead>
                    <tr>
                        <th class="sortable">Quote Number</th>
                        <th>Quote For</th>
                        <th>Assigned To</th>
                        <th class="sortable">Status</th>
                        <th class="sortable">Total</th>
                        <th class="sortable">Valid Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="quotes-tbody" id="lead-quotes-tbody">
                    @forelse($leadQuotes as $quote)
                        <tr data-status="{{ $quote->status }}">
                            <td>
                                <span class="font-medium">{{ $quote->quote_no }}</span>
                                @if($quote->tax_amount > 0)
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
                            <td>{{ $quote->assignedTo->name ?? '—' }}</td>
                            <td>
                                <span
                                    class="badge badge-{{ $quote->status === 'accepted' ? 'success' : ($quote->status === 'rejected' ? 'destructive' : ($quote->status === 'sent' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($quote->status) }}
                                </span>
                            </td>
                            <td class="font-medium">₹{{ number_format($quote->grand_total_in_rupees, 2) }}</td>
                            <td>{{ $quote->valid_till ? $quote->valid_till->format('d M Y') : '—' }}</td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-ghost btn-icon btn-sm" onclick="viewQuote({{ $quote->id }})"
                                        title="View"><i data-lucide="eye" style="width:16px;height:16px"></i></button>
                                    <button class="btn btn-ghost btn-icon btn-sm" onclick="downloadQuote({{ $quote->id }})"
                                        title="Download"><i data-lucide="download" style="width:16px;height:16px"></i></button>
                                    @if(can('quotes.write') && $quote->client_id)
                                        <button class="btn btn-ghost btn-icon btn-sm" style="color:#16a34a"
                                            onclick="convertQuote({{ $quote->id }})" title="Convert Quote"><i
                                                data-lucide="check-circle" style="width:16px;height:16px"></i></button>
                                    @endif
                                    @if(can('quotes.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" onclick="editQuote({{ $quote->id }})"
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

        <!-- CLIENTS TABLE (Invoices) -->
        <div class="table-wrapper quote-tab-content" id="tab-content-clients" style="display:none;">
            <table class="table" id="client-quotes-table">
                <thead>
                    <tr>
                        <th class="sortable">Invoice Number</th>
                        <th>Client</th>
                        <th>Assigned To</th>
                        <th class="sortable">Status</th>
                        <th class="sortable">Total</th>
                        <th class="sortable">Due Amount</th>
                        <th class="sortable">Valid Until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="quotes-tbody" id="client-quotes-tbody">
                    @forelse($clientQuotes as $quote)
                        <tr data-status="{{ $quote->status }}">
                            <td>
                                <span class="font-medium">{{ $quote->quote_no }}</span>
                                @if($quote->tax_amount > 0)
                                    <span style="margin-left:6px;font-size:11px;color:#0ea5e9;background:#e0f2fe;padding:2px 6px;border-radius:4px;font-weight:600">GST</span>
                                @endif
                                @if($quote->lead_id)
                                    <span
                                        style="margin-left:6px;font-size:11px;color:#6366f1;background:#eef2ff;padding:2px 6px;border-radius:4px">Lead
                                        #{{ $quote->lead_id }}</span>
                                @endif
                            </td>
                            <td>{{ $quote->client ? $quote->client->display_name : '—' }}</td>
                            <td>{{ $quote->assignedTo->name ?? '—' }}</td>
                            <td>
                                <span
                                    class="badge badge-{{ $quote->status === 'accepted' ? 'success' : ($quote->status === 'rejected' ? 'destructive' : ($quote->status === 'sent' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($quote->status) }}
                                </span>
                            </td>
                            <td class="font-medium">₹{{ number_format($quote->grand_total_in_rupees, 2) }}</td>
                            <td id="due-amount-{{ $quote->id }}">
                                @php $dueAmt = $quote->due_amount_in_rupees; @endphp
                                <span style="font-weight:700;color:{{ $dueAmt > 0 ? '#ef4444' : '#059669' }};">
                                    ₹{{ number_format($dueAmt, 2) }}
                                </span>
                            </td>
                            <td>{{ $quote->valid_till ? $quote->valid_till->format('d M Y') : '—' }}</td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-ghost btn-icon btn-sm" onclick="viewQuote({{ $quote->id }})"
                                        title="View"><i data-lucide="eye" style="width:16px;height:16px"></i></button>
                                    <button class="btn btn-ghost btn-icon btn-sm" onclick="downloadQuote({{ $quote->id }})"
                                        title="Download"><i data-lucide="download" style="width:16px;height:16px"></i></button>
                                    @if(can('quotes.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" style="color:#059669;"
                                            onclick="openPaymentModal({{ $quote->id }}, '{{ $quote->quote_no }}', {{ $quote->grand_total_in_rupees }})"
                                            title="Record Payment"><i data-lucide="indian-rupee"
                                                style="width:16px;height:16px"></i></button>
                                        <button class="btn btn-ghost btn-icon btn-sm" onclick="editQuote({{ $quote->id }})"
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
                            <td colspan="8" style="text-align:center;padding:40px 0;color:#999">
                                <i data-lucide="file-text" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                                <p style="margin:0;font-size:14px">No Invoices found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="table-footer">
                <span>Showing {{ $clientQuotes->count() }} of {{ $clientQuotes->total() }} entries</span>
                {{ $clientQuotes->links() }}
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
            <form id="quote-form" method="POST" action="{{ route('admin.quotes.store') }}">
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
                                    data-desc="{{ Str::limit($product->description ?? '', 60) }}">
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
                        <i data-lucide="edit" style="width:16px;height:16px;margin-right:6px"></i> Edit Quote
                    </button>
                @endif
                <button id="btn-save-quote" class="btn btn-primary" onclick="submitQuoteForm()">Save Quote</button>
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
            addQuoteProductRow(pid, name, price, desc, 1, 0);
            sel.value = '';
        }

        function addQuoteProductRow(pid, name, price, desc, qty, discount) {
            var existing = document.querySelectorAll('#quote-products-body input[data-qproduct-id="' + pid + '"]');
            if (existing.length > 0) {
                alert('This product is already added!');
                return;
            }

            var tbody = document.getElementById('quote-products-body');
            var table = document.getElementById('quote-products-table');
            var idx = qProductRowIndex++;
            discount = discount || 0;

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
                '<td style="padding:8px 10px;border:1px solid #e0e0e0;text-align:center">' +
                '<button type="button" onclick="removeQuoteProductRow(' + idx + ')" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:16px" title="Remove">&times;</button>' +
                '<input type="hidden" name="product_ids[]" value="' + pid + '" data-qproduct-id="' + pid + '">' +
                '</td>';
            tbody.appendChild(tr);
            table.style.display = 'table';

            calcQuoteTotalFromProducts();
            if (typeof lucide !== 'undefined') lucide.createIcons();
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
                    subtotal += (price - disc) * qty;
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
            setFormEditable(true); // Ensure editable

            var editBtn = document.getElementById('btn-edit-quote');
            if (editBtn) editBtn.style.display = 'none';

            document.getElementById('quote-form').reset();
            var qAssign = document.querySelector('#quote-drawer select[name=assigned_to_user_id]');
            if (qAssign) qAssign.value = '{{ auth()->id() }}';

            var isClientTab = document.getElementById('tab-btn-clients').classList.contains('active');
            document.getElementById('quote-drawer-title').textContent = isClientTab ? 'Create New Invoice' : 'Create New Quote';
            document.getElementById('quote-form').action = '{{ route('admin.quotes.store') }}';
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
            if (!confirm('Convert this Lead to a Client?\n\nIf the client already exists, new products will be added to the existing project as tasks.\nIf this is a new conversion, a new client, project, and tasks will be created.')) return;

            fetch(`{{ url('admin/leads') }}/${leadId}/convert-to-client`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    if (res.status === 200) {
                        alert(res.body.message);
                        window.location.reload();
                    } else {
                        alert(res.body.message || 'Error converting lead to client.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred.');
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

        function editQuote(id) {
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

                    // Populate fields - detect client type
                    if (quote.lead_id) {
                        document.getElementById('quote-drawer-title').textContent = 'Edit Quote';
                        switchQuoteClientType('lead');
                        setInputValue('lead_id', quote.lead_id);
                        // Explicitly show Convert to Client button for lead quotes
                        var convertBtn = document.getElementById('btn-convert-client');
                        if (convertBtn) convertBtn.style.display = 'flex';
                    } else {
                        document.getElementById('quote-drawer-title').textContent = 'Edit Invoice';
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
                .catch(error => console.error('Error fetching quote:', error));
        }

        function viewQuote(id) {
            fetch(`{{ url('admin/quotes') }}/${id}`)
                .then(response => response.json())
                .then(quote => {
                    setFormEditable(false); // Make read-only

                    var editBtn = document.getElementById('btn-edit-quote');
                    if (editBtn) {
                        editBtn.onclick = function () { editQuote(id); };
                    }

                    // Populate fields (same as edit)
                    if (quote.lead_id) {
                        document.getElementById('quote-drawer-title').textContent = 'View Quote';
                        switchQuoteClientType('lead');
                        setInputValue('lead_id', quote.lead_id);
                        // Explicitly show Convert to Client button for lead quotes
                        var convertBtn = document.getElementById('btn-convert-client');
                        if (convertBtn) convertBtn.style.display = 'flex';
                    } else {
                        document.getElementById('quote-drawer-title').textContent = 'View Invoice';
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
                .catch(error => console.error('Error fetching quote:', error));
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

                // Try to find original product price to calculate discount
                // This is a bit hacky but works if products list is available in DOM
                // Or we can just show net price and 0 discount for now if we can't determine it.
                // Let's try to match product_id

                // Since this runs after page load, product-selector options exist
                var option = document.querySelector(`#quote-product-selector option[value="${item.product_id}"]`);
                if (option) {
                    var currentPrice = parseFloat(option.getAttribute('data-price'));
                    // If current price is higher than item price, assume the difference is discount
                    if (currentPrice > price) {
                        discount = currentPrice - price;
                        price = currentPrice;
                    }
                }

                addQuoteProductRow(item.product_id, item.product_name, price, item.description, item.qty, discount);
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
            if (confirm('Are you sure you want to delete this quote?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ url('admin/quotes') }}/${id}`;

                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function convertQuote(id) {
            if (!confirm('Are you sure you want to convert this quote to an Invoice? This will create a project and auto-purchase entries.')) return;

            fetch(`{{ url('admin/quotes') }}/${id}/convert`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || 'Quote converted successfully.');
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while converting the quote.');
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
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        return response.json().then(data => {
                            if (data.errors) {
                                var list = document.getElementById('quote-error-list');
                                Object.values(data.errors).forEach(errs => {
                                    errs.forEach(err => {
                                        var li = document.createElement('li');
                                        li.textContent = err;
                                        list.appendChild(li);
                                    });
                                });
                                document.getElementById('quote-error-container').style.display = 'block';
                                // Scroll to top of drawer
                                document.querySelector('.drawer-body').scrollTop = 0;
                            } else {
                                alert(data.message || 'An error occurred');
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the quote.');
                });
        }

        const leadTotalAmountFormatted = "{{ number_format($leadTotalAmount, 2) }}";
        const leadDueAmountFormatted = "{{ number_format($leadDueAmount, 2) }}";
        const clientTotalAmountFormatted = "{{ number_format($clientTotalAmount, 2) }}";
        const clientDueAmountFormatted = "{{ number_format($clientDueAmount, 2) }}";

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

            // Tab switching logic
            const activeTab = localStorage.getItem('activeQuoteTab') || 'leads';
            switchQuoteTab(activeTab);
        });

        function switchQuoteTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.quote-tab-content').forEach(el => el.style.display = 'none');
            // Remove active class from all buttons
            document.querySelectorAll('.quote-tab-btn').forEach(el => {
                el.classList.remove('active');
                el.style.borderBottomColor = 'transparent';
                el.style.color = '#64748b';
            });

            // Show selected tab content
            const contentEl = document.getElementById('tab-content-' + tabName);
            if (contentEl) contentEl.style.display = 'block';

            // Set active class on selected button
            const btnEl = document.getElementById('tab-btn-' + tabName);
            if (btnEl) {
                btnEl.classList.add('active');
                btnEl.style.borderBottomColor = '#3b82f6';
                btnEl.style.color = '#0f172a';
            }

            // Update Summary Cards based on active tab
            const totalEl = document.getElementById('summary-total');
            const dueEl = document.getElementById('summary-due');
            const totalLabel = document.getElementById('summary-total-label');
            const dueLabel = document.getElementById('summary-due-label');

            if (tabName === 'leads') {
                if (totalEl) totalEl.textContent = '₹' + leadTotalAmountFormatted;
                if (dueEl) dueEl.textContent = '₹' + leadDueAmountFormatted;
                if (totalLabel) totalLabel.textContent = 'Total Amount (Quotes)';
                if (dueLabel) dueLabel.textContent = 'Due Amount (Quotes)';
            } else if (tabName === 'clients') {
                if (totalEl) totalEl.textContent = '₹' + clientTotalAmountFormatted;
                if (dueEl) dueEl.textContent = '₹' + clientDueAmountFormatted;
                if (totalLabel) totalLabel.textContent = 'Total Amount (Invoices)';
                if (dueLabel) dueLabel.textContent = 'Due Amount (Invoices)';
            }

            // Save to local storage so pagination keeps state
            localStorage.setItem('activeQuoteTab', tabName);
        }

        // ====== Payment Modal Functions ======
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
        });
    </script>
@endpush