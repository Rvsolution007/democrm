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
                                @if($column->is_category)
                                    <span class="badge badge-info" style="margin:2px;background:#3b82f6;color:white;border-color:#3b82f6">📂 Category</span>
                                @endif
                                @if($column->show_in_ai)
                                    <span class="badge badge-primary" style="margin:2px;font-size:10px">🤖 AI</span>
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
    <style>
        /* Modal UI Enhancements */
        @keyframes fadeInScale {
            0% { opacity: 0; transform: scale(0.95) translateY(10px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .premium-modal-backdrop {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            z-index: 9999; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
        }
        .premium-modal-content {
            background: #ffffff; border-radius: 16px; width: 95%; max-width: 650px; max-height: 92vh;
            overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            transform: scale(0.95) translateY(10px); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex; flex-direction: column;
        }
        .premium-input {
            width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px;
            font-size: 15px; color: #0f172a; transition: all 0.2s; outline: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); background: #fff;
        }
        .premium-input:focus {
            border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        }
        .premium-checkbox-card {
            display: flex; align-items: flex-start; gap: 12px; padding: 16px;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
            cursor: pointer; transition: all 0.2s;
        }
        .premium-checkbox-card:hover {
            border-color: #cbd5e1; background: #f8fafc;
        }
        .btn-gradient {
            padding: 10px 24px; border: none; background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;
            font-size: 14px; box-shadow: 0 4px 6px -1px rgba(59,130,246,0.3);
        }
        .btn-gradient:hover {
            transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(59,130,246,0.4);
        }
        .badge-anim {
            transition: transform 0.2s;
        }
        .badge-anim:hover {
            transform: scale(1.05);
        }
    </style>

    <div id="column-modal" class="premium-modal-backdrop">
        <div id="modal-content-box" class="premium-modal-content">
            
            <div style="padding:24px 32px;border-bottom:1px solid rgba(241, 245, 249, 0.8);display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,0.9);backdrop-filter:blur(8px);border-radius:16px 16px 0 0;position:sticky;top:0;z-index:10;">
                <div>
                    <h3 id="modal-title" style="margin:0;font-size:20px;font-weight:700;color:#0f172a;letter-spacing:-0.02em">Add Custom Field</h3>
                    <p style="margin:4px 0 0 0;font-size:13px;color:#64748b;font-weight:400">Define how this property behaves across the system</p>
                </div>
                <button type="button" onclick="closeColumnModal()" class="btn-close-modal" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:50%;font-size:22px;cursor:pointer;color:#64748b;transition:all 0.2s cubic-bezier(0.4, 0, 0.2, 1);line-height:1;outline:none" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';this.style.transform='rotate(90deg) scale(1.1)';this.style.borderColor='#fca5a5'" onmouseout="this.style.background='#f8fafc';this.style.color='#64748b';this.style.transform='none';this.style.borderColor='#e2e8f0'">
                    &times;
                </button>
            </div>

            <form id="column-form" onsubmit="saveColumn(event)" style="display:flex;flex-direction:column;flex:1">
                <input type="hidden" id="col-id" value="">
                <input type="hidden" id="col-is-system" value="0">
                
                <div style="padding:32px;flex:1">
                    <div id="system-warning" style="display:none;padding:16px;background:linear-gradient(to right, #eff6ff, #e0f2fe);border:1px solid #bae6fd;border-left:4px solid #3b82f6;border-radius:8px;margin-bottom:24px;font-size:14px;color:#0369a1;box-shadow:0 1px 2px rgba(0,0,0,0.05)">
                        <div style="display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:4px;">
                            <i data-lucide="lock" style="width:16px;height:16px"></i> System Field Lock
                        </div>
                        You can rename its label, but its type, slug, and core flags cannot be altered because core CRM modules depend on it.
                    </div>
                    
                    <div style="margin-bottom:24px">
                        <label style="display:flex;align-items:center;gap:4px;margin-bottom:8px;font-weight:600;color:#334155;font-size:14px">Field Label (Name) <span style="color:#ef4444">*</span></label>
                        <input type="text" id="col-name" class="premium-input" required placeholder="e.g., Material, Brand, Warranty">
                    </div>
                    
                    <div class="system-locked-group" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:24px">
                        <div style="margin-bottom:20px">
                            <label style="display:flex;align-items:center;gap:4px;margin-bottom:8px;font-weight:600;color:#334155;font-size:14px">Input Type <span style="color:#ef4444">*</span></label>
                            <div style="position:relative">
                                <select id="col-type" class="premium-input" onchange="toggleOptions()" style="appearance:none;cursor:pointer;padding-right:40px">
                                    <option value="text">Short Text</option>
                                    <option value="textarea">Long Text (Description)</option>
                                    <option value="number">Number</option>
                                    <option value="select">Select (Dropdown)</option>
                                    <option value="multiselect">Multi-Select</option>
                                    <option value="boolean">Yes/No Switch</option>
                                </select>
                                <div style="position:absolute;right:16px;top:50%;transform:translateY(-50%);pointer-events:none;color:#64748b;display:flex">
                                    <i data-lucide="chevron-down" style="width:18px;height:18px"></i>
                                </div>
                            </div>
                        </div>

                        <div id="options-group" style="margin-bottom:20px;display:none;animation:fadeIn 0.3s ease">
                            <label style="display:flex;align-items:center;gap:4px;margin-bottom:8px;font-weight:600;color:#334155;font-size:14px">Dropdown Options <span style="color:#ef4444">*</span></label>
                            <input type="text" id="col-options" class="premium-input" placeholder="e.g., Samsung, Apple, Sony">
                            <p style="margin:6px 0 0;font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px">
                                <i data-lucide="info" style="width:14px;height:14px"></i> Separate multiple values with a comma
                            </p>
                        </div>

                        <!-- Advanced Flags -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <label class="premium-checkbox-card">
                                <input type="checkbox" id="col-required" style="margin-top:2px;width:18px;height:18px;accent-color:#3b82f6;cursor:pointer">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">Required Field</div>
                                    <div style="font-size:12px;color:#64748b">Must be filled to save</div>
                                </div>
                            </label>
                            
                            <label class="premium-checkbox-card">
                                <input type="checkbox" id="col-unique" style="margin-top:2px;width:18px;height:18px;accent-color:#10b981;cursor:pointer">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">Unique Identifier</div>
                                    <div style="font-size:12px;color:#64748b">Cannot duplicate across items</div>
                                </div>
                            </label>

                            <label class="premium-checkbox-card badge-anim">
                                <input type="checkbox" id="col-category" style="margin-top:2px;width:18px;height:18px;accent-color:#3b82f6;cursor:pointer">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">📂 Category Linked</div>
                                    <div style="font-size:12px;color:#64748b">Ties into Product Categories</div>
                                </div>
                            </label>

                            <label class="premium-checkbox-card badge-anim">
                                <input type="checkbox" id="col-combo" style="margin-top:2px;width:18px;height:18px;accent-color:#f59e0b;cursor:pointer">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">Variation Matrix</div>
                                    <div style="font-size:12px;color:#64748b">Creates product variants</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;">
                        <h4 style="margin:0 0 16px 0;font-size:13px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em">Visibility Settings</h4>
                        <div style="display:flex;flex-wrap:wrap;gap:24px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:#1e293b;font-size:14px">
                                <input type="checkbox" id="col-show-list" style="width:18px;height:18px;accent-color:#3b82f6">
                                Show in Product Table
                            </label>
                            <label class="badge-anim" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:#1e293b;font-size:14px">
                                <input type="checkbox" id="col-show-ai" style="width:18px;height:18px;accent-color:#8b5cf6">
                                <span style="display:flex;align-items:center;gap:6px;color:#7c3aed"><i data-lucide="bot" style="width:18px;height:18px"></i> AI Bot (WhatsApp) Access</span>
                            </label>
                        </div>
                    </div>

                    <div id="combo-note" style="display:none;margin-top:20px;padding:16px;background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;border-radius:8px;font-size:14px;color:#b45309">
                        <div style="display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:4px">
                            <i data-lucide="alert-triangle" style="width:16px;height:16px"></i> Variation Limit Notice
                        </div>
                        Combo columns create multidimensional product variations. Maximum of 5 allowed to maintain performance.
                    </div>
                </div>
                
                <div style="padding:20px 32px;background:rgba(248, 250, 252, 0.95);backdrop-filter:blur(8px);border-top:1px solid rgba(226, 232, 240, 0.8);display:flex;justify-content:flex-end;gap:12px;border-radius:0 0 16px 16px;position:sticky;bottom:0;z-index:10">
                    <button type="button" onclick="closeColumnModal()" style="padding:10px 20px;border:1px solid #cbd5e1;background:#fff;border-radius:8px;font-weight:600;color:#475569;cursor:pointer;transition:all 0.2s;font-size:14px;box-shadow:0 1px 2px rgba(0,0,0,0.05)" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#fff';this.style.borderColor='#cbd5e1';this.style.transform='none'">Cancel</button>
                    <button type="submit" class="btn-gradient">Save Required Changes</button>
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
        document.getElementById('col-category').checked = false;
        document.getElementById('col-combo').checked = false;
        document.getElementById('col-show-list').checked = false;
        document.getElementById('col-show-ai').checked = true;
        
        document.getElementById('system-warning').style.display = 'none';
        document.querySelectorAll('.system-locked-group input:not([type="checkbox"]), .system-locked-group select').forEach(el => el.disabled = false);
        document.querySelectorAll('.system-locked-group input[type="checkbox"]').forEach(el => el.disabled = false);
        
        toggleOptions();
        openModalWithAnim();
    }

    function editColumn(col) {
        document.getElementById('modal-title').textContent = 'Edit Field';
        document.getElementById('col-id').value = col.id;
        document.getElementById('col-is-system').value = col.is_system ? '1' : '0';
        document.getElementById('col-name').value = col.name;
        document.getElementById('col-type').value = col.type;
        // Fix for options potentially being null from JS context
        var opts = col.options;
        if (typeof opts === 'string') {
            try { opts = JSON.parse(opts); } catch(e) { }
        }
        document.getElementById('col-options').value = (Array.isArray(opts) ? opts.join(', ') : (opts || ''));
        document.getElementById('col-required').checked = col.is_required;
        document.getElementById('col-unique').checked = col.is_unique;
        document.getElementById('col-category').checked = col.is_category;
        document.getElementById('col-combo').checked = col.is_combo;
        document.getElementById('col-show-list').checked = col.show_on_list || false;
        document.getElementById('col-show-ai').checked = col.show_in_ai !== undefined ? col.show_in_ai : true;
        
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
        openModalWithAnim();
    }

    function openModalWithAnim() {
        var modal = document.getElementById('column-modal');
        var content = document.getElementById('modal-content-box');
        modal.style.display = 'flex';
        // force reflow
        void modal.offsetWidth;
        modal.style.opacity = '1';
        content.style.transform = 'scale(1) translateY(0)';
        
        // Reinitialize icons if any
        if (typeof lucide !== 'undefined') {
            setTimeout(() => lucide.createIcons(), 50);
        }
    }

    function closeColumnModal() {
        var modal = document.getElementById('column-modal');
        var content = document.getElementById('modal-content-box');
        modal.style.opacity = '0';
        content.style.transform = 'scale(0.95) translateY(10px)';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
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
                is_category: document.getElementById('col-category').checked ? 1 : 0,
                is_combo: document.getElementById('col-combo').checked ? 1 : 0,
                show_on_list: document.getElementById('col-show-list').checked ? 1 : 0,
                show_in_ai: document.getElementById('col-show-ai').checked ? 1 : 0,
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

    document.getElementById('column-modal').addEventListener('click', function (e) {
        if (e.target === this) closeColumnModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.getElementById('column-modal').style.display === 'flex') {
            closeColumnModal();
        }
    });

    // ═══════════ DRAG & DROP REORDER ═══════════
    (function() {
        var tbody = document.getElementById('columns-tbody');
        if (!tbody) return;
        var dragRow = null;
        var placeholder = null;

        function getRows() {
            return Array.from(tbody.querySelectorAll('tr[data-id]'));
        }

        // Make each row draggable via handle
        getRows().forEach(function(row) {
            var handle = row.querySelector('td:first-child');
            if (!handle) return;

            handle.style.cursor = 'grab';
            handle.setAttribute('draggable', 'true');

            handle.addEventListener('dragstart', function(e) {
                dragRow = row;
                // Delay class add so browser captures the ghost
                setTimeout(function() {
                    row.style.opacity = '0.4';
                    row.style.background = '#f0f9ff';
                }, 0);
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', row.dataset.id);
            });

            handle.addEventListener('dragend', function() {
                dragRow.style.opacity = dragRow.querySelector('input[type=checkbox]') && !dragRow.querySelector('input[type=checkbox]').checked ? '0.6' : '1';
                dragRow.style.background = '';
                if (placeholder && placeholder.parentNode) {
                    placeholder.parentNode.removeChild(placeholder);
                }
                dragRow = null;
                placeholder = null;
                // Remove all hover states
                getRows().forEach(function(r) {
                    r.style.borderTop = '';
                    r.style.borderBottom = '';
                });
            });

            row.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (!dragRow || dragRow === row) return;

                var rect = row.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;

                // Clear all borders
                getRows().forEach(function(r) {
                    r.style.borderTop = '';
                    r.style.borderBottom = '';
                });

                if (e.clientY < midY) {
                    row.style.borderTop = '3px solid #3b82f6';
                } else {
                    row.style.borderBottom = '3px solid #3b82f6';
                }
            });

            row.addEventListener('dragleave', function() {
                row.style.borderTop = '';
                row.style.borderBottom = '';
            });

            row.addEventListener('drop', function(e) {
                e.preventDefault();
                if (!dragRow || dragRow === row) return;

                var rect = row.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;

                if (e.clientY < midY) {
                    tbody.insertBefore(dragRow, row);
                } else {
                    tbody.insertBefore(dragRow, row.nextSibling);
                }

                // Clear all borders
                getRows().forEach(function(r) {
                    r.style.borderTop = '';
                    r.style.borderBottom = '';
                });

                // Save new order
                saveOrder();
            });
        });

        function saveOrder() {
            var ids = getRows().map(function(r) { return parseInt(r.dataset.id); });

            // Animate success briefly
            getRows().forEach(function(r, i) {
                r.style.transition = 'background 0.3s ease';
                r.style.background = '#f0fdf4';
                setTimeout(function() { r.style.background = ''; }, 600);
            });

            fetch('{{ route("admin.catalogue-columns.reorder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order: ids })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('Order updated successfully', 'success');
                }
            })
            .catch(function() {
                showToast('Failed to save order', 'error');
            });
        }
    })();
</script>
@endpush
