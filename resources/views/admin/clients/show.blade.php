@extends('admin.layouts.app')

@section('title', $client->display_name . ' - Client')
@section('breadcrumb', 'Client Details')

@section('content')
    <div style="margin-bottom:20px">
        <a href="{{ route('admin.clients.index') }}" class="btn btn-outline btn-sm" style="gap:6px">
            <i data-lucide="arrow-left" style="width:16px;height:16px"></i> Back to Clients
        </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
        <!-- Client Info Card -->
        <div class="card">
            <div class="card-content">
                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
                    <div
                        style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);display:flex;align-items:center;justify-content:center;color:white;font-size:22px;font-weight:700">
                        {{ strtoupper(substr($client->contact_name ?? $client->business_name ?? 'C', 0, 1)) }}{{ strtoupper(substr($client->contact_name ?? $client->business_name ?? 'C', 1, 1)) }}
                    </div>
                    <div style="flex:1">
                        <h2 style="font-size:20px;font-weight:700;margin:0 0 4px 0">{{ $client->display_name }}</h2>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span
                                class="badge {{ $client->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($client->status ?? 'Active') }}</span>
                            <span class="badge badge-secondary">{{ ucfirst($client->type ?? 'Business') }}</span>
                        </div>
                    </div>
                </div>

                <div style="display:grid;gap:12px">
                    @if($client->contact_name)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="user" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Contact Name</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->contact_name }}</p>
                            </div>
                        </div>
                    @endif

                    @if($client->business_category)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="tag" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Business Category</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->business_category }}</p>
                            </div>
                        </div>
                    @endif

                    @if($client->business_name)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="building-2" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Business Name</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->business_name }}</p>
                            </div>
                        </div>
                    @endif

                    @if($client->phone)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="phone" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Phone</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->phone }}</p>
                            </div>
                        </div>
                    @endif

                    @if($client->email)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="mail" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Email</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->email }}</p>
                            </div>
                        </div>
                    @endif

                    @if($client->gstin)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="file-badge" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">GSTIN</p>
                                <p style="font-size:14px;font-weight:500;font-family:monospace;margin:0">{{ $client->gstin }}
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($client->pan)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px">
                            <i data-lucide="credit-card" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">PAN</p>
                                <p style="font-size:14px;font-weight:500;font-family:monospace;margin:0">{{ $client->pan }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Financial & Address Card -->
        <div style="display:flex;flex-direction:column;gap:20px">
            <!-- Financial Info -->
            <div class="card">
                <div class="card-content">
                    <h3 style="font-size:15px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                        <i data-lucide="indian-rupee" style="width:18px;height:18px;color:var(--primary)"></i> Financial
                        Details
                    </h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        @php
                            // All quotes bound to this client are converted quotes. Summing their amounts.
                            $totalQuotesAmount = $client->quotes->sum('grand_total_in_rupees');
                            $quotesDueAmount = $client->quotes->sum('due_amount_in_rupees');
                        @endphp

                        <div
                            style="padding:16px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:10px;text-align:center">
                            <p
                                style="font-size:11px;color:#2563eb;font-weight:600;text-transform:uppercase;margin:0 0 6px 0">
                                Total Amount (Quotes)</p>
                            <p style="font-size:22px;font-weight:700;color:#1d4ed8;margin:0">
                                ₹{{ number_format($totalQuotesAmount, 2) }}</p>
                        </div>
                        <div
                            style="padding:16px;background:linear-gradient(135deg,{{ $quotesDueAmount > 0 ? '#fee2e2,#fecaca' : '#f0fdf4,#dcfce7' }});border-radius:10px;text-align:center">
                            <p
                                style="font-size:11px;color:{{ $quotesDueAmount > 0 ? '#dc2626' : '#16a34a' }};font-weight:600;text-transform:uppercase;margin:0 0 6px 0">
                                Due Amount</p>
                            <p
                                style="font-size:22px;font-weight:700;color:{{ $quotesDueAmount > 0 ? '#b91c1c' : '#15803d' }};margin:0">
                                ₹{{ number_format($quotesDueAmount, 2) }}</p>
                        </div>
                    </div>
                    @if($client->payment_terms_days)
                        <div
                            style="margin-top:12px;padding:10px 14px;background:var(--muted);border-radius:8px;display:flex;align-items:center;gap:8px">
                            <i data-lucide="clock" style="width:14px;height:14px;color:#888"></i>
                            <span style="font-size:13px;color:#666">Payment Terms: <strong>{{ $client->payment_terms_days }}
                                    days</strong></span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Billing Address -->
            @if($client->billing_address)
                <div class="card">
                    <div class="card-content">
                        <h3 style="font-size:15px;font-weight:600;margin:0 0 12px 0;display:flex;align-items:center;gap:8px">
                            <i data-lucide="map-pin" style="width:18px;height:18px;color:var(--primary)"></i> Billing Address
                        </h3>
                        <p style="font-size:14px;color:#555;line-height:1.6;margin:0">
                            @if(is_array($client->billing_address))
                                {{ $client->billing_address['street'] ?? '' }}<br>
                                {{ $client->billing_address['city'] ?? '' }}{{ isset($client->billing_address['state']) ? ', ' . $client->billing_address['state'] : '' }}<br>
                                {{ $client->billing_address['pincode'] ?? '' }}
                            @else
                                {{ $client->billing_address }}
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <!-- Shipping Address -->
            @if($client->shipping_address)
                <div class="card">
                    <div class="card-content">
                        <h3 style="font-size:15px;font-weight:600;margin:0 0 12px 0;display:flex;align-items:center;gap:8px">
                            <i data-lucide="truck" style="width:18px;height:18px;color:var(--primary)"></i> Shipping Address
                        </h3>
                        <p style="font-size:14px;color:#555;line-height:1.6;margin:0">
                            @if(is_array($client->shipping_address))
                                {{ $client->shipping_address['street'] ?? '' }}<br>
                                {{ $client->shipping_address['city'] ?? '' }}{{ isset($client->shipping_address['state']) ? ', ' . $client->shipping_address['state'] : '' }}<br>
                                {{ $client->shipping_address['pincode'] ?? '' }}
                            @else
                                {{ $client->shipping_address }}
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if($client->lead)
        <div class="card" style="margin-bottom:24px">
            <div class="card-content">
                <h3 style="font-size:15px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                    <i data-lucide="target" style="width:18px;height:18px;color:var(--primary)"></i>
                    Origin Lead
                </h3>
                <div
                    style="display:flex;align-items:center;justify-content:space-between;padding:16px;background:var(--muted);border-radius:8px">
                    <div>
                        <h4 style="margin:0 0 4px 0;font-size:16px;font-weight:600">{{ $client->lead->name }}</h4>
                        <p style="margin:0;font-size:13px;color:#666">
                            Created on: <strong
                                style="color:#333">{{ $client->lead->created_at->format('d M Y, h:i A') }}</strong>
                        </p>
                    </div>
                    <div>
                        <button type="button" onclick="openLeadModal()" class="btn btn-outline btn-sm">
                            <i data-lucide="eye" style="width:14px;height:14px;margin-right:6px"></i> View Lead
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lead Detail Modal --}}
        <div id="lead-modal-overlay" onclick="closeLeadModal()"
            style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;animation:fadeIn 0.2s ease;">
        </div>
        <div id="lead-modal"
            style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;width:600px;max-width:90vw;max-height:85vh;overflow-y:auto;z-index:1001;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);animation:slideUp 0.3s ease;">
            <div
                style="padding:24px 24px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:16px;">
                <h3 style="margin:0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
                    <i data-lucide="target" style="width:20px;height:20px;color:var(--primary)"></i> Lead Details
                </h3>
                <button onclick="closeLeadModal()"
                    style="background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;display:flex;align-items:center;"
                    title="Close">
                    <i data-lucide="x" style="width:20px;height:20px;color:#666"></i>
                </button>
            </div>
            <div style="padding:24px;">
                {{-- Lead Name & Stage Badge --}}
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                    <div
                        style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;color:white;font-size:18px;font-weight:700;">
                        {{ strtoupper(substr($client->lead->name, 0, 2)) }}
                    </div>
                    <div>
                        <h4 style="margin:0 0 4px 0;font-size:17px;font-weight:600;">{{ $client->lead->name }}</h4>
                        <span
                            class="badge badge-{{ $client->lead->stage === 'won' ? 'success' : ($client->lead->stage === 'lost' ? 'destructive' : 'secondary') }}">{{ ucfirst($client->lead->stage ?? 'New') }}</span>
                    </div>
                </div>

                <div style="display:grid;gap:10px;">
                    @if($client->lead->phone)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="phone" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Phone</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->lead->phone }}</p>
                            </div>
                        </div>
                    @endif
                    @if($client->lead->email)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="mail" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Email</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->lead->email }}</p>
                            </div>
                        </div>
                    @endif
                    @if($client->lead->city || $client->lead->state)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="map-pin" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Location</p>
                                <p style="font-size:14px;font-weight:500;margin:0">
                                    {{ $client->lead->city }}{{ $client->lead->state ? ', ' . $client->lead->state : '' }}
                                </p>
                            </div>
                        </div>
                    @endif
                    @if($client->lead->source)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="globe" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Source</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ ucfirst($client->lead->source) }}</p>
                            </div>
                        </div>
                    @endif
                    @if($client->lead->assignedTo)
                        <div
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="user" style="width:16px;height:16px;color:#888"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Assigned To</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->lead->assignedTo->name }}</p>
                            </div>
                        </div>
                    @endif
                    @if($client->lead->query_message)
                        <div
                            style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="message-square" style="width:16px;height:16px;color:#888;margin-top:2px"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Query</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->lead->query_message }}</p>
                            </div>
                        </div>
                    @endif
                    @if($client->lead->notes)
                        <div
                            style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:var(--muted);border-radius:8px;">
                            <i data-lucide="sticky-note" style="width:16px;height:16px;color:#888;margin-top:2px"></i>
                            <div>
                                <p style="font-size:11px;color:#888;margin:0">Notes</p>
                                <p style="font-size:14px;font-weight:500;margin:0">{{ $client->lead->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Follow-ups Section --}}
                @if($client->lead->followups && $client->lead->followups->count() > 0)
                    <div style="margin-top:20px;border-top:1px solid #eee;padding-top:16px;">
                        <h4 style="font-size:14px;font-weight:600;margin:0 0 12px 0;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="message-circle" style="width:16px;height:16px;color:var(--primary)"></i> Follow-ups
                            <span class="badge badge-secondary"
                                style="font-size:11px;">{{ $client->lead->followups->count() }}</span>
                        </h4>
                        <div style="display:flex;flex-direction:column;gap:10px;max-height:250px;overflow-y:auto;">
                            @foreach($client->lead->followups as $followup)
                                <div style="padding:12px 14px;background:var(--muted);border-radius:8px;border-left:3px solid #6366f1;">
                                    <p style="margin:0 0 6px 0;font-size:13px;color:#333;">{{ $followup->message }}</p>
                                    <div style="display:flex;justify-content:space-between;font-size:11px;color:#888;">
                                        <span>By: <strong>{{ $followup->user->name ?? 'Unknown' }}</strong></span>
                                        <span>{{ $followup->created_at->format('d M Y, h:i A') }}</span>
                                    </div>
                                    @if($followup->next_follow_up_date)
                                        <p style="margin:4px 0 0;font-size:11px;color:#d97706;">
                                            <i data-lucide="calendar"
                                                style="width:12px;height:12px;display:inline;vertical-align:middle;"></i>
                                            Next: {{ $followup->next_follow_up_date->format('d M Y') }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div style="margin-top:16px;font-size:12px;color:#999;display:flex;justify-content:space-between;">
                    <span>Lead ID: {{ $client->lead->id }}</span>
                    <span>Created: {{ $client->lead->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>

        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translate(-50%, -48%);
                }

                to {
                    opacity: 1;
                    transform: translate(-50%, -50%);
                }
            }
        </style>
    @endif

    <!-- Quotes Section -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-content">
            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                <i data-lucide="file-text" style="width:18px;height:18px;color:var(--primary)"></i>
                Quotes
                <span class="badge badge-secondary">{{ $client->quotes->count() }}</span>
            </h3>
            @if($client->quotes->count() > 0)
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($client->quotes as $quote)
                                <tr>
                                    <td class="font-medium">{{ $quote->quote_no }}</td>
                                    <td class="text-sm text-muted">{{ $quote->created_at->format('d M Y') }}</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $quote->status === 'accepted' ? 'success' : ($quote->status === 'rejected' ? 'destructive' : ($quote->status === 'sent' ? 'info' : 'secondary')) }}">
                                            {{ ucfirst($quote->status) }}
                                        </span>
                                    </td>
                                    <td class="font-medium">₹{{ number_format($quote->grand_total_in_rupees, 2) }}</td>
                                    <td>
                                        <button type="button" onclick="openQuoteModal({{ $quote->id }})"
                                            class="btn btn-ghost btn-icon btn-sm" title="View Quote">
                                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center;padding:32px 0;color:#999">
                    <i data-lucide="file-x" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                    <p style="margin:0;font-size:14px">No quotes yet for this client</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Quote Detail Modal --}}
    <div id="quote-modal-overlay" onclick="closeQuoteModal()"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;animation:fadeIn 0.2s ease;">
    </div>
    <div id="quote-modal"
        style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;width:700px;max-width:92vw;max-height:85vh;overflow-y:auto;z-index:1001;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);animation:slideUp 0.3s ease;">
        <div
            style="padding:24px 24px 0;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding-bottom:16px;">
            <h3 id="quote-modal-title"
                style="margin:0;font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px;">
                <i data-lucide="file-text" style="width:20px;height:20px;color:var(--primary)"></i> Quote Details
            </h3>
            <button onclick="closeQuoteModal()"
                style="background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;display:flex;align-items:center;"
                title="Close">
                <i data-lucide="x" style="width:20px;height:20px;color:#666"></i>
            </button>
        </div>
        <div id="quote-modal-body" style="padding:24px;"></div>
    </div>

    {{-- Pre-render quote data for JS --}}
    <script>
        var clientQuotesData = {};
        @foreach($client->quotes as $quote)
            clientQuotesData[{{ $quote->id }}] = {
                quote_no: @json($quote->quote_no),
                status: @json($quote->status),
                date: @json($quote->created_at ? $quote->created_at->format('d M Y') : ''),
                valid_till: @json($quote->valid_till ? $quote->valid_till->format('d M Y') : '—'),
                subtotal: {{ $quote->subtotal_in_rupees }},
                discount: {{ $quote->discount_in_rupees }},
                tax: {{ $quote->gst_total_in_rupees }},
                grand_total: {{ $quote->grand_total_in_rupees }},
                notes: @json($quote->notes ?? ''),
                items: [
                    @foreach($quote->items as $item)
                                                {
                        product_name: @json($item->product_name),
                        qty: {{ $item->qty }},
                        unit_price: {{ $item->unit_price / 100 }},
                        line_total: {{ $item->line_total / 100 }}
                                                },
                    @endforeach
                                        ]
            };
        @endforeach
    </script>

    <!-- Notes Section -->
    @if($client->notes)
        <div class="card">
            <div class="card-content">
                <h3 style="font-size:15px;font-weight:600;margin:0 0 12px 0;display:flex;align-items:center;gap:8px">
                    <i data-lucide="sticky-note" style="width:18px;height:18px;color:var(--primary)"></i> Notes
                </h3>
                <p style="font-size:14px;color:#555;line-height:1.6;margin:0">{{ $client->notes }}</p>
            </div>
        </div>
    @endif

    <!-- Meta Info -->
    <div
        style="margin-top:20px;padding:12px 16px;background:var(--muted);border-radius:8px;display:flex;justify-content:space-between;font-size:12px;color:#888">
        <span>Client ID: <strong>{{ $client->id }}</strong></span>
        <span>Created: <strong>{{ $client->created_at->format('d M Y, h:i A') }}</strong></span>
        <span>Last Updated: <strong>{{ $client->updated_at->format('d M Y, h:i A') }}</strong></span>
    </div>
@endsection

@push('scripts')
    <script>
            function openLeadModal() {
                document.getElementById('lead-modal-overlay').style.display = 'block';
                document.getElementById('lead-modal').style.display = 'block';
                document.body.style.overflow = 'hidden';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }

        function closeLeadModal() {
            document.getElementById('lead-modal-overlay').style.display = 'none';
            document.getElementById('lead-modal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function openQuoteModal(quoteId) {
            var q = clientQuotesData[quoteId];
            if (!q) return;

            var statusClass = q.status === 'accepted' ? 'success' : (q.status === 'rejected' ? 'destructive' : (q.status === 'sent' ? 'info' : 'secondary'));

            var html = '';
            // Header info
            html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">';
            html += '<div>';
            html += '<h4 style="margin:0 0 4px 0;font-size:17px;font-weight:600;">' + q.quote_no + '</h4>';
            html += '<span style="font-size:13px;color:#666;">Date: ' + q.date + ' &bull; Valid Till: ' + q.valid_till + '</span>';
            html += '</div>';
            html += '<span class="badge badge-' + statusClass + '">' + q.status.charAt(0).toUpperCase() + q.status.slice(1) + '</span>';
            html += '</div>';

            // Items table
            if (q.items.length > 0) {
                html += '<table style="width:100%;border-collapse:collapse;margin-bottom:16px;">';
                html += '<thead><tr style="background:#f8f9fa;text-align:left;">';
                html += '<th style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;font-weight:600;">Product</th>';
                html += '<th style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;font-weight:600;width:70px;">Qty</th>';
                html += '<th style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;font-weight:600;width:110px;">Unit Price</th>';
                html += '<th style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;font-weight:600;width:110px;">Total</th>';
                html += '</tr></thead><tbody>';
                q.items.forEach(function (item) {
                    html += '<tr>';
                    html += '<td style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;">' + item.product_name + '</td>';
                    html += '<td style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;text-align:center;">' + item.qty + '</td>';
                    html += '<td style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;">₹' + item.unit_price.toFixed(2) + '</td>';
                    html += '<td style="padding:10px 12px;border:1px solid #e0e0e0;font-size:13px;font-weight:600;">₹' + item.line_total.toFixed(2) + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
            }

            // Totals
            html += '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">';
            html += '<div style="display:flex;gap:24px;font-size:13px;"><span style="color:#666;">Subtotal:</span><span style="font-weight:500;">₹' + q.subtotal.toFixed(2) + '</span></div>';
            if (q.discount > 0) {
                html += '<div style="display:flex;gap:24px;font-size:13px;"><span style="color:#666;">Discount:</span><span style="font-weight:500;color:#ef4444;">-₹' + q.discount.toFixed(2) + '</span></div>';
            }
            if (q.tax > 0) {
                html += '<div style="display:flex;gap:24px;font-size:13px;"><span style="color:#666;">Tax/GST:</span><span style="font-weight:500;">₹' + q.tax.toFixed(2) + '</span></div>';
            }
            html += '<div style="display:flex;gap:24px;font-size:15px;border-top:2px solid #333;padding-top:6px;margin-top:4px;"><span style="font-weight:700;">Grand Total:</span><span style="font-weight:700;color:#16a34a;">₹' + q.grand_total.toFixed(2) + '</span></div>';
            html += '</div>';

            // Notes
            if (q.notes) {
                html += '<div style="margin-top:16px;padding:12px 14px;background:var(--muted);border-radius:8px;">';
                html += '<p style="font-size:11px;color:#888;margin:0 0 4px;">Notes</p>';
                html += '<p style="font-size:13px;margin:0;color:#555;">' + q.notes + '</p>';
                html += '</div>';
            }

            document.getElementById('quote-modal-title').innerHTML = '<i data-lucide="file-text" style="width:20px;height:20px;color:var(--primary)"></i> ' + q.quote_no;
            document.getElementById('quote-modal-body').innerHTML = html;
            document.getElementById('quote-modal-overlay').style.display = 'block';
            document.getElementById('quote-modal').style.display = 'block';
            document.body.style.overflow = 'hidden';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeQuoteModal() {
            document.getElementById('quote-modal-overlay').style.display = 'none';
            document.getElementById('quote-modal').style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLeadModal();
                closeQuoteModal();
            }
        });
    </script>
@endpush