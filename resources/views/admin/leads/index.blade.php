@extends('admin.layouts.app')

@section('title', 'Leads')
@section('breadcrumb', 'Leads')

@section('content')
    <div class="page-header" style="margin-bottom:24px">
        <div class="page-header-content"
            style="display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px;display:flex;align-items:center;gap:16px;">
                <div>
                    <h1 class="page-title" style="margin:0;">Leads</h1>
                    <p class="page-description" style="margin:0;">Manage your sales leads and track their progress</p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;justify-content:flex-end">
                <div style="min-width:240px;max-width:350px">
                    <div
                        style="background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%);padding:10px 16px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <p
                                style="margin:0 0 4px 0;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em">
                                Total Product Amount</p>
                            <h3 style="margin:0;font-size:20px;font-weight:700;color:#0f172a;letter-spacing:-0.5px">
                                ₹{{ number_format($totalAmount, 2) }}</h3>
                        </div>
                        <div
                            style="width:36px;height:36px;background:#f0fdf4;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#16a34a">
                            <i data-lucide="indian-rupee" style="width:18px;height:18px"></i>
                        </div>
                    </div>
                </div>
                <div class="page-actions" style="display:flex;gap:12px;align-items:center">
                    <!-- View Toggles moved to right side aligned before Add Lead -->
                    <div style="display:flex;gap:4px;background:#f1f1f1;padding:4px;border-radius:6px;align-self:stretch;">
                        <button id="list-view-btn" onclick="switchView('list')"
                            style="padding:6px 12px;background:white;border:none;border-radius:4px;cursor:pointer;display:flex;align-items:center;gap:4px">
                            <i data-lucide="list" style="width:16px;height:16px"></i>
                            List
                        </button>
                        <button id="kanban-view-btn" onclick="switchView('kanban')"
                            style="padding:6px 12px;background:transparent;border:none;border-radius:4px;cursor:pointer;display:flex;align-items:center;gap:4px">
                            <i data-lucide="layout-grid" style="width:16px;height:16px"></i>
                            Kanban
                        </button>
                    </div>
                    @if(can('leads.write'))
                        <button class="btn btn-primary" onclick="openAddLeadModal()">
                            <i data-lucide="plus" style="width:16px;height:16px"></i> Add Lead
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>


    @if(session('success'))
        <div class="alert alert-success"
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:20px">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger"
            style="padding:12px 20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:4px;margin-bottom:20px">
            <ul style="margin:0;padding-left:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Search and Filters -->
    <div style="background:white;padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
        <form method="GET" action="{{ route('admin.leads.index') }}" id="filter-form">
            <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:end">
                <div style="flex:2;min-width:200px;">
                    <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px">Search</label>
                    <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                        placeholder="Search by name, phone, email..." oninput="autoAjaxSearch(this.form)"
                        style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px">
                </div>
                <div style="flex:1;min-width:140px;">
                    <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px">Stage</label>
                    <div style="position:relative">
                        <i data-lucide="filter"
                            style="position:absolute;left:24px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#9ca3af;z-index:10;pointer-events:none"></i>
                        <select name="stage" class="form-input" style="padding-left:36px;font-size:13px"
                            onchange="autoAjaxSearch(this.form)">
                            <option value="">All Stages</option>
                            @foreach(\App\Models\Lead::getDynamicStages() as $stageVal)
                                <option value="{{ $stageVal }}" {{ request('stage') === $stageVal ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $stageVal)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="flex:2;min-width:240px;max-width:320px;">
                    <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px">Created Date Range</label>
                    <div style="position:relative">
                        <i data-lucide="calendar"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                        <input type="text" id="lead-date-range-picker" class="form-input" placeholder="Select Date Range"
                            style="width:100%;padding:8px 10px 8px 32px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:white;cursor:pointer;outline:none">
                        <input type="hidden" name="created_from" id="created-from" value="{{ request('created_from') }}">
                        <input type="hidden" name="created_to" id="created-to" value="{{ request('created_to') }}">
                    </div>
                </div>
                @if(can('leads.global') || auth()->user()->isAdmin())
                    <div style="flex:1;min-width:140px;">
                        <label style="display:block;margin-bottom:4px;font-weight:500;font-size:14px">Assigned To</label>
                        <select name="assigned" id="assigned-filter" onchange="autoAjaxSearch(this.form)"
                            style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px">
                            <option value="">All Users</option>
                            <option value="unassigned" {{ request('assigned') == 'unassigned' ? 'selected' : '' }}>Unassigned
                            </option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('assigned') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <!-- Removed physical Search button as requested -->
            </div>
            <div
                style="display:flex;align-items:center;justify-content:space-between;margin-top:16px;border-top:1px solid #f1f5f9;padding-top:16px">
                <div style="display:flex;align-items:center;gap:12px">
                    <div id="leads-count-badge"
                        style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border:1px solid #e2e8f0;padding:6px 14px;border-radius:20px;display:flex;align-items:center;gap:8px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.8), 0 1px 2px rgba(0,0,0,0.04)">
                        <div
                            style="width:20px;height:20px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="users" style="width:12px;height:12px;color:#4f46e5"></i>
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#334155;letter-spacing:0.3px">
                            <span id="filtered-leads-count"
                                style="color:#4f46e5;font-weight:700">{{ $leads->total() }}</span>
                            <span style="color:#64748b;font-weight:500">Leads Found</span>
                        </span>
                    </div>
                </div>
                @if(request('search') || request('stage') || request('created_from') || request('created_to') || request('assigned'))
                    <div>
                        <a href="{{ route('admin.leads.index') }}"
                            style="color:#64748b;text-decoration:none;font-size:13px;font-weight:500;display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;transition:all 0.15s"
                            onmouseover="this.style.background='#f1f5f9';this.style.color='#ef4444'"
                            onmouseout="this.style.background='#f8fafc';this.style.color='#64748b'">
                            <i data-lucide="filter-x" style="width:14px;height:14px"></i>
                            Clear Filters
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>

    <!-- List View -->
    <div id="list-view">
        <div class="table-container">
            <div class="table-wrapper">
                <table class="table" id="leads-table">
                    <thead>
                        <tr>
                            <th data-col="id" style="width:70px">Lead ID</th>
                            <th data-col="name">Lead Name</th>
                            <th data-col="source">Source</th>
                            <th data-col="stage">Stage</th>
                            <th data-col="products">Products</th>
                            <th data-col="amount">Amount</th>
                            <th data-col="assigned">Assigned To</th>
                            <th data-col="actions" style="width:200px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr>
                                <td data-col="id"><span class="badge badge-secondary"
                                        style="font-size:11px">#{{ $lead->id }}</span></td>
                                <td data-col="name">
                                    <div>
                                        <p class="font-medium">{{ $lead->name }}</p>
                                        <p class="text-xs text-muted">
                                            <i data-lucide="phone" style="width:12px;height:12px;display:inline"></i>
                                            {{ $lead->phone }}
                                        </p>
                                    </div>
                                </td>
                                <td data-col="source"><span class="badge badge-secondary">{{ ucfirst($lead->source) }}</span>
                                </td>
                                <td data-col="stage"><span
                                        class="badge badge-{{ $lead->stage }}">{{ ucfirst($lead->stage) }}</span></td>
                                <td data-col="products">
                                    @if($lead->products->count() > 0)
                                        <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:200px">
                                            @foreach($lead->products as $product)
                                                <span
                                                    style="background:#f1f5f9;color:#475569;padding:2px 6px;border-radius:4px;font-size:10px;font-weight:500;white-space:nowrap;border:1px solid #e2e8f0"
                                                    title="{{ $product->name }}">
                                                    {{ Str::limit($product->name, 20) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color:#94a3b8;font-size:11px">—</span>
                                    @endif
                                </td>
                                <td data-col="amount">
                                    <span
                                        style="font-weight:600;color:#15803d;background:#f0fdf4;padding:4px 8px;border-radius:6px;font-size:13px">
                                        ₹{{ number_format($lead->total_amount, 2) }}
                                    </span>
                                </td>
                                <td data-col="assigned">{{ $lead->assignedTo->name ?? 'Unassigned' }}</td>
                                <td data-col="actions">
                                    <div style="display:flex;gap:6px;align-items:center">
                                        <button onclick='viewLead({{ $lead->id }})'
                                            style="width:32px;height:32px;border-radius:8px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all 0.15s"
                                            title="View" onmouseover="this.style.background='#dbeafe'"
                                            onmouseout="this.style.background='#eff6ff'">
                                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                                        </button>

                                        @if(can('leads.write'))
                                            <button onclick='editLead({{ $lead->id }})'
                                                style="width:32px;height:32px;border-radius:8px;background:#fffbeb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#f59e0b;transition:all 0.15s"
                                                title="Edit" onmouseover="this.style.background='#fef3c7'"
                                                onmouseout="this.style.background='#fffbeb'">
                                                <i data-lucide="edit" style="width:16px;height:16px"></i>
                                            </button>
                                        @endif
                                        @if(can('leads.delete'))
                                            <form action="{{ route('admin.leads.destroy', $lead->id) }}" method="POST"
                                                style="display:inline;margin:0"
                                                onsubmit="return confirm('Are you sure you want to delete this lead?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    style="width:32px;height:32px;border-radius:8px;background:#fef2f2;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#ef4444;transition:all 0.15s"
                                                    title="Delete" onmouseover="this.style.background='#fee2e2'"
                                                    onmouseout="this.style.background='#fef2f2'">
                                                    <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-muted">No leads found. Click "Add Lead" to create
                                    one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                {{ $leads->links() }}
            </div>
        </div>
    </div>

    <!-- Kanban View -->
    <div id="kanban-view" style="display:none;width:0;min-width:100%">
        <div class="kanban-board" id="kanban-board"
            style="display:flex;gap:16px;overflow-x:auto;padding-bottom:20px;min-height:calc(100vh - 340px)">
            @foreach(\App\Models\Lead::getDynamicStages() as $stage)
                <div class="kanban-column" data-stage="{{ $stage }}"
                    style="min-width:300px;background:white;border-radius:12px;padding:12px 1px;box-shadow:0 1px 3px rgba(0,0,0,0.08);transition:all 0.2s;display:flex;flex-direction:column"
                    ondragover="kanbanDragOver(event)" ondragleave="kanbanDragLeave(event)" ondrop="kanbanDrop(event)">
                    <div
                        style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #eee">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span class="badge badge-{{ $stage }}">{{ ucfirst(str_replace('_', ' ', $stage)) }}</span>
                            <span class="kanban-count-{{ $stage }}"
                                style="background:#f1f1f1;padding:2px 8px;border-radius:12px;font-size:12px;font-weight:600">{{ $allLeads->where('stage', $stage)->count() }}</span>
                        </div>
                    </div>
                    <div class="kanban-cards" data-stage="{{ $stage }}"
                        style="display:flex;flex-direction:column;gap:12px;flex:1;overflow-y:auto;min-height:60px">
                        @forelse($allLeads->where('stage', $stage) as $lead)
                            <div class="kanban-card" draggable="true" data-lead-id="{{ $lead->id }}"
                                style="background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:12px;cursor:grab;transition:all 0.2s;user-select:none"
                                ondragstart="kanbanDragStart(event)" ondragend="kanbanDragEnd(event)"
                                onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'"
                                onmouseout="this.style.boxShadow='none'">
                                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
                                    <div style="flex:1;padding-right:8px">
                                        <p style="font-weight:600;margin:0 0 6px 0;font-size:14px;line-height:1.3">{{ $lead->name }}
                                            <span style="font-size:11px;color:#999;font-weight:400">#{{ $lead->id }}</span>
                                        </p>
                                        <p style="margin:0;font-size:13px;color:#666;display:flex;align-items:center;gap:4px">
                                            <i data-lucide="phone" style="width:12px;height:12px"></i>
                                            {{ $lead->phone }}
                                        </p>
                                    </div>
                                    <span class="badge badge-secondary" style="font-size:10px">{{ ucfirst($lead->source) }}</span>
                                </div>
                                @if($lead->products->count() > 0)
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
                                        @foreach($lead->products as $product)
                                            <span
                                                style="background:#f1f5f9;color:#475569;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:500;white-space:nowrap;border:1px solid #e2e8f0"
                                                title="{{ $product->name }}">
                                                {{ Str::limit($product->name, 20) }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if($lead->total_amount > 0)
                                        <p
                                            style="margin:0 0 12px 0;font-size:13px;font-weight:600;color:#15803d;background:#f0fdf4;padding:4px 8px;border-radius:6px;display:inline-block">
                                            ₹{{ number_format($lead->total_amount, 2) }}
                                        </p>
                                    @endif
                                @endif
                                @if($lead->city || $lead->state)
                                    <p style="margin:10px 0;font-size:13px;color:#555;display:flex;align-items:center;gap:4px">
                                        <i data-lucide="map-pin" style="width:13px;height:13px"></i>
                                        {{ $lead->city }}{{ $lead->state ? ', ' . $lead->state : '' }}
                                    </p>
                                @endif
                                <div
                                    style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;padding-top:10px;border-top:1px solid #e5e7eb">
                                    <span
                                        style="font-size:12px;color:#666;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding-right:8px">{{ $lead->assignedTo->name ?? 'Unassigned' }}</span>
                                    <div style="display:flex;gap:6px">

                                        <button onclick='viewLead({{ $lead->id }})'
                                            style="width:30px;height:30px;border-radius:6px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:background 0.2s"
                                            title="View" onmouseover="this.style.background='#dbeafe'"
                                            onmouseout="this.style.background='#eff6ff'">
                                            <i data-lucide="eye" style="width:15px;height:15px"></i>
                                        </button>
                                        @if(can('leads.write'))
                                            <button onclick='editLead({{ $lead->id }})'
                                                style="width:30px;height:30px;border-radius:6px;background:#fffbeb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#f59e0b;transition:background 0.2s"
                                                title="Edit" onmouseover="this.style.background='#fef3c7'"
                                                onmouseout="this.style.background='#fffbeb'">
                                                <i data-lucide="edit" style="width:15px;height:15px"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="kanban-empty" style="text-align:center;color:#999;font-size:13px;padding:20px 0">No leads</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Add/Edit Lead Modal -->
    <div id="lead-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div
            style="background:white;border-radius:12px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
            <div
                style="padding:18px 24px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff);border-radius:12px 12px 0 0;position:sticky;top:0;z-index:10">
                <h3 id="modal-title" style="margin:0;font-size:18px;font-weight:600;color:#1a1a2e">Add New Lead</h3>
                <button onclick="closeLeadModal()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;padding:0;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all 0.15s"
                    onmouseover="this.style.background='#e2e8f0'"
                    onmouseout="this.style.background='#f1f5f9'">&times;</button>
            </div>
            <form id="lead-form" method="POST" action="{{ route('admin.leads.store') }}">
                @csrf
                <input type="hidden" id="form-method" name="_method" value="">
                <div style="padding:24px">
                    <style>
                        .responsive-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
                        .responsive-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
                        @media (max-width: 768px) {
                            .responsive-grid-2, .responsive-grid-3 { grid-template-columns: 1fr; gap: 12px; }
                        }
                    </style>

                    <div class="responsive-grid-2">
                        <div data-field="name">
                            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Name
                                <span style="color:#ef4444">*</span></label>
                            <input type="text" class="form-input" name="name" id="lead-name" required
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        <div data-field="source">
                            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Source
                                <span style="color:#ef4444">*</span></label>
                            <select class="form-select" name="source" id="lead-source" required
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;background:white;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                                <option value="">Select Source</option>
                                @foreach(\App\Models\Lead::getDynamicSources() as $sourceVal)
                                    <option value="{{ $sourceVal }}">{{ ucfirst(str_replace('_', ' ', $sourceVal)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="responsive-grid-3">
                        <div data-field="phone">
                            <label
                                style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Phone
                                <span style="color:#ef4444">*</span></label>
                            <input type="tel" class="form-input" name="phone" id="lead-phone" required
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        <div data-field="stage">
                            <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Stage <span
                                    style="color:#ef4444">*</span></label>
                            <select name="stage" id="lead-stage" class="form-input" required
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;background:white;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                                @foreach(\App\Models\Lead::getDynamicStages() as $stageVal)
                                    <option value="{{ $stageVal }}">{{ ucfirst(str_replace('_', ' ', $stageVal)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div data-field="email">
                            <label
                                style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Email</label>
                            <input type="email" class="form-input" name="email" id="lead-email"
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                    </div>

                    <div class="responsive-grid-3">
                        <div data-field="city">
                            <label
                                style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">City</label>
                            <input type="text" class="form-input" name="city" id="lead-city"
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        <div data-field="state">
                            <label
                                style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">State</label>
                            <input type="text" class="form-input" name="state" id="lead-state"
                                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color 0.15s;outline:none"
                                onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        @if(can('leads.global') || auth()->user()->isAdmin())
                            <div data-field="assigned">
                                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Assigned
                                    To</label>
                                <select class="form-select" name="assigned_to_user_id" id="lead-assigned"
                                    style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;background:white;transition:border-color 0.15s;outline:none"
                                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                                    <option value="">Unassigned</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div data-field="assigned">
                                <input type="hidden" name="assigned_to_user_id" value="{{ auth()->id() }}">
                                <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Assigned
                                    To</label>
                                <input type="text" class="form-input" value="{{ auth()->user()->name }}" readonly
                                    style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;background-color:#f8fafc;color:#666">
                            </div>
                        @endif
                    </div>
                    <div style="margin-bottom:16px">
                        <label
                            style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Notes</label>
                        <textarea class="form-textarea" name="notes" id="lead-notes" rows="3"
                            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;transition:border-color 0.15s;outline:none;resize:vertical"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                    </div>

                    {{-- Product Selection - Premium UI --}}
                    <div style="margin-bottom:16px;border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff">
                        <div style="padding:12px 16px;background:linear-gradient(135deg,#f8fafc,#eef2ff);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px">
                            <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center">
                                <i data-lucide="package" style="width:14px;height:14px;color:white"></i>
                            </div>
                            <span style="font-weight:600;font-size:14px;color:#1e293b">Products</span>
                        </div>
                        <div style="padding:16px">
                            <div style="display:flex;gap:10px;margin-bottom:12px;align-items:stretch">
                                <div style="flex:1;position:relative">
                                    <select id="product-selector" class="form-select" style="width:100%">
                                        <option value="">🔍 Search or select a product...</option>
                                        @foreach($products as $product)
                                            @php $displayPrice = ($product->mrp ?: $product->sale_price) / 100; @endphp
                                            <option value="{{ $product->id }}" data-name="{{ $product->name }}"
                                                data-price="{{ $displayPrice }}" data-mrp="{{ $product->mrp }}"
                                                data-desc="{{ Str::limit($product->description ?? '', 60) }}">
                                                {{ $product->name }} — ₹{{ number_format($displayPrice, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" class="btn" onclick="addSelectedProduct()"
                                    style="padding:10px 20px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:10px;cursor:pointer;white-space:nowrap;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(37,99,235,0.25);transition:all 0.2s"
                                    onmouseover="this.style.boxShadow='0 4px 14px rgba(37,99,235,0.4)';this.style.transform='translateY(-1px)'"
                                    onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,0.25)';this.style.transform='translateY(0)'">
                                    <i data-lucide="plus" style="width:14px;height:14px"></i> Add
                                </button>
                            </div>
                            <div id="selected-products-wrapper">
                                <table id="selected-products-table" style="width:100%;border-collapse:separate;border-spacing:0;display:none;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden">
                                    <thead>
                                        <tr style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);text-align:left">
                                            <th style="padding:10px 12px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0">Product Name</th>
                                            <th style="padding:10px 12px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;width:100px">Price (₹)</th>
                                            <th style="padding:10px 12px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0">Description</th>
                                            <th style="padding:10px 12px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;width:70px">Qty</th>
                                            <th style="padding:10px 12px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;width:100px">Dis. Price</th>
                                            <th style="padding:10px 12px;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e2e8f0;width:50px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="selected-products-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Follow-up Section (only visible when editing) --}}
                    <div id="followup-section"
                        style="display:none;margin-bottom:16px;border-top:1px solid #eee;padding-top:16px">
                        <label style="display:block;margin-bottom:8px;font-weight:600;font-size:15px">Follow-up</label>
                        <div
                            style="display:grid;grid-template-columns:1fr 2fr auto;gap:8px;align-items:end;margin-bottom:12px">
                            <div>
                                <label style="display:block;margin-bottom:4px;font-size:12px;color:#666">Next Follow-up Date
                                    & Time</label>
                                <input type="datetime-local" id="followup-date"
                                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px">
                            </div>
                            <div>
                                <label style="display:block;margin-bottom:4px;font-size:12px;color:#666">Message</label>
                                <input type="text" id="followup-message" placeholder="Enter follow-up note..."
                                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px">
                            </div>
                            <div>
                                <button type="button" onclick="addFollowup()"
                                    style="padding:8px 16px;background:#10b981;color:white;border:none;border-radius:4px;cursor:pointer;font-size:13px;white-space:nowrap">
                                    + Add
                                </button>
                            </div>
                        </div>
                        <div id="followup-history" style="max-height:250px;overflow-y:auto">
                            <!-- Follow-up history items injected via JS -->
                        </div>
                    </div>
                </div>
                <div
                    style="padding:16px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:#fafbfc;border-radius:0 0 12px 12px">
                    <div id="quote-action-container" style="display:none">
                        <button type="button" id="btn-convert-to-quote" onclick="convertLeadToQuote()"
                            style="padding:8px 16px;background:#10b981;color:white;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;font-weight:500;transition:all 0.15s"
                            onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                            <i data-lucide="file-text" style="width:14px;height:14px"></i> Convert to Quote
                        </button>
                        <button type="button" id="btn-view-quote" onclick="viewLeadQuote()"
                            style="padding:8px 16px;background:#6366f1;color:white;border:none;border-radius:8px;cursor:pointer;display:none;align-items:center;gap:6px;font-size:13px;font-weight:500;transition:all 0.15s"
                            onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                            <i data-lucide="eye" style="width:14px;height:14px"></i> View Quote
                        </button>
                    </div>
                    <div style="display:flex;gap:10px;margin-left:auto">
                        <button type="button" class="btn btn-outline" onclick="closeLeadModal()"
                            style="padding:9px 20px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;transition:all 0.15s"
                            onmouseover="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'"
                            onmouseout="this.style.borderColor='#e2e8f0';this.style.background='white'">Cancel</button>
                        <button type="submit" id="btn-save-lead" class="btn btn-primary"
                            style="padding:9px 24px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:all 0.15s;box-shadow:0 2px 8px rgba(37,99,235,0.3)"
                            onmouseover="this.style.boxShadow='0 4px 12px rgba(37,99,235,0.4)'"
                            onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,0.3)'">Save Lead</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- View Lead Modal -->
    <div id="view-lead-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div
            style="background:white;border-radius:16px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,0.18)">
            <div
                style="padding:18px 24px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff);border-radius:16px 16px 0 0;position:sticky;top:0;z-index:10">
                <div style="display:flex;align-items:center;gap:10px">
                    <div
                        style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center">
                        <i data-lucide="user" style="width:18px;height:18px;color:white"></i>
                    </div>
                    <h3 style="margin:0;font-size:17px;font-weight:600;color:#1a1a2e">Lead Details</h3>
                </div>
                <button onclick="closeViewLeadModal()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;padding:0;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all 0.15s"
                    onmouseover="this.style.background='#e2e8f0'"
                    onmouseout="this.style.background='#f1f5f9'">&times;</button>
            </div>
            <div style="padding:24px" id="view-lead-content">
                <!-- Content injected via JS -->
            </div>
            <div id="view-followup-history" style="padding:0 24px 20px;display:none">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                    <div
                        style="width:28px;height:28px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center">
                        <i data-lucide="message-square" style="width:14px;height:14px;color:#22c55e"></i>
                    </div>
                    <h4 style="margin:0;font-size:14px;font-weight:600;color:#1e293b">Follow-up History</h4>
                </div>
                <div id="view-followup-list"></div>
            </div>
            <div
                style="padding:16px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc;border-radius:0 0 16px 16px">
                <button type="button" onclick="closeViewLeadModal()"
                    style="padding:9px 20px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;transition:all 0.15s"
                    onmouseover="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'"
                    onmouseout="this.style.borderColor='#e2e8f0';this.style.background='white'">Close</button>
                @if(can('leads.write'))
                    <button type="button" id="btn-view-edit"
                        style="padding:9px 20px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px;transition:all 0.15s;box-shadow:0 2px 8px rgba(37,99,235,0.3)"
                        onmouseover="this.style.boxShadow='0 4px 12px rgba(37,99,235,0.4)'"
                        onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,0.3)'"><i data-lucide="pencil"
                            style="width:14px;height:14px"></i> Edit Lead</button>
                @endif
            </div>
        </div>
    </div>


    <!-- Description Edit Popup -->
    <div id="desc-edit-popup"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div
            style="background:white;border-radius:12px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden">
            <div
                style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:600;color:#1a1a2e">Edit Description</h3>
                <button onclick="closeDescPopup()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b">&times;</button>
            </div>
            <div style="padding:20px">
                <textarea id="desc-edit-textarea" rows="6"
                    style="width:100%;padding:12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;resize:vertical;outline:none;font-family:inherit;line-height:1.6"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"
                    placeholder="Enter detailed description..."></textarea>
            </div>
            <div
                style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc">
                <button type="button" onclick="closeDescPopup()"
                    style="padding:8px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;color:#64748b">Cancel</button>
                <button type="button" onclick="saveDescPopup()"
                    style="padding:8px 18px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(37,99,235,0.3)">Save</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
    <script>
        let editingLeadId = null;
        let currentLeadQuoteId = null;
        let initialFormSnapshot = null;
        let isLeadSubmitting = false;

        // AJAX form submission with double-click prevention
        document.addEventListener('DOMContentLoaded', function () {
            var leadForm = document.getElementById('lead-form');
            if (leadForm) {
                leadForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (isLeadSubmitting) return;
                    isLeadSubmitting = true;

                    var saveBtn = document.getElementById('btn-save-lead');
                    var originalText = saveBtn.innerHTML;
                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px"><svg style="width:14px;height:14px;animation:spin 1s linear infinite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4m0 12v4m-7.07-3.93l2.83-2.83m8.48-8.48l2.83-2.83M2 12h4m12 0h4m-3.93 7.07l-2.83-2.83M7.76 7.76L4.93 4.93"/></svg> Saving...</span>';
                    saveBtn.style.opacity = '0.7';
                    saveBtn.style.cursor = 'not-allowed';

                    var formData = new FormData(leadForm);

                    fetch(leadForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html, application/json'
                        }
                    })
                        .then(function (response) {
                            if (response.redirected) {
                                window.location.href = response.url;
                                return;
                            }
                            if (!response.ok) {
                                return response.text().then(function (text) { throw new Error(text); });
                            }
                            window.location.href = '{{ route("admin.leads.index") }}';
                        })
                        .catch(function (err) {
                            console.error('Lead save error:', err);
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = originalText;
                            saveBtn.style.opacity = '1';
                            saveBtn.style.cursor = 'pointer';
                            isLeadSubmitting = false;
                            alert('Error saving lead. Please try again.');
                        });
                });
            }
        });

        // ─── WhatsApp Quick-Add: Auto-open lead modal from Chrome Extension ───
        document.addEventListener('DOMContentLoaded', function () {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('quick_add') === '1' && urlParams.get('phone')) {
                var phone = urlParams.get('phone');
                // Small delay to ensure modal JS is ready
                setTimeout(function () {
                    openAddLeadModal();
                    var phoneInput = document.getElementById('lead-phone');
                    if (phoneInput) phoneInput.value = phone;
                    var sourceSelect = document.getElementById('lead-source');
                    if (sourceSelect) sourceSelect.value = 'whatsapp';
                    // Focus on name field since phone is already filled
                    var nameInput = document.getElementById('lead-name');
                    if (nameInput) nameInput.focus();
                    // Clean URL to prevent re-trigger on refresh
                    window.history.replaceState({}, '', window.location.pathname);
                }, 300);
            }
        });

        function openAddLeadModal() {
            editingLeadId = null;
            currentLeadQuoteId = null;
            initialFormSnapshot = null;
            isLeadSubmitting = false;
            var saveBtn = document.getElementById('btn-save-lead');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = 'Save Lead'; saveBtn.style.opacity = '1'; saveBtn.style.cursor = 'pointer'; }
            document.getElementById('modal-title').textContent = 'Add New Lead';
            document.getElementById('lead-form').action = '{{ route('admin.leads.store') }}';
            document.getElementById('form-method').value = '';
            document.getElementById('lead-form').reset();
            var leadAssign = document.getElementById('lead-assigned');
            if (leadAssign) leadAssign.value = '{{ auth()->id() }}';
            clearProductTable();
            // Hide quote action buttons for new leads
            document.getElementById('quote-action-container').style.display = 'none';            // Hide followup section for new leads
            document.getElementById('followup-section').style.display = 'none';
            document.getElementById('lead-modal').style.display = 'flex';
            
            // Initialize Select2 after modal opens
            setTimeout(initProductSelect2, 100);
        }

        function viewLead(id) {
            fetch('{{ url("admin/leads") }}/' + id)
                .then(response => response.json())
                .then(lead => {
                    // Products section
                    let productsHtml = '';
                    if (lead.products && lead.products.length > 0) {
                        let productRows = '';
                        lead.products.forEach(p => {
                            let price = p.pivot.price || p.sale_price || p.mrp || 0;
                            let discount = p.pivot.discount || 0;
                            let finalPrice = ((price - discount) / 100).toFixed(2);
                            productRows += `<tr>
                                                                                                                                                                                        <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155">${escapeHtml(p.name)}</td>
                                                                                                                                                                                        <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155;text-align:center">${p.pivot.quantity}</td>
                                                                                                                                                                                        <td style="padding:10px 14px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:600;color:#059669">₹${finalPrice}</td>
                                                                                                                                                                                                                                                                                    </tr>`;
                        });
                        productsHtml = `<div style="margin-top:20px">
                                                                                                                                                                                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                                                                                                                                                                                        <div style="width:28px;height:28px;border-radius:8px;background:#fdf4ff;display:flex;align-items:center;justify-content:center">
                                                                                                                                                                                            <i data-lucide="package" style="width:14px;height:14px;color:#a855f7"></i>
                                                                                                                                                                                        </div>
                                                                                                                                                                                        <h4 style="margin:0;font-size:14px;font-weight:600;color:#1e293b">Products</h4>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <table style="width:100%;border-collapse:collapse;border-radius:10px;overflow:hidden;border:1px solid #f1f5f9">
                                                                                                                                                                                        <thead><tr style="background:linear-gradient(135deg,#f8fafc,#f1f5f9)">
                                                                                                                                                                                            <th style="padding:10px 14px;text-align:left;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Product</th>
                                                                                                                                                                                            <th style="padding:10px 14px;text-align:center;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Qty</th>
                                                                                                                                                                                            <th style="padding:10px 14px;text-align:left;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px">Price</th>
                                                                                                                                                                                        </tr></thead>
                                                                                                                                                                                        <tbody>${productRows}</tbody>
                                                                                                                                                                            </table>
                                                                                                                                                                    </div>`;
                    }

                    // Stage color mapping
                    let stageColors = {
                        'new': { bg: '#eff6ff', color: '#2563eb' },
                        'contacted': { bg: '#fdf4ff', color: '#a855f7' },
                        'qualified': { bg: '#f0fdf4', color: '#16a34a' },
                        'proposal': { bg: '#fffbeb', color: '#d97706' },
                        'negotiation': { bg: '#fef2f2', color: '#dc2626' },
                        'won': { bg: '#f0fdf4', color: '#16a34a' },
                        'lost': { bg: '#fef2f2', color: '#dc2626' }
                    };
                    let sc = stageColors[lead.stage] || { bg: '#f1f5f9', color: '#64748b' };

                    let content = `
                                                                                                                                                                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Lead Name</div>
                                                                                                                                                                                        <div style="font-size:15px;font-weight:600;color:#1e293b">${escapeHtml(lead.name || '-')}</div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Phone</div>
                                                                                                                                                                                        <div style="font-size:15px;font-weight:600;color:#1e293b"><a href="tel:${lead.phone}" style="color:#3b82f6;text-decoration:none">${lead.phone || '-'}</a></div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Email</div>
                                                                                                                                                                                        <div style="font-size:14px;font-weight:500;color:#334155">${escapeHtml(lead.email || '-')}</div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Source</div>
                                                                                                                                                                                        <div><span style="background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">${escapeHtml(lead.source || '-')}</span></div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Stage</div>
                                                                                                                                                                                        <div><span style="background:${sc.bg};color:${sc.color};padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600">${escapeHtml(lead.stage || '-')}</span></div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Assigned To</div>
                                                                                                                                                                                        <div style="font-size:14px;font-weight:500;color:#334155;display:flex;align-items:center;gap:6px"><span style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#8b5cf6);display:inline-flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:700">${(lead.assigned_to?.name || 'U').charAt(0).toUpperCase()}</span> ${escapeHtml(lead.assigned_to?.name || 'Unassigned')}</div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                </div>
                                                                                                                                                                                <div style="display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:20px">
                                                                                                                                                                                    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px">
                                                                                                                                                                                        <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px">Location</div>
                                                                                                                                                                                        <div style="font-size:14px;font-weight:500;color:#334155;display:flex;align-items:center;gap:4px">${escapeHtml(lead.city || '')} ${escapeHtml(lead.state || '') || '-'}</div>
                                                                                                                                                                                    </div>
                                                                                                                                                                                </div>
                                                                                                                                                                                <div style="margin-bottom:16px">
                                                                                                                                                                                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                                                                                                                                                                                        <div style="width:28px;height:28px;border-radius:8px;background:#fffbeb;display:flex;align-items:center;justify-content:center">
                                                                                                                                                                                            <i data-lucide="file-text" style="width:14px;height:14px;color:#f59e0b"></i>
                                                                                                                                                                                        </div>
                                                                                                                                                                                        <span style="font-size:14px;font-weight:600;color:#1e293b">Notes</span>
                                                                                                                                                                                    </div>
                                                                                                                                                                                    <div style="background:#f8fafc;padding:14px 16px;border-radius:10px;border:1px solid #f1f5f9;font-size:13px;color:#475569;white-space:pre-wrap;line-height:1.6">${escapeHtml(lead.notes || 'No notes')}</div>
                                                                                                                                                                                </div>
                                                                                                                                                                                ${productsHtml}
                                                                                                                                                                            `;
                    document.getElementById('view-lead-content').innerHTML = content;

                    // Reinitialize lucide icons for dynamic content
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                    // Render follow-up history in view modal
                    if (lead.followups && lead.followups.length > 0) {
                        document.getElementById('view-followup-history').style.display = 'block';
                        document.getElementById('view-followup-list').innerHTML = renderFollowupHistoryHtml(lead.followups);
                    } else {
                        document.getElementById('view-followup-history').style.display = 'none';
                    }

                    let editBtn = document.getElementById('btn-view-edit');
                    if (editBtn) {
                        editBtn.onclick = function () {
                            closeViewLeadModal();
                            editLead(id);
                        };
                    }

                    document.getElementById('view-lead-modal').style.display = 'flex';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                })
                .catch(err => {
                    console.error(err);
                    alert("Error loading lead details");
                });
        }

        function closeViewLeadModal() {
            document.getElementById('view-lead-modal').style.display = 'none';
        }

        function editLead(id) {
            editingLeadId = id;
            currentLeadQuoteId = null;
            isLeadSubmitting = false;
            var saveBtn = document.getElementById('btn-save-lead');
            if (saveBtn) { saveBtn.disabled = false; saveBtn.innerHTML = 'Save Lead'; saveBtn.style.opacity = '1'; saveBtn.style.cursor = 'pointer'; }
            document.getElementById('modal-title').textContent = 'Edit Lead';
            document.getElementById('lead-form').action = '{{ url("admin/leads") }}/' + id;
            document.getElementById('form-method').value = 'PUT';
            document.getElementById('lead-form').reset();
            clearProductTable();

            // Fetch lead data via AJAX to get quote_id and products
            fetch('{{ url("admin/leads") }}/' + id + '/edit')
                .then(function (response) { return response.json(); })
                .then(function (lead) {
                    document.getElementById('lead-name').value = lead.name || '';
                    document.getElementById('lead-source').value = lead.source || 'walk-in';
                    document.getElementById('lead-phone').value = lead.phone || '';
                    document.getElementById('lead-email').value = lead.email || '';
                    document.getElementById('lead-city').value = lead.city || '';
                    document.getElementById('lead-state').value = lead.state || '';
                    document.getElementById('lead-stage').value = lead.stage || 'new';
                    var assignedEl = document.getElementById('lead-assigned');
                    if (assignedEl) assignedEl.value = lead.assigned_to_user_id || '';
                    document.getElementById('lead-notes').value = lead.notes || '';

                    // Load products
                    if (lead.products && lead.products.length > 0) {
                        loadLeadProducts(lead.products);
                    }

                    // Handle quote action buttons
                    currentLeadQuoteId = lead.quote_id || null;
                    updateQuoteButtons();

                    // Show followup section and load history
                    document.getElementById('followup-section').style.display = 'block';
                    if (lead.followups && lead.followups.length > 0) {
                        renderFollowupHistory(lead.followups);
                    } else {
                        document.getElementById('followup-history').innerHTML = '<p style="color:#999;font-size:13px;text-align:center;padding:12px 0">No follow-ups yet</p>';
                    }
                    document.getElementById('followup-date').value = '';
                    document.getElementById('followup-message').value = '';

                    // Take snapshot for dirty checking (after a short delay for DOM to settle)
                    // Save initial state for dirty checking
                    setTimeout(() => {
                        let formData = new FormData(document.getElementById('lead-form'));
                        initialFormSnapshot = new URLSearchParams(formData).toString();
                        
                        // Initialize Select2 after modal opens and data is loaded
                        initProductSelect2();
                    }, 100);

                    // Reinitialize lucide icons for new buttons
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                    document.getElementById('lead-modal').style.display = 'flex';
                })
                .catch(function (err) {
                    console.error('Error fetching lead:', err);
                    alert('Error loading lead data.');
                });
        }

        function closeLeadModal() {
            document.getElementById('lead-modal').style.display = 'none';
            document.getElementById('lead-form').reset();
            clearProductTable();
            editingLeadId = null;
            currentLeadQuoteId = null;
            initialFormSnapshot = null;
        }

        // ====== Follow-up Functions ======
        function addFollowup() {
            if (!editingLeadId) return;
            var message = document.getElementById('followup-message').value.trim();
            var date = document.getElementById('followup-date').value;
            if (!message) { alert('Please enter a follow-up message.'); return; }

            fetch('{{ url("admin/leads") }}/' + editingLeadId + '/followups', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message, next_follow_up_date: date || null })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('followup-message').value = '';
                        document.getElementById('followup-date').value = '';
                        // Prepend to history
                        var container = document.getElementById('followup-history');
                        var noMsg = container.querySelector('p');
                        if (noMsg) noMsg.remove();
                        container.insertAdjacentHTML('afterbegin', renderFollowupItemHtml(data.followup));
                    } else {
                        alert('Error adding follow-up');
                    }
                })
                .catch(err => { console.error(err); alert('Error adding follow-up'); });
        }

        function renderFollowupHistory(followups) {
            var container = document.getElementById('followup-history');
            container.innerHTML = followups.map(f => renderFollowupItemHtml(f)).join('');
        }

        function renderFollowupHistoryHtml(followups) {
            if (!followups || followups.length === 0) return '<p style="color:#999;font-size:13px;text-align:center;padding:12px 0">No follow-ups yet</p>';
            return followups.map(f => renderFollowupItemHtml(f)).join('');
        }

        function renderFollowupItemHtml(f) {
            var userName = f.user ? f.user.name : 'Unknown';
            var date = formatFollowupDate(f.created_at);
            var nextDate = f.next_follow_up_date ? '<span style="background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:8px">Next: ' + formatFollowupDate(f.next_follow_up_date) + '</span>' : '';
            return '<div style="padding:10px 12px;background:#f8f9fa;border-left:3px solid #10b981;border-radius:4px;margin-bottom:8px;position:relative">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">' +
                '<span style="font-size:12px;font-weight:600;color:#333">' + escapeHtml(userName) + nextDate + '</span>' +
                '<span style="font-size:11px;color:#999">' + date + '</span>' +
                '</div>' +
                '<div style="font-size:13px;color:#555">' + escapeHtml(f.message) + '</div>' +
                '</div>';
        }

        function deleteFollowup(followupId) {
            if (!editingLeadId || !confirm('Delete this follow-up?')) return;
            fetch('{{ url("admin/leads") }}/' + editingLeadId + '/followups/' + followupId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        var el = document.getElementById('followup-item-' + followupId);
                        if (el) el.remove();
                    }
                });
        }

        function formatFollowupDate(dateStr) {
            if (!dateStr) return '';
            var d = new Date(dateStr);
            var day = d.getDate().toString().padStart(2, '0');
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var month = months[d.getMonth()];
            var year = d.getFullYear();
            var hours = d.getHours();
            var mins = d.getMinutes().toString().padStart(2, '0');
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return day + ' ' + month + ' ' + year + ', ' + hours + ':' + mins + ' ' + ampm;
        }

        function updateQuoteButtons() {
            var container = document.getElementById('quote-action-container');
            var convertBtn = document.getElementById('btn-convert-to-quote');
            var viewBtn = document.getElementById('btn-view-quote');

            if (!editingLeadId) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'block';
            if (currentLeadQuoteId) {
                convertBtn.style.display = 'none';
                viewBtn.style.display = 'flex';
            } else {
                convertBtn.style.display = 'flex';
                viewBtn.style.display = 'none';
            }
        }

        // ====== Dirty Checking Functions ======
        function getFormSnapshot() {
            var form = document.getElementById('lead-form');
            var data = {};
            var inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(function (el) {
                if (el.name && el.type !== 'hidden') {
                    data[el.name + '_' + (el.dataset.productId || '')] = el.value;
                }
            });
            return JSON.stringify(data);
        }

        function isFormDirty() {
            if (!initialFormSnapshot) return false;
            return getFormSnapshot() !== initialFormSnapshot;
        }

        function convertLeadToQuote() {
            if (isFormDirty()) {
                alert('You have unsaved changes. Please save the lead first before converting to a quote.');
                return;
            }

            if (!confirm('Are you sure you want to create a quote from this lead?')) return;

            fetch('{{ url("admin/leads") }}/' + editingLeadId + '/convert-to-quote', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })
                .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
                .then(function (result) {
                    if (result.ok) {
                        window.location.href = result.data.redirect;
                    } else {
                        alert(result.data.message || 'An error occurred.');
                    }
                })
                .catch(function (err) {
                    console.error('Error converting lead to quote:', err);
                    alert('An error occurred while converting the lead to a quote.');
                });
        }

        function viewLeadQuote() {
            if (isFormDirty()) {
                alert('You have unsaved changes. Please save the lead first before viewing the quote.');
                return;
            }
            window.location.href = '{{ route('admin.quotes.index') }}';
        }

        // ====== Product Selection Functions ======
        var productRowIndex = 0;

        function addSelectedProduct() {
            var sel = document.getElementById('product-selector');
            if (!sel.value) return;
            var opt = sel.options[sel.selectedIndex];
            var pid = sel.value;
            var name = opt.getAttribute('data-name');
            var price = opt.getAttribute('data-price');
            var desc = opt.getAttribute('data-desc');
            addProductRow(pid, name, price, desc, 1);
            sel.value = '';
        }

        function addProductRow(pid, name, price, desc, qty, discount) {
            // Check if product already added
            var existing = document.querySelectorAll('#selected-products-body input[data-product-id="' + pid + '"]');
            if (existing.length > 0) {
                alert('This product is already added!');
                return;
            }

            var tbody = document.getElementById('selected-products-body');
            var table = document.getElementById('selected-products-table');
            var idx = productRowIndex++;
            discount = discount || 0;

            var tr = document.createElement('tr');
            tr.id = 'product-row-' + idx;
            tr.innerHTML = '<td style="padding:8px 10px;border:1px solid #e0e0e0;font-size:13px">' + escapeHtml(name) + '</td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0"><input type="number" name="product_prices[]" value="' + parseFloat(price).toFixed(2) + '" min="0" step="0.01" data-idx="' + idx + '" oninput="updateDiscountedPrice(this.closest(\'tr\').querySelector(\'input[name=\\\'product_discounts[]\\\']\'))" style="width:100px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center;font-weight:600"></td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0">' +
                '<textarea name="product_descriptions[]" id="desc-store-' + idx + '" style="display:none">' + escapeHtml(desc || '') + '</textarea>' +
                '<div style="display:flex;align-items:center;gap:4px">' +
                '<span id="desc-preview-' + idx + '" style="flex:1;padding:4px;font-size:13px;color:#666;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:200px;display:inline-block">' + escapeHtml((desc || '').replace(/\n/g, ' ')) + '</span>' +
                '<button type="button" onclick="openDescPopup(' + idx + ')" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;cursor:pointer;padding:4px 6px;display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0" title="Edit Description"><i data-lucide="pencil" style="width:12px;height:12px"></i></button>' +
                '</div>' +
                '</td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0"><input type="number" name="product_quantities[]" value="' + qty + '" min="1" style="width:60px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center"></td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0"><input type="number" name="product_discounts[]" value="' + discount + '" min="0" data-idx="' + idx + '" oninput="updateDiscountedPrice(this)" style="width:90px;padding:4px;border:1px solid #ddd;border-radius:4px;text-align:center" placeholder="0"></td>' +
                '<td style="padding:8px 10px;border:1px solid #e0e0e0;text-align:center">' +
                '<button type="button" onclick="removeProductRow(' + idx + ')" style="background:none;border:none;color:#dc3545;cursor:pointer;font-size:16px" title="Remove">&times;</button>' +
                '<input type="hidden" name="product_ids[]" value="' + pid + '" data-product-id="' + pid + '">' +
                '</td>';
            tbody.appendChild(tr);
            table.style.display = 'table';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function updateDiscountedPrice(input) {
            // Price is now editable directly, no visual update needed
        }

        function removeProductRow(idx) {
            var row = document.getElementById('product-row-' + idx);
            if (row) row.remove();
            var tbody = document.getElementById('selected-products-body');
            if (tbody.children.length === 0) {
                document.getElementById('selected-products-table').style.display = 'none';
            }
        }

        function clearProductTable() {
            document.getElementById('selected-products-body').innerHTML = '';
            document.getElementById('selected-products-table').style.display = 'none';
            productRowIndex = 0;
        }

        function loadLeadProducts(products) {
            products.forEach(function (p) {
                var price = p.pivot && typeof p.pivot.price !== 'undefined'
                    ? (p.pivot.price / 100)
                    : ((p.mrp || p.sale_price || 0) / 100);
                var discount = p.pivot ? (p.pivot.discount / 100) : 0;
                addProductRow(p.id, p.name, price, p.description || p.pivot?.description || '', p.pivot ? p.pivot.quantity : 1, discount);
            });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // ====== Description Edit Popup ======
        var activeDescIdx = null;

        function openDescPopup(idx) {
            activeDescIdx = idx;
            var textarea = document.getElementById('desc-store-' + idx);
            if (textarea) {
                document.getElementById('desc-edit-textarea').value = textarea.value;
            }
            document.getElementById('desc-edit-popup').style.display = 'flex';
            document.getElementById('desc-edit-textarea').focus();
        }

        function closeDescPopup() {
            document.getElementById('desc-edit-popup').style.display = 'none';
            activeDescIdx = null;
        }

        function saveDescPopup() {
            if (activeDescIdx !== null) {
                var val = document.getElementById('desc-edit-textarea').value;
                var textarea = document.getElementById('desc-store-' + activeDescIdx);
                if (textarea) {
                    textarea.value = val;
                }
                var preview = document.getElementById('desc-preview-' + activeDescIdx);
                if (preview) {
                    preview.textContent = val.replace(/\n/g, ' ');
                }
            }
            closeDescPopup();
        }

        function switchView(view) {
            localStorage.setItem('leads_view_mode', view);
            if (view === 'list') {
                document.getElementById('list-view').style.display = 'block';
                document.getElementById('kanban-view').style.display = 'none';
                document.getElementById('list-view-btn').style.background = 'white';
                document.getElementById('kanban-view-btn').style.background = 'transparent';
            } else {
                document.getElementById('list-view').style.display = 'none';
                document.getElementById('kanban-view').style.display = 'block';
                document.getElementById('list-view-btn').style.background = 'transparent';
                document.getElementById('kanban-view-btn').style.background = 'white';
                // Reinitialize icons for kanban cards
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }

        function viewLeadQuote() {
            if (currentLeadQuoteId) {
                window.location.href = '{{ route("admin.quotes.index") }}?open_quote=' + currentLeadQuoteId;
            }
        }

        // Initialize Lucide icons and View Mode
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Restore preferred view mode
            var preferredView = localStorage.getItem('leads_view_mode') || 'list';
            switchView(preferredView);
        });

        // Apply column visibility from database Settings
        (function () {
            var leadSettings = @json($columnVisibility ?? []);
            Object.keys(leadSettings).forEach(function (col) {
                if (leadSettings[col] === false) {
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

        // ========== Kanban Drag & Drop ==========
        let draggedCard = null;
        let draggedLeadId = null;
        let sourceColumn = null;

        function kanbanDragStart(e) {
            draggedCard = e.target.closest('.kanban-card');
            draggedLeadId = draggedCard.getAttribute('data-lead-id');
            sourceColumn = draggedCard.closest('.kanban-cards');

            // Visual feedback
            setTimeout(function () {
                draggedCard.style.opacity = '0.4';
                draggedCard.style.transform = 'rotate(2deg) scale(0.95)';
            }, 0);

            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', draggedLeadId);
        }

        function kanbanDragEnd(e) {
            if (draggedCard) {
                draggedCard.style.opacity = '1';
                draggedCard.style.transform = 'none';
            }
            // Remove all highlights
            document.querySelectorAll('.kanban-column').forEach(function (col) {
                col.style.background = 'white';
                col.style.outline = 'none';
            });
            draggedCard = null;
            draggedLeadId = null;
            sourceColumn = null;
        }

        function kanbanDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';

            var column = e.target.closest('.kanban-column');
            if (column) {
                column.style.background = '#f0f7ff';
                column.style.outline = '2px dashed #3b82f6';
                column.style.outlineOffset = '-2px';
            }
        }

        function kanbanDragLeave(e) {
            var column = e.target.closest('.kanban-column');
            if (column && !column.contains(e.relatedTarget)) {
                column.style.background = 'white';
                column.style.outline = 'none';
            }
        }

        function kanbanDrop(e) {
            e.preventDefault();

            var targetColumn = e.target.closest('.kanban-column');
            if (!targetColumn || !draggedCard) return;

            var newStage = targetColumn.getAttribute('data-stage');
            var oldStage = sourceColumn ? sourceColumn.getAttribute('data-stage') : null;

            // Don't do anything if same column
            if (newStage === oldStage) {
                targetColumn.style.background = 'white';
                targetColumn.style.outline = 'none';
                return;
            }

            // Move card in DOM
            var targetCards = targetColumn.querySelector('.kanban-cards');
            var emptyMsg = targetCards.querySelector('.kanban-empty');
            if (emptyMsg) emptyMsg.remove();

            // Animate insertion
            draggedCard.style.opacity = '0';
            draggedCard.style.transform = 'translateY(-10px)';
            targetCards.appendChild(draggedCard);

            // Smooth appear
            requestAnimationFrame(function () {
                draggedCard.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                draggedCard.style.opacity = '1';
                draggedCard.style.transform = 'none';
                setTimeout(function () {
                    draggedCard.style.transition = 'all 0.2s';
                }, 300);
            });

            // Check if source column is now empty
            if (sourceColumn && sourceColumn.querySelectorAll('.kanban-card').length === 0) {
                sourceColumn.innerHTML = '<p class="kanban-empty" style="text-align:center;color:#999;font-size:13px;padding:20px 0">No leads</p>';
            }

            // Update column counts
            updateKanbanCounts();

            // Reset column styles
            targetColumn.style.background = 'white';
            targetColumn.style.outline = 'none';

            // AJAX update stage
            fetch('{{ url("admin/leads") }}/' + draggedLeadId + '/stage', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stage: newStage })
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Failed to update');
                    return response.json();
                })
                .then(function () {
                    showKanbanToast('Lead moved to ' + newStage.charAt(0).toUpperCase() + newStage.slice(1));
                })
                .catch(function (err) {
                    console.error('Stage update failed:', err);
                    // Revert on failure
                    if (sourceColumn) {
                        var emptyInSource = sourceColumn.querySelector('.kanban-empty');
                        if (emptyInSource) emptyInSource.remove();
                        sourceColumn.appendChild(draggedCard);
                        updateKanbanCounts();
                    }
                    showKanbanToast('Failed to update stage', true);
                });
        }

        function updateKanbanCounts() {
            document.querySelectorAll('.kanban-column').forEach(function (col) {
                var stage = col.getAttribute('data-stage');
                var count = col.querySelectorAll('.kanban-card').length;
                var countEl = col.querySelector('.kanban-count-' + stage);
                if (countEl) countEl.textContent = count;
            });
        }

        function showKanbanToast(message, isError) {
            var existing = document.getElementById('kanban-toast');
            if (existing) existing.remove();

            var toast = document.createElement('div');
            toast.id = 'kanban-toast';
            toast.textContent = message;
            toast.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:500;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.15);transition:all 0.4s cubic-bezier(0.4,0,0.2,1);transform:translateY(20px);opacity:0;' +
                (isError ? 'background:#fef2f2;color:#dc2626;border:1px solid #fecaca' : 'background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0');
            document.body.appendChild(toast);

            requestAnimationFrame(function () {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });

            setTimeout(function () {
                toast.style.transform = 'translateY(20px)';
                toast.style.opacity = '0';
                setTimeout(function () { toast.remove(); }, 400);
            }, 2500);
        }
        document.addEventListener('DOMContentLoaded', function () {
            let df = document.getElementById('created-from').value;
            let dt = document.getElementById('created-to').value;
            let defDates = [];
            if (df && dt) defDates = [df, dt];

            flatpickr("#lead-date-range-picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: defDates,
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('created-from').value = instance.formatDate(selectedDates[0], "Y-m-d");
                        document.getElementById('created-to').value = instance.formatDate(selectedDates[1], "Y-m-d");
                        document.getElementById('filter-form').submit();
                    } else if (selectedDates.length === 0) {
                        document.getElementById('created-from').value = '';
                        document.getElementById('created-to').value = '';
                        document.getElementById('filter-form').submit();
                    }
                }
            });
            
            // Re-initialize select2 if modal is ever opened
            $(document).on('shown.bs.modal', '#lead-modal', function () {
                initProductSelect2();
            });
        });

        // Initialize Select2 for product search — Premium Facebook-style UI
        function initProductSelect2() {
            if (typeof $ === 'undefined' || !$.fn.select2) {
                console.error('Select2 or jQuery is not loaded');
                return;
            }

            // Destroy previous instance if any
            if ($('#product-selector').hasClass('select2-hidden-accessible')) {
                $('#product-selector').select2('destroy');
            }

            // Inject premium select2 styles if not already present
            if (!document.getElementById('select2-premium-styles')) {
                var styleEl = document.createElement('style');
                styleEl.id = 'select2-premium-styles';
                styleEl.textContent = `
                    /* Premium Select2 Theme */
                    .select2-container--default .select2-selection--single {
                        height: 44px !important;
                        border: 1.5px solid #e2e8f0 !important;
                        border-radius: 10px !important;
                        background: #fff !important;
                        padding: 0 12px !important;
                        display: flex !important;
                        align-items: center !important;
                        transition: all 0.2s ease !important;
                        font-size: 14px !important;
                    }
                    .select2-container--default .select2-selection--single:hover {
                        border-color: #94a3b8 !important;
                    }
                    .select2-container--default.select2-container--open .select2-selection--single {
                        border-color: #6366f1 !important;
                        box-shadow: 0 0 0 3px rgba(99,102,241,0.12) !important;
                    }
                    .select2-container--default .select2-selection--single .select2-selection__rendered {
                        line-height: normal !important;
                        padding-left: 0 !important;
                        color: #334155 !important;
                        font-weight: 500 !important;
                    }
                    .select2-container--default .select2-selection--single .select2-selection__arrow {
                        height: 100% !important;
                        right: 10px !important;
                    }
                    .select2-container--default .select2-selection--single .select2-selection__arrow b {
                        border-color: #94a3b8 transparent transparent transparent !important;
                    }
                    .select2-container--default .select2-selection--single .select2-selection__placeholder {
                        color: #94a3b8 !important;
                        font-weight: 400 !important;
                    }
                    .select2-dropdown {
                        border: 1.5px solid #e2e8f0 !important;
                        border-radius: 12px !important;
                        box-shadow: 0 12px 36px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04) !important;
                        overflow: hidden !important;
                        margin-top: 6px !important;
                    }
                    .select2-container--default .select2-search--dropdown {
                        padding: 12px !important;
                        background: #f8fafc !important;
                        border-bottom: 1px solid #e2e8f0 !important;
                    }
                    .select2-container--default .select2-search--dropdown .select2-search__field {
                        border: 1.5px solid #e2e8f0 !important;
                        border-radius: 8px !important;
                        padding: 10px 14px !important;
                        font-size: 14px !important;
                        outline: none !important;
                        transition: all 0.2s !important;
                        background: #fff !important;
                    }
                    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
                        border-color: #6366f1 !important;
                        box-shadow: 0 0 0 3px rgba(99,102,241,0.12) !important;
                    }
                    .select2-results__options {
                        max-height: 260px !important;
                        padding: 6px !important;
                    }
                    .select2-container--default .select2-results__option {
                        padding: 10px 14px !important;
                        font-size: 13px !important;
                        border-radius: 8px !important;
                        margin-bottom: 2px !important;
                        color: #334155 !important;
                        transition: all 0.15s !important;
                    }
                    .select2-container--default .select2-results__option--highlighted[aria-selected] {
                        background: linear-gradient(135deg,#eef2ff,#e0e7ff) !important;
                        color: #4338ca !important;
                        font-weight: 500 !important;
                    }
                    .select2-container--default .select2-results__option[aria-selected=true] {
                        background: #f0fdf4 !important;
                        color: #15803d !important;
                        font-weight: 600 !important;
                    }
                    .select2-container--default .select2-results__option[aria-selected=true]::after {
                        content: '✓';
                        float: right;
                        font-weight: 700;
                        color: #16a34a;
                    }
                    .select2-container--default .select2-results__message {
                        padding: 16px !important;
                        color: #94a3b8 !important;
                        font-size: 13px !important;
                        text-align: center !important;
                    }
                `;
                document.head.appendChild(styleEl);
            }

            $('#product-selector').select2({
                dropdownParent: $('#lead-modal'),
                placeholder: '🔍 Search or select a product...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 0,
                language: {
                    inputTooShort: function() { return 'Type to search products...'; },
                    noResults: function() { return '😔 No products found'; },
                    searching: function() { return '🔍 Searching...'; }
                }
            });
        }
    </script>
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endpush