@forelse($products as $product)
    <tr>
        @if(can('products.delete'))
            <td style="text-align:center">
                <input type="checkbox" class="product-checkbox" value="{{ $product->id }}" onchange="updateBulkBar()" style="width:16px;height:16px;accent-color:#6366f1;cursor:pointer">
            </td>
        @endif
        @foreach($customColumns->where('show_on_list', true)->where('is_required', true) as $col)
            <td>
                @if($col->is_category && $col->is_title)
                    <span class="font-medium">{{ $product->category->name ?? $product->name }}</span>
                @elseif($col->is_category)
                    <span class="badge badge-secondary">{{ $product->category->name ?? 'N/A' }}</span>
                @elseif($col->is_title)
                    @php
                        $displayName = $product->name;
                        if (!$col->is_system) {
                            $titleCustomVal = $product->customValues->where('column_id', $col->id)->first();
                            if ($titleCustomVal && !empty($titleCustomVal->value)) {
                                $displayName = $titleCustomVal->value;
                            }
                        }
                        if (preg_match('/^(Product\s+\d+|Unnamed Product)$/i', $displayName) && $product->category) {
                            $displayName = $product->category->name;
                        }
                    @endphp
                    <span class="font-medium">{{ $displayName }}</span>
                @elseif($col->is_system)
                    @php
                        $val = $product->{$col->slug};
                        if(in_array($col->slug, ['mrp', 'sale_price'])) $val = '₹' . number_format($val / 100, 2);
                        elseif($col->slug === 'gst_percent') $val = ($val ?? 0) . '%';
                    @endphp
                    <span class="{{ in_array($col->slug, ['mrp', 'sale_price']) ? 'font-medium' : '' }}">{{ $val }}</span>
                @else
                    @php
                        $customVal = $product->customValues->where('column_id', $col->id)->first();
                        $valText = $customVal ? $customVal->value : '-';
                        if (is_string($valText)) {
                            $decoded = json_decode($valText, true);
                            if (is_array($decoded)) $valText = implode(', ', $decoded);
                        }
                    @endphp
                    <span>{{ is_array($valText) ? implode(', ', $valText) : $valText }}</span>
                @endif
            </td>
        @endforeach
        <td>
            <span class="badge badge-{{ ($product->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                {{ ucfirst($product->status ?? 'active') }}
            </span>
        </td>
        <td>
            <div style="display:flex;gap:6px;align-items:center">
                @if(can('products.write'))
                    <button class="btn btn-ghost btn-icon btn-sm edit-product-btn action-btn" title="Edit"
                        data-product="{{ json_encode([
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'category_id' => $product->category_id,
                            'description' => $product->description,
                            'unit' => $product->unit,
                            'mrp' => $product->mrp / 100,
                            'sale_price' => $product->sale_price / 100,
                            'gst_percent' => $product->gst_percent,
                            'hsn_code' => $product->hsn_code,
                            'is_purchase_enabled' => $product->is_purchase_enabled ? 1 : 0,
                            'cover_media_url' => $product->cover_media_url
                        ]) }}"
                        data-custom-values="{{ json_encode($product->customValues->pluck('value', 'column_id')) }}"
                        data-variations="{{ json_encode($product->variations ?? []) }}"
                    >
                        <i data-lucide="edit"></i>
                    </button>
                @endif
                @if(can('products.delete'))
                    <button type="button" onclick="ajaxDelete('{{ route('admin.products.destroy', $product->id) }}')" class="btn btn-ghost btn-icon btn-sm action-btn" style="color:var(--destructive)" title="Delete">
                        <i data-lucide="trash-2"></i>
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="20" class="text-center py-8 text-muted">No products found matching your search.</td>
    </tr>
@endforelse
