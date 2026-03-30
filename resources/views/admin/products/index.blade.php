@extends('admin.layouts.app')

@section('title', 'Products')
@section('breadcrumb', 'Products')

@push('styles')
<style>
/* ─── Tag Chip Multiselect ─── */
.chip-select-wrap { position:relative; }
.chip-select-trigger {
    display:flex; flex-wrap:wrap; gap:6px; align-items:center;
    min-height:42px; padding:6px 10px; border:1.5px solid #e2e8f0;
    border-radius:8px; background:#fff; cursor:pointer;
    transition: border-color .2s, box-shadow .2s;
}
.chip-select-trigger:hover { border-color:#94a3b8; }
.chip-select-trigger.open { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.chip-select-trigger .cs-placeholder { color:#94a3b8; font-size:13px; }
.chip-tag {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 8px 3px 10px; border-radius:6px; font-size:12px; font-weight:500;
    background:linear-gradient(135deg,#eef2ff,#e0e7ff); color:#4338ca;
    border:1px solid #c7d2fe; animation: chipIn .2s ease;
}
@keyframes chipIn { from{transform:scale(.85);opacity:0} to{transform:scale(1);opacity:1} }
.chip-tag .chip-x {
    width:16px; height:16px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:13px; line-height:1; color:#6366f1; transition:all .15s;
}
.chip-tag .chip-x:hover { background:#c7d2fe; color:#312e81; }
.chip-dropdown {
    display:none; position:absolute; top:calc(100% + 4px); left:0; right:0;
    background:#fff; border:1.5px solid #e2e8f0; border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.1); z-index:50; max-height:200px; overflow-y:auto;
}
.chip-dropdown.open { display:block; animation: ddIn .15s ease; }
@keyframes ddIn { from{opacity:0;transform:translateY(-4px)} to{opacity:1;transform:translateY(0)} }
.chip-dropdown-item {
    padding:8px 14px; font-size:13px; cursor:pointer; transition: background .1s;
    display:flex; align-items:center; gap:8px;
}
.chip-dropdown-item:hover { background:#f1f5f9; }
.chip-dropdown-item.selected { background:#eef2ff; color:#4338ca; font-weight:500; }
.chip-dropdown-item .check-icon { width:14px; height:14px; opacity:0; color:#6366f1; }
.chip-dropdown-item.selected .check-icon { opacity:1; }

/* ─── Combo Matrix ─── */
.combo-section {
    margin:0 20px 20px; padding:20px; border-radius:10px;
    background:linear-gradient(135deg,#fafbff 0%,#f0f4ff 100%);
    border:1.5px solid #e0e7ff;
}
.combo-section h4 {
    margin:0 0 4px; font-size:15px; font-weight:600; color:#312e81;
    display:flex; align-items:center; gap:8px;
}
.combo-section .combo-subtitle { font-size:12px; color:#6b7280; margin-bottom:16px; }
.combo-table { width:100%; border-collapse:separate; border-spacing:0; border-radius:8px; overflow:hidden; }
.combo-table thead th {
    padding:10px 14px; font-size:11px; text-transform:uppercase; letter-spacing:.5px;
    font-weight:600; color:#6366f1; background:#e0e7ff; text-align:left;
    border-bottom:2px solid #c7d2fe;
}
.combo-table tbody tr { transition:background .15s; }
.combo-table tbody tr:hover { background:rgba(99,102,241,.04); }
.combo-table tbody td {
    padding:8px 14px; border-bottom:1px solid #e8ecf4; vertical-align:middle;
}
.combo-table .combo-label {
    display:inline-flex; align-items:center; gap:6px;
    font-size:13px; font-weight:500; color:#1e293b;
}
.combo-table .combo-dot {
    width:8px; height:8px; border-radius:50%; flex-shrink:0;
}
.combo-table input[type="number"] {
    width:100%; padding:7px 10px; border:1.5px solid #e2e8f0; border-radius:6px;
    font-size:13px; transition: border-color .15s, box-shadow .15s; background:#fff;
}
.combo-table input[type="number"]:focus {
    outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1);
}
.combo-badge-count {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:22px; height:22px; border-radius:12px;
    background:#6366f1; color:#fff; font-size:11px; font-weight:700;
}
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Products</h1>
                <p class="page-description">Manage your product catalog</p>
            </div>
            <div class="page-actions">
                @if(can('products.write'))
                    <button class="btn btn-primary" onclick="openAddModal()"><i data-lucide="plus"
                            style="width:16px;height:16px"></i> Add Product</button>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="table-container">
        <div class="table-wrapper">
            <table class="table" id="products-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        @foreach($customColumns->where('show_on_list', true) as $col)
                            <th>{{ $col->name }}</th>
                        @endforeach
                        <th>Status</th>
                        <th style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><span class="badge badge-secondary">{{ $product->category->name ?? 'N/A' }}</span></td>
                            @foreach($customColumns->where('show_on_list', true) as $col)
                                <td>
                                    @if($col->is_system)
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
                                            // Try to decode JSON for multiselect display
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
                                <div style="display:flex;gap:8px">
                                    @if(can('products.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm edit-product-btn" title="Edit"
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
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                    @if(can('products.delete'))
                                        <button type="button" onclick="ajaxDelete('{{ route('admin.products.destroy', $product->id) }}')" class="btn btn-ghost btn-icon btn-sm" style="color:var(--destructive)" title="Delete">
                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                            </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-muted">No products found. Click "Add Product" to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">{{ $products->links() }}</div>
    </div>

    <!-- ════════════ Add/Edit Product Modal ════════════ -->
    <div id="product-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:12px;width:95%;max-width:960px;max-height:92vh;overflow-y:auto">
            <div style="padding:20px 24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="modal-title" style="margin:0;font-size:18px">Add New Product</h3>
                <button type="button" onclick="closeProductModal(event)"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px;color:#64748b">&times;</button>
            </div>
            <form id="product-form" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="form-method" name="_method" value="">

                <!-- ── Top-Level Anchor: Category ── -->
                @if(!$customColumns->where('is_category', true)->count())
                <div style="padding:20px 24px 12px;">
                    <label style="display:block;margin-bottom:4px;font-weight:500;font-size:13px">Category *</label>
                    <select class="form-select" name="category_id" id="prod-category_id" required
                        style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px">
                        <option value="">Select Category</option>
                        @foreach($categories->whereNull('parent_category_id') as $cat)
                            @php $subcats = $categories->where('parent_category_id', $cat->id); @endphp
                            @if($subcats->count() > 0)
                                <optgroup label="{{ $cat->name }}">
                                    <option value="{{ $cat->id }}">{{ $cat->name }} (Main)</option>
                                    @foreach($subcats as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                    @endforeach
                                </optgroup>
                            @else
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- ── Product Details (non-combo dynamic) ── -->
                <div style="padding:8px 24px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div style="grid-column:span 2">
                        <h4 style="margin:8px 0;padding-bottom:8px;border-bottom:1px solid #eee;font-size:14px;color:#334155">Product Details</h4>
                    </div>
                    @foreach($customColumns->where('is_combo', false) as $col)
                        @php
                            $inputName = $col->is_category ? 'category_id' : ($col->is_system ? $col->slug : "custom_data[{$col->id}]");
                            $inputId = $col->is_category ? 'prod-category_id' : ($col->is_system ? "prod-{$col->slug}" : "custom-{$col->id}");
                            $requiredStr = $col->is_required ? 'required' : '';
                            $reqLabel = $col->is_required ? ' *' : '';
                            $span = $col->type === 'textarea' ? 'grid-column: span 2;' : '';
                        @endphp
                        <div style="{{ $span }}">
                            <label style="display:block;margin-bottom:4px;font-weight:500;font-size:13px">{{ $col->name }}{{ $reqLabel }}</label>
                            @if($col->is_category)
                                <select class="form-select" name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }}
                                    style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px">
                                    <option value="">Select {{ $col->name }}</option>
                                    @foreach($categories->whereNull('parent_category_id') as $cat)
                                        @php $subcats = $categories->where('parent_category_id', $cat->id); @endphp
                                        @if($subcats->count() > 0)
                                            <optgroup label="{{ $cat->name }}">
                                                <option value="{{ $cat->id }}">{{ $cat->name }} (Main)</option>
                                                @foreach($subcats as $sub)
                                                    <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @else
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @elseif($col->type === 'textarea')
                                <textarea class="form-textarea" name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }} rows="3"
                                    style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px"></textarea>
                            @elseif($col->type === 'select')
                                <select class="form-select" name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }}
                                    style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px">
                                    <option value="">Select {{ $col->name }}</option>
                                    @if($col->is_system && $col->slug === 'gst_percent')
                                        @foreach([0,5,12,18,28] as $pct)
                                            <option value="{{ $pct }}">{{ $pct }}%</option>
                                        @endforeach
                                    @endif
                                    @foreach($col->options ?? [] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @elseif($col->type === 'boolean')
                                <select class="form-select" name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }}
                                    style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            @else
                                <input type="{{ $col->type === 'number' ? 'number' : 'text' }}" class="form-input"
                                    name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }}
                                    {{ $col->type === 'number' ? 'step=0.01 min=0' : '' }}
                                    placeholder="Enter {{ strtolower($col->name) }}"
                                    style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px">
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- ── Product Media Upload ── -->
                <div style="padding:8px 24px 20px;">
                    <h4 style="margin:8px 0;padding-bottom:8px;border-bottom:1px solid #eee;font-size:14px;color:#334155;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Product Media
                    </h4>
                    
                    <div style="margin-top:12px">
                        <label style="display:flex;align-items:center;gap:6px;margin-bottom:4px;font-weight:600;font-size:13px;color:#1e293b">
                            Unique Model Image
                        </label>
                        <p style="font-size:11px;color:#64748b;margin:0 0 8px;line-height:1.4">Displays when user explicitly selects THIS specific variation/model (e.g. Model 001). This is unique to this product entry.</p>
                        <div style="position:relative;border:1.5px dashed #cbd5e1;border-radius:8px;padding:12px;background:#f8fafc;text-align:center">
                            <input type="file" name="cover_media" id="prod-cover-media" accept="image/*,video/*,application/pdf" style="width:100%;font-size:12px">
                            <div id="cover-media-preview" style="margin-top:8px;font-size:11px;color:#6366f1;display:none">
                                <a href="#" target="_blank" style="text-decoration:none;color:#6366f1;font-weight:500">📎 View Current File</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Combo Variation Selector (Tag Chips) ── -->
                @php $comboCols = $customColumns->where('is_combo', true); @endphp
                @if($comboCols->count() > 0)
                <div style="padding:0 24px 12px;">
                    <h4 style="margin:8px 0;padding-bottom:8px;border-bottom:1px solid #eee;font-size:14px;color:#334155;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                        Variations
                    </h4>
                    <p style="font-size:12px;color:#6b7280;margin:0 0 14px">Select options below. All combinations will auto-generate as variation rows.</p>
                    <div style="display:grid;grid-template-columns:repeat({{ min($comboCols->count(), 3) }}, 1fr);gap:14px;">
                        @foreach($comboCols as $combo)
                            <div>
                                <label style="display:block;margin-bottom:4px;font-weight:500;font-size:13px;color:#475569">{{ $combo->name }}</label>
                                <div class="chip-select-wrap" data-combo-id="{{ $combo->id }}" data-combo-name="{{ $combo->slug }}">
                                    <div class="chip-select-trigger" onclick="toggleChipDrop(this)">
                                        <span class="cs-placeholder">Select {{ $combo->name }}...</span>
                                    </div>
                                    <div class="chip-dropdown">
                                        @foreach($combo->options ?? [] as $opt)
                                            <div class="chip-dropdown-item" data-value="{{ $opt }}" onclick="toggleChipItem(this)">
                                                <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                {{ $opt }}
                                            </div>
                                        @endforeach
                                    </div>
                                    <!-- Hidden inputs will be injected by JS -->
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- ── Combo Matrix (auto-generated) ── -->
                <div id="combo-matrix-section" class="combo-section" style="display:none">
                    <h4>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                        Variation Pricing
                        <span id="combo-count" class="combo-badge-count">0</span>
                    </h4>
                    <p class="combo-subtitle">Set individual price & discount for each variation combination</p>
                    <div style="overflow-x:auto">
                        <table class="combo-table" id="combo-matrix-table">
                            <thead><tr id="combo-matrix-header"></tr></thead>
                            <tbody id="combo-matrix-body"></tbody>
                        </table>
                    </div>
                </div>
                @endif

                <div style="padding:12px 24px;">
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="checkbox" name="is_purchase_enabled" id="prod-purchase-enabled" value="1"
                            style="width:16px;height:16px">
                        <label for="prod-purchase-enabled" style="font-weight:500;margin:0;font-size:13px">Enable Auto-Purchase generation for this Product/Service</label>
                    </div>
                </div>

                <div style="padding:16px 24px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeProductModal(event)"
                        style="padding:8px 20px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding:8px 20px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
var comboColDefs = @json(isset($comboCols) ? $comboCols->values() : collect());
var comboSelections = {}; // { comboId: [val1, val2..] }
var existingVariationData = {}; // { "black|large": { price: 100, discount: 10 } }
var dotColors = ['#6366f1','#f59e0b','#10b981','#ef4444','#8b5cf6','#ec4899','#06b6d4'];

// ─── Chip Multiselect ───
function toggleChipDrop(trigger) {
    var wrap = trigger.closest('.chip-select-wrap');
    var dd = wrap.querySelector('.chip-dropdown');
    var isOpen = dd.classList.contains('open');
    document.querySelectorAll('.chip-dropdown.open').forEach(function(d) { d.classList.remove('open'); });
    document.querySelectorAll('.chip-select-trigger.open').forEach(function(t) { t.classList.remove('open'); });
    if (!isOpen) { dd.classList.add('open'); trigger.classList.add('open'); }
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.chip-select-wrap')) {
        document.querySelectorAll('.chip-dropdown.open').forEach(function(d) { d.classList.remove('open'); });
        document.querySelectorAll('.chip-select-trigger.open').forEach(function(t) { t.classList.remove('open'); });
    }
});

function toggleChipItem(item) {
    item.classList.toggle('selected');
    var wrap = item.closest('.chip-select-wrap');
    refreshChips(wrap);
}

function refreshChips(wrap) {
    var comboId = wrap.dataset.comboId;
    var comboName = wrap.dataset.comboName;
    var trigger = wrap.querySelector('.chip-select-trigger');
    var selected = Array.from(wrap.querySelectorAll('.chip-dropdown-item.selected'));
    var vals = selected.map(function(el) { return el.dataset.value; });
    comboSelections[comboId] = vals;

    // Remove old chips & hidden inputs
    wrap.querySelectorAll('.chip-tag').forEach(function(c) { c.remove(); });
    wrap.querySelectorAll('input[type="hidden"]').forEach(function(h) { h.remove(); });
    trigger.querySelector('.cs-placeholder').style.display = vals.length ? 'none' : '';

    vals.forEach(function(val) {
        // Chip tag
        var chip = document.createElement('span');
        chip.className = 'chip-tag';
        chip.innerHTML = val + '<span class="chip-x" data-val="' + val + '">&times;</span>';
        trigger.insertBefore(chip, trigger.querySelector('.cs-placeholder'));

        // Hidden input
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'combo_data[' + comboId + '][]';
        inp.value = val;
        wrap.appendChild(inp);
    });

    // Chip remove handler
    trigger.querySelectorAll('.chip-x').forEach(function(x) {
        x.onclick = function(e) {
            e.stopPropagation();
            var v = this.dataset.val;
            var ddItem = wrap.querySelector('.chip-dropdown-item[data-value="' + v + '"]');
            if (ddItem) ddItem.classList.remove('selected');
            refreshChips(wrap);
        };
    });

    rebuildComboMatrix();
}

// ─── Combo Matrix Builder ───
function rebuildComboMatrix() {
    var section = document.getElementById('combo-matrix-section');
    var header = document.getElementById('combo-matrix-header');
    var body = document.getElementById('combo-matrix-body');
    if (!section) return;

    // --- Save current DOM values BEFORE tearing down ---
    if (body.children.length > 0) {
        Array.from(body.children).forEach(function(row) {
            var priceInput = row.querySelector('input[name$="[price]"]');
            var discountInput = row.querySelector('input[name$="[discount]"]');
            var hiddenComboInputs = Array.from(row.querySelectorAll('input[type="hidden"]'));
            
            if (priceInput && discountInput && hiddenComboInputs.length > 0) {
                var rowComboVals = hiddenComboInputs.map(function(inp) { return inp.value; });
                var key = rowComboVals.map(function(v) { return v.toLowerCase().replace(/\s+/g,'-'); }).join('|');
                existingVariationData[key] = {
                    price: priceInput.value,
                    discount: discountInput.value
                };
            }
        });
    }

    // Gather active combo columns that have selections
    var activeCombo = [];
    comboColDefs.forEach(function(col) {
        var id = col.id.toString();
        var vals = comboSelections[id] || [];
        if (vals.length > 0) activeCombo.push({ id: id, name: col.name, slug: col.slug, vals: vals });
    });

    if (activeCombo.length === 0) {
        section.style.display = 'none';
        body.innerHTML = '';
        return;
    }

    // Generate cartesian product
    var combos = cartesian(activeCombo.map(function(c) { return c.vals; }));

    // Build header
    var hHtml = '';
    activeCombo.forEach(function(c) { hHtml += '<th>' + c.name + '</th>'; });
    hHtml += '<th style="width:160px">Price (₹)</th>';
    hHtml += '<th style="width:140px">Discount (%)</th>';
    header.innerHTML = hHtml;

    // Build rows
    var bHtml = '';
    combos.forEach(function(combo, idx) {
        var comboArr = Array.isArray(combo) ? combo : [combo];
        var key = comboArr.map(function(v) { return v.toLowerCase().replace(/\s+/g,'-'); }).join('|');
        var ex = existingVariationData[key] || { price: '', discount: '' };

        bHtml += '<tr>';
        comboArr.forEach(function(val, ci) {
            var color = dotColors[ci % dotColors.length];
            bHtml += '<td><span class="combo-label"><span class="combo-dot" style="background:' + color + '"></span>' + val + '</span></td>';
        });
        bHtml += '<td><input type="number" name="variations[' + idx + '][price]" placeholder="0.00" step="0.01" min="0" value="' + ex.price + '"></td>';
        bHtml += '<td><input type="number" name="variations[' + idx + '][discount]" placeholder="0" step="0.01" min="0" max="100" value="' + ex.discount + '"></td>';

        // Hidden combination data
        activeCombo.forEach(function(c, ci) {
            bHtml += '<input type="hidden" name="variations[' + idx + '][combination][' + c.slug + ']" value="' + comboArr[ci] + '">';
        });

        bHtml += '</tr>';
    });

    body.innerHTML = bHtml;
    document.getElementById('combo-count').textContent = combos.length;
    section.style.display = '';
}

function cartesian(arrays) {
    if (arrays.length === 0) return [];
    if (arrays.length === 1) return arrays[0].map(function(v) { return [v]; });
    return arrays.reduce(function(a, b) {
        var ret = [];
        a.forEach(function(x) {
            b.forEach(function(y) {
                ret.push((Array.isArray(x) ? x : [x]).concat([y]));
            });
        });
        return ret;
    });
}

// ─── Modal Controls ───
function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add New Product';
    document.getElementById('product-form').action = '{{ route("admin.products.store") }}';
    document.getElementById('form-method').value = '';
    document.getElementById('product-form').reset();
    document.getElementById('prod-purchase-enabled').checked = false;

    // Reset all chip selects
    document.querySelectorAll('.chip-dropdown-item.selected').forEach(function(i) { i.classList.remove('selected'); });
    document.querySelectorAll('.chip-select-wrap').forEach(function(w) { refreshChips(w); });
    comboSelections = {};
    existingVariationData = {};
    rebuildComboMatrix();

    document.getElementById('product-modal').style.display = 'flex';
    if(typeof lucide !== 'undefined') lucide.createIcons();
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.edit-product-btn');
    if (!btn) return;

    var product = JSON.parse(btn.dataset.product);
    var customValues = JSON.parse(btn.dataset.customValues);
    var variations = JSON.parse(btn.dataset.variations || '[]');

    existingVariationData = {};
    variations.forEach(function(v) {
        if (v.combination) {
            var comboVals = [];
            comboColDefs.forEach(function(col) {
                if (v.combination[col.slug]) {
                    comboVals.push(v.combination[col.slug]);
                }
            });
            var fKey = comboVals.map(function(val) { return val.toLowerCase().replace(/\s+/g,'-'); }).join('|');
            existingVariationData[fKey] = {
                price: v.price ? (v.price / 100).toFixed(2) : '',
                discount: v.discount || ''
            };
        }
    });

    document.getElementById('modal-title').textContent = 'Edit Product';
    document.getElementById('product-form').action = '{{ url("admin/products") }}/' + product.id;
    document.getElementById('form-method').value = 'PUT';
    document.getElementById('product-form').reset();

    document.getElementById('prod-category_id').value = product.category_id || '';
    document.getElementById('prod-purchase-enabled').checked = product.is_purchase_enabled == 1;

    // System columns
    var sysCols = @json($customColumns->where('is_system', true)->pluck('slug')->values());
    sysCols.forEach(function(slug) {
        var el = document.getElementById('prod-' + slug);
        if (el) el.value = product[slug] !== null && product[slug] !== undefined ? product[slug] : '';
    });

    // Non-system non-combo columns
    var nonSysCols = @json($customColumns->where('is_system', false)->where('is_combo', false)->pluck('id')->values());
    nonSysCols.forEach(function(id) {
        var el = document.getElementById('custom-' + id);
        if (el && customValues[id] !== undefined) el.value = customValues[id];
    });

    // Populate media previews
    var coverMediaPreview = document.getElementById('cover-media-preview');
    if (product.cover_media_url) {
        coverMediaPreview.style.display = 'block';
        coverMediaPreview.querySelector('a').href = product.cover_media_url;
    } else {
        coverMediaPreview.style.display = 'none';
        coverMediaPreview.querySelector('a').href = '#';
    }

    // Combo columns: restore tag chips
    document.querySelectorAll('.chip-dropdown-item.selected').forEach(function(i) { i.classList.remove('selected'); });
    var comboCols = @json($customColumns->where('is_combo', true)->pluck('id')->values());
    comboCols.forEach(function(id) {
        if (customValues[id]) {
            var vals;
            try { vals = JSON.parse(customValues[id]); } catch(e) { vals = [customValues[id]]; }
            if (!Array.isArray(vals)) vals = [vals];
            var wrap = document.querySelector('.chip-select-wrap[data-combo-id="' + id + '"]');
            if (wrap) {
                vals.forEach(function(v) {
                    var item = wrap.querySelector('.chip-dropdown-item[data-value="' + v + '"]');
                    if (item) item.classList.add('selected');
                });
                refreshChips(wrap);
            }
        }
    });

    document.getElementById('product-modal').style.display = 'flex';
    if(typeof lucide !== 'undefined') lucide.createIcons();
});

function closeProductModal(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    document.getElementById('product-modal').style.display = 'none';
    document.getElementById('product-form').reset();
}

document.getElementById('product-modal').addEventListener('click', function (e) { if (e.target === this) closeProductModal(e); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeProductModal(e); });
</script>
@endpush