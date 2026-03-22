@forelse($payments as $payment)
    <tr>
        <td>
            @if($payment->quote && $payment->quote->client)
                <span class="font-medium">{{ $payment->quote->client->display_name }}</span>
            @elseif($payment->quote && $payment->quote->lead)
                <span class="font-medium">{{ $payment->quote->lead->name }}</span>
            @else
                <span style="color:#999">—</span>
            @endif
        </td>
        <td><span class="font-medium">{{ $payment->quote->quote_no ?? '—' }}</span></td>
        <td>
            <span style="font-weight:700;color:#059669;">₹{{ number_format($payment->amount_in_rupees, 2) }}</span>
        </td>
        <td>
            @php
                $typeColors = [
                    'cash' => 'success',
                    'online' => 'info',
                    'cheque' => 'secondary',
                    'upi' => 'info',
                    'bank_transfer' => 'secondary',
                ];
                $typeLabels = [
                    'cash' => 'Cash',
                    'online' => 'Online',
                    'cheque' => 'Cheque',
                    'upi' => 'UPI',
                    'bank_transfer' => 'Bank Transfer',
                ];
            @endphp
            <span class="badge badge-{{ $typeColors[$payment->payment_type] ?? 'secondary' }}">
                {{ $typeLabels[$payment->payment_type] ?? ucfirst($payment->payment_type) }}
            </span>
        </td>
        <td>{{ $payment->payment_date->format('d M Y, h:i A') }}</td>
        <td>{{ $payment->user->name ?? '—' }}</td>
        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
            title="{{ $payment->notes }}">
            {{ $payment->notes ?? '—' }}
        </td>
        <td>
            <div style="display:flex;gap:4px;justify-content:center;">
                <button type="button" class="btn btn-icon btn-ghost btn-sm"
                    onclick="openEditPaymentModal({{ $payment->id }}, {{ $payment->amount_in_rupees }}, '{{ $payment->payment_type }}', '{{ $payment->payment_date->format('Y-m-d\TH:i') }}', '{{ addslashes($payment->notes) }}')"
                    title="Edit Payment">
                    <i data-lucide="edit" style="width:14px;height:14px;color:#3b82f6;"></i>
                </button>
                <button type="button" onclick="ajaxDelete('{{ route('admin.payments.destroy', $payment->id) }}')" class="btn btn-icon btn-ghost btn-sm" title="Delete Payment">
                        <i data-lucide="trash-2" style="width:14px;height:14px;color:#ef4444;"></i>
                    </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" style="text-align:center;padding:40px 0;color:#999">
            <i data-lucide="credit-card" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
            <p style="margin:0;font-size:14px">No payments found.</p>
        </td>
    </tr>
@endforelse