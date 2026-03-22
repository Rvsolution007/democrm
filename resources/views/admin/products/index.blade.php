@extends('admin.layouts.app')

@section('title', 'Products')
@section('breadcrumb', 'Products')

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
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="table-container">
        <div class="table-wrapper">
            <table class="table" id="products-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        @foreach($customColumns->where('show_on_list', true)->whereNotIn('slug', ['name', 'description']) as $col)
                            <th>{{ $col->name }}</th>
                        @endforeach
                        <th>Status</th>
                        <th style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div>
                                    <p class="font-medium">
                                        {{ $product->name }}
                                        @if($product->is_purchase_enabled)
                                            <span class="badge badge-secondary" style="font-size:10px;margin-left:5px">Purchase
                                                Enabled</span>
                                        @endif
                                    </p>
                                    @if($product->description)
                                        <p class="text-xs text-muted">{{ Str::limit($product->description, 50) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td><span class="font-mono text-sm">{{ $product->sku }}</span></td>
                            <td><span class="badge badge-secondary">{{ $product->category->name ?? 'N/A' }}</span></td>
                            
                            @foreach($customColumns->where('show_on_list', true)->whereNotIn('slug', ['name', 'description']) as $col)
                                <td>
                                    @if($col->is_system)
                                        @php
                                            $val = $product->{$col->slug};
                                            if(in_array($col->slug, ['mrp', 'sale_price', 'cost_price'])) {
                                                $val = '₹' . number_format($val / 100, 2);
                                            } elseif($col->slug === 'gst_percent') {
                                                $val = $val . '%';
                                            }
                                        @endphp
                                        <span class="{{ in_array($col->slug, ['mrp', 'sale_price']) ? 'font-medium' : '' }}">{{ $val }}</span>
                                    @else
                                        @php
                                            $customVal = $product->customValues->where('column_id', $col->id)->first();
                                            $valText = $customVal ? $customVal->value : '-';
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
                                                'is_purchase_enabled' => $product->is_purchase_enabled ? 1 : 0
                                            ]) }}"
                                            data-custom-values="{{ json_encode($product->customValues->pluck('value', 'column_id')) }}"
                                        >
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                    @if(can('products.delete'))
                                        <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                            style="display:inline;margin:0"
                                            onsubmit="return confirm('Are you sure you want to delete this product?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-icon btn-sm"
                                                style="color:var(--destructive)" title="Delete">
                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-8 text-muted">No products found. Click "Add Product" to create
                                one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="product-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto">
            <div
                style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="modal-title" style="margin:0">Add New Product</h3>
                <button onclick="closeModal()"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px">&times;</button>
            </div>
            <form id="product-form" method="POST" action="{{ route('admin.products.store') }}">
                @csrf
                <input type="hidden" id="form-method" name="_method" value="">
                
                <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    
                    <!-- Permanent Top-Level Fields -->
                    <div style="grid-column: span 2;">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Category *</label>
                        <select class="form-select" name="category_id" id="prod-category_id" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
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

                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:500">SKU *</label>
                        <input type="text" class="form-input" name="sku" id="prod-sku" required placeholder="SKU-001"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                </div>

                <div style="padding:20px;padding-top:0;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div style="grid-column: span 2;">
                        <h4 style="margin:10px 0;padding-bottom:10px;border-bottom:1px solid #eee">Product Details</h4>
                    </div>
                    
                    <!-- Dynamic Product Details driven by CatalogueCustomColumns -->
                    @foreach($customColumns as $col)
                        @php
                            $inputName = $col->is_system ? $col->slug : "custom_data[{$col->id}]";
                            $inputId = $col->is_system ? "prod-{$col->slug}" : "custom-{$col->id}";
                            $requiredStr = $col->is_required ? 'required' : '';
                            $reqLabel = $col->is_required ? ' *' : '';
                            $span = $col->type === 'textarea' ? 'grid-column: span 2;' : '';
                        @endphp
                        
                        <div style="{{ $span }}">
                            <label style="display:block;margin-bottom:4px;font-weight:500">{{ $col->name }}{{ $reqLabel }}</label>
                            
                            @if($col->type === 'textarea')
                                <textarea class="form-textarea" name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }} rows="3"
                                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></textarea>
                            
                            @elseif($col->type === 'select' || $col->type === 'multiselect')
                                <select class="form-select" name="{{ $inputName }}{{ $col->type === 'multiselect' ? '[]' : '' }}" id="{{ $inputId }}" {{ $requiredStr }} {{ $col->type === 'multiselect' ? 'multiple' : '' }}
                                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                                    <option value="">{{ "Select {$col->name}" }}</option>
                                    @if($col->is_system && $col->slug === 'gst_percent')
                                        <!-- Special handling for GST to preset some Indian values if not provided via 'options' -->
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
                                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>

                            @else
                                <!-- Text and Number inputs -->
                                <input type="{{ $col->type === 'number' ? 'number' : 'text' }}" class="form-input" 
                                    name="{{ $inputName }}" id="{{ $inputId }}" {{ $requiredStr }} 
                                    {{ $col->type === 'number' ? 'step=0.01 min=0' : '' }}
                                    placeholder="Enter {{ strtolower($col->name) }}"
                                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <div style="padding:20px;padding-top:0;">
                    <div style="margin-top:16px;display:flex;align-items:center;gap:8px">
                        <input type="checkbox" name="is_purchase_enabled" id="prod-purchase-enabled" value="1"
                            style="width:16px;height:16px;">
                        <label for="prod-purchase-enabled" style="font-weight:500;margin:0">Enable Auto-Purchase generation
                            for this Product/Service</label>
                    </div>
                </div>

                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeModal()"
                        style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding:8px 16px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add New Product';
            document.getElementById('product-form').action = '{{ route("admin.products.store") }}';
            document.getElementById('form-method').value = '';
            document.getElementById('product-form').reset();
            document.getElementById('prod-purchase-enabled').checked = false;
            document.getElementById('product-modal').style.display = 'flex';
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.edit-product-btn');
            if (!btn) return;

            var product = JSON.parse(btn.dataset.product);
            var customValues = JSON.parse(btn.dataset.customValues);

            document.getElementById('modal-title').textContent = 'Edit Product';
            document.getElementById('product-form').action = '{{ url("admin/products") }}/' + product.id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('product-form').reset();
            
            // Core Top-Level Fields
            document.getElementById('prod-sku').value = product.sku || '';
            document.getElementById('prod-category_id').value = product.category_id || '';
            document.getElementById('prod-purchase-enabled').checked = product.is_purchase_enabled == 1;

            // System Custom Columns (Mapped to product object)
            var sysCols = @json($customColumns->where('is_system', true)->pluck('slug'));
            sysCols.forEach(function(slug) {
                var el = document.getElementById('prod-' + slug);
                if (el) {
                    el.value = product[slug] !== null && product[slug] !== undefined ? product[slug] : '';
                }
            });

            // True Custom Columns (Mapped to CatalogueCustomValues)
            var nonSysCols = @json($customColumns->where('is_system', false)->pluck('id'));
            nonSysCols.forEach(function(id) {
                var el = document.getElementById('custom-' + id);
                if (el && customValues[id] !== undefined) {
                    var val = customValues[id];
                    
                    try {
                        // Check if multi-select which is stored as JSON string
                        var parsedVal = JSON.parse(val);
                        if(Array.isArray(parsedVal)) {
                            Array.from(el.options).forEach(function(opt) {
                                opt.selected = parsedVal.includes(opt.value);
                            });
                            return;
                        }
                    } catch(e) {}
                    
                    el.value = val;
                }
            });

            document.getElementById('product-modal').style.display = 'flex';
        });

        function closeModal() {
            document.getElementById('product-modal').style.display = 'none';
            document.getElementById('product-form').reset();
        }

        document.getElementById('product-modal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
@endpush