<tr>
    <td>
        <p class="font-medium">{{ $purchase->purchase_no }}</p>
    </td>
    <td>{{ \Carbon\Carbon::parse($purchase->date)->format('M d, Y') }}</td>
    <td>{{ $purchase->vendor->name ?? 'N/A' }}</td>
    <td>{{ $purchase->client->business_name ?? $purchase->client->contact_name ?? 'N/A' }}</td>
    {{-- Dynamic custom field value columns --}}
    @foreach($customFields as $cf)
        @php
            $cfValue = $purchase->customFieldValues->where('vendor_custom_field_id', $cf->id)->first();
        @endphp
        <td>{{ $cfValue ? $cfValue->value : '—' }}</td>
    @endforeach
    <td>{{ $purchase->product->name ?? 'N/A' }}</td>
    <td>₹{{ number_format($purchase->total_amount / 100, 2) }}</td>
    <td>₹{{ number_format($purchase->paid_amount / 100, 2) }}</td>
    <td>
        @php $badgeClass = match ($purchase->status) { 'completed' => 'success', 'active' => 'primary', default => 'secondary'}; @endphp
        <span class="badge badge-{{ $badgeClass }}">{{ ucfirst($purchase->status) }}</span>
    </td>
    <td>
        @php
            $cfValuesMap = [];
            foreach ($purchase->customFieldValues as $cfv) {
                $cfValuesMap[$cfv->vendor_custom_field_id] = $cfv->value;
            }
        @endphp
        <div style="display:flex;gap:6px;align-items:center">
            <button onclick="viewPurchase({{ $purchase->id }})"
                style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all 0.15s"
                title="View" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                <i data-lucide="eye" style="width:16px;height:16px"></i>
            </button>
            @if(can('projects.write'))
                <button class="edit-purchase-btn" data-id="{{ $purchase->id }}" data-vendor="{{ $purchase->vendor_id }}"
                    data-client="{{ $purchase->client_id }}" data-product="{{ $purchase->product->name ?? '' }}"
                    data-date="{{ \Carbon\Carbon::parse($purchase->date)->format('Y-m-d') }}"
                    data-amount="{{ $purchase->total_amount / 100 }}" data-notes="{{ $purchase->notes }}"
                    data-status="{{ $purchase->status }}" data-cf-values="{{ json_encode($cfValuesMap) }}"
                    style="width:32px;height:32px;border-radius:8px;background:#fffbeb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#f59e0b;transition:all 0.15s"
                    title="Edit" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#fffbeb'">
                    <i data-lucide="edit" style="width:16px;height:16px"></i>
                </button>
                <button onclick="openPaymentModal({{ $purchase->id }})"
                    style="width:32px;height:32px;border-radius:8px;background:#f0fdf4;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#16a34a;transition:all 0.15s"
                    title="Add Payment" onmouseover="this.style.background='#dcfce7'"
                    onmouseout="this.style.background='#f0fdf4'">
                    <i data-lucide="indian-rupee" style="width:16px;height:16px"></i>
                </button>
                @if(can('projects.delete'))
                    <button type="button" onclick="ajaxDelete('{{ route('admin.purchases.destroy', $purchase->id) }}')" style="width:32px;height:32px;border-radius:8px;background:#fef2f2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;transition:all 0.15s"
                            title="Delete" onmouseover="this.style.background='#fee2e2'"
                            onmouseout="this.style.background='#fef2f2'">
                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                        </button>
                @endif
            @endif
        </div>
    </td>
</tr>