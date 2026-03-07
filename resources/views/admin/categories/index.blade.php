@extends('admin.layouts.app')

@section('title', 'Categories')
@section('breadcrumb', 'Categories')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Categories</h1>
                <p class="page-description">Manage product categories</p>
            </div>
            <div class="page-actions">
                @if(can('categories.write'))
                    <button class="btn btn-primary" onclick="openAddModal()"><i data-lucide="plus"
                            style="width:16px;height:16px"></i> Add Category</button>
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

    <div class="grid grid-cols-3 gap-4">
        @forelse($categories as $category)
            <div class="card">
                <div class="card-content">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="stats-card-icon"><i data-lucide="folder" style="width:20px;height:20px"></i></div>
                        <div>
                            <h3 class="font-semibold" data-col="name">{{ $category->name }}</h3>
                            <p class="text-xs text-muted" data-col="products_count">{{ $category->products_count }} products</p>
                        </div>
                    </div>
                    <p class="text-sm text-muted" data-col="description">{{ $category->description ?? 'No description' }}</p>
                    <div style="margin-top:8px" data-col="status">
                        <span class="badge {{ $category->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                            {{ ucfirst($category->status ?? 'active') }}
                        </span>
                    </div>
                    <div class="flex gap-2 mt-4" data-col="actions">
                        @if(can('categories.write'))
                            <button class="btn btn-outline btn-sm"
                                onclick="editCategory({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', '{{ $category->status ?? 'active' }}', {{ $category->sort_order ?? 0 }}, {{ $category->parent_category_id ?? 'null' }})">
                                <i data-lucide="edit" style="width:14px;height:14px"></i> Edit
                            </button>
                        @endif
                        @if(can('categories.delete'))
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST"
                                style="display:inline;margin:0"
                                onsubmit="return confirm('Are you sure you want to delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--destructive)">
                                    <i data-lucide="trash-2" style="width:14px;height:14px"></i> Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="grid-column: span 3">
                <div class="card-content" style="text-align:center;padding:40px">
                    <i data-lucide="folder-plus" style="width:48px;height:48px;color:#ccc;margin:0 auto 16px;display:block"></i>
                    <p class="text-muted">No categories found. Click "Add Category" to create one.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="category-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto">
            <div
                style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="modal-title" style="margin:0">Add New Category</h3>
                <button onclick="closeModal()"
                    style="background:none;border:none;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px">&times;</button>
            </div>
            <form id="category-form" method="POST" action="{{ route('admin.categories.store') }}">
                @csrf
                <input type="hidden" id="form-method" name="_method" value="">
                <div style="padding:20px">
                    <div data-field="name" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Category Name *</label>
                        <input type="text" class="form-input" name="name" id="cat-name" required
                            placeholder="Enter category name"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div data-field="description" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Description</label>
                        <textarea class="form-textarea" name="description" id="cat-description" rows="3"
                            placeholder="Enter description (optional)"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px"></textarea>
                    </div>
                    <div data-field="status" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Status *</label>
                        <select class="form-select" name="status" id="cat-status"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Sort Order</label>
                        <input type="number" class="form-input" name="sort_order" id="cat-sort-order" value="0" min="0"
                            style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    @if($categories->count() > 0)
                        <div style="margin-bottom:16px">
                            <label style="display:block;margin-bottom:4px;font-weight:500">Parent Category</label>
                            <select class="form-select" name="parent_category_id" id="cat-parent"
                                style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                                <option value="">None (Root Category)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeModal()"
                        style="padding:8px 16px;border:1px solid #ddd;background:white;border-radius:4px;cursor:pointer">Cancel</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding:8px 16px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer">
                        Save Category
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
            var catSettings = @json($columnVisibility ?? []);
            Object.keys(catSettings).forEach(function (col) {
                if (catSettings[col] === false) {
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
            document.getElementById('modal-title').textContent = 'Add New Category';
            document.getElementById('category-form').action = '{{ route("admin.categories.store") }}';
            document.getElementById('form-method').value = '';
            document.getElementById('category-form').reset();
            document.getElementById('category-modal').style.display = 'flex';
        }

        function editCategory(id, name, description, status, sortOrder, parentId) {
            document.getElementById('modal-title').textContent = 'Edit Category';
            document.getElementById('category-form').action = '{{ url("admin/categories") }}/' + id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('cat-name').value = name;
            document.getElementById('cat-description').value = description;
            document.getElementById('cat-status').value = status || 'active';
            document.getElementById('cat-sort-order').value = sortOrder || 0;
            var parentSelect = document.getElementById('cat-parent');
            if (parentSelect) {
                parentSelect.value = parentId || '';
            }
            document.getElementById('category-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('category-modal').style.display = 'none';
            document.getElementById('category-form').reset();
        }

        // Close modal on clicking outside
        document.getElementById('category-modal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
@endpush