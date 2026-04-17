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

/* ─── Product Table: Full Width, No Scroll ─── */
#products-table { table-layout:fixed; width:100%; }
#products-table th, #products-table td { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; padding:10px 8px; font-size:13px; }
#products-table td { white-space:normal; word-wrap:break-word; }
#products-table .col-checkbox { width:40px; text-align:center; }
#products-table .col-status { width:70px; }
#products-table .col-actions { width:90px; }
.table-wrapper { overflow-x:hidden !important; }

/* Bigger action icons */
#products-table .action-btn { padding:6px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; }
#products-table .action-btn i, #products-table .action-btn svg { width:18px !important; height:18px !important; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Products</h1>
                <p class="page-description">Manage your product catalog</p>
            </div>
            <div class="page-actions" style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('admin.products.demo-excel') }}" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #10b981;color:#10b981;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;transition:all .2s" onmouseover="this.style.background='#10b981';this.style.color='white'" onmouseout="this.style.background='transparent';this.style.color='#10b981'">
                    <i data-lucide="download" style="width:16px;height:16px"></i> Demo Excel
                </a>
                @if(can('products.write'))
                    <button class="btn btn-outline" onclick="openImportModal()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #6366f1;color:#6366f1;border-radius:8px;font-size:13px;font-weight:500;transition:all .2s" onmouseover="this.style.background='#6366f1';this.style.color='white'" onmouseout="this.style.background='transparent';this.style.color='#6366f1'">
                        <i data-lucide="upload" style="width:16px;height:16px"></i> Import Excel
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()" style="display:inline-flex;align-items:center;gap:6px"><i data-lucide="plus" style="width:16px;height:16px"></i> Add Product</button>
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

    {{-- ════ Search Bar ════ --}}
    @php
        $categoryCol = $customColumns->where('is_category', true)->first();
        $uniqueCol = $customColumns->where('is_unique', true)->first();
    @endphp
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
        {{-- Category Filter --}}
        @if($categoryCol)
        <div style="flex:1;min-width:180px;max-width:250px">
            <select id="search-category" onchange="searchProducts()"
                style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;color:#1e293b;outline:none;transition:border-color .2s;cursor:pointer"
                onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.1)'"
                onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                <option value="">All {{ $categoryCol->name ?? 'Categories' }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_search') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Unique ID Search --}}
        @if($uniqueCol)
        <div style="flex:1;min-width:180px;max-width:280px;position:relative">
            <i data-lucide="search" style="width:16px;height:16px;position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
            <input type="text" id="search-unique" placeholder="Search by {{ $uniqueCol->name ?? 'Code' }}..."
                value="{{ request('unique_search') }}"
                oninput="debounceSearch()"
                style="width:100%;padding:9px 12px 9px 36px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;color:#1e293b;outline:none;transition:border-color .2s"
                onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.1)'"
                onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
        </div>
        @endif

        {{-- Reset Button --}}
        <button onclick="resetSearch()" title="Reset"
            style="padding:9px 14px;border:1.5px solid #e2e8f0;background:#fff;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px;transition:all .2s"
            onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
            onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
            <i data-lucide="x-circle" style="width:15px;height:15px"></i> Reset
        </button>
    </div>

    <div class="table-container">
        {{-- Bulk Delete Floating Bar --}}
        <div id="bulk-action-bar" style="display:none;position:sticky;top:0;z-index:50;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;padding:12px 20px;border-radius:10px;margin-bottom:12px;display:none;align-items:center;justify-content:space-between;box-shadow:0 4px 15px rgba(239,68,68,0.3);animation:slideDown .3s ease">
            <div style="display:flex;align-items:center;gap:10px">
                <i data-lucide="check-square" style="width:20px;height:20px"></i>
                <span id="bulk-count" style="font-weight:600;font-size:14px">0 selected</span>
            </div>
            <div style="display:flex;gap:8px">
                <button onclick="selectAllProducts()" style="padding:6px 14px;border:1px solid rgba(255,255,255,0.4);background:transparent;color:white;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">Select All On Page</button>
                <button onclick="bulkDeleteProducts()" style="padding:6px 14px;background:white;color:#ef4444;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:13px;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,0.1)" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">🗑️ Delete Selected</button>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="table" id="products-table">
                <thead>
                    <tr>
                        @if(can('products.delete'))
                            <th class="col-checkbox">
                                <input type="checkbox" id="select-all-products" onchange="toggleAllProducts(this)" style="width:16px;height:16px;accent-color:#6366f1;cursor:pointer">
                            </th>
                        @endif
                        @foreach($customColumns->where('show_on_list', true)->where('is_required', true) as $col)
                            <th>{{ $col->name }}</th>
                        @endforeach
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
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
                                        {{-- Column is both Category + Title: show category name --}}
                                        <span class="font-medium">{{ $product->category->name ?? $product->name }}</span>
                                    @elseif($col->is_category)
                                        {{-- Category-only column: show category name as badge --}}
                                        <span class="badge badge-secondary">{{ $product->category->name ?? 'N/A' }}</span>
                                    @elseif($col->is_title)
                                        {{-- Title-only column: prefer custom value for non-system columns --}}
                                        @php
                                            $displayName = $product->name;
                                            // For non-system title columns, read from custom values
                                            if (!$col->is_system) {
                                                $titleCustomVal = $product->customValues->where('column_id', $col->id)->first();
                                                if ($titleCustomVal && !empty($titleCustomVal->value)) {
                                                    $displayName = $titleCustomVal->value;
                                                }
                                            }
                                            // Fallback for generic/broken names
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
                            <td colspan="10" class="text-center py-8 text-muted">No products found. Click "Add Product" to create one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            {{ $products->links() }}
        </div>
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
                    <div style="display:flex; gap:8px;">
                        <select class="form-select inline-category-select" name="category_id" id="prod-category_id" required
                            style="flex-grow:1;width:auto;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px" onchange="toggleCategoryButtons(this)">
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
                        <button type="button" class="btn btn-outline" onclick="openInlineCategoryModal(this, 'Category')" title="Add Category" style="padding:4px 10px; border:1.5px solid #e2e8f0; border-radius:8px; background:white; color:#6366f1;">
                            <i data-lucide="plus" style="width:16px;height:16px"></i>
                        </button>
                        <button type="button" class="btn btn-outline inline-cat-edit-btn" onclick="editInlineCategory(this, 'Category')" title="Edit Selected Category" style="padding:4px 10px; border:1.5px solid #e2e8f0; border-radius:8px; background:white; color:#f59e0b; display:none;">
                            <i data-lucide="edit-2" style="width:16px;height:16px"></i>
                        </button>
                        <button type="button" class="btn btn-outline inline-cat-delete-btn" onclick="deleteInlineCategory(this)" title="Delete Selected Category" style="padding:4px 10px; border:1.5px solid #ef4444; border-radius:8px; background:white; color:#ef4444; display:none;">
                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                        </button>
                    </div>
                </div>
                @endif

                <!-- ── Product Details (non-combo dynamic) ── -->
                <div style="padding:8px 24px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div style="grid-column:span 2">
                        <h4 style="margin:8px 0;padding-bottom:8px;border-bottom:1px solid #eee;font-size:14px;color:#334155">Product Details</h4>
                    </div>
                    @foreach($customColumns->filter(function($c) { return !$c->is_combo && !$c->is_variation_field; }) as $col)
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
                                <div style="display:flex; gap:8px;">
                                    <select class="form-select inline-category-select" name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }}
                                        style="flex-grow:1;width:auto;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px" onchange="toggleCategoryButtons(this)">
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
                                    <button type="button" class="btn btn-outline" onclick="openInlineCategoryModal(this, '{{ addslashes($col->name) }}')" title="Add {{ $col->name }}" style="padding:4px 10px; border:1.5px solid #e2e8f0; border-radius:8px; background:white; color:#6366f1;">
                                        <i data-lucide="plus" style="width:16px;height:16px"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline inline-cat-edit-btn" onclick="editInlineCategory(this, '{{ addslashes($col->name) }}')" title="Edit Selected {{ $col->name }}" style="padding:4px 10px; border:1.5px solid #e2e8f0; border-radius:8px; background:white; color:#f59e0b; display:none;">
                                        <i data-lucide="edit-2" style="width:16px;height:16px"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline inline-cat-delete-btn" onclick="deleteInlineCategory(this)" title="Delete Selected {{ $col->name }}" style="padding:4px 10px; border:1.5px solid #ef4444; border-radius:8px; background:white; color:#ef4444; display:none;">
                                        <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                    </button>
                                </div>
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

    <!-- ════════════ Add/Edit Category Modal (Inline AJAX) ════════════ -->
    <div id="category-ajax-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:2000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:12px;width:90%;max-width:500px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:#f8fafc;border-top-left-radius:12px;border-top-right-radius:12px;">
                <h3 id="cat-modal-title" style="margin:0;font-size:16px;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:8px">
                    <i data-lucide="folder-plus" style="width:18px;height:18px;color:#6366f1"></i>
                    <span>Add New Category</span>
                </h3>
                <button type="button" onclick="closeInlineCategoryModal()" style="background:none;border:none;cursor:pointer;color:#64748b;display:flex;align-items:center">
                    <i data-lucide="x" style="width:20px;height:20px"></i>
                </button>
            </div>
            <form id="category-ajax-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="cat-form-method" name="_method" value="POST">
                <input type="hidden" id="cat-id" value="">
                <input type="hidden" name="status" value="active">
                
                <div style="padding:20px;">
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:13px;color:#334155">Name <span style="color:#ef4444">*</span></label>
                        <input type="text" name="name" id="cat-name-input" required class="form-input" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px" placeholder="Enter name...">
                    </div>
                    
                    <div>
                        <label style="display:block;margin-bottom:6px;font-weight:500;font-size:13px;color:#334155">Image (Optional)</label>
                        <div style="border:1.5px dashed #cbd5e1;padding:12px;border-radius:8px;background:#f8fafc;text-align:center">
                            <input type="file" name="image" id="cat-image-input" accept="image/*" style="width:100%;font-size:12px;color:#64748b" onchange="previewCatModalImage(this)">
                            <div id="cat-current-image-wrapper" style="display:none;margin-top:10px;text-align:center;background:white;padding:4px;border:1px solid #e2e8f0;border-radius:8px;position:relative;overflow:hidden;">
                                <a id="cat-image-link" href="#" target="_blank" title="Click to view full image">
                                    <img id="cat-image-preview" src="" alt="Preview" style="max-width:100%;height:100px;object-fit:contain;border-radius:4px;transition:transform 0.2s" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px;background:#f8fafc;border-bottom-left-radius:12px;border-bottom-right-radius:12px;">
                    <button type="button" class="btn btn-outline" onclick="closeInlineCategoryModal()" style="padding:8px 16px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500">Cancel</button>
                    <button type="submit" id="cat-submit-btn" class="btn btn-primary" style="padding:8px 16px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px">
                        <i data-lucide="save" style="width:16px;height:16px"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════════════ Excel Import Modal ════════════ -->
    <div id="import-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:3000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:16px;width:95%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)">
            <!-- Header -->
            <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#eef2ff);border-radius:16px 16px 0 0">
                <div>
                    <h3 style="margin:0;font-size:18px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        Import Products from Excel
                    </h3>
                    <p style="margin:4px 0 0;font-size:12px;color:#64748b">Upload your filled Excel template to bulk import products</p>
                </div>
                <button type="button" onclick="closeImportModal()" style="width:32px;height:32px;border-radius:50%;border:1px solid #e2e8f0;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;font-size:18px;transition:all .2s" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444'" onmouseout="this.style.background='white';this.style.color='#64748b'">&times;</button>
            </div>

            <!-- Step 1: Upload -->
            <div id="import-step-upload" style="padding:24px">
                <div id="import-dropzone" style="border:2px dashed #cbd5e1;border-radius:12px;padding:40px 24px;text-align:center;cursor:pointer;transition:all .2s;background:#fafbff" onclick="document.getElementById('import-file-input').click()" ondragover="event.preventDefault();this.style.borderColor='#6366f1';this.style.background='#eef2ff'" ondragleave="this.style.borderColor='#cbd5e1';this.style.background='#fafbff'" ondrop="handleFileDrop(event)">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin:0 auto 12px"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p style="margin:0;font-size:15px;font-weight:600;color:#334155">Drop Excel file here or click to browse</p>
                    <p style="margin:6px 0 0;font-size:12px;color:#94a3b8">Supports .xlsx, .xls (max 10MB)</p>
                    <input type="file" id="import-file-input" accept=".xlsx,.xls" style="display:none" onchange="handleFileSelect(this)">
                </div>
                <div id="import-file-info" style="display:none;margin-top:12px;padding:12px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;display:none;align-items:center;gap:10px">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span id="import-file-name" style="font-size:13px;font-weight:500;color:#15803d;flex:1"></span>
                    <button type="button" onclick="clearImportFile()" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:18px">&times;</button>
                </div>
                <div style="margin-top:16px;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" onclick="closeImportModal()" style="padding:10px 20px;border:1px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b">Cancel</button>
                    <button type="button" id="import-validate-btn" onclick="validateImport()" disabled style="padding:10px 24px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;opacity:0.5;transition:all .2s;display:inline-flex;align-items:center;gap:6px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                        Validate & Preview
                    </button>
                </div>
            </div>

            <!-- Step 2: Validation Results -->
            <div id="import-step-results" style="display:none;padding:24px">
                <!-- Summary Cards -->
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
                    <div style="padding:16px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;border-radius:10px;text-align:center">
                        <div id="import-valid-count" style="font-size:28px;font-weight:800;color:#16a34a">0</div>
                        <div style="font-size:11px;font-weight:600;color:#15803d;text-transform:uppercase;letter-spacing:.5px">Ready to Import</div>
                    </div>
                    <div style="padding:16px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid #fde68a;border-radius:10px;text-align:center">
                        <div id="import-cat-error-count" style="font-size:28px;font-weight:800;color:#d97706">0</div>
                        <div style="font-size:11px;font-weight:600;color:#b45309;text-transform:uppercase;letter-spacing:.5px">New Categories</div>
                    </div>
                    <div style="padding:16px;background:linear-gradient(135deg,#fef2f2,#fecaca);border:1px solid #fca5a5;border-radius:10px;text-align:center">
                        <div id="import-hard-error-count" style="font-size:28px;font-weight:800;color:#dc2626">0</div>
                        <div style="font-size:11px;font-weight:600;color:#b91c1c;text-transform:uppercase;letter-spacing:.5px">Data Errors</div>
                    </div>
                </div>

                <!-- Missing Categories Section -->
                <div id="import-cat-section" style="display:none;margin-bottom:16px">
                    <div style="padding:16px;background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;border-radius:0 8px 8px 0">
                        <div style="font-weight:600;color:#92400e;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            New Categories Found
                        </div>
                        <p style="font-size:12px;color:#78350f;margin:0 0 10px">These categories don't exist yet. Choose an action:</p>
                        <div id="import-cat-list" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px"></div>
                        <div style="display:flex;gap:8px">
                            <button type="button" onclick="processImport('skip')" style="padding:8px 16px;border:1.5px solid #dc2626;color:#dc2626;background:white;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;transition:all .2s" onmouseover="this.style.background='#dc2626';this.style.color='white'" onmouseout="this.style.background='white';this.style.color='#dc2626'">
                                ✕ Skip These Rows
                            </button>
                            <button type="button" onclick="processImport('create')" style="padding:8px 16px;border:none;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600;transition:all .2s">
                                ✚ Create Categories & Import All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hard Errors -->
                <div id="import-hard-section" style="display:none;margin-bottom:16px">
                    <div style="padding:16px;background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:0 8px 8px 0">
                        <div style="font-weight:600;color:#991b1b;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            Data Errors (will be skipped)
                        </div>
                        <div id="import-hard-list" style="max-height:200px;overflow-y:auto;font-size:12px"></div>
                    </div>
                </div>

                <!-- Unmapped Headers -->
                <div id="import-unmapped-section" style="display:none;margin-bottom:16px">
                    <div style="padding:12px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#475569">
                        <strong>ℹ Unrecognized columns (ignored):</strong> <span id="import-unmapped-list"></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div id="import-action-btns" style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                    <button type="button" onclick="resetImportModal()" style="padding:10px 20px;border:1px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b">← Re-upload</button>
                    <button type="button" id="import-direct-btn" onclick="processImport('skip')" style="display:none;padding:10px 24px;background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600">
                        🚀 Import All
                    </button>
                </div>
            </div>

            <!-- Step 3: Processing -->
            <div id="import-step-processing" style="display:none;padding:40px 24px;text-align:center">
                <div style="width:48px;height:48px;border:4px solid #e2e8f0;border-top-color:#6366f1;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px"></div>
                <p style="font-size:15px;font-weight:600;color:#334155;margin:0">Importing products...</p>
                <p style="font-size:12px;color:#94a3b8;margin:6px 0 0">Please wait, do not close this window</p>
            </div>

            <!-- Step 4: Final Results -->
            <div id="import-step-done" style="display:none;padding:24px">
                <div style="text-align:center;margin-bottom:20px">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="margin:0 auto 12px"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <h3 id="import-done-title" style="margin:0;font-size:20px;font-weight:700;color:#1e293b">Import Complete!</h3>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px">
                    <div style="padding:14px;background:#f0fdf4;border-radius:8px;text-align:center">
                        <div id="import-done-created" style="font-size:24px;font-weight:800;color:#16a34a">0</div>
                        <div style="font-size:11px;color:#15803d;font-weight:600">Created</div>
                    </div>
                    <div style="padding:14px;background:#eff6ff;border-radius:8px;text-align:center">
                        <div id="import-done-updated" style="font-size:24px;font-weight:800;color:#2563eb">0</div>
                        <div style="font-size:11px;color:#1d4ed8;font-weight:600">Updated</div>
                    </div>
                    <div style="padding:14px;background:#fef2f2;border-radius:8px;text-align:center">
                        <div id="import-done-skipped" style="font-size:24px;font-weight:800;color:#dc2626">0</div>
                        <div style="font-size:11px;color:#b91c1c;font-weight:600">Skipped</div>
                    </div>
                </div>
                <div id="import-done-errors" style="display:none;margin-bottom:16px">
                    <div style="padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;max-height:150px;overflow-y:auto">
                        <div style="font-weight:600;color:#991b1b;margin-bottom:6px">Skipped Rows:</div>
                        <div id="import-done-error-list"></div>
                    </div>
                </div>
                <div id="import-done-cats" style="display:none;margin-bottom:16px">
                    <div style="padding:12px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:12px">
                        <strong style="color:#92400e">New categories created:</strong> <span id="import-done-cat-list" style="color:#78350f"></span>
                    </div>
                </div>
                <div style="text-align:center">
                    <button type="button" onclick="closeImportModal();location.reload()" style="padding:10px 24px;background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600">Done — Refresh Page</button>
                </div>
            </div>
        </div>
    </div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
@endsection

@push('scripts')
<script>
var allSysCategories = @json($categories); // Raw array of all categories
var activeCategorySelect = null; // Store reference to the dropdown being modified

function toggleCategoryButtons(select) {
    var val = select.value;
    var wrapper = select.parentElement;
    var editBtn = wrapper.querySelector('.inline-cat-edit-btn');
    var delBtn = wrapper.querySelector('.inline-cat-delete-btn');
    
    if (val) {
        if (editBtn) editBtn.style.display = '';
        if (delBtn) delBtn.style.display = '';
    } else {
        if (editBtn) editBtn.style.display = 'none';
        if (delBtn) delBtn.style.display = 'none';
    }
}

function openInlineCategoryModal(btn, label) {
    activeCategorySelect = btn.parentElement.querySelector('select');
    
    document.getElementById('cat-modal-title').innerHTML = '<i data-lucide="folder-plus" style="width:18px;height:18px;color:#6366f1"></i> <span>Add New ' + label + '</span>';
    document.getElementById('cat-form-method').value = 'POST';
    document.getElementById('cat-id').value = '';
    document.getElementById('category-ajax-form').reset();
    document.getElementById('cat-current-image-wrapper').style.display = 'none';
    
    document.getElementById('category-ajax-modal').style.display = 'flex';
    if(typeof lucide !== 'undefined') lucide.createIcons();
}

function editInlineCategory(btn, label) {
    activeCategorySelect = btn.parentElement.querySelector('select');
    var catId = activeCategorySelect.value;
    if(!catId) return;
    
    // Find category from global var
    var catObj = allSysCategories.find(c => c.id == catId);
    if(!catObj) { alert('Category data missing!'); return; }
    
    document.getElementById('cat-modal-title').innerHTML = '<i data-lucide="edit-3" style="width:18px;height:18px;color:#f59e0b"></i> <span>Edit ' + label + '</span>';
    document.getElementById('cat-form-method').value = 'PUT';
    document.getElementById('cat-id').value = catId;
    document.getElementById('category-ajax-form').reset();
    document.getElementById('cat-name-input').value = catObj.name;
    
    if(catObj.image) {
        var url = '{{ asset("storage") }}/' + catObj.image;
        document.getElementById('cat-image-preview').src = url;
        document.getElementById('cat-image-link').href = url;
        document.getElementById('cat-image-link').target = '_blank';
        document.getElementById('cat-current-image-wrapper').style.display = 'block';
    } else {
        document.getElementById('cat-current-image-wrapper').style.display = 'none';
        document.getElementById('cat-image-preview').src = '';
    }
    
    document.getElementById('category-ajax-modal').style.display = 'flex';
    if(typeof lucide !== 'undefined') lucide.createIcons();
}

window.previewCatModalImage = function(input) {
    var wrapper = document.getElementById('cat-current-image-wrapper');
    var img = document.getElementById('cat-image-preview');
    var link = document.getElementById('cat-image-link');
    
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            link.href = '#';
            link.removeAttribute('target');
            wrapper.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        // Fallback to existing image
        var catId = document.getElementById('cat-id').value;
        if(catId) {
            var catObj = allSysCategories.find(c => c.id == catId);
            if(catObj && catObj.image) {
                var url = '{{ asset("storage") }}/' + catObj.image;
                img.src = url;
                link.href = url;
                link.target = '_blank';
                wrapper.style.display = 'block';
            } else {
                wrapper.style.display = 'none';
            }
        } else {
            wrapper.style.display = 'none';
        }
    }
}

function closeInlineCategoryModal() {
    document.getElementById('category-ajax-modal').style.display = 'none';
    activeCategorySelect = null;
}

document.getElementById('category-ajax-form').addEventListener('submit', function(e) {
    e.preventDefault();
    if(!activeCategorySelect) return;
    
    var form = e.target;
    var btn = document.getElementById('cat-submit-btn');
    var isEdit = document.getElementById('cat-form-method').value === 'PUT';
    var catId = document.getElementById('cat-id').value;
    
    var url = '{{ route("admin.categories.store") }}';
    if(isEdit) url = '{{ url("admin/categories") }}/' + catId;
    
    var formData = new FormData(form);
    
    btn.disabled = true;
    btn.innerHTML = 'Saving...';
    
    fetch(url, {
        method: 'POST', // Note: Laravel treats PUT via _method field, so actual method is POST
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" style="width:16px;height:16px"></i> Save';
        
        if(data.success) {
            var newCat = data.category;
            
            if(isEdit) {
                // Update local array
                for(var i=0; i<allSysCategories.length; i++){
                    if(allSysCategories[i].id == newCat.id) {
                        allSysCategories[i] = newCat; break;
                    }
                }
                // Update text in all dropdowns
                document.querySelectorAll('.inline-category-select option[value="'+newCat.id+'"]').forEach(opt => {
                    opt.textContent = newCat.name;
                });
            } else {
                // Add to local array
                allSysCategories.push(newCat);
                // Append to ALL category dropdowns
                document.querySelectorAll('.inline-category-select').forEach(sel => {
                    var opt = new Option(newCat.name, newCat.id);
                    sel.add(opt);
                });
                // Autoselect in the active one
                activeCategorySelect.value = newCat.id;
                toggleCategoryButtons(activeCategorySelect);
            }
            
            closeInlineCategoryModal();
            
            // Optional: Show toast
            var alertDiv = document.createElement('div');
            alertDiv.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 20px;background:#10b981;color:white;border-radius:8px;z-index:9999;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);animation:chipIn 0.3s ease;';
            alertDiv.textContent = data.message;
            document.body.appendChild(alertDiv);
            setTimeout(() => { alertDiv.remove(); }, 3000);
            
        } else {
            alert(data.message || 'Validation error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="save" style="width:16px;height:16px"></i> Save';
        alert('Server Error. Check console.');
        console.error(err);
    });
});

function deleteInlineCategory(btn) {
    activeCategorySelect = btn.parentElement.querySelector('select');
    var catId = activeCategorySelect.value;
    if(!catId) return;
    
    if(!confirm("Are you sure you want to delete this Category? Warning: Products linked to this will lose their category association!")) {
        return;
    }
    
    var originalBtnHTML = btn.innerHTML;
    btn.innerHTML = '...';
    btn.disabled = true;
    
    fetch('{{ url("admin/categories") }}/' + catId, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalBtnHTML;
        
        if(data.success) {
            // Remove from local tracking array
            allSysCategories = allSysCategories.filter(c => c.id != catId);
            
            // Remove option from ALL select dropdowns!
            document.querySelectorAll('.inline-category-select').forEach(sel => {
                var opt = sel.querySelector('option[value="'+catId+'"]');
                if(opt) {
                    // if it was selected, reset
                    if(sel.value == catId) {
                        sel.value = '';
                        toggleCategoryButtons(sel);
                    }
                    opt.remove();
                }
            });
            
            var alertDiv = document.createElement('div');
            alertDiv.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 20px;background:#ef4444;color:white;border-radius:8px;z-index:9999;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);animation:chipIn 0.3s ease;';
            alertDiv.textContent = "Category deleted successfully!";
            document.body.appendChild(alertDiv);
            setTimeout(() => { alertDiv.remove(); }, 3000);
        } else {
            alert(data.message || 'Could not delete category.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalBtnHTML;
        alert('Server Error during deletion.');
        console.error(err);
    });
}

var comboColDefs = @json(isset($comboCols) ? $comboCols->values() : collect());
var variationFieldDefs = @json(isset($customColumns) ? $customColumns->where('is_variation_field', true)->where('is_active', true)->values() : collect());
var comboSelections = {}; // { comboId: [val1, val2..] }
var existingVariationData = {}; // { "black|large": { price: 100, discount: 10, custom_fields: {...} } }
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
            var hiddenComboInputs = Array.from(row.querySelectorAll('input[name*="[combination]"]'));
            if (hiddenComboInputs.length === 0) return;

            var rowComboVals = hiddenComboInputs.map(function(inp) { return inp.value; });
            var key = rowComboVals.map(function(v) { return v.toLowerCase().replace(/\s+/g,'-'); }).join('|');

            var saved = { custom_fields: {} };

            // Save dynamic variation field values
            if (variationFieldDefs.length > 0) {
                variationFieldDefs.forEach(function(vf) {
                    var inp = row.querySelector('input[name$="[custom_fields][' + vf.slug + ']"]');
                    if (inp) saved.custom_fields[vf.slug] = inp.value;
                });
            }

            // Save legacy price/discount if present
            var priceInput = row.querySelector('input[name$="[price]"]');
            var discountInput = row.querySelector('input[name$="[discount]"]');
            if (priceInput) saved.price = priceInput.value;
            if (discountInput) saved.discount = discountInput.value;

            existingVariationData[key] = saved;
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

    // Determine which fields to show in variation table
    var useCustomFields = variationFieldDefs.length > 0;

    // Generate cartesian product
    var combos = cartesian(activeCombo.map(function(c) { return c.vals; }));

    // Build header
    var hHtml = '';
    activeCombo.forEach(function(c) { hHtml += '<th>' + c.name + '</th>'; });

    if (useCustomFields) {
        // Dynamic headers from variation field definitions
        variationFieldDefs.forEach(function(vf) {
            hHtml += '<th style="min-width:120px">' + vf.name + '</th>';
        });
    } else {
        // Fallback to default Price + Discount
        hHtml += '<th style="width:160px">Price (₹)</th>';
        hHtml += '<th style="width:140px">Discount (%)</th>';
    }
    header.innerHTML = hHtml;

    // Build rows
    var bHtml = '';
    combos.forEach(function(combo, idx) {
        var comboArr = Array.isArray(combo) ? combo : [combo];
        var key = comboArr.map(function(v) { return v.toLowerCase().replace(/\s+/g,'-'); }).join('|');
        var ex = existingVariationData[key] || { price: '', discount: '', custom_fields: {} };

        bHtml += '<tr>';
        // Combo label cells
        comboArr.forEach(function(val, ci) {
            var color = dotColors[ci % dotColors.length];
            bHtml += '<td><span class="combo-label"><span class="combo-dot" style="background:' + color + '"></span>' + val + '</span></td>';
        });

        if (useCustomFields) {
            // Dynamic input cells for each variation field
            variationFieldDefs.forEach(function(vf) {
                var existVal = (ex.custom_fields && ex.custom_fields[vf.slug]) || '';
                var inputType = (vf.type === 'number') ? 'number' : 'text';
                var placeholder = (vf.type === 'number') ? '0' : '';
                var step = (vf.type === 'number') ? ' step="0.01" min="0"' : '';
                bHtml += '<td><input type="' + inputType + '" name="variations[' + idx + '][custom_fields][' + vf.slug + ']" placeholder="' + placeholder + '"' + step + ' value="' + existVal + '" style="width:100%"></td>';
            });
        } else {
            // Fallback: hardcoded Price + Discount
            bHtml += '<td><input type="number" name="variations[' + idx + '][price]" placeholder="0.00" step="0.01" min="0" value="' + (ex.price || '') + '"></td>';
            bHtml += '<td><input type="number" name="variations[' + idx + '][discount]" placeholder="0" step="0.01" min="0" max="100" value="' + (ex.discount || '') + '"></td>';
        }

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
                discount: v.discount || '',
                custom_fields: v.custom_fields || {}
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

    // Trigger category button visibility for pre-selected values
    document.querySelectorAll('.inline-category-select').forEach(function(sel) {
        toggleCategoryButtons(sel);
    });
});

function closeProductModal(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    document.getElementById('product-modal').style.display = 'none';
    document.getElementById('product-form').reset();
}

document.getElementById('product-modal').addEventListener('click', function (e) { if (e.target === this) closeProductModal(e); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeProductModal(e); });

// ═══════════════════════════════════════════════════════
// EXCEL IMPORT MODAL
// ═══════════════════════════════════════════════════════
var importTempFile = null;
var importSelectedFile = null;

function openImportModal() {
    resetImportModal();
    document.getElementById('import-modal').style.display = 'flex';
}
function closeImportModal() {
    document.getElementById('import-modal').style.display = 'none';
    importTempFile = null;
    importSelectedFile = null;
}
document.getElementById('import-modal').addEventListener('click', function(e) { if (e.target === this) closeImportModal(); });

function resetImportModal() {
    importTempFile = null;
    importSelectedFile = null;
    document.getElementById('import-file-input').value = '';
    document.getElementById('import-file-info').style.display = 'none';
    document.getElementById('import-step-upload').style.display = 'block';
    document.getElementById('import-step-results').style.display = 'none';
    document.getElementById('import-step-processing').style.display = 'none';
    document.getElementById('import-step-done').style.display = 'none';
    var btn = document.getElementById('import-validate-btn');
    btn.disabled = true;
    btn.style.opacity = '0.5';
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        setImportFile(input.files[0]);
    }
}
function handleFileDrop(e) {
    e.preventDefault();
    var dz = document.getElementById('import-dropzone');
    dz.style.borderColor = '#cbd5e1';
    dz.style.background = '#fafbff';
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        var file = e.dataTransfer.files[0];
        if (file.name.match(/\.(xlsx|xls)$/i)) {
            setImportFile(file);
            // Also set the file input for form submission
            var dt = new DataTransfer();
            dt.items.add(file);
            document.getElementById('import-file-input').files = dt.files;
        } else {
            showImportToast('Please select an Excel file (.xlsx or .xls)', 'error');
        }
    }
}
function setImportFile(file) {
    importSelectedFile = file;
    document.getElementById('import-file-name').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
    document.getElementById('import-file-info').style.display = 'flex';
    var btn = document.getElementById('import-validate-btn');
    btn.disabled = false;
    btn.style.opacity = '1';
}
function clearImportFile() {
    importSelectedFile = null;
    document.getElementById('import-file-input').value = '';
    document.getElementById('import-file-info').style.display = 'none';
    var btn = document.getElementById('import-validate-btn');
    btn.disabled = true;
    btn.style.opacity = '0.5';
}

function validateImport() {
    if (!importSelectedFile) return;
    var btn = document.getElementById('import-validate-btn');
    btn.disabled = true;
    btn.innerHTML = '<div style="width:14px;height:14px;border:2px solid rgba(255,255,255,0.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite"></div> Validating...';

    var formData = new FormData();
    formData.append('file', importSelectedFile);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.products.import-validate") }}', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
    .then(function(resp) {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg> Validate & Preview';

        if (!resp.ok) {
            showImportToast(resp.data.error || resp.data.message || 'Validation failed', 'error');
            return;
        }

        var data = resp.data;
        importTempFile = data.temp_file;
        showValidationResults(data);
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg> Validate & Preview';
        showImportToast('Network error. Please try again.', 'error');
        console.error(err);
    });
}

function showValidationResults(data) {
    document.getElementById('import-step-upload').style.display = 'none';
    document.getElementById('import-step-results').style.display = 'block';

    // Summary counts
    document.getElementById('import-valid-count').textContent = data.valid_count;
    document.getElementById('import-cat-error-count').textContent = data.category_error_count;
    document.getElementById('import-hard-error-count').textContent = data.hard_error_count;

    // Category errors section
    var catSection = document.getElementById('import-cat-section');
    if (data.missing_categories && data.missing_categories.length > 0) {
        catSection.style.display = 'block';
        var catList = document.getElementById('import-cat-list');
        catList.innerHTML = '';
        data.missing_categories.forEach(function(cat) {
            catList.innerHTML += '<span style="display:inline-block;padding:4px 12px;background:white;border:1px solid #fde68a;border-radius:6px;font-size:12px;font-weight:500;color:#92400e">' + escHtml(cat) + '</span>';
        });
    } else {
        catSection.style.display = 'none';
    }

    // Hard errors section
    var hardSection = document.getElementById('import-hard-section');
    if (data.hard_errors && data.hard_errors.length > 0) {
        hardSection.style.display = 'block';
        var hardList = document.getElementById('import-hard-list');
        hardList.innerHTML = '';
        data.hard_errors.forEach(function(err) {
            var errMsgs = err.errors.map(function(e) { return escHtml(e); }).join(', ');
            hardList.innerHTML += '<div style="padding:6px 0;border-bottom:1px solid #fecaca;color:#991b1b"><strong>Row ' + err.row + ':</strong> ' + errMsgs + '</div>';
        });
    } else {
        hardSection.style.display = 'none';
    }

    // Unmapped headers
    var unmappedSection = document.getElementById('import-unmapped-section');
    if (data.unmapped_headers && data.unmapped_headers.length > 0) {
        unmappedSection.style.display = 'block';
        document.getElementById('import-unmapped-list').textContent = data.unmapped_headers.join(', ');
    } else {
        unmappedSection.style.display = 'none';
    }

    // Direct import button (when no category errors)
    var directBtn = document.getElementById('import-direct-btn');
    if (data.category_error_count === 0 && data.valid_count > 0) {
        directBtn.style.display = 'inline-flex';
        directBtn.innerHTML = '🚀 Import ' + data.valid_count + ' Product' + (data.valid_count > 1 ? 's' : '');
    } else {
        directBtn.style.display = 'none';
    }
}

function processImport(categoryAction) {
    if (!importTempFile) { showImportToast('No file to process. Please re-upload.', 'error'); return; }

    // Show processing step
    document.getElementById('import-step-results').style.display = 'none';
    document.getElementById('import-step-processing').style.display = 'block';

    var formData = new FormData();
    formData.append('temp_file', importTempFile);
    formData.append('category_action', categoryAction);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("admin.products.import-process") }}', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
    .then(function(resp) {
        document.getElementById('import-step-processing').style.display = 'none';

        if (!resp.ok) {
            document.getElementById('import-step-results').style.display = 'block';
            showImportToast(resp.data.error || 'Import failed', 'error');
            return;
        }

        showFinalResults(resp.data);
    })
    .catch(function(err) {
        document.getElementById('import-step-processing').style.display = 'none';
        document.getElementById('import-step-results').style.display = 'block';
        showImportToast('Network error during import.', 'error');
        console.error(err);
    });
}

function showFinalResults(data) {
    document.getElementById('import-step-done').style.display = 'block';

    document.getElementById('import-done-created').textContent = data.created;
    document.getElementById('import-done-updated').textContent = data.updated;
    document.getElementById('import-done-skipped').textContent = data.skipped;

    // Title
    var total = data.created + data.updated;
    if (total === 0) {
        document.getElementById('import-done-title').textContent = 'No Products Imported';
    } else {
        document.getElementById('import-done-title').textContent = total + ' Product' + (total > 1 ? 's' : '') + ' Imported Successfully!';
    }

    // Errors
    if (data.errors && data.errors.length > 0) {
        document.getElementById('import-done-errors').style.display = 'block';
        var list = document.getElementById('import-done-error-list');
        list.innerHTML = '';
        data.errors.forEach(function(err) {
            list.innerHTML += '<div style="padding:4px 0;color:#991b1b"><strong>Row ' + err.row + ':</strong> ' + err.errors.map(function(e) { return escHtml(e); }).join(', ') + '</div>';
        });
    } else {
        document.getElementById('import-done-errors').style.display = 'none';
    }

    // Created categories
    if (data.created_categories && data.created_categories.length > 0) {
        document.getElementById('import-done-cats').style.display = 'block';
        document.getElementById('import-done-cat-list').textContent = data.created_categories.join(', ');
    } else {
        document.getElementById('import-done-cats').style.display = 'none';
    }
}

function showImportToast(msg, type) {
    var bg = type === 'error' ? '#ef4444' : '#10b981';
    var div = document.createElement('div');
    div.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 20px;background:' + bg + ';color:white;border-radius:8px;z-index:9999;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);font-size:13px;font-weight:500;max-width:400px;animation:chipIn 0.3s ease';
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(function() { div.remove(); }, 4000);
}

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// ═══════════ BULK SELECT & DELETE ═══════════
function toggleAllProducts(masterCheckbox) {
    var checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = masterCheckbox.checked; });
    updateBulkBar();
}

function selectAllProducts() {
    var checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(function(cb) { cb.checked = true; });
    var master = document.getElementById('select-all-products');
    if (master) master.checked = true;
    updateBulkBar();
}

function updateBulkBar() {
    var checked = document.querySelectorAll('.product-checkbox:checked');
    var bar = document.getElementById('bulk-action-bar');
    var countEl = document.getElementById('bulk-count');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        countEl.textContent = checked.length + ' product' + (checked.length > 1 ? 's' : '') + ' selected';
    } else {
        bar.style.display = 'none';
    }
    // Update master checkbox state
    var all = document.querySelectorAll('.product-checkbox');
    var master = document.getElementById('select-all-products');
    if (master) master.checked = all.length > 0 && checked.length === all.length;
}

function bulkDeleteProducts() {
    var checked = document.querySelectorAll('.product-checkbox:checked');
    if (checked.length === 0) return;
    if (!confirm('Are you sure you want to delete ' + checked.length + ' product(s)? This action cannot be undone.')) return;

    var ids = Array.from(checked).map(function(cb) { return parseInt(cb.value); });

    fetch('{{ route("admin.products.bulk-destroy") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showImportToast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 800);
        } else {
            showImportToast(data.message || 'Delete failed', 'error');
        }
    })
    .catch(function() {
        showImportToast('Network error. Try again.', 'error');
    });
}
// ═══ AJAX Product Search ═══
var _searchTimer = null;

function debounceSearch() {
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(searchProducts, 400);
}

function searchProducts() {
    var catEl = document.getElementById('search-category');
    var uniqueEl = document.getElementById('search-unique');
    var catVal = catEl ? catEl.value : '';
    var uniqueVal = uniqueEl ? uniqueEl.value.trim() : '';

    var params = new URLSearchParams();
    if (catVal) params.set('category_search', catVal);
    if (uniqueVal) params.set('unique_search', uniqueVal);

    // Update URL without reload
    var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newUrl);

    // Show loading state on tbody
    var tbody = document.querySelector('#products-table tbody');
    if (tbody) tbody.style.opacity = '0.5';

    fetch('{{ route("admin.products.index") }}?' + params.toString(), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(function(r) { return r.text(); })
    .then(function(html) {
        if (tbody) {
            tbody.innerHTML = html;
            tbody.style.opacity = '1';
            // Re-init lucide icons for new content
            if (typeof lucide !== 'undefined') lucide.createIcons();
            // Re-bind edit buttons
            if (typeof bindEditButtons === 'function') bindEditButtons();
        }
    })
    .catch(function() {
        if (tbody) tbody.style.opacity = '1';
    });
}

function resetSearch() {
    var catEl = document.getElementById('search-category');
    var uniqueEl = document.getElementById('search-unique');
    if (catEl) catEl.value = '';
    if (uniqueEl) uniqueEl.value = '';
    searchProducts();
}
</script>
@endpush