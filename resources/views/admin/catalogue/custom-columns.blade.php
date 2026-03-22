@extends('admin.layouts.app')

@section('title', 'Catalogue Columns')
@section('breadcrumb', 'Catalogue Columns')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Dynamic Product Form Manager</h1>
                <p class="page-description">Control exactly what fields appear when creating or editing a product. System fields are locked to preserve CRM functionality, but can be hidden.</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="openAddModal()"><i data-lucide="plus" style="width:16px;height:16px"></i> Add Custom Field</button>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    <div class="card">
        <div class="card-content" style="padding:0">
            <table class="data-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);width:30px">⠿</th>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Name & Slug</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Type</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">System Links</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Flags</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Visibility</th>
                        <th style="padding:12px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="columns-tbody">
                    @forelse($columns as $column)
                        <tr data-id="{{ $column->id }}" style="border-bottom:1px solid var(--border); opacity: {{ $column->is_active ? '1' : '0.6' }}">
                            <td style="padding:12px 16px;cursor:grab;color:var(--text-muted)">⠿</td>
                            <td style="padding:12px 16px;">
                                <div style="font-weight:600;display:flex;align-items:center;gap:6px">
                                    {{ $column->name }}
                                    @if($column->is_system)
                                        <span class="badge badge-primary" style="font-size:10px;padding:2px 6px">System Lock</span>
                                    @endif
                                </div>
                                <div style="color:var(--text-muted);font-family:monospace;font-size:11px;margin-top:2px">
                                    [slug: {{ $column->slug }}]
                                </div>
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                <span class="badge badge-outline">{{ $column->type }}</span>
                                @if(in_array($column->type, ['select', 'multiselect']) && $column->options)
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        {{ implode(', ', $column->options) }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:center;">
                                @if($column->connected_modules && count($column->connected_modules) > 0)
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;justify-content:center;max-width:140px">
                                        @foreach($column->connected_modules as $mod)
                                            <span style="font-size:10px;background:#f1f5f9;border:1px solid #e2e8f0;padding:2px 6px;border-radius:4px;color:#475569">{{ $mod }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                @if($column->is_combo)
                                    <span class="badge badge-warning" style="margin:2px">Combo</span>
                                @endif
                                @if($column->is_required)
                                    <span class="badge badge-info" style="margin:2px">Required</span>
                                @endif
                                @if($column->is_unique)
                                    <span class="badge badge-success" style="margin:2px">Unique</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                <div style="display:flex;align-items:center;gap:12px;justify-content:center">
                                    <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer">
                                        <input type="checkbox" onchange="toggleActive({{ $column->id }}, this.checked)" {{ $column->is_active ? 'checked' : '' }}>
                                        Active
                                    </label>
                                </div>
                            </td>
                            <td style="padding:12px 16px;text-align:right">
                                <div style="display:flex;gap:4px;justify-content:flex-end">
                                    <button class="btn btn-outline btn-sm" onclick='editColumn({{ json_encode($column) }})' style="padding:4px 10px;font-size:12px">
                                        <i data-lucide="edit" style="width:13px;height:13px"></i>
                                    </button>
                                    @if(!$column->is_system)
                                        <button class="btn btn-ghost btn-sm" onclick="deleteColumn({{ $column->id }})" style="color:var(--destructive);padding:4px 10px;font-size:12px">
                                            <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="7" style="text-align:center;padding:40px">
                                <i data-lucide="columns" style="width:48px;height:48px;color:#ccc;margin:0 auto 16px;display:block"></i>
                                <p class="text-muted">No custom columns defined. Click "Add Custom Field" to create one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Column Modal -->
    <div id="column-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:600px;max-height:92vh;overflow-y:auto">
            <div style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="modal-title" style="margin:0">Add Custom Field</h3>
                <button onclick="closeModal()" style="background:none;border:none;font-size:24px;cursor:pointer">&times;</button>
            </div>
            <form id="column-form" onsubmit="saveColumn(event)">
                <input type="hidden" id="col-id" value="">
                <input type="hidden" id="col-is-system" value="0">
                <div style="padding:20px">
                    <div id="system-warning" style="display:none;padding:12px;background:#e0f2fe;border:1px solid #bae6fd;border-radius:4px;margin-bottom:16px;font-size:13px;color:#0369a1">
                        <i data-lucide="lock" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                        This is a <strong>System Field</strong>. You can rename its label, but its type, slug, and core flags cannot be altered because core CRM modules depend on it.
                    </div>
                    
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Field Label (Name) *</label>
                        <input type="text" id="col-name" required placeholder="e.g., Material, Brand, Warranty" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    
                    <div class="system-locked-group">
                        <div style="margin-bottom:16px">
                            <label style="display:block;margin-bottom:4px;font-weight:500">Input Type *</label>
                            <select id="col-type" onchange="toggleOptions()" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                                <option value="text">Short Text</option>
                                <option value="textarea">Long Text (Description)</option>
                                <option value="number">Number</option>
                                <option value="select">Select (Dropdown)</option>
                                <option value="multiselect">Multi-Select</option>
                                <option value="boolean">Yes/No</option>
                            </select>
                        </div>
                        <div id="options-group" style="margin-bottom:16px;display:none">
                            <label style="display:block;margin-bottom:4px;font-weight:500">Dropdown Options (comma separated) *</label>
                            <input type="text" id="col-options" placeholder="e.g., Samsung, Apple, Sony" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <small style="color:var(--text-muted)">Values that will appear in the UI list</small>
                        </div>
                        <div style="display:flex;gap:20px;margin-bottom:16px">
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                <input type="checkbox" id="col-required"> Required Field
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                <input type="checkbox" id="col-unique"> Unique Identifier
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                                <input type="checkbox" id="col-combo"> Combo (Variation Matrix)
                            </label>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:20px;margin-bottom:16px;padding-top:16px;border-top:1px dashed #ddd">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500">
                            <input type="checkbox" id="col-show-list"> Show Column in Product Table (Index page)
                        </label>
                    </div>

                    <div id="combo-note" style="display:none;padding:8px 12px;background:#fff3cd;border:1px solid #ffc107;border-radius:4px;font-size:13px">
                        ⚠️ Combo columns create multidimensional product variations. Max 5 allowed.
                    </div>
                </div>
                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openAddModal() {
        document.getElementById('modal-title').textContent = 'Add Custom Field';
        document.getElementById('col-id').value = '';
        document.getElementById('col-is-system').value = '0';
        document.getElementById('col-name').value = '';
        document.getElementById('col-type').value = 'text';
        document.getElementById('col-options').value = '';
        document.getElementById('col-required').checked = false;
        document.getElementById('col-unique').checked = false;
        document.getElementById('col-combo').checked = false;
        document.getElementById('col-show-list').checked = false;
        
        document.getElementById('system-warning').style.display = 'none';
        document.querySelectorAll('.system-locked-group input:not([type="checkbox"]), .system-locked-group select').forEach(el => el.disabled = false);
        document.querySelectorAll('.system-locked-group input[type="checkbox"]').forEach(el => el.disabled = false);
        
        toggleOptions();
        document.getElementById('column-modal').style.display = 'flex';
    }

    function editColumn(col) {
        document.getElementById('modal-title').textContent = 'Edit Field';
        document.getElementById('col-id').value = col.id;
        document.getElementById('col-is-system').value = col.is_system ? '1' : '0';
        document.getElementById('col-name').value = col.name;
        document.getElementById('col-type').value = col.type;
        document.getElementById('col-options').value = col.options ? col.options.join(', ') : '';
        document.getElementById('col-required').checked = col.is_required;
        document.getElementById('col-unique').checked = col.is_unique;
        document.getElementById('col-combo').checked = col.is_combo;
        document.getElementById('col-show-list').checked = col.show_on_list;
        
        if (col.is_system) {
            document.getElementById('system-warning').style.display = 'block';
            document.querySelectorAll('.system-locked-group input:not([type="checkbox"]), .system-locked-group select').forEach(el => el.disabled = true);
            document.querySelectorAll('.system-locked-group input[type="checkbox"]').forEach(el => el.disabled = true);
        } else {
            document.getElementById('system-warning').style.display = 'none';
            document.querySelectorAll('.system-locked-group input:not([type="checkbox"]), .system-locked-group select').forEach(el => el.disabled = false);
            document.querySelectorAll('.system-locked-group input[type="checkbox"]').forEach(el => el.disabled = false);
        }

        toggleOptions();
        document.getElementById('column-modal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('column-modal').style.display = 'none';
    }

    function toggleOptions() {
        var type = document.getElementById('col-type').value;
        document.getElementById('options-group').style.display = (type === 'select' || type === 'multiselect') ? 'block' : 'none';
        document.getElementById('combo-note').style.display = document.getElementById('col-combo').checked && !document.getElementById('col-is-system').checked ? 'block' : 'none';
    }

    document.getElementById('col-combo').addEventListener('change', toggleOptions);

    function toggleActive(id, isActive) {
        fetch('{{ url("admin/catalogue-custom-columns") }}/' + id + '/toggle-active', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ is_active: isActive })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert('error', data.message);
                setTimeout(() => location.reload(), 1000);
            }
        });
    }

    function saveColumn(e) {
        e.preventDefault();
        var id = document.getElementById('col-id').value;
        var url = id ? '{{ url("admin/catalogue-columns") }}/' + id : '{{ route("admin.catalogue-columns.store") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('col-name').value,
                type: document.getElementById('col-type').value,
                options: document.getElementById('col-options').value,
                is_required: document.getElementById('col-required').checked ? 1 : 0,
                is_unique: document.getElementById('col-unique').checked ? 1 : 0,
                is_combo: document.getElementById('col-combo').checked ? 1 : 0,
                show_on_list: document.getElementById('col-show-list').checked ? 1 : 0,
                is_active: id ? undefined : 1
            })
        }).then(r => r.json()).then(data => {
            if (data.success) { showAlert('success', data.message); setTimeout(() => location.reload(), 500); }
            else { showAlert('error', data.message || 'Error saving column'); }
        }).catch(err => showAlert('error', 'Request failed'));
    }

    function deleteColumn(id) {
        if (!confirm('Delete this column? This will also remove all product values for this column.')) return;
        fetch('{{ url("admin/catalogue-columns") }}/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(r => r.json()).then(data => {
            if (data.success) { showAlert('success', data.message); setTimeout(() => location.reload(), 500); }
            else { showAlert('error', data.message); }
        });
    }

    function showAlert(type, msg) {
        var bg = type === 'success' ? '#d4edda' : '#f8d7da';
        var color = type === 'success' ? '#155724' : '#721c24';
        document.getElementById('alert-container').innerHTML = '<div style="padding:12px 20px;background:' + bg + ';color:' + color + ';border-radius:4px;margin-bottom:20px">' + msg + '</div>';
    }

    document.getElementById('column-modal').addEventListener('click', function (e) { if (e.target === this) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
</script>
@endpush
