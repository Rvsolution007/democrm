<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quote->status === 'accepted' ? 'Invoice' : 'Quote' }} {{ $quote->quote_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            padding: 40px;
        }

        .quote-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
        }

        .quote-meta h2 {
            font-size: 32px;
            color: #0f172a;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
        }

        .quote-meta p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .quote-meta .quote-no {
            font-size: 16px;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 12px;
        }

        .parties-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
        }

        .party-block {
            flex: 1;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-top: 4px solid #3b82f6;
        }

        .party-block.for {
            border-top: 4px solid #8b5cf6;
        }

        .party-block h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .party-block p {
            font-size: 14px;
            margin-bottom: 4px;
            line-height: 1.5;
            color: #475569;
        }

        .party-block .name {
            font-weight: 700;
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #475569;
            padding: 12px 14px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }

        .items-table th:last-child,
        .items-table th:nth-child(3),
        .items-table th:nth-child(4) {
            text-align: right;
        }

        .items-table td {
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #334155;
        }

        .items-table td:last-child,
        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: right;
        }

        .items-table tbody tr:hover {
            background: #f8fafc;
        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 14px;
            font-size: 14px;
            color: #475569;
        }

        .totals-table tr td:last-child {
            text-align: right;
            font-weight: 600;
            color: #1e293b;
        }

        .totals-table .grand-total td {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
        }

        .status-sent {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-accepted {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-expired {
            background: #fef3c7;
            color: #d97706;
        }

        .notes-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #eab308;
            margin-bottom: 30px;
        }

        .notes-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .notes-section p {
            font-size: 14px;
            color: #475569;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 12px;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s;
            z-index: 100;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }

        @media print {
            .print-btn {
                display: none;
            }

            body {
                padding: 0;
                background: white;
            }

            .quote-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                border: none;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>

    <div class="quote-container">
        <div class="header">
            <div>
                @if($quote->company && $quote->company->logo)
                    <img src="{{ asset('storage/' . $quote->company->logo) }}" alt="{{ $quote->company->name }}" style="max-width:120px;max-height:80px;object-fit:contain;">
                @endif
            </div>
            <div class="quote-meta">
                <h2>{{ $quote->status === 'accepted' ? 'Invoice' : 'Quotation' }}</h2>
                <p class="quote-no">{{ $quote->quote_no }}</p>
                <p>Date: {{ $quote->date ? $quote->date->format('d M Y') : '—' }}</p>
                <p>Valid Till: {{ $quote->valid_till ? $quote->valid_till->format('d M Y') : '—' }}</p>
                <p style="margin-top:10px">
                    <span class="status-badge status-{{ $quote->status }}">{{ ucfirst($quote->status) }}</span>
                </p>
            </div>
        </div>

        <div class="parties-section">
            <div class="party-block from">
                <h3>Quote From</h3>
                <p class="name">{{ $quote->company->name ?? 'Company Name' }}</p>

                @if($quote->company && $quote->company->gstin)
                    <p><strong>GSTIN:</strong> {{ $quote->company->gstin }}</p>
                @endif

                @if($quote->company && $quote->company->phone)
                    <p><strong>Phone:</strong> {{ $quote->company->phone }}</p>
                @endif

                @if($quote->company && $quote->company->email)
                    <p><strong>Email:</strong> {{ $quote->company->email }}</p>
                @endif

                @php
                    $address = $quote->company->address ?? null;
                    $addressStr = '';
                    if (is_string($address)) {
                        $addressStr = $address;
                    } elseif (is_array($address)) {
                        $parts = [];
                        if (!empty($address['street']))
                            $parts[] = $address['street'];
                        if (!empty($address['city']))
                            $parts[] = $address['city'];
                        if (!empty($address['state']))
                            $parts[] = $address['state'];
                        if (!empty($address['postal_code']))
                            $parts[] = $address['postal_code'];
                        $addressStr = implode(', ', $parts);
                    }
                @endphp
                @if($addressStr)
                    <p style="margin-top:4px;">{{ $addressStr }}</p>
                @endif
            </div>

            <div class="party-block for">
                <h3>Quote For</h3>
                @if($quote->client)
                    <p class="name">{{ $quote->client->display_name }}</p>

                    @if($quote->client->gstin)
                        <p><strong>GSTIN:</strong> {{ $quote->client->gstin }}</p>
                    @endif

                    @if($quote->client->phone)
                        <p><strong>Phone:</strong> {{ $quote->client->phone }}</p>
                    @endif

                    @if($quote->client->email)
                        <p><strong>Email:</strong> {{ $quote->client->email }}</p>
                    @endif

                    @php
                        $cAddress = $quote->client->billing_address ?? null;
                        $cAddressStr = '';
                        if (is_string($cAddress)) {
                            $cAddressStr = $cAddress;
                        } elseif (is_array($cAddress)) {
                            $cParts = [];
                            if (!empty($cAddress['street']))
                                $cParts[] = $cAddress['street'];
                            if (!empty($cAddress['city']))
                                $cParts[] = $cAddress['city'];
                            if (!empty($cAddress['state']))
                                $cParts[] = $cAddress['state'];
                            if (!empty($cAddress['postal_code']))
                                $cParts[] = $cAddress['postal_code'];
                            $cAddressStr = implode(', ', $cParts);
                        }
                    @endphp
                    @if($cAddressStr)
                        <p style="margin-top:4px;">{{ $cAddressStr }}</p>
                    @endif

                @elseif($quote->lead)
                    <p class="name">{{ $quote->lead->name }}</p>

                    @if($quote->lead->phone)
                        <p><strong>Phone:</strong> {{ $quote->lead->phone }}</p>
                    @endif

                    @if($quote->lead->email)
                        <p><strong>Email:</strong> {{ $quote->lead->email }}</p>
                    @endif

                    @if($quote->lead->city || $quote->lead->state)
                        <p style="margin-top:4px;">
                            {{ $quote->lead->city }}{{ $quote->lead->state ? ', ' . $quote->lead->state : '' }}
                        </p>
                    @endif
                @else
                    <p class="name">—</p>
                @endif
            </div>
        </div>

        @if($quote->items && $quote->items->count() > 0)
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price (₹)</th>
                        <th>Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $item->product_name }}</strong>
                                @if($item->description)
                                    <br><span
                                        style="color:#64748b;font-size:12px;margin-top:2px;display:block;">{{ $item->description }}</span>
                                @endif
                            </td>
                            <td>{{ $item->qty }}</td>
                            <td>₹{{ number_format($item->unit_price / 100, 2) }}</td>
                            <td>₹{{ number_format($item->line_total / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align:center;padding:40px 0;color:#94a3b8;font-style:italic">No items in this quote.</p>
        @endif

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td>₹{{ number_format($quote->subtotal_in_rupees, 2) }}</td>
                </tr>

                @if($quote->discount > 0)
                    <tr>
                        <td>Discount</td>
                        <td style="color:#ef4444">- ₹{{ number_format($quote->discount_in_rupees, 2) }}</td>
                    </tr>
                @endif

                    @php
                        // Calculate tax percentage based on subtotal - discount
                        $taxableAmount = $quote->subtotal_in_rupees - $quote->discount_in_rupees;
                        $taxRate = 0;
                        if ($taxableAmount > 0 && $quote->gst_total_in_rupees > 0) {
                            $taxRate = round(($quote->gst_total_in_rupees / $taxableAmount) * 100);
                        }
                    @endphp
                    <tr>
                        <td>Tax / GST {{ $taxRate > 0 ? "($taxRate%)" : "(0%)" }}</td>
                        <td>₹{{ number_format($quote->gst_total_in_rupees, 2) }}</td>
                    </tr>

                <tr class="grand-total">
                    <td>Grand Total</td>
                    <td>₹{{ number_format($quote->grand_total_in_rupees, 2) }}</td>
                </tr>
            </table>
        </div>

        @if($quote->notes)
            <div class="notes-section">
                <h3>Notes / Terms</h3>
                <p>{{ $quote->notes }}</p>
            </div>
        @endif

        <div class="footer">
            <p>This is a computer-generated quotation.</p>
        </div>
    </div>
</body>

</html>