@extends('admin.layouts.app')

@section('title', 'Tasks')
@section('breadcrumb', 'Tasks')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Tasks</h1>
                <p class="page-description">Manage and track your tasks</p>
            </div>
            <div class="page-actions" style="display:flex;gap:10px;align-items:center">
                @if(can('tasks.write'))
                    <button class="btn btn-primary" onclick="document.getElementById('task-form').reset(); var assignEl = document.querySelector('#task-drawer select[name=assigned_to_user_id]'); if(assignEl) assignEl.value = '{{ auth()->id() }}'; openDrawer('task-drawer')"
                        style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;font-weight:600;border-radius:10px;box-shadow:0 2px 8px rgba(59,130,246,0.25);transition:all .2s">
                        <i data-lucide="plus" style="width:16px;height:16px"></i> Add Task
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="task-toast-success" id="success-toast">
            <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="task-toolbar" style="flex-wrap:wrap;gap:10px;padding:14px 20px">
        <form method="GET" id="tasks-filter-form" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;flex:1">
            {{-- Search --}}
            <div style="min-width:200px;max-width:280px;flex:1">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Search</label>
                <div style="position:relative">
                    <i data-lucide="search"
                        style="width:14px;height:14px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
                    <input type="text" name="search" id="tasks-search" value="{{ request('search') }}"
                        placeholder="Title, client, number..." autocomplete="off"
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#f8fafc;transition:all .2s"
                        onfocus="this.style.borderColor='#3b82f6';this.style.background='#fff'"
                        onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"
                        oninput="autoAjaxSearch(this.form)">
                </div>
            </div>

            {{-- Priority --}}
            <div style="min-width:110px">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Priority</label>
                <select name="priority" id="filter-priority"
                    style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc"
                    onchange="autoAjaxSearch(this.form)">
                    <option value="">All</option>
                    @foreach(['low', 'medium', 'high'] as $p)
                        <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div style="min-width:120px">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Status</label>
                <select name="status" id="filter-status"
                    style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc"
                    onchange="autoAjaxSearch(this.form)">
                    <option value="">All</option>
                    @foreach($dynamicStatuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div style="min-width:180px">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Date
                    Range</label>
                <div style="position:relative">
                    <i data-lucide="calendar"
                        style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                    <input type="text" id="task-date-range-picker" placeholder="Select Date Range" autocomplete="off"
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc;cursor:pointer">
                    <input type="hidden" name="start_date" id="filter-start-date" value="{{ request('start_date') }}">
                    <input type="hidden" name="due_date" id="filter-due-date" value="{{ request('due_date') }}">
                </div>
            </div>

            {{-- Assigned To (only for global users) --}}
            @if(can('tasks.global') || auth()->user()->isAdmin())
                <div style="min-width:140px">
                    <label
                        style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Assigned
                        To</label>
                    <select name="assigned_to" id="filter-assigned"
                        style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc"
                        onchange="autoAjaxSearch(this.form)">
                        <option value="">All</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

        </form>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;width:100%">
            <div style="display:flex;align-items:center;gap:12px">
                <div id="tasks-count-badge"
                    style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border:1px solid #e2e8f0;padding:6px 14px;border-radius:20px;display:flex;align-items:center;gap:8px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.8), 0 1px 2px rgba(0,0,0,0.04)">
                    <div
                        style="width:20px;height:20px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center">
                        <i data-lucide="layers" style="width:12px;height:12px;color:#4f46e5"></i>
                    </div>
                    <span style="font-size:13px;font-weight:600;color:#334155;letter-spacing:0.3px">
                        <span id="filtered-tasks-count" style="color:#4f46e5;font-weight:700">{{ $tasks->count() }}</span>
                        <span style="color:#64748b;font-weight:500">Tasks Found</span>
                    </span>
                </div>
            </div>

            <div style="display:flex;gap:12px;align-items:center;">
                <a href="{{ route('admin.tasks.index') }}" id="btn-clear-filters"
                    style="display:{{ request()->hasAny(['search', 'priority', 'status', 'due_date', 'assigned_to']) ? 'flex' : 'none' }};color:#64748b;text-decoration:none;font-size:13px;font-weight:500;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;transition:all 0.15s"
                    onmouseover="this.style.background='#f1f5f9';this.style.color='#ef4444'"
                    onmouseout="this.style.background='#f8fafc';this.style.color='#64748b'">
                    <i data-lucide="filter-x" style="width:14px;height:14px"></i>
                    Clear Filters
                </a>

                <div class="view-toggle">
                    <button type="button" class="view-btn active" id="btn-kanban" onclick="switchView('kanban')"
                        title="Kanban View">
                        <i data-lucide="layout-grid" style="width:16px;height:16px"></i>
                    </button>
                    <button type="button" class="view-btn" id="btn-list" onclick="switchView('list')" title="List View">
                        <i data-lucide="list" style="width:16px;height:16px"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban Board -->
    @php
        $dynamicStatuses = \App\Models\Task::getDynamicStatuses();
        $statusMeta = [
            'todo' => ['icon' => 'circle', 'gradient' => 'linear-gradient(135deg,#64748b,#475569)', 'dot' => '#94a3b8', 'bg' => '#f8fafc', 'border' => '#e2e8f0'],
            'doing' => ['icon' => 'loader', 'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)', 'dot' => '#60a5fa', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
            'done' => ['icon' => 'check-circle-2', 'gradient' => 'linear-gradient(135deg,#22c55e,#16a34a)', 'dot' => '#4ade80', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
        ];
        $defaultMeta = ['icon' => 'circle-dot', 'gradient' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)', 'dot' => '#a78bfa', 'bg' => '#faf5ff', 'border' => '#ddd6fe'];
    @endphp

    <div class="kanban-board" id="kanban-board">
        @foreach ($dynamicStatuses as $idx => $status)
            @php
                $statusTasks = $tasks->where('status', $status);
                $meta = $statusMeta[$status] ?? $defaultMeta;
            @endphp
            <div class="kb-column" data-status="{{ $status }}" ondragover="kanbanDragOver(event)"
                ondragleave="kanbanDragLeave(event)" ondrop="kanbanDrop(event, '{{ $status }}')"
                style="--col-bg:{{ $meta['bg'] }};--col-border:{{ $meta['border'] }};--col-dot:{{ $meta['dot'] }}">
                <!-- Column Header -->
                <div class="kb-col-header">
                    <div class="kb-col-header-left">
                        <span class="kb-col-dot" style="background:{{ $meta['dot'] }}"></span>
                        <span class="kb-col-title">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                        <span class="kb-col-count kanban-count-{{ $status }}">{{ $statusTasks->count() }}</span>
                    </div>
                </div>

                <!-- Column Body -->
                <div class="kb-col-body kanban-cards" data-status="{{ $status }}" id="column-{{ $status }}">
                    @forelse($statusTasks as $task)
                        <div class="kb-card kanban-card" draggable="true" data-task-id="{{ $task->id }}" id="task-{{ $task->id }}"
                            data-title="{{ strtolower($task->title) }}" ondragstart="kanbanDragStart(event)"
                            ondragend="kanbanDragEnd(event)" onclick="openViewTaskModal({{ $task->id }})" style="cursor: pointer;">

                            <!-- Card Top: Tags -->
                            <div class="kb-card-top">
                                @php
                                    $prioMap = ['high' => ['label' => 'High', 'class' => 'prio-high'], 'medium' => ['label' => 'Medium', 'class' => 'prio-medium'], 'low' => ['label' => 'Low', 'class' => 'prio-low']];
                                    $prio = $prioMap[$task->priority] ?? $prioMap['low'];
                                @endphp
                                @php
                                    $clientName = null;
                                    $leadName = null;
                                    if ($task->entity_type === 'client' && $task->clientEntity) {
                                        $clientName = $task->clientEntity->business_name ?: $task->clientEntity->contact_name;
                                        if ($task->clientEntity->lead) {
                                            $leadName = $task->clientEntity->lead->name;
                                        }
                                    } elseif ($task->entity_type === 'lead' && $task->leadEntity) {
                                        $clientName = $task->leadEntity->name . ' (Lead)';
                                    } elseif ($task->project_id && $task->project) {
                                        if ($task->project->client) {
                                            $clientName = $task->project->client->business_name ?: $task->project->client->contact_name;
                                            if ($task->project->client->lead) {
                                                $leadName = $task->project->client->lead->name;
                                            }
                                        } elseif ($task->project->lead) {
                                            $clientName = $task->project->lead->name . ' (Lead)';
                                        }
                                    }
                                @endphp
                                @if($task->title)
                                    <span class="kb-entity-tag"
                                        style="max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        title="{{ $task->title }}">
                                        {{ str()->limit($task->title, 20) }}
                                    </span>
                                @endif
                                @if($task->project)
                                    <span class="kb-entity-tag" style="background:#eef2ff;color:#4f46e5;border:1px solid #c7d2fe;">
                                        <i data-lucide="link" style="width:10px;height:10px"></i>
                                        {{ $task->project->project_id_code }}
                                    </span>
                                @endif
                                <span class="kb-prio {{ $prio['class'] }}">
                                    <span class="kb-prio-dot"></span> {{ $prio['label'] }}
                                </span>
                            </div>

                            <!-- Card Title -->
                            <h4 class="kb-card-title">{{ $clientName ?? $task->title }}</h4>

                            <!-- Card Description Preview -->
                            @if($task->description)
                                <div class="kb-card-desc-row">
                                    <p class="kb-card-desc">{{ Str::limit(str_replace("\n", ' ', $task->description), 55) }}</p>
                                </div>
                            @endif

                            <!-- Card Meta: Contact + Due -->
                            <div class="kb-card-meta">
                                @if($task->contact_phone)
                                    <a href="tel:{{ $task->contact_phone }}" class="kb-meta-item kb-meta-phone"
                                        onclick="event.stopPropagation()">
                                        <i data-lucide="phone" style="width:12px;height:12px"></i>
                                        {{ $task->contact_phone }}
                                    </a>
                                @endif
                                @if($task->due_at)
                                    <span class="kb-meta-item {{ $task->isOverdue() ? 'kb-meta-overdue' : '' }}">
                                        <i data-lucide="calendar" style="width:12px;height:12px"></i>
                                        {{ $task->due_at->format('d M Y') }}
                                        @if($task->isOverdue())
                                            <span class="kb-overdue-badge">Overdue</span>
                                        @endif
                                    </span>
                                @endif
                            </div>

                            <!-- Card Footer: Avatar + Actions -->
                            <div class="kb-card-footer">
                                <div class="kb-avatar-row">
                                    <span
                                        class="kb-avatar">{{ $task->assignedUsers->isNotEmpty() ? strtoupper(substr($task->assignedUsers->first()->name, 0, 2)) : '?' }}</span>
                                    <span class="kb-avatar-name">{{ $task->assignedUsers->isNotEmpty() ? $task->assignedUsers->pluck('name')->implode(', ') : 'Unassigned' }}</span>
                                </div>
                                <div class="kb-card-actions" draggable="false" onmousedown="event.stopPropagation()">
                                    <button type="button" class="kb-action-btn kb-action-edit"
                                        onclick="event.stopPropagation(); openViewTaskModal({{ $task->id }})" draggable="false"
                                        title="View">
                                        <i data-lucide="eye" style="width:13px;height:13px"></i>
                                    </button>
                                    @if(can('tasks.write'))
                                        <button type="button" class="kb-action-btn kb-action-edit"
                                            onclick="event.stopPropagation(); openEditTaskModal({{ $task->id }})" draggable="false"
                                            title="Edit">
                                            <i data-lucide="pencil" style="width:13px;height:13px"></i>
                                        </button>
                                    @endif
                                    @if(can('tasks.delete'))
                                        <button type="button" onclick="ajaxDelete('{{ route('admin.tasks.destroy', $task->id) }}')" class="kb-action-btn kb-action-delete" draggable="false"
                                                onclick="event.stopPropagation()" title="Delete">
                                                <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                                            </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="kb-empty">
                            <div class="kb-empty-icon">
                                <i data-lucide="inbox" style="width:28px;height:28px"></i>
                            </div>
                            <p>No tasks yet</p>
                            <span>Drag a task here or create new</span>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- List View -->
    <div class="list-view-container" id="list-view" style="display:none;">
        <table class="list-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Contact</th>
                    <th>Due Date</th>
                    <th>Assignee</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr class="list-row" data-title="{{ strtolower($task->title) }}">
                        <td>
                            <div style="font-weight:600;color:#1e293b;margin-bottom:4px">{{ $task->title }}</div>
                            @if($task->project)
                                <span class="kb-entity-tag"
                                    style="background:transparent;border:1px solid #e2e8f0;color:#64748b;margin-top:2px;display:inline-flex">
                                    <i data-lucide="link" style="width:10px;height:10px;margin-right:3px"></i>
                                    {{ $task->project->project_id_code }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @php
                                $clientName = '—';
                                $leadName = null;
                                if ($task->entity_type === 'client' && $task->clientEntity) {
                                    $clientName = $task->clientEntity->business_name ?: $task->clientEntity->contact_name;
                                    if ($task->clientEntity->lead) {
                                        $leadName = $task->clientEntity->lead->name;
                                    }
                                } elseif ($task->entity_type === 'lead' && $task->leadEntity) {
                                    $clientName = $task->leadEntity->name . ' (Lead)';
                                } elseif ($task->project_id && $task->project) {
                                    if ($task->project->client) {
                                        $clientName = $task->project->client->business_name ?: $task->project->client->contact_name;
                                        if ($task->project->client->lead) {
                                            $leadName = $task->project->client->lead->name;
                                        }
                                    } elseif ($task->project->lead) {
                                        $clientName = $task->project->lead->name . ' (Lead)';
                                    }
                                }
                            @endphp
                            <span style="font-size:13px;color:#1e293b;font-weight:500">{{ $clientName }}</span>
                            @if($leadName)
                                <br><span style="font-size:11px;color:#059669;font-weight:500"><i data-lucide="user"
                                        style="width:10px;height:10px;vertical-align:middle"></i> Lead: {{ $leadName }}</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $prioMap = ['high' => ['label' => 'High', 'class' => 'prio-high'], 'medium' => ['label' => 'Medium', 'class' => 'prio-medium'], 'low' => ['label' => 'Low', 'class' => 'prio-low']];
                                $prio = $prioMap[$task->priority] ?? $prioMap['low'];
                            @endphp
                            <span class="kb-prio {{ $prio['class'] }}">
                                <span class="kb-prio-dot"></span> {{ $prio['label'] }}
                            </span>
                        </td>
                        <td>
                            <span
                                style="background:#f1f5f9;color:#475569;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </td>
                        <td>
                            @if($task->contact_phone)
                                <a href="tel:{{ $task->contact_phone }}"
                                    style="color:#3b82f6;text-decoration:none;font-size:13px;display:flex;align-items:center;gap:4px">
                                    <i data-lucide="phone" style="width:12px;height:12px"></i> {{ $task->contact_phone }}
                                </a>
                            @else
                                <span style="color:#94a3b8;font-size:13px">—</span>
                            @endif
                        </td>
                        <td>
                            @if($task->due_at)
                                <span
                                    style="font-size:13px;color:{{ $task->isOverdue() ? '#dc2626' : '#64748b' }};display:flex;align-items:center;gap:4px">
                                    <i data-lucide="calendar" style="width:12px;height:12px"></i>
                                    {{ $task->due_at->format('d M Y') }}
                                </span>
                            @else
                                <span style="color:#94a3b8;font-size:13px">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="kb-avatar-row">
                                <span
                                    class="kb-avatar">{{ $task->assignedUsers->isNotEmpty() ? strtoupper(substr($task->assignedUsers->first()->name, 0, 2)) : '?' }}</span>
                                <span class="kb-avatar-name">{{ $task->assignedUsers->isNotEmpty() ? $task->assignedUsers->pluck('name')->implode(', ') : 'Unassigned' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="list-actions">
                                <button type="button" class="kb-action-btn kb-action-edit"
                                    onclick="openViewTaskModal({{ $task->id }})" title="View">
                                    <i data-lucide="eye" style="width:14px;height:14px"></i>
                                </button>
                                @if(can('tasks.write'))
                                    <button type="button" class="kb-action-btn kb-action-edit"
                                        onclick="openEditTaskModal({{ $task->id }})" title="Edit">
                                        <i data-lucide="pencil" style="width:14px;height:14px"></i>
                                    </button>
                                @endif
                                @if(can('tasks.delete'))
                                    <button type="button" onclick="ajaxDelete('{{ route('admin.tasks.destroy', $task->id) }}')" class="kb-action-btn kb-action-delete" title="Delete">
                                            <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                                        </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8">
                            <i data-lucide="inbox" style="width:36px;height:36px;margin-bottom:12px;color:#cbd5e1"></i>
                            <p style="margin:0;font-size:14px;font-weight:600;color:#64748b">No tasks yet</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Task Drawer --}}
    <div id="drawer-overlay" class="overlay" onclick="closeDrawer('task-drawer')"></div>
    <div id="task-drawer" class="drawer drawer-lg">
        <div class="drawer-header">
            <div>
                <h3 class="drawer-title">Add New Task</h3>
            </div>
            <button class="drawer-close" onclick="closeDrawer('task-drawer')"><i data-lucide="x"></i></button>
        </div>
        <div class="drawer-body">
            <form id="task-form" method="POST" action="{{ route('admin.tasks.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label required">Task Title</label>
                    <input type="text" name="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-input" placeholder="Enter contact number">
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="datetime-local" name="due_at" class="form-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Client (Optional)</label>
                    <select name="client_id" class="form-select">
                        <option value="">None (Internal Task / Other Entity)</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->business_name ?: $c->contact_name }}</option>
                        @endforeach
                    </select>
                </div>
                @if(can('tasks.global') || auth()->user()->isAdmin())
                    <div class="form-group">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to_user_id" class="form-select">
                            <option value="">Select user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $user->id == auth()->id() ? 'selected' : '' }}>{{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="assigned_to_user_id" value="{{ auth()->id() }}">
                @endif
            </form>
        </div>
        <div class="drawer-footer">
            <button class="btn btn-outline" onclick="closeDrawer('task-drawer')">Cancel</button>
            <button class="btn btn-primary" onclick="document.getElementById('task-form').submit()">Save Task</button>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="edit-task-modal-overlay" class="overlay" onclick="closeEditTaskModal()"></div>
    <div id="edit-task-modal" class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Task</h3>
            <button class="modal-close" onclick="closeEditTaskModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="edit-task-form" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label required">Task Title</label>
                    <input type="text" name="title" id="edit-task-title" class="form-input" required>
                </div>
                @if(can('tasks.global') || auth()->user()->isAdmin())
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit-task-description" class="form-textarea" rows="3"></textarea>
                    </div>
                @else
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit-task-description" class="form-textarea" rows="3" readonly
                            style="background-color: #e0f2fe; color: #334155; cursor: not-allowed;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Add New Description (Optional)</label>
                        <textarea name="additional_description" id="edit-task-additional-description" class="form-textarea"
                            rows="2" placeholder="Write new description here to append..."></textarea>
                    </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <div id="edit-task-contact-view"
                        style="padding:10px 12px;background:#f9f9f9;border:1px solid #ddd;border-radius:6px;font-size:14px;display:flex;align-items:center;gap:6px;min-height:42px">
                        <span style="color:#999">—</span>
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit-task-status" class="form-select">
                            @foreach(\App\Models\Task::getDynamicStatuses() as $statusVal)
                                <option value="{{ $statusVal }}">{{ ucfirst(str_replace('_', ' ', $statusVal)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" id="edit-task-priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="form-row form-row-2">
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="datetime-local" name="due_at" id="edit-task-due" class="form-input">
                    </div>
                </div>
                <div class="form-row form-row-2">
                    @if(can('tasks.global') || auth()->user()->isAdmin())
                        <div class="form-group">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to_user_id" id="edit-task-assigned" class="form-select">
                                <option value="">Select user</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="assigned_to_user_id" id="edit-task-assigned" value="{{ auth()->id() }}">
                    @endif
                </div>

                <!-- Micro Tasks -->
                <div class="form-group" style="margin-top:16px">
                    <label class="form-label"
                        style="display:flex;align-items:center;gap:6px;justify-content:space-between;">
                        <span style="display:flex;align-items:center;gap:6px">
                            <i data-lucide="list-checks" style="width:14px;height:14px;color:#10b981"></i>
                            Micro Tasks
                            <span id="micro-task-count-badge"
                                style="background:#d1fae5;color:#10b981;padding:1px 8px;border-radius:10px;font-size:11px;font-weight:600">0</span>
                        </span>
                        @if(can('tasks.write'))
                            <button type="button" onclick="addNewMicroTaskUI()"
                                style="border:none;background:none;color:#3b82f6;cursor:pointer;font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px">
                                + Add Nnew
                            </button>
                        @endif
                    </label>
                    <div id="task-micro-tasks-list" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">
                        <div style="padding:12px;text-align:center;color:#999;font-size:12px;background:#f8fafc;">Loading...
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="form-group" style="margin-top:16px">
                    <label class="form-label" style="display:flex;align-items:center;gap:6px">
                        <i data-lucide="clock" style="width:14px;height:14px;color:#3b82f6"></i>
                        Activity Timeline
                        <span id="activity-count-badge"
                            style="background:#f0f4ff;color:#3b82f6;padding:1px 8px;border-radius:10px;font-size:11px;font-weight:600">0</span>
                    </label>
                    <div id="task-activities-timeline"
                        style="max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:8px">
                        <p style="text-align:center;color:#999;font-size:12px;margin:12px 0">No activities yet</p>
                    </div>
                </div>

                <!-- Add Activity -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-top:4px">
                    <p style="font-size:12px;font-weight:600;color:#475569;margin:0 0 8px">Add Activity</p>
                    <div style="display:flex;gap:8px;align-items:flex-end">
                        <select id="activity-type"
                            style="padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;width:130px;background:white">
                            <option value="note">📝 Note</option>
                            <option value="client_reply">💬 Client Reply</option>
                            <option value="revision">🔄 Revision</option>
                        </select>
                        <select id="activity-mention" style="padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;width:130px;background:white">
                            <option value="">👤 No Mention</option>
                            @foreach($globalTaskUsers as $gu)
                                <option value="{{ $gu->id }}">@ {{ $gu->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="activity-message" placeholder="Write activity note..."
                            style="flex:1;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:12px">
                        <button type="button" onclick="submitActivity()"
                            style="background:#3b82f6;color:white;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:500;white-space:nowrap">Add</button>
                    </div>
                </div>
                <div class="modal-footer" style="padding:16px 0 0;margin:0">
                    <button type="button" class="btn btn-outline" onclick="closeEditTaskModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Task Description View Popup -->
    <div id="tdesc-view-popup"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(4px)">
        <div
            style="background:white;border-radius:16px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.25);overflow:hidden;animation:modalIn .25s ease">
            <div
                style="padding:18px 24px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px">
                    <i data-lucide="file-text" style="width:18px;height:18px;color:#3b82f6"></i> Task Description
                </h3>
                <button onclick="closeTDescPopup()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all .15s"
                    onmouseover="this.style.background='#e2e8f0'"
                    onmouseout="this.style.background='#f1f5f9'">&times;</button>
            </div>
            <div style="padding:24px">
                <textarea id="tdesc-view-textarea" rows="8" readonly
                    style="width:100%;padding:14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;resize:vertical;outline:none;font-family:inherit;line-height:1.7;background:#f8fafc;color:#334155;"></textarea>
            </div>
            <div
                style="padding:14px 24px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;background:#fafbfc">
                <button type="button" onclick="closeTDescPopup()"
                    style="padding:9px 22px;background:linear-gradient(135deg,#64748b,#475569);color:white;border:none;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(100,116,139,0.3);transition:all .15s"
                    onmouseover="this.style.transform='translateY(-1px)'"
                    onmouseout="this.style.transform=''">Close</button>
            </div>
        </div>
    </div>

    <!-- View Task Modal -->
    <div id="view-task-modal-overlay" class="overlay" onclick="closeViewTaskModal()" style="z-index: 1000;"></div>
    <div id="view-task-modal" class="modal" style="z-index: 1001;">
        <div class="modal-header">
            <h3 class="modal-title">Task Details</h3>
            <button class="modal-close" onclick="closeViewTaskModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <div style="margin-bottom: 20px;">
                <h4 id="view-task-title" style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #1e293b;"></h4>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <span id="view-task-status" class="badge" style="font-size: 12px; padding: 4px 8px;"></span>
                    <span id="view-task-priority" class="badge" style="font-size: 12px; padding: 4px 8px;"></span>
                </div>
            </div>

            <div
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div>
                    <p
                        style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin: 0 0 4px 0;">
                        Assigned To</p>
                    <p id="view-task-assignee" style="margin: 0; font-size: 14px; color: #334155; font-weight: 500;"></p>
                </div>
                <div>
                    <p
                        style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin: 0 0 4px 0;">
                        Due Date</p>
                    <p id="view-task-due" style="margin: 0; font-size: 14px; color: #334155; font-weight: 500;"></p>
                </div>
                <div>
                    <p
                        style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin: 0 0 4px 0;">
                        Contact Number</p>
                    <p id="view-task-contact" style="margin: 0; font-size: 14px; color: #3b82f6; font-weight: 500;"></p>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <p style="font-size: 12px; font-weight: 600; color: #475569; margin: 0 0 8px 0;">Description</p>
                <div id="view-task-desc"
                    style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 14px; color: #334155; line-height: 1.6; white-space: pre-wrap; min-height: 60px;">
                </div>
            </div>

            <div style="margin-top: 24px;">
                <p
                    style="font-size: 12px; font-weight: 600; color: #475569; margin: 0 0 8px 0; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="list-checks" style="width: 14px; height: 14px;"></i> Micro Tasks
                    <span id="view-task-micro-count"
                        style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:10px; font-size:11px;">0</span>
                </p>
                <div id="view-task-micro-tasks"
                    style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; max-height: 200px; overflow-y: auto;">
                </div>
            </div>
        </div>
        <div class="modal-footer"
            style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; margin: 0; display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-outline" onclick="closeViewTaskModal()"
                style="background: white;">Close</button>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* ═══════════════════════════════════════════
                                                                                                       PREMIUM KANBAN BOARD STYLES
                                                                                                       ═══════════════════════════════════════════ */

        /* ── Toast ── */
        .task-toast-success {
            padding: 14px 22px;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #065f46;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 14px;
            animation: slideDown .4s ease;
            box-shadow: 0 2px 12px rgba(34, 197, 94, 0.12);
        }

        /* ── Toolbar ── */
        .task-toolbar {
            background: white;
            padding: 14px 20px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .task-search-wrap {
            flex: 1;
            position: relative;
            max-width: 420px;
        }

        .task-search-icon {
            width: 16px;
            height: 16px;
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .task-search-input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            background: #f8fafc;
            transition: all .2s;
            outline: none;
        }

        .task-search-input:focus {
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .task-toolbar-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .task-total-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #f1f5f9;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        /* ── Board ── */
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 24px;
            min-height: calc(100vh - 300px);
            scroll-behavior: smooth;
        }

        .kanban-board::-webkit-scrollbar {
            height: 8px;
        }

        .kanban-board::-webkit-scrollbar-track {
            background: transparent;
        }

        .kanban-board::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 20px;
        }

        /* ── Column ── */
        .kb-column {
            min-width: 330px;
            flex: 1;
            background: var(--col-bg, #f8fafc);
            border: 1.5px solid var(--col-border, #e2e8f0);
            border-radius: 16px;
            padding: 0;
            display: flex;
            flex-direction: column;
            transition: all .25s ease;
        }

        .kb-column.drag-over {
            border-color: #3b82f6 !important;
            background: #eff6ff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
            transform: scale(1.01);
        }

        /* ── Column Header ── */
        .kb-col-header {
            padding: 16px 18px 14px;
            border-bottom: 1.5px solid var(--col-border, #e2e8f0);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kb-col-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kb-col-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.06);
            flex-shrink: 0;
        }

        .kb-col-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.01em;
        }

        .kb-col-count {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 2px 9px;
            border-radius: 20px;
            min-width: 24px;
            text-align: center;
        }

        /* ── Column Body ── */
        .kb-col-body {
            padding: 12px 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
            overflow-y: auto;
            max-height: calc(100vh - 340px);
            min-height: 80px;
        }

        .kb-col-body::-webkit-scrollbar {
            width: 5px;
        }

        .kb-col-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .kb-col-body::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            border-radius: 20px;
        }

        /* ── Card ── */
        .kb-card {
            background: white;
            border: 1px solid #e8ecf0;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: grab;
            transition: all .2s ease;
            user-select: none;
            position: relative;
        }

        .kb-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .kb-card:active {
            cursor: grabbing;
        }

        .kb-card.dragging {
            opacity: 0.45;
            transform: scale(0.96) rotate(1deg);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        /* ── Card Top ── */
        .kb-card-top {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        /* ── Priority ── */
        .kb-prio {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        .kb-prio-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .prio-high {
            background: #fef2f2;
            color: #dc2626;
        }

        .prio-high .kb-prio-dot {
            background: #ef4444;
        }

        .prio-medium {
            background: #fffbeb;
            color: #b45309;
        }

        .prio-medium .kb-prio-dot {
            background: #f59e0b;
        }

        .prio-low {
            background: #f0fdf4;
            color: #15803d;
        }

        .prio-low .kb-prio-dot {
            background: #22c55e;
        }

        /* ── Entity Tag ── */
        .kb-entity-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 3px 8px;
            background: #f5f3ff;
            color: #7c3aed;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        /* ── Card Title ── */
        .kb-card-title {
            margin: 0 0 6px;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
            letter-spacing: -0.01em;
        }

        /* ── Description ── */
        .kb-card-desc-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 8px;
        }

        .kb-card-desc {
            margin: 0;
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.5;
            flex: 1;
        }

        .kb-desc-btn {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            cursor: pointer;
            padding: 3px 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            flex-shrink: 0;
            transition: all .15s;
        }

        .kb-desc-btn:hover {
            background: #dbeafe;
            transform: scale(1.08);
        }

        /* ── Card Meta ── */
        .kb-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .kb-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #64748b;
            padding: 3px 8px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #f1f5f9;
        }

        .kb-meta-phone {
            color: #3b82f6;
            text-decoration: none;
            border-color: #dbeafe;
            background: #eff6ff;
        }

        .kb-meta-phone:hover {
            background: #dbeafe;
        }

        .kb-meta-overdue {
            color: #dc2626;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .kb-overdue-badge {
            font-size: 10px;
            font-weight: 700;
            background: #dc2626;
            color: white;
            padding: 1px 6px;
            border-radius: 10px;
            margin-left: 2px;
        }

        /* ── Card Footer ── */
        .kb-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }

        .kb-avatar-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kb-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.03em;
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
        }

        .kb-avatar-name {
            font-size: 12px;
            color: #475569;
            font-weight: 500;
        }

        .kb-card-actions {
            display: flex;
            gap: 4px;
            opacity: 0;
            transition: opacity .2s ease;
        }

        .kb-card:hover .kb-card-actions {
            opacity: 1;
        }

        .kb-action-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
        }

        .kb-action-edit {
            background: #fffbeb;
            color: #d97706;
        }

        .kb-action-edit:hover {
            background: #fef3c7;
            transform: scale(1.08);
        }

        .kb-action-delete {
            background: #fef2f2;
            color: #ef4444;
        }

        .kb-action-delete:hover {
            background: #fecaca;
            transform: scale(1.08);
        }

        /* ── Empty State ── */
        .kb-empty {
            text-align: center;
            padding: 32px 16px;
            color: #94a3b8;
        }

        .kb-empty-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: #cbd5e1;
        }

        .kb-empty p {
            margin: 0 0 4px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }

        .kb-empty span {
            font-size: 12px;
            color: #94a3b8;
        }

        /* ── View Toggle ── */
        .view-toggle {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            gap: 4px;
        }

        .view-btn {
            background: transparent;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
        }

        .view-btn:hover {
            color: #1e293b;
        }

        .view-btn.active {
            background: white;
            color: #3b82f6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* ── List View ── */
        .list-view-container {
            background: white;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(0, 0, 0, 0.03);
            overflow-x: auto;
            margin-bottom: 24px;
        }

        .list-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .list-table th {
            background: #f8fafc;
            padding: 14px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1.5px solid #e2e8f0;
        }

        .list-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .list-row {
            transition: all .15s;
        }

        .list-row:hover {
            background: #f8fafc;
        }

        .list-actions {
            display: flex;
            gap: 6px;
        }

        /* ── Animations ── */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@push('scripts')
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize Flatpickr for Tasks Date Range
        document.addEventListener('DOMContentLoaded', function () {
            let startD = document.getElementById('filter-start-date').value;
            let dueD = document.getElementById('filter-due-date').value;
            let defDates = [];
            if (startD && dueD) defDates = [startD, dueD];

            flatpickr("#task-date-range-picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: defDates,
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('filter-start-date').value = instance.formatDate(selectedDates[0], "Y-m-d");
                        document.getElementById('filter-due-date').value = instance.formatDate(selectedDates[1], "Y-m-d");
                        if (typeof autoAjaxSearch === 'function') autoAjaxSearch(document.getElementById('tasks-filter-form'));
                    } else if (selectedDates.length === 0) {
                        document.getElementById('filter-start-date').value = '';
                        document.getElementById('filter-due-date').value = '';
                        if (typeof autoAjaxSearch === 'function') autoAjaxSearch(document.getElementById('tasks-filter-form'));
                    }
                }
            });
        });
        // Store current user info for micro task permissions
        var currentUserRoleId = {{ auth()->user()->role_id ?? 'null' }};
        var currentUserIsAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};

        // ═══════════════════════════════════════
        //  DRAG & DROP
        // ═══════════════════════════════════════
        function kanbanDragStart(ev) {
            const card = ev.target.closest('.kanban-card');
            ev.dataTransfer.setData('text', card.getAttribute('data-task-id'));
            card.classList.add('dragging');
        }

        function kanbanDragEnd(ev) {
            const card = ev.target.closest('.kanban-card');
            if (card) card.classList.remove('dragging');
            document.querySelectorAll('.kb-column').forEach(col => col.classList.remove('drag-over'));
        }

        function kanbanDragOver(ev) {
            ev.preventDefault();
            const column = ev.target.closest('.kb-column');
            if (column) column.classList.add('drag-over');
        }

        function kanbanDragLeave(ev) {
            const column = ev.target.closest('.kb-column');
            if (column && !column.contains(ev.relatedTarget)) {
                column.classList.remove('drag-over');
            }
        }

        function kanbanDrop(ev, newStatus) {
            ev.preventDefault();
            const column = ev.target.closest('.kb-column');
            if (column) column.classList.remove('drag-over');

            const taskId = ev.dataTransfer.getData('text');
            if (!taskId) return;

            const card = document.getElementById('task-' + taskId);
            if (!card) return;

            const targetColumn = document.getElementById('column-' + newStatus);
            const oldStatus = card.closest('.kb-column').getAttribute('data-status');
            if (oldStatus === newStatus) return;

            // Remove empty state if present
            const emptyMsg = targetColumn.querySelector('.kb-empty');
            if (emptyMsg) emptyMsg.remove();

            targetColumn.appendChild(card);
            updateCounts();
            updateTaskStatus(taskId, newStatus, oldStatus);
        }

        function updateCounts() {
            document.querySelectorAll('.kb-column').forEach(col => {
                const status = col.getAttribute('data-status');
                const count = col.querySelectorAll('.kanban-card').length;
                const countBadge = col.querySelector('.kanban-count-' + status);
                if (countBadge) countBadge.textContent = count;
            });
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}';

        function updateTaskStatus(taskId, newStatus, oldStatus) {
            fetch(`/admin/tasks/${taskId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        revertCard(taskId, oldStatus);
                        alert('Failed to update task status.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    revertCard(taskId, oldStatus);
                });
        }

        function revertCard(taskId, oldStatus) {
            const oldColumn = document.getElementById('column-' + oldStatus);
            const card = document.getElementById('task-' + taskId);
            if (oldColumn && card) {
                oldColumn.appendChild(card);
                updateCounts();
            }
        }

        // ═══════════════════════════════════════
        //  VIEW SWITCHER
        // ═══════════════════════════════════════
        function switchView(view) {
            const kbBoard = document.getElementById('kanban-board');
            const listView = document.getElementById('list-view');
            const btnKb = document.getElementById('btn-kanban');
            const btnList = document.getElementById('btn-list');

            if (view === 'list') {
                kbBoard.style.display = 'none';
                listView.style.display = 'block';
                btnList.classList.add('active');
                btnKb.classList.remove('active');
            } else {
                kbBoard.style.display = 'flex';
                listView.style.display = 'none';
                btnKb.classList.add('active');
                btnList.classList.remove('active');
            }
            localStorage.setItem('tasks_view_pref', view);
        }

        // ═══════════════════════════════════════
        //  VIEW SWITCHER RESTORE
        // ═══════════════════════════════════════
        function initTasksView() {
            // Restore view preference
            const savedView = localStorage.getItem('tasks_view_pref');
            if (savedView === 'list') {
                switchView('list');
            }

            // Auto-hide success toast
            const toast = document.getElementById('success-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-12px)';
                    toast.style.transition = 'all .4s ease';
                    setTimeout(() => toast.remove(), 400);
                }, 4000);
            }
        }
        
        document.addEventListener('DOMContentLoaded', initTasksView);
        document.addEventListener('turbo:render', initTasksView);

        // ═══════════════════════════════════════
        //  EDIT MODAL
        // ═══════════════════════════════════════
        var tasksData = {};
        @foreach($tasks as $t)
            tasksData[{{ $t->id }}] = @json($t);
        @endforeach

            function openEditTaskModal(taskId) {
                var task = tasksData[taskId];
                if (!task) { alert('Task not found'); return; }
                document.getElementById('edit-task-form').action = '/admin/tasks/' + task.id;
                document.getElementById('edit-task-title').value = task.title;
                document.getElementById('edit-task-description').value = task.description || '';
                var addDesc = document.getElementById('edit-task-additional-description');
                if (addDesc) addDesc.value = '';

                var contactEl = document.getElementById('edit-task-contact-view');
                if (contactEl) {
                    var displayPhone = task.contact_phone || task.contact_number;
                    if (displayPhone) {
                        contactEl.innerHTML = '<i data-lucide="phone" style="width:14px;height:14px;color:#3b82f6"></i> <a href="tel:' + displayPhone + '" style="color:#3b82f6;text-decoration:none">' + displayPhone + '</a>';
                    } else {
                        contactEl.innerHTML = '<span style="color:#999">—</span>';
                    }
                }

                document.getElementById('edit-task-status').value = task.status;
                document.getElementById('edit-task-priority').value = task.priority;

                if (task.due_at) {
                    var date = new Date(task.due_at);
                    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
                    document.getElementById('edit-task-due').value = date.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit-task-due').value = '';
                }

                var assignedSelect = document.getElementById('edit-task-assigned');
                if (assignedSelect) {
                    assignedSelect.value = task.assigned_to_user_id || '';
                }

                document.getElementById('edit-task-modal').classList.add('active');
                document.getElementById('edit-task-modal-overlay').classList.add('active');

                // Set current task ID for micro tasks
                window.currentEditTaskId = task.id;

                // Render micro tasks and activities
                renderMicroTasksList(task.micro_tasks || []);
                renderActivityTimeline(task.activities || []);

                lucide.createIcons();
            }

        function closeEditTaskModal() {
            document.getElementById('edit-task-modal').classList.remove('active');
            document.getElementById('edit-task-modal-overlay').classList.remove('active');
            window.currentEditTaskId = null;
        }

        function renderMicroTasksList(microTasks) {
            var container = document.getElementById('task-micro-tasks-list');
            var countBadge = document.getElementById('micro-task-count-badge');

            // Show all micro tasks (no role-based hiding)
            var visibleMicroTasks = microTasks;

            if (countBadge) countBadge.textContent = visibleMicroTasks.length;

            if (visibleMicroTasks.length === 0) {
                if (container) container.innerHTML = '<div style="padding:12px;text-align:center;color:#999;font-size:12px;background:#f8fafc;">No micro tasks available.</div>';
                return;
            }

            visibleMicroTasks.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

            var html = '';
            visibleMicroTasks.forEach(function (mt, index) {
                var isDone = mt.status === 'done';
                var sDate = mt.follow_up_date ? mt.follow_up_date.split('T')[0] : '';

                // Editability: Admin can edit all. User can ONLY edit matched roles. Unassigned is read-only for users.
                var canEditThisMt = currentUserIsAdmin || (mt.role_id && mt.role_id == currentUserRoleId);

                html += '<div class="micro-task-row" style="display:grid;grid-template-columns:1fr 100px 120px 30px;gap:8px;padding:8px 12px;border-bottom:1px solid #e2e8f0;background:' + (isDone ? '#f8fafc' : '#fff') + ';align-items:center;">';
                html += '<div style="font-size:13px;font-weight:500;color:' + (isDone ? '#94a3b8;text-decoration:line-through' : '#334155') + '">';
                html += (index + 1) + '. ' + escapeHtml(mt.title);
                if (mt.role) {
                    html += ' <span style="display:inline-flex;align-items:center;background:#fff7ed;color:#ea580c;font-size:10px;padding:2px 6px;border-radius:10px;border:1px solid #fed7aa;margin-left:6px;" title="Role: ' + escapeHtml(mt.role.name) + '"><i data-lucide="shield" style="width:10px;height:10px;margin-right:3px;"></i>' + escapeHtml(mt.role.name) + '</span>';
                }
                html += '</div>';

                if (canEditThisMt) {
                    html += '<select onchange="updateMicroTask(' + mt.id + ', \'status\', this.value)" style="padding:4px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:11px;background:' + (isDone ? '#f0fdf4' : '#fff') + '">';
                    html += '<option value="todo" ' + (mt.status === 'todo' ? 'selected' : '') + '>Todo</option>';
                    html += '<option value="doing" ' + (mt.status === 'doing' ? 'selected' : '') + '>Doing</option>';
                    html += '<option value="done" ' + (mt.status === 'done' ? 'selected' : '') + '>Done</option>';
                    html += '</select>';
                    html += '<input type="date" value="' + sDate + '" onchange="updateMicroTask(' + mt.id + ', \'follow_up_date\', this.value)" style="padding:4px;border:1px solid #e2e8f0;border-radius:4px;font-size:11px;color:#64748b" title="Follow Up Date">';
                    html += '<button type="button" onclick="deleteMicroTask(' + mt.id + ')" style="background:none;border:none;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center" title="Delete"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button>';
                } else {
                    html += '<span style="padding:4px 8px;font-size:11px;color:#64748b;background:#f8fafc;border-radius:4px;text-align:center;">' + (mt.status.charAt(0).toUpperCase() + mt.status.slice(1)) + '</span>';
                    html += '<span style="font-size:11px;color:#94a3b8;padding:4px;text-align:center;">' + (sDate || '—') + '</span>';
                    html += '<span style="display:flex;align-items:center;justify-content:center" title="Not authorized to edit"><i data-lucide="lock" style="width:12px;height:12px;color:#cbd5e1"></i></span>';
                }
                html += '</div>';
            });

            if (container) container.innerHTML = html;
        }

        function addNewMicroTaskUI() {
            var title = prompt("Enter new micro task description:");
            if (!title || !window.currentEditTaskId) return;
            fetch('/admin/tasks/' + window.currentEditTaskId + '/micro-tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ title: title })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        var mtList = tasksData[window.currentEditTaskId].micro_tasks || [];
                        mtList.push(data.micro_task);
                        tasksData[window.currentEditTaskId].micro_tasks = mtList;
                        renderMicroTasksList(mtList);
                        lucide.createIcons();
                    } else {
                        alert(data.message || 'Error adding micro task');
                    }
                })
                .catch(err => console.error(err));
        }

        function updateMicroTask(id, field, value) {
            fetch('/admin/tasks/' + window.currentEditTaskId + '/micro-tasks/' + id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ [field]: value })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        var mtList = tasksData[window.currentEditTaskId].micro_tasks;
                        var mt = mtList.find(m => m.id === id);
                        if (mt) mt[field] = value;
                        renderMicroTasksList(mtList);
                        lucide.createIcons();
                    } else {
                        alert(data.message || 'Error updating micro task');
                    }
                })
                .catch(err => console.error(err));
        }

        function deleteMicroTask(id) {
            if (!confirm('Delete this micro task?')) return;
            fetch('/admin/tasks/' + window.currentEditTaskId + '/micro-tasks/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        var mtList = tasksData[window.currentEditTaskId].micro_tasks.filter(m => m.id !== id);
                        tasksData[window.currentEditTaskId].micro_tasks = mtList;
                        renderMicroTasksList(mtList);
                        lucide.createIcons();
                    }
                });
        }

        function renderActivityTimeline(activities) {
            var container = document.getElementById('task-activities-timeline');
            var countBadge = document.getElementById('activity-count-badge');
            if (countBadge) countBadge.textContent = activities.length;

            if (activities.length === 0) {
                if (container) container.innerHTML = '<p style="text-align:center;color:#999;font-size:12px;margin:12px 0">No activities yet</p>';
                return;
            }

            var typeColors = {
                'status_change': '#3b82f6', 'note': '#8b5cf6', 'client_reply': '#10b981',
                'revision': '#f59e0b', 'file_upload': '#6366f1'
            };
            var typeLabels = {
                'status_change': 'Status Changed', 'note': 'Note', 'client_reply': 'Client Reply',
                'revision': 'Revision', 'file_upload': 'File Upload'
            };

            var html = '';
            activities.forEach(function (a) {
                var color = typeColors[a.type] || '#64748b';
                var label = typeLabels[a.type] || a.type;
                var userName = a.user ? a.user.name : 'System';
                var date = new Date(a.created_at);
                var dateStr = date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' });

                html += '<div style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid #f0f0f0">' +
                    '<div style="width:4px;background:' + color + ';border-radius:2px;flex-shrink:0"></div>' +
                    '<div style="flex:1;min-width:0">' +
                    '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2px">' +
                    '<span style="font-size:11px;font-weight:600;color:' + color + ';text-transform:uppercase;letter-spacing:0.5px">' + label + '</span>' +
                    '<span style="font-size:10px;color:#94a3b8">' + dateStr + '</span>' +
                    '</div>' +
                    '<p style="margin:0;font-size:12px;color:#334155;line-height:1.4">' + (a.message || '') + '</p>' +
                    '<p style="margin:2px 0 0;font-size:10px;color:#94a3b8">by ' + userName + '</p>' +
                    '</div></div>';
            });
            if (container) container.innerHTML = html;
        }

        function submitActivity() {
            var type = document.getElementById('activity-type').value;
            var messageEl = document.getElementById('activity-message');
            var message = messageEl.value.trim();
            var mentionEl = document.getElementById('activity-mention');
            var notified_user_id = mentionEl ? mentionEl.value : '';

            if (!message || !window.currentEditTaskId) return;

            var payload = { type: type, message: message };
            if (notified_user_id) {
                payload.notified_user_id = notified_user_id;
            }

            fetch('/admin/tasks/' + window.currentEditTaskId + '/activities', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        messageEl.value = '';
                        var acts = tasksData[window.currentEditTaskId].activities || [];
                        acts.unshift(data.activity);
                        tasksData[window.currentEditTaskId].activities = acts;
                        renderActivityTimeline(acts);
                    } else {
                        alert(data.message || 'Error adding activity');
                    }
                })
                .catch(err => console.error(err));
        }

        // View Task Description Popup
        function viewTaskDescription(taskId) {
            var task = tasksData[taskId];
            if (!task) return;
            document.getElementById('tdesc-view-textarea').value = task.description || '';
            document.getElementById('tdesc-view-popup').style.display = 'flex';
        }

        function closeTDescPopup() {
            document.getElementById('tdesc-view-popup').style.display = 'none';
        }

        // View Task Modal Complete
        function escapeHtml(unsafe) {
            return (unsafe || '').toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function openViewTaskModal(taskId) {
            var task = tasksData[taskId];
            if (!task) { alert('Task not found'); return; }

            document.getElementById('view-task-title').textContent = task.title;

            // Status badge
            var statusEl = document.getElementById('view-task-status');
            statusEl.textContent = task.status === 'doing' ? 'In Progress' : (task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('_', ' '));
            statusEl.className = 'badge badge-' + (task.status === 'done' ? 'success' : (task.status === 'doing' ? 'info' : 'secondary'));

            // Priority badge
            var prioEl = document.getElementById('view-task-priority');
            prioEl.textContent = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
            prioEl.className = 'badge badge-' + (task.priority === 'high' ? 'destructive' : (task.priority === 'medium' ? 'warning' : 'secondary'));

            // Meta info
            document.getElementById('view-task-assignee').textContent = task.assigned_to ? task.assigned_to.name : 'Unassigned';

            var dueDateStr = '—';
            if (task.due_at) {
                var d = new Date(task.due_at);
                dueDateStr = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }
            document.getElementById('view-task-due').textContent = dueDateStr;

            var contactStr = task.contact_phone || task.contact_number;
            document.getElementById('view-task-contact').innerHTML = contactStr ? '<a href="tel:' + contactStr + '" style="color: inherit; text-decoration: none;">' + contactStr + '</a>' : '<span style="color:#94a3b8">—</span>';

            // Description
            var descEl = document.getElementById('view-task-desc');
            descEl.textContent = task.description || 'No description provided.';
            descEl.style.color = task.description ? '#334155' : '#94a3b8';

            // Micro Tasks
            // The index view tasksData doesn't explicitly eagerly load microTasks right now based on our checks,
            // but if they exist we will render them.
            var microContainer = document.getElementById('view-task-micro-tasks');
            var microTasks = task.micro_tasks || [];
            // Show all micro tasks (no role-based hiding)
            var visibleMicroTasks = microTasks;

            document.getElementById('view-task-micro-count').textContent = visibleMicroTasks.length;

            if (visibleMicroTasks.length === 0) {
                microContainer.innerHTML = '<div style="padding:16px; text-align:center; color:#94a3b8; font-size:13px; background:#f8fafc;">No micro tasks available.</div>';
            } else {
                visibleMicroTasks.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
                var microHtml = '';
                visibleMicroTasks.forEach(function (mt, idx) {
                    var isDone = mt.status === 'done';
                    var sDate = mt.follow_up_date ? new Date(mt.follow_up_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' }) : '—';

                    microHtml += '<div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-bottom:1px solid #f1f5f9; background:' + (isDone ? '#f8fafc' : '#fff') + ';">';
                    microHtml += '  <div style="font-size:13px; color:' + (isDone ? '#94a3b8; text-decoration:line-through' : '#334155') + '; font-weight:500;">' + (idx + 1) + '. ' + escapeHtml(mt.title) + '</div>';
                    microHtml += '  <div style="display:flex; gap:12px; align-items:center;">';
                    microHtml += '      <span style="font-size:11px; padding:2px 6px; border-radius:4px; background:' + (isDone ? '#dcfce7; color:#166534' : (mt.status === 'doing' ? '#dbeafe; color:#1e40af' : '#f1f5f9; color:#475569')) + '">' + mt.status.toUpperCase() + '</span>';
                    microHtml += '      <span style="font-size:11px; color:#64748b; width:45px; text-align:right;">' + sDate + '</span>';
                    microHtml += '  </div>';
                    microHtml += '</div>';
                });
                microContainer.innerHTML = microHtml;
            }

            document.getElementById('view-task-modal').classList.add('active');
            document.getElementById('view-task-modal-overlay').classList.add('active');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeViewTaskModal() {
            document.getElementById('view-task-modal').classList.remove('active');
            document.getElementById('view-task-modal-overlay').classList.remove('active');
        }        
    </script>
@endpush