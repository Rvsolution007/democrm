@extends('admin.layouts.app')

@section('title', 'Catalogue Columns')
@section('breadcrumb', 'Catalogue Columns')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content" style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <h1 class="page-title" style="margin:0;font-size:24px;font-weight:700;letter-spacing:-0.025em">Dynamic Product Form Manager</h1>
                <p class="page-description" style="margin:4px 0 0;font-size:14px;color:#64748b">Control exactly what fields appear when creating or editing a product. System fields are locked to preserve CRM functionality, but can be hidden.</p>
            </div>
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:flex-end">
                <div style="min-width:180px;max-width:280px">
                    <div style="background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%);padding:10px 16px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <div>
                            <p style="margin:0 0 2px 0;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">Total Fields</p>
                            <h3 style="margin:0;font-size:20px;font-weight:700;color:#0f172a;letter-spacing:-0.5px">{{ count($columns) }}</h3>
                        </div>
                        <div style="width:36px;height:36px;background:#eef2ff;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#6366f1">
                            <i data-lucide="columns" style="width:18px;height:18px"></i>
                        </div>
                    </div>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="openAddModal()"><i data-lucide="plus" style="width:16px;height:16px"></i> Add Custom Field</button>
                </div>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    {{-- Bulk Delete Floating Bar --}}
    <div id="col-bulk-bar" style="display:none;position:sticky;top:70px;z-index:50;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;padding:12px 20px;border-radius:10px;margin-bottom:12px;align-items:center;justify-content:space-between;box-shadow:0 4px 15px rgba(239,68,68,0.3)">
        <div style="display:flex;align-items:center;gap:10px">
            <i data-lucide="check-square" style="width:20px;height:20px"></i>
            <span id="col-bulk-count" style="font-weight:600;font-size:14px">0 selected</span>
        </div>
        <div style="display:flex;gap:8px">
            <button onclick="selectAllColumns()" style="padding:6px 14px;border:1px solid rgba(255,255,255,0.4);background:transparent;color:white;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;transition:all .2s" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">Select All</button>
            <button onclick="bulkDeleteColumns()" style="padding:6px 14px;background:white;color:#ef4444;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:13px;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,0.1)" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'">🗑️ Delete Selected</button>
        </div>
    </div>

    {{-- Filter / Info Bar --}}
    <div style="background:white;padding:16px 20px;border-radius:8px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border:1px solid #e2e8f0;padding:6px 14px;border-radius:20px;display:flex;align-items:center;gap:8px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.8), 0 1px 2px rgba(0,0,0,0.04)">
                <div style="width:20px;height:20px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center">
                    <i data-lucide="list" style="width:12px;height:12px;color:#4f46e5"></i>
                </div>
                <span style="font-size:13px;font-weight:600;color:#334155;letter-spacing:0.3px">
                    <span style="color:#4f46e5;font-weight:700">{{ count($columns) }}</span>
                    <span style="color:#64748b;font-weight:500">Fields Configured</span>
                </span>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <span style="font-size:12px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;padding:4px 10px;border-radius:6px;display:flex;align-items:center;gap:4px">
                <i data-lucide="lock" style="width:11px;height:11px;color:#94a3b8"></i>
                {{ $columns->where('is_system', true)->count() }} System
            </span>
            <span style="font-size:12px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;padding:4px 10px;border-radius:6px;display:flex;align-items:center;gap:4px">
                <i data-lucide="plus-circle" style="width:11px;height:11px;color:#94a3b8"></i>
                {{ $columns->where('is_system', false)->count() }} Custom
            </span>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-container">
        <div class="table-wrapper">
            <table class="table" id="columns-table">
                <thead>
                    <tr>
                        <th style="width:50px;text-align:center">
                            <input type="checkbox" id="select-all-cols" onchange="toggleAllColumns(this)" style="width:16px;height:16px;accent-color:#6366f1;cursor:pointer">
                        </th>
                        <th style="width:40px">⠿</th>
                        <th data-col="name">Name & Slug</th>
                        <th data-col="type">Type</th>
                        <th data-col="links">System Links</th>
                        <th data-col="flags">Flags</th>
                        <th data-col="visibility">Visibility</th>
                        <th data-col="actions" style="width:200px">Actions</th>
                    </tr>
                </thead>
                <tbody id="columns-tbody">
                    @forelse($columns as $column)
                        <tr data-id="{{ $column->id }}" style="{{ !$column->is_active ? 'opacity:0.55' : '' }}">
                            <td data-col="checkbox" style="text-align:center">
                                @if(!$column->is_system)
                                    <input type="checkbox" class="col-checkbox" value="{{ $column->id }}" onchange="updateColBulkBar()" style="width:16px;height:16px;accent-color:#6366f1;cursor:pointer">
                                @else
                                    <span style="color:#cbd5e1;font-size:14px" title="System columns cannot be deleted">🔒</span>
                                @endif
                            </td>
                            <td data-col="drag" style="cursor:grab;color:#94a3b8;font-size:16px">⠿</td>
                            <td data-col="name">
                                <div>
                                    <p class="font-medium" style="font-size:14px;font-weight:600;margin:0 0 2px;display:flex;align-items:center;gap:6px">
                                        {{ $column->name }}
                                        @if($column->is_system)
                                            <span class="badge badge-primary" style="font-size:10px;padding:2px 8px">System</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-muted" style="margin:0;font-family:'SF Mono', SFMono-Regular, ui-monospace, Menlo, monospace;font-size:11px;color:#94a3b8;letter-spacing:0.02em">
                                        {{ $column->slug }}
                                    </p>
                                </div>
                            </td>
                            <td data-col="type">
                                <span class="badge badge-secondary" style="font-size:12px;font-weight:500">{{ $column->type }}</span>
                                @if(in_array($column->type, ['select', 'multiselect']) && $column->options)
                                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        {{ implode(', ', $column->options) }}
                                    </div>
                                @endif
                            </td>
                            <td data-col="links">
                                @if($column->connected_modules && count($column->connected_modules) > 0)
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:160px">
                                        @foreach($column->connected_modules as $mod)
                                            <span style="font-size:10px;background:#f1f5f9;border:1px solid #e2e8f0;padding:3px 8px;border-radius:4px;color:#475569;font-weight:500">{{ $mod }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:#94a3b8;font-size:12px">—</span>
                                @endif
                            </td>
                            <td data-col="flags">
                                <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:240px">
                                    @if($column->is_combo)
                                        <span class="badge badge-warning" style="font-size:11px">Combo</span>
                                    @endif
                                    @if($column->is_required)
                                        <span class="badge badge-info" style="font-size:11px">Required</span>
                                    @endif
                                    @if($column->is_unique)
                                        <span class="badge badge-success" style="font-size:11px">Unique</span>
                                    @endif
                                    @if($column->is_category)
                                        <span class="badge" style="font-size:11px;background:#eff6ff;color:#3b82f6;border:1px solid rgba(59,130,246,0.2)">📂 Category</span>
                                    @endif
                                    @if($column->is_title)
                                        <span class="badge" style="font-size:11px;background:#f5f3ff;color:#8b5cf6;border:1px solid rgba(139,92,246,0.2)">🏷️ Title</span>
                                    @endif
                                    @if($column->is_variation_field)
                                        <span class="badge badge-destructive" style="font-size:11px">🔄 Per-Variation</span>
                                    @endif
                                    @if($column->show_in_ai)
                                        <span class="badge badge-primary" style="font-size:11px">🤖 AI</span>
                                    @endif
                                </div>
                            </td>
                            <td data-col="visibility">
                                <div style="display:flex;align-items:center;gap:8px;justify-content:center">
                                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;font-weight:500;color:#334155;white-space:nowrap">
                                        <input type="checkbox" onchange="toggleActive({{ $column->id }}, this.checked)" {{ $column->is_active ? 'checked' : '' }} style="width:16px;height:16px;accent-color:#22c55e;cursor:pointer">
                                        {{ $column->is_active ? 'Active' : 'Hidden' }}
                                    </label>
                                </div>
                            </td>
                            <td data-col="actions">
                                <div style="display:flex;gap:6px;align-items:center">
                                    <button onclick='editColumn({{ json_encode($column) }})'
                                        style="width:32px;height:32px;border-radius:8px;background:#fffbeb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#f59e0b;transition:all 0.15s"
                                        title="Edit" onmouseover="this.style.background='#fef3c7'"
                                        onmouseout="this.style.background='#fffbeb'">
                                        <i data-lucide="edit" style="width:16px;height:16px"></i>
                                    </button>
                                    @if(!$column->is_system)
                                        <button onclick="deleteColumn({{ $column->id }})"
                                            style="width:32px;height:32px;border-radius:8px;background:#fef2f2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;transition:all 0.15s"
                                            title="Delete" onmouseover="this.style.background='#fee2e2'"
                                            onmouseout="this.style.background='#fef2f2'">
                                            <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="8" class="text-center py-8 text-muted">
                                <div style="padding:40px 0">
                                    <i data-lucide="columns" style="width:48px;height:48px;color:#cbd5e1;margin:0 auto 16px;display:block"></i>
                                    <p style="color:#94a3b8;font-size:14px;margin:0">No custom columns defined. Click "Add Custom Field" to create one.</p>
                                </div>
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
                                <input type="checkbox" id="col-combo" style="margin-top:2px;width:18px;height:18px;accent-color:#f59e0b;cursor:pointer" onchange="toggleVariationField()">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">Variation Matrix</div>
                                    <div style="font-size:12px;color:#64748b">Creates product variants (e.g., Capacity: 500ML, 750ML)</div>
                                </div>
                            </label>

                            <label class="premium-checkbox-card badge-anim" id="variation-field-card">
                                <input type="checkbox" id="col-variation-field" onchange="toggleComboField()" style="margin-top:2px;width:18px;height:18px;accent-color:#ef4444;cursor:pointer">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">🔄 Per-Variation Field</div>
                                    <div style="font-size:12px;color:#64748b">Value changes per variant (e.g., different price per size)</div>
                                </div>
                            </label>

                            <label class="premium-checkbox-card badge-anim">
                                <input type="checkbox" id="col-title" style="margin-top:2px;width:18px;height:18px;accent-color:#8b5cf6;cursor:pointer">
                                <div>
                                    <div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:2px">🏷️ Quote/Lead Title</div>
                                    <div style="font-size:12px;color:#64748b">This column's value becomes the Product Name heading in Quotes & Leads</div>
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
        document.getElementById('col-variation-field').checked = false;
        document.getElementById('col-title').checked = false;
        document.getElementById('col-show-list').checked = false;
        document.getElementById('col-show-ai').checked = true;
        
        // Reset states first before applying toggles
        document.getElementById('variation-field-card').style.opacity = '1';
        document.getElementById('variation-field-card').style.pointerEvents = 'auto';
        document.getElementById('col-combo').closest('.premium-checkbox-card').style.opacity = '1';
        document.getElementById('col-combo').closest('.premium-checkbox-card').style.pointerEvents = 'auto';
        
        toggleVariationField();
        toggleComboField();
        
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
        document.getElementById('col-variation-field').checked = col.is_variation_field || false;
        document.getElementById('col-title').checked = col.is_title || false;
        document.getElementById('col-show-list').checked = col.show_on_list || false;
        document.getElementById('col-show-ai').checked = col.show_in_ai !== undefined ? col.show_in_ai : true;
        
        // Reset styles first
        document.getElementById('variation-field-card').style.opacity = '1';
        document.getElementById('variation-field-card').style.pointerEvents = 'auto';
        document.getElementById('col-combo').closest('.premium-checkbox-card').style.opacity = '1';
        document.getElementById('col-combo').closest('.premium-checkbox-card').style.pointerEvents = 'auto';
        
        toggleVariationField();
        toggleComboField();
        
        if (col.is_system) {
            document.getElementById('system-warning').style.display = 'block';
            document.querySelectorAll('.system-locked-group input:not([type="checkbox"]), .system-locked-group select').forEach(el => el.disabled = true);
            document.querySelectorAll('.system-locked-group input[type="checkbox"]').forEach(el => {
                if (el.id !== 'col-title') el.disabled = true;
            });
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
        fetch('{{ url("admin/catalogue-columns") }}/' + id + '/toggle-active', {
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
                is_title: document.getElementById('col-title').checked ? 1 : 0,
                is_combo: document.getElementById('col-combo').checked ? 1 : 0,
                is_variation_field: document.getElementById('col-variation-field').checked ? 1 : 0,
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
            var handle = row.querySelector('td[data-col="drag"]');
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

    // ═══════════ BULK SELECT & DELETE FOR COLUMNS ═══════════
    function toggleAllColumns(masterCheckbox) {
        var checkboxes = document.querySelectorAll('.col-checkbox');
        checkboxes.forEach(function(cb) { cb.checked = masterCheckbox.checked; });
        updateColBulkBar();
    }

    function selectAllColumns() {
        var checkboxes = document.querySelectorAll('.col-checkbox');
        checkboxes.forEach(function(cb) { cb.checked = true; });
        var master = document.getElementById('select-all-cols');
        if (master) master.checked = true;
        updateColBulkBar();
    }

    function updateColBulkBar() {
        var checked = document.querySelectorAll('.col-checkbox:checked');
        var bar = document.getElementById('col-bulk-bar');
        var countEl = document.getElementById('col-bulk-count');
        if (checked.length > 0) {
            bar.style.display = 'flex';
            countEl.textContent = checked.length + ' column' + (checked.length > 1 ? 's' : '') + ' selected';
        } else {
            bar.style.display = 'none';
        }
        var all = document.querySelectorAll('.col-checkbox');
        var master = document.getElementById('select-all-cols');
        if (master) master.checked = all.length > 0 && checked.length === all.length;
    }

    function bulkDeleteColumns() {
        var checked = document.querySelectorAll('.col-checkbox:checked');
        if (checked.length === 0) return;
        if (!confirm('Delete ' + checked.length + ' column(s)? This will also remove all product values for these columns. System columns will be skipped.')) return;

        var ids = Array.from(checked).map(function(cb) { return parseInt(cb.value); });

        fetch('{{ route("admin.catalogue-columns.bulk-destroy") }}', {
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
                showAlert('success', data.message);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert('error', data.message || 'Delete failed');
            }
        })
        .catch(function() {
            showAlert('error', 'Network error. Try again.');
        });
    }

    // ═══════════ TOGGLE VARIATION FIELD & COMBO FIELD ═══════════
    function toggleVariationField() {
        var comboChecked = document.getElementById('col-combo').checked;
        var card = document.getElementById('variation-field-card');
        var input = document.getElementById('col-variation-field');
        if (comboChecked) {
            // A combo column can't also be a per-variation field
            input.checked = false;
            card.style.opacity = '0.4';
            card.style.pointerEvents = 'none';
        } else if (!document.getElementById('col-combo').disabled) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
        }
    }

    function toggleComboField() {
        var variationChecked = document.getElementById('col-variation-field').checked;
        var comboInput = document.getElementById('col-combo');
        var comboCard = comboInput.closest('.premium-checkbox-card');
        if (variationChecked) {
            // A per-variation field can't also be a combo matrix
            comboInput.checked = false;
            comboCard.style.opacity = '0.4';
            comboCard.style.pointerEvents = 'none';
            toggleOptions();
        } else if (!document.getElementById('col-variation-field').disabled) {
            comboCard.style.opacity = '1';
            comboCard.style.pointerEvents = 'auto';
        }
    }
</script>
@endpush
