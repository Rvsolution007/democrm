<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice {{ $quote->quote_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Calibri, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: #e8e8e8;
            padding: 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-wrapper {
            width: 780px;
            min-height: 950px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #bbb;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* Tax Invoice Title Bar */
        .title-bar {
            text-align: center;
            padding: 8px 0;
            font-size: 16px;
            font-weight: 700;
            color: #222;
            border-bottom: 1px solid #bbb;
            letter-spacing: 0.5px;
        }

        /* Company Header */
        .header-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #bbb;
            padding: 12px 16px 12px 16px;
        }

        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 120px;
            padding-right: 10px;
        }

        .header-left img {
            max-width: 100px;
            max-height: 85px;
        }

        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #222;
            margin-bottom: 2px;
        }

        .header-right .addr-line {
            font-size: 12px;
            color: #555;
            line-height: 1.45;
        }

        /* Bill To + Invoice Info Row */
        .bill-info-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #bbb;
        }

        .bill-col {
            display: table-cell;
            vertical-align: top;
            width: 58%;
            border-right: 1px solid #bbb;
        }

        .info-col {
            display: table-cell;
            vertical-align: middle;
            width: 42%;
            padding: 8px 14px;
        }

        .bill-header {
            background: #0097a7;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
        }

        .bill-body {
            padding: 8px 10px;
        }

        .bill-body .cl-name {
            font-size: 13px;
            font-weight: 700;
            color: #222;
            margin-bottom: 3px;
        }

        .bill-body .cl-line {
            font-size: 11.5px;
            color: #444;
            line-height: 1.5;
        }

        .info-col .info-line {
            font-size: 12px;
            color: #444;
            text-align: right;
            line-height: 1.7;
        }

        .info-col .info-line .val {
            font-weight: 700;
            color: #222;
        }

        /* Products Table */
        table.products {
            width: 100%;
            border-collapse: collapse;
        }

        table.products th {
            background: #0097a7;
            color: #fff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 6px 8px;
            border: 1px solid #0097a7;
            text-align: left;
        }

        table.products th.ctr { text-align: center; }
        table.products th.rgt { text-align: right; }

        table.products td {
            font-size: 12px;
            color: #333;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-top: none;
        }

        table.products td.ctr { text-align: center; }
        table.products td.rgt { text-align: right; }

        table.products tr.total-row td {
            border-top: 1.5px solid #555;
            font-weight: 700;
        }

        /* Tax + Amounts row */
        .tax-amounts-row {
            display: table;
            width: 100%;
            border-top: 1px solid #bbb;
        }

        .tax-col {
            display: table-cell;
            vertical-align: top;
            width: 52%;
            border-right: 1px solid #bbb;
        }

        .amt-col {
            display: table-cell;
            vertical-align: top;
            width: 48%;
        }

        table.tax-tbl,
        table.amt-tbl {
            width: 100%;
            border-collapse: collapse;
        }

        table.tax-tbl th,
        table.amt-tbl th {
            background: #0097a7;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 8px;
            border: 1px solid #0097a7;
            text-align: left;
        }

        table.tax-tbl td,
        table.amt-tbl td {
            font-size: 12px;
            padding: 5px 8px;
            border: 1px solid #ccc;
            border-top: none;
            color: #333;
        }

        table.amt-tbl td.rgt {
            text-align: right;
        }

        table.amt-tbl tr.total-row td {
            font-weight: 700;
            font-size: 12.5px;
        }

        /* Amount in Words */
        .words-bar {
            border-top: 1px solid #bbb;
        }

        .words-header {
            background: #0097a7;
            color: #fff;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
        }

        .words-body {
            text-align: center;
            font-size: 12.5px;
            color: #333;
            padding: 6px 10px;
        }

        /* Terms + Signatory */
        .bottom-row {
            display: table;
            width: 100%;
            border-top: 1px solid #bbb;
        }

        .terms-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            border-right: 1px solid #bbb;
        }

        .sign-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            text-align: center;
            padding: 8px 12px;
        }

        .terms-hdr {
            background: #0097a7;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
        }

        .terms-body {
            padding: 8px 10px;
            font-size: 11.5px;
            color: #555;
            line-height: 1.5;
            min-height: 90px;
        }

        .sign-col .for-line {
            font-size: 12.5px;
            color: #333;
            margin-bottom: 6px;
        }

        .sign-col .sig-space {
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sign-col .sig-label {
            font-size: 12px;
            color: #555;
            margin-top: 4px;
        }

        /* Print button */
        .print-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #0097a7, #00838f);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            z-index: 100;
        }

        .print-btn:hover {
            background: linear-gradient(135deg, #00838f, #006064);
        }

        @media print {
            .print-btn { display: none !important; }
            body { padding: 0; background: #fff; }
            .invoice-wrapper { 
                border: none; 
                box-shadow: none; 
                width: 100%; 
                min-height: auto; 
                padding: 30px; 
                margin: 0;
            }
            @page { margin: 5mm; size: A4; }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>

    @php
        $company = $quote->company;
        $client = $quote->client;

        // Company address parts (each on separate line)
        $compAddrParts = [];
        $companyState = '';
        $companyStateCode = '';
        if ($company && is_array($company->address)) {
            $a = $company->address;
            if (!empty($a['street'])) $compAddrParts[] = $a['street'];
            if (!empty($a['city'])) $compAddrParts[] = $a['city'];
            if (!empty($a['state'])) {
                $compAddrParts[] = $a['state'];
                $companyState = $a['state'];
            }
            if (!empty($a['postal_code'])) $compAddrParts[] = $a['postal_code'];
            if (!empty($a['country'])) $compAddrParts[] = $a['country'];
            if (!empty($a['state_code'])) $companyStateCode = $a['state_code'];
        } elseif ($company && is_string($company->address)) {
            $compAddrParts = explode(',', $company->address);
            $compAddrParts = array_map('trim', $compAddrParts);
        }

        // Client address parts
        $clientAddrLines = [];
        $clientState = '';
        $clientStateCode = '';
        if ($client) {
            $ca = $client->billing_address;
            if (is_string($ca) && $ca) {
                $clientAddrLines[] = $ca;
            } elseif (is_array($ca)) {
                if (!empty($ca['street'])) $clientAddrLines[] = $ca['street'];
                $cityState = '';
                if (!empty($ca['city'])) $cityState .= $ca['city'];
                if (!empty($ca['state'])) {
                    $cityState .= ($cityState ? ', ' : '') . $ca['state'];
                    $clientState = $ca['state'];
                }
                if ($cityState) $clientAddrLines[] = $cityState;
                if (!empty($ca['state_code'])) $clientStateCode = $ca['state_code'];
            }
        }

        // Tax calculations
        $subtotal = $quote->subtotal_in_rupees;
        $discount = $quote->discount_in_rupees;
        $taxableAmount = $subtotal - $discount;
        if ($taxableAmount < 0) $taxableAmount = 0;
        $totalTax = $quote->gst_total_in_rupees;
        $grandTotal = $quote->grand_total_in_rupees;
        $paidAmount = $quote->paid_amount_in_rupees;
        $balance = $grandTotal - $paidAmount;

        $taxRate = 0;
        if ($taxableAmount > 0 && $totalTax > 0) {
            $taxRate = round(($totalTax / $taxableAmount) * 100);
        }

        $halfRate = $taxRate / 2;
        $halfTax = $totalTax / 2;

        $taxBreakdown = [];
        if ($taxRate > 0) {
            $taxBreakdown[] = ['name' => 'SGST', 'taxable' => $taxableAmount, 'rate' => $halfRate, 'amount' => round($halfTax, 2)];
            $taxBreakdown[] = ['name' => 'CGST', 'taxable' => $taxableAmount, 'rate' => $halfRate, 'amount' => round($halfTax, 2)];
        }

        // Number to words (Indian system)
        if (!function_exists('numToIndianWords')) {
            function numToIndianWords($number) {
                $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                          'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                          'Seventeen', 'Eighteen', 'Nineteen'];
                $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
                $number = (int) round($number);
                if ($number == 0) return 'Zero';
                $w = '';
                if ($number >= 10000000) {
                    $w .= numToIndianWords((int)($number / 10000000)) . ' Crore ';
                    $number %= 10000000;
                }
                if ($number >= 100000) {
                    $w .= numToIndianWords((int)($number / 100000)) . ' Lakh ';
                    $number %= 100000;
                }
                if ($number >= 1000) {
                    $w .= numToIndianWords((int)($number / 1000)) . ' Thousand ';
                    $number %= 1000;
                }
                if ($number >= 100) {
                    $w .= $ones[(int)($number / 100)] . ' Hundred ';
                    $number %= 100;
                }
                if ($number > 0) {
                    if ($number < 20) { $w .= $ones[$number]; }
                    else {
                        $w .= $tens[(int)($number / 10)];
                        if ($number % 10 > 0) $w .= ' ' . $ones[$number % 10];
                    }
                }
                return trim($w);
            }
        }
        $amountWords = numToIndianWords($grandTotal) . ' Rupees only';

        $invoiceDate = $quote->date ? $quote->date->format('d-m-Y') : now()->format('d-m-Y');

        // State display with code
        $placeOfSupply = '';
        if ($companyStateCode && $companyState) {
            $placeOfSupply = $companyStateCode . '-' . $companyState;
        } elseif ($companyState) {
            $placeOfSupply = $companyState;
        }

        $gstinState = '';
        if ($company && $company->gstin) {
            $stateCode = substr($company->gstin, 0, 2);
            $gstinState = $stateCode . '-' . ($companyState ?: 'India');
        }

        $clientStateFull = '';
        if ($clientStateCode && $clientState) {
            $clientStateFull = $clientStateCode . '-' . $clientState;
        } elseif ($clientState) {
            $clientStateFull = $clientState;
        }
    @endphp

    <div class="invoice-wrapper">

        {{-- Title --}}
        <div class="title-bar">Tax Invoice</div>

        {{-- Company Header --}}
        <div class="header-row">
            <div class="header-left">
                @if($company && $company->logo)
                    <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->name }}">
                @else
                    <div style="width:90px;height:75px;border:2px solid #0097a7;border-radius:8px;display:flex;align-items:center;justify-content:center;text-align:center;font-size:10px;font-weight:700;color:#0097a7;line-height:1.2;padding:4px;">
                        {{ $company->name ?? 'COMPANY' }}
                    </div>
                @endif
            </div>
            <div class="header-right">
                <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
                @foreach($compAddrParts as $part)
                    <div class="addr-line">{{ $part }}</div>
                @endforeach
                @if($company && $company->phone)
                    <div class="addr-line">Phone no.: {{ $company->phone }}</div>
                @endif
                @if($company && $company->gstin)
                    <div class="addr-line">GSTIN: {{ $company->gstin }}{{ $gstinState ? ', State: ' . $gstinState : '' }}</div>
                @endif
            </div>
        </div>

        {{-- Bill To + Invoice Info --}}
        <div class="bill-info-row">
            <div class="bill-col">
                <div class="bill-header">Bill To</div>
                <div class="bill-body">
                    @if($client)
                        <div class="cl-name">{{ $client->business_name ?: $client->contact_name }}</div>
                        @foreach($clientAddrLines as $line)
                            <div class="cl-line">{{ $line }}</div>
                        @endforeach
                        @if($client->gstin)
                            <div class="cl-line">GSTIN : {{ $client->gstin }}</div>
                        @endif
                        @if($clientStateFull)
                            <div class="cl-line">State: {{ $clientStateFull }}</div>
                        @elseif($clientState)
                            <div class="cl-line">State: {{ $clientState }}</div>
                        @endif
                    @elseif($quote->lead)
                        <div class="cl-name">{{ $quote->lead->name }}</div>
                        @if($quote->lead->phone)
                            <div class="cl-line">Phone: {{ $quote->lead->phone }}</div>
                        @endif
                        @if($quote->lead->city || $quote->lead->state)
                            <div class="cl-line">{{ $quote->lead->city }}{{ $quote->lead->state ? ', ' . $quote->lead->state : '' }}</div>
                        @endif
                    @else
                        <div class="cl-name">—</div>
                    @endif
                </div>
            </div>
            <div class="info-col">
                @if($placeOfSupply)
                    <div class="info-line">Place of supply: <span class="val">{{ $placeOfSupply }}</span></div>
                @endif
                <div class="info-line"><strong>Invoice No. : {{ $quote->quote_no }}</strong></div>
                <div class="info-line"><strong>Date : {{ $invoiceDate }}</strong></div>
            </div>
        </div>

        {{-- Products Table --}}
        <table class="products">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th style="width:auto">Item name</th>
                    <th style="width:90px">HSN/ SAC</th>
                    <th class="ctr" style="width:75px">Quantity</th>
                    <th class="rgt" style="width:95px">Price/ Unit</th>
                    <th class="rgt" style="width:95px">Amount</th>
                </tr>
            </thead>
            <tbody>
                @if($quote->items && $quote->items->count() > 0)
                    @foreach($quote->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->hsn_code ?? '' }}</td>
                            <td class="ctr">{{ $item->qty }}</td>
                            <td class="rgt">₹ {{ number_format($item->rate / 100, 2) }}</td>
                            <td class="rgt">₹ {{ number_format(($item->rate * $item->qty) / 100, 2) }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr class="total-row">
                    <td></td>
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="rgt"><strong>₹ {{ number_format($subtotal, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        {{-- Tax Breakdown + Amounts --}}
        <div class="tax-amounts-row">
            <div class="tax-col">
                <table class="tax-tbl">
                    <thead>
                        <tr>
                            <th>Tax type</th>
                            <th>Taxable amount</th>
                            <th>Rate</th>
                            <th>Tax amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($taxBreakdown) > 0)
                            @foreach($taxBreakdown as $tax)
                                <tr>
                                    <td>{{ $tax['name'] }}</td>
                                    <td>₹ {{ number_format($tax['taxable'], 2) }}</td>
                                    <td>{{ $tax['rate'] }}%</td>
                                    <td>₹ {{ number_format($tax['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" style="text-align:center;color:#999;">No tax applied</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="amt-col">
                <table class="amt-tbl">
                    <thead>
                        <tr>
                            <th colspan="2">Amounts:</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sub Total</td>
                            <td class="rgt">₹ {{ number_format($subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Tax ({{ $taxRate }}%)</td>
                            <td class="rgt">₹ {{ number_format($totalTax, 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total</strong></td>
                            <td class="rgt"><strong>₹ {{ number_format($grandTotal, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Received</td>
                            <td class="rgt">₹ {{ number_format($paidAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Balance</td>
                            <td class="rgt">₹ {{ number_format($balance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Amount In Words --}}
        <div class="words-bar">
            <div class="words-header">Invoice Amount In Words</div>
            <div class="words-body">{{ $amountWords }}</div>
        </div>

        {{-- Terms + Signatory --}}
        <div class="bottom-row">
            <div class="terms-col">
                <div class="terms-hdr">Terms and Conditions</div>
                <div class="terms-body">{{ $company->terms_and_conditions ?? 'Thanks for doing business with us!' }}</div>
            </div>
            <div class="sign-col">
                <div class="for-line">For, {{ strtoupper($company->name ?? 'COMPANY') }}</div>
                <div class="sig-space">
                    {{-- Signature image if available --}}
                </div>
                <div class="sig-label">Authorized Signatory</div>
            </div>
        </div>

    </div>
</body>

</html>
