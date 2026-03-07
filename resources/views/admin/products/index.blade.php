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
                        <th data-col="name">Product Name</th>
                        <th data-col="sku">SKU</th>
                        <th data-col="category">Category</th>
                        <th data-col="mrp">MRP (₹)</th>
                        <th data-col="sale_price">Sale Price (₹)</th>
                        <th data-col="unit">Unit</th>
                        <th data-col="gst">GST %</th>
                        <th data-col="status">Status</th>
                        <th data-col="actions" style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td data-col="name">
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
                            <td data-col="sku"><span class="font-mono text-sm">{{ $product->sku }}</span></td>
                            <td data-col="category"><span
                                    class="badge badge-secondary">{{ $product->category->name ?? 'N/A' }}</span></td>
                            <td data-col="mrp" class="font-medium">₹{{ number_format($product->mrp / 100, 2) }}</td>
                            <td data-col="sale_price" class="font-medium">₹{{ number_format($product->sale_price / 100, 2) }}
                            </td>
                            <td data-col="unit">{{ $product->unit }}</td>
                            <td data-col="gst">{{ $product->gst_percent ?? 0 }}%</td>
                            <td data-col="status">
                                <span
                                    class="badge badge-{{ ($product->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($product->status ?? 'active') }}
                                </span>
                            </td>
                            <td data-col="actions">
                                <div style="display:flex;gap:8px">
                                    @if(can('products.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm edit-product-btn" title="Edit"
                                            data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                            data-sku="{{ $product->sku }}" data-category-id="{{ $product->category_id }}"
                                            data-description="{{ $product->description ?? '' }}" data-unit="{{ $product->unit }}"
                                            data-mrp="{{ $product->mrp / 100 }}" data-sale-price="{{ $product->sale_price / 100 }}"
                                            data-gst="{{ $product->gst_percent ?? 0 }}"
                                            data-purchase-enabled="{{ $product->is_purchase_enabled ? '1' : '0' }}">
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
                            <td colspan="9" class="text-center py-8 text-muted">No products found. Click "Add Product" to create
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
                <div style="padding:20px">
                    <div data-field="name" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Product Name *</label>
                        <input type="text" class="form-input" name="name" id="prod-name" required
                            placeholder="Enter product name"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div data-field="sku" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">SKU *</label>
                        <input type="text" class="form-input" name="sku" id="prod-sku" required placeholder="SKU-001"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div data-field="category" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Category *</label>
                        <select class="form-select" name="category_id" id="prod-category" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div data-field="mrp" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">MRP (₹) *</label>
                        <input type="number" class="form-input" name="mrp" id="prod-mrp" required placeholder="0.00"
                            step="0.01" min="0" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div data-field="sale_price" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Sale Price (₹) *</label>
                        <input type="number" class="form-input" name="sale_price" id="prod-sale-price" required
                            placeholder="0.00" step="0.01" min="0"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div data-field="unit" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Unit *</label>
                        <select class="form-select" name="unit" id="prod-unit" required
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="ltr">Litre (ltr)</option>
                            <option value="mtr">Meter (mtr)</option>
                            <option value="sqft">Sq. Feet (sqft)</option>
                            <option value="box">Box</option>
                            <option value="set">Set</option>
                            <option value="nos">Numbers (nos)</option>
                        </select>
                    </div>
                    <div data-field="gst" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">GST %</label>
                        <select class="form-select" name="gst_percent" id="prod-gst"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="0">0%</option>
                            <option value="5">5%</option>
                            <option value="12">12%</option>
                            <option value="18" selected>18%</option>
                            <option value="28">28%</option>
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Description</label>
                        <textarea class="form-textarea" name="description" id="prod-description" rows="3"
                            placeholder="Enter product description (optional)"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></textarea>
                    </div>
                    <div style="margin-bottom:16px;display:flex;align-items:center;gap:8px">
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
        // Apply column visibility from database Settings
        (function () {
            var moduleSettings = @json($columnVisibility ?? []);
            Object.keys(moduleSettings).forEach(function (col) {
                if (moduleSettings[col] === false) {
                    document.querySelectorAll('[data-col="' + col + '"]').forEach(function (el) {
                        el.style.display = 'none';
                    });
                    document.querySelectorAll('[data-field="' + col + '"]').forEach(function (el) {
                        el.style.display = 'none';
                        var inputs = el.querySelectorAll('[required]');
                        inputs.forEach(function (inp) { inp.removeAttribute('required'); });
                    });
                }
            });
        })();

        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add New Product';
            document.getElementById('product-form').action = '{{ route("admin.products.store") }}';
            document.getElementById('form-method').value = '';
            document.getElementById('product-form').reset();
            document.getElementById('prod-gst').value = '18';
            document.getElementById('prod-purchase-enabled').checked = false;
            document.getElementById('product-modal').style.display = 'flex';
        }

        // Use event delegation for edit buttons — safe from special characters
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.edit-product-btn');
            if (!btn) return;

            document.getElementById('modal-title').textContent = 'Edit Product';
            document.getElementById('product-form').action = '{{ url("admin/products") }}/' + btn.dataset.id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('prod-name').value = btn.dataset.name;
            document.getElementById('prod-sku').value = btn.dataset.sku;
            document.getElementById('prod-category').value = btn.dataset.categoryId || '';
            document.getElementById('prod-description').value = btn.dataset.description;
            document.getElementById('prod-unit').value = btn.dataset.unit;
            document.getElementById('prod-mrp').value = btn.dataset.mrp;
            document.getElementById('prod-sale-price').value = btn.dataset.salePrice;
            document.getElementById('prod-gst').value = btn.dataset.gst;
            document.getElementById('prod-purchase-enabled').checked = btn.dataset.purchaseEnabled === '1';
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