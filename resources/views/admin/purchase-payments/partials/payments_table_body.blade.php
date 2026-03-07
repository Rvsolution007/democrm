@forelse($payments as $payment)
    <tr style="border-bottom:1px solid #e2e8f0;transition:background 0.2s" onmouseover="this.style.background='#f8fafc'"
        onmouseout="this.style.background='transparent'">
        <td style="padding:16px 20px;vertical-align:top">
            @if($payment->purchase && $payment->purchase->vendor)
                <div style="font-weight:600;color:#0f172a;font-size:14px;margin-bottom:2px">
                    {{ $payment->purchase->vendor->name }}
                </div>
                <div style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px">
                    <i data-lucide="building" style="width:12px;height:12px"></i>
                    Vendor
                </div>
            @else
                <span style="color:#94a3b8;font-style:italic;font-size:13px">No Vendor</span>
            @endif

            @if($payment->purchase && $payment->purchase->client)
                <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #e2e8f0">
                    <div style="font-weight:500;color:#334155;font-size:13px">
                        {{ $payment->purchase->client->business_name ?? $payment->purchase->client->contact_name }}
                    </div>
                </div>
            @endif
        </td>

        <td style="padding:16px 20px;vertical-align:top">
            @if($payment->purchase)
                <div
                    style="font-family:'Courier New', Courier, monospace;font-weight:600;color:#3b82f6;font-size:13px;background:#eff6ff;padding:4px 8px;border-radius:4px;display:inline-block">
                    {{ $payment->purchase->purchase_no }}
                </div>
                <div style="color:#64748b;font-size:12px;margin-top:6px">
                    Total: <span
                        style="font-weight:600;color:#0f172a">₹{{ number_format($payment->purchase->total_amount / 100, 2) }}</span>
                </div>
            @else
                <span style="color:#94a3b8;font-style:italic">Deleted Purchase</span>
            @endif
        </td>

        <td style="padding:16px 20px;vertical-align:top;text-align:right">
            <div style="font-weight:700;color:#10b981;font-size:15px">
                ₹{{ number_format($payment->amount / 100, 2) }}
            </div>
        </td>

        <td style="padding:16px 20px;vertical-align:top">
            @if($payment->payment_type || $payment->payment_method)
                @php
                    $methodColors = [
                        'cash' => ['bg' => '#f0fdf4', 'text' => '#16a34a', 'border' => '#bbf7d0'],
                        'online' => ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe'],
                        'cheque' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca'],
                        'upi' => ['bg' => '#faf5ff', 'text' => '#9333ea', 'border' => '#e9d5ff'],
                        'bank_transfer' => ['bg' => '#f8fafc', 'text' => '#475569', 'border' => '#e2e8f0']
                    ];
                    $actualMethod = $payment->payment_type ?? $payment->payment_method;
                    $color = $methodColors[$actualMethod] ?? ['bg' => '#f1f5f9', 'text' => '#64748b', 'border' => '#e2e8f0'];
                @endphp
                <span
                    style="display:inline-block;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $color['bg'] }};color:{{ $color['text'] }};border:1px solid {{ $color['border'] }}">
                    {{ ucwords(str_replace('_', ' ', $actualMethod)) }}
                </span>
            @else
                <span style="color:#94a3b8">—</span>
            @endif
        </td>

        <td style="padding:16px 20px;vertical-align:top">
            <div style="font-weight:500;color:#334155;font-size:13px">
                {{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '—' }}
            </div>
            @if($payment->created_at)
                <div style="color:#94a3b8;font-size:11px;margin-top:4px">
                    Added: {{ $payment->created_at->format('M d, Y H:i') }}
                </div>
            @endif
        </td>

        <td style="padding:16px 20px;vertical-align:top">
            @if($payment->reference_no)
                <div
                    style="font-size:13px;color:#475569;font-family:monospace;background:#f8fafc;padding:4px 8px;border-radius:4px;border:1px solid #e2e8f0;display:inline-block">
                    {{ $payment->reference_no }}
                </div>
            @else
                <span style="color:#94a3b8;font-style:italic;font-size:13px">No Reference</span>
            @endif
        </td>

        <td style="padding:16px 20px;vertical-align:top">
            @if($payment->notes)
                <div style="font-size:13px;color:#475569;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                    title="{{ $payment->notes }}">
                    {{ $payment->notes }}
                </div>
            @else
                <span style="color:#94a3b8;font-style:italic;font-size:13px">No Notes</span>
            @endif
        </td>

        <td style="padding:16px 20px;vertical-align:top;text-align:center">
            <div style="display:flex;gap:4px;justify-content:center;">
                <button type="button" class="btn btn-icon btn-ghost btn-sm"
                    onclick="openEditPaymentModal({{ $payment->id }}, {{ number_format($payment->amount / 100, 2, '.', '') }}, '{{ $payment->payment_type ?? $payment->payment_method }}', '{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d\TH:i') : '' }}', '{{ addslashes($payment->notes) }}')"
                    title="Edit Payment">
                    <i data-lucide="edit" style="width:14px;height:14px;color:#3b82f6;"></i>
                </button>
                <form action="{{ route('admin.purchase-payments.destroy', $payment->id) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this payment? This will update the purchase balance.');"
                    style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-icon btn-ghost btn-sm" title="Delete Payment">
                        <i data-lucide="trash-2" style="width:14px;height:14px;color:#ef4444;"></i>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" style="padding:40px 20px;text-align:center;color:#64748b">
            <div style="display:flex;flex-direction:column;align-items:center;gap:12px">
                <div
                    style="width:48px;height:48px;background:#f1f5f9;border-radius:50%;display:flex;align-items:center;justify-content:center">
                    <i data-lucide="inbox" style="width:24px;height:24px;color:#94a3b8"></i>
                </div>
                <div>
                    <div style="font-weight:600;color:#334155;font-size:15px;margin-bottom:4px">No Purchase Payments Found
                    </div>
                    <div style="font-size:13px">Try adjusting your filters or search query</div>
                </div>
            </div>
        </td>
    </tr>
@endforelse