@extends('admin.layouts.app')

@section('title', 'Micro Tasks')
@section('breadcrumb', 'Micro Tasks')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Micro Tasks</h1>
                <p class="page-description">Manage and track your detailed sub-tasks</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="task-toast-success" id="success-toast"
            style="padding: 14px 22px; background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #065f46; border: 1px solid #a7f3d0; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 14px;">
            <i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="task-toolbar"
        style="flex-wrap:wrap;gap:10px;padding:14px 20px; background: white; border-radius: 14px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" id="tasks-filter-form" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;flex:1">
            {{-- Search --}}
            <div style="min-width:200px;max-width:280px;flex:1">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px">Search</label>
                <div style="position:relative">
                    <i data-lucide="search"
                        style="width:14px;height:14px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search micro tasks..."
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;"
                        oninput="autoAjaxSearch(this.form)">
                </div>
            </div>

            {{-- Status --}}
            <div style="min-width:120px">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px">Status</label>
                <select name="status"
                    style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;"
                    onchange="autoAjaxSearch(this.form)">
                    <option value="">All</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div style="min-width:180px">
                <label
                    style="display:block;font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px">Date
                    Range</label>
                <div style="position:relative">
                    <i data-lucide="calendar"
                        style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                    <input type="text" id="mt-date-range-picker" placeholder="Select Date Range" autocomplete="off"
                        style="width:100%;padding:7px 10px 7px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;cursor:pointer">
                    <input type="hidden" name="date_from" id="mt-date-from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="mt-date-to" value="{{ request('date_to') }}">
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('admin.micro-tasks.index') }}" class="btn btn-outline"
                        style="padding: 7px 14px; border-radius: 8px; font-size: 13px; display:inline-flex; align-items:center;">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Kanban Board -->
    @php
        $statusMeta = [
            'todo' => ['icon' => 'circle', 'gradient' => 'linear-gradient(135deg,#64748b,#475569)', 'dot' => '#94a3b8', 'bg' => '#f8fafc', 'border' => '#e2e8f0'],
            'doing' => ['icon' => 'loader', 'gradient' => 'linear-gradient(135deg,#3b82f6,#2563eb)', 'dot' => '#60a5fa', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
            'done' => ['icon' => 'check-circle-2', 'gradient' => 'linear-gradient(135deg,#22c55e,#16a34a)', 'dot' => '#4ade80', 'bg' => '#f0fdf4', 'border' => '#bbf7d0'],
        ];
        $defaultMeta = ['icon' => 'circle-dot', 'gradient' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)', 'dot' => '#a78bfa', 'bg' => '#faf5ff', 'border' => '#ddd6fe'];
    @endphp

    <div class="kanban-board" id="kanban-board">
        @foreach ($statuses as $status)
            @php
                $statusTasks = $microTasks->where('status', $status);
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
                    @forelse($statusTasks as $mt)
                        @php $canDragThisMt = auth()->user()->isAdmin() || (!is_null($mt->role_id) && $mt->role_id == auth()->user()->role_id); @endphp
                        <div class="kb-card kanban-card" draggable="{{ $canDragThisMt ? 'true' : 'false' }}"
                            data-mt-id="{{ $mt->id }}" id="mt-{{ $mt->id }}" @if($canDragThisMt)
                            ondragstart="kanbanDragStart(event)" ondragend="kanbanDragEnd(event)" @endif @if(!$canDragThisMt)
                            style="cursor: default; opacity: 0.85;" @endif>

                            <!-- Card Top Layout -->
                            <div style="padding: 12px 14px 10px;">
                                <!-- Main Project ID -->
                                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; padding-right: 80px;">
                                    @if($mt->task && $mt->task->project)
                                        <i data-lucide="briefcase" style="width:16px;height:16px;color:#4f46e5;"></i>
                                        <span
                                            style="font-size: 15px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                            title="{{ $mt->task->project->name }}">{{ $mt->task->project->project_id_code }}</span>
                                    @else
                                        <i data-lucide="folder" style="width:16px;height:16px;color:#64748b;"></i>
                                        <span style="font-size: 15px; font-weight: 700; color: #0f172a;">General Task</span>
                                    @endif
                                </div>

                                <!-- Micro Task Name (left) and Task/Service Name (right tag) -->
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <div
                                        style="font-size: 13px; font-weight: 500; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;">
                                        {{ $mt->title ?? 'Unknown Micro Task' }}
                                    </div>
                                    <div style="font-size: 11px; font-weight: 500; color: #475569; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px; flex-shrink: 0; background-color: #f8fafc;"
                                        title="{{ $mt->task->title ?? 'Unknown' }}">
                                        {{ str()->limit($mt->task->title ?? 'Unknown', 20) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Client Tag (if any) -->
                            @php
                                $clientName = null;
                                $task = $mt->task;
                                if ($task && $task->entity_type === 'client' && $task->clientEntity) {
                                    $clientName = $task->clientEntity->business_name ?: $task->clientEntity->contact_name;
                                } elseif ($task && $task->entity_type === 'lead' && $task->leadEntity) {
                                    $clientName = $task->leadEntity->name . ' (Lead)';
                                } elseif ($task && $task->project_id && $task->project) {
                                    if ($task->project->client) {
                                        $clientName = $task->project->client->business_name ?: $task->project->client->contact_name;
                                    }
                                }
                            @endphp
                            @if($clientName)
                                <div style="border-top: 1px solid #f8fafc; padding: 8px 14px;">
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 4px; background: #faf5ff; border: 1px solid #f3e8ff; color: #a855f7; font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 12px; white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis;"
                                        title="{{ $clientName }}">
                                        <i data-lucide="user" style="width:10px;height:10px"></i>
                                        {{ $clientName }}
                                    </span>
                                    @if($mt->role)
                                        <span
                                            style="display: inline-flex; align-items: center; gap: 4px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 12px; white-space: nowrap; margin-left: 4px;"
                                            title="Role: {{ $mt->role->name }}">
                                            <i data-lucide="shield" style="width:10px;height:10px"></i>
                                            {{ $mt->role->name }}
                                        </span>
                                    @endif
                                </div>
                            @elseif($mt->role)
                                <div style="border-top: 1px solid #f8fafc; padding: 8px 14px;">
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 4px; background: #fff7ed; border: 1px solid #fed7aa; color: #ea580c; font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 12px; white-space: nowrap;"
                                        title="Role: {{ $mt->role->name }}">
                                        <i data-lucide="shield" style="width:10px;height:10px"></i>
                                        {{ $mt->role->name }}
                                    </span>
                                </div>
                            @endif

                            <!-- Bottom Footer (Avatar + Priority) -->
                            <div
                                style="border-top: 1px solid #f8fafc; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div
                                        style="width: 24px; height: 24px; border-radius: 50%; background: #6366f1; color: white; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700;">
                                        {{ isset($mt->task) && isset($mt->task->assignedTo) ? strtoupper(substr($mt->task->assignedTo->name, 0, 2)) : '?' }}
                                    </div>
                                    <div
                                        style="font-size: 12px; font-weight: 500; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;">
                                        {{ $mt->task->assignedTo->name ?? 'Unassigned' }}
                                    </div>
                                </div>
                                <div style="display:flex; gap: 8px; align-items: center;">
                                    @if($mt->follow_up_date)
                                        <span class="kb-meta-item"
                                            style="font-size: 11px; padding: 2px 6px; background: #f1f5f9; border-radius: 4px; color: #475569;">
                                            <i data-lucide="calendar" style="width:10px;height:10px; margin-right: 4px;"></i>
                                            {{ $mt->follow_up_date->format('d M') }}
                                        </span>
                                    @endif
                                    @php
                                        $prioMap = ['high' => ['class' => 'prio-high'], 'medium' => ['class' => 'prio-medium'], 'low' => ['class' => 'prio-low']];
                                        $prio = $prioMap[$mt->task->priority ?? 'medium'] ?? $prioMap['low'];
                                    @endphp
                                    <span class="kb-prio-dot {{ $prio['class'] }}-dot" style="margin-left: 4px;"></span>
                                </div>
                            </div>

                            <!-- Action buttons container (visible on hover via CSS) -->
                            @php
                                $canEditThisMt = auth()->user()->isAdmin() || (!is_null($mt->role_id) && $mt->role_id == auth()->user()->role_id);
                            @endphp
                            <div class="kb-card-actions" draggable="false" onmousedown="event.stopPropagation()">
                                <button type="button" class="kb-action-btn kb-action-view"
                                    onclick="event.stopPropagation(); openViewMtModal({{ $mt->id }})" title="View">
                                    <i data-lucide="eye" style="width:12px;height:12px"></i>
                                </button>
                                @if(can('tasks.write') && $canEditThisMt)
                                    <button type="button" class="kb-action-btn kb-action-edit"
                                        onclick="event.stopPropagation(); openEditMtModal({{ $mt->id }})" title="Edit">
                                        <i data-lucide="pencil" style="width:12px;height:12px"></i>
                                    </button>
                                @endif
                                @if(can('tasks.delete') && $canEditThisMt)
                                    <form method="POST" action="{{ route('admin.micro-tasks.destroy', $mt->id) }}"
                                        style="display:inline;margin:0"
                                        onsubmit="event.stopPropagation(); return confirm('Delete this micro task?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="kb-action-btn kb-action-delete" onclick="event.stopPropagation()"
                                            title="Delete">
                                            <i data-lucide="trash-2" style="width:12px;height:12px"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="kb-empty">
                            <div class="kb-empty-icon">
                                <i data-lucide="inbox" style="width:28px;height:28px"></i>
                            </div>
                            <p>No tasks yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- View Modal -->
    <div id="view-mt-modal-overlay" onclick="closeViewMtModal()"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;">
    </div>
    <div id="view-mt-modal"
        style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:24px;border-radius:12px;z-index:1000;width:90%;max-width:500px;box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;font-size:18px;">Micro Task Details</h3>
            <button onclick="closeViewMtModal()" style="background:none;border:none;cursor:pointer;"><i
                    data-lucide="x"></i></button>
        </div>
        <div id="view-mt-content" style="font-size: 14px; line-height: 1.6; color: #334155;">Loading...</div>
    </div>

    <!-- Edit Modal -->
    <div id="edit-mt-modal-overlay" onclick="closeEditMtModal()"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;">
    </div>
    <div id="edit-mt-modal"
        style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:24px;border-radius:12px;z-index:1000;width:90%;max-width:500px;box-shadow:0 10px 25px rgba(0,0,0,0.1);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;font-size:18px;">Edit Micro Task</h3>
            <button onclick="closeEditMtModal()" style="background:none;border:none;cursor:pointer;"><i
                    data-lucide="x"></i></button>
        </div>
        <form id="edit-mt-form" method="POST">
            @csrf @method('PUT')
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Title</label>
                <input type="text" name="title" id="edit-mt-title" required class="form-input"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Status</label>
                <select name="status" id="edit-mt-status"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;">
                    <option value="todo">Todo</option>
                    <option value="doing">Doing</option>
                    <option value="done">Done</option>
                </select>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Follow-up Date</label>
                <input type="datetime-local" name="follow_up_date" id="edit-mt-date"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Activity Note
                    (Optional)</label>
                <textarea name="note" id="edit-mt-note" rows="3" placeholder="Add a note to task activities..."
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;"></textarea>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeEditMtModal()" class="btn btn-outline"
                    style="padding: 8px 16px;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">Save Changes</button>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <style>
        /* ── Board ── */
        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 24px;
            min-height: calc(100vh - 200px);
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
            min-width: 300px;
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
            padding: 14px 16px 12px;
            border-bottom: 1.5px solid var(--col-border, #e2e8f0);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kb-col-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kb-col-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.06);
            flex-shrink: 0;
        }

        .kb-col-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.01em;
        }

        .kb-col-count {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            background: white;
            border: 1px solid #e2e8f0;
            padding: 2px 8px;
            border-radius: 20px;
            min-width: 22px;
            text-align: center;
        }

        /* ── Column Body ── */
        .kb-col-body {
            padding: 10px 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            overflow-y: auto;
            max-height: calc(100vh - 240px);
            min-height: 80px;
        }

        /* ── Card ── */
        .kb-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0;
            /* padding handled in internal elements */
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            /* Match screenshot gentle shadow */
            cursor: grab;
            position: relative;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, border-color 0.2s;
        }

        .kb-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
            z-index: 10;
        }

        /* ── Action Buttons ── */
        .kb-card-actions {
            position: absolute;
            top: 10px;
            right: 10px;
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
            border: 1px solid transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .kb-action-view {
            color: #64748b;
        }

        .kb-action-view:hover {
            color: #3b82f6;
            border-color: #bfdbfe;
            background: #eff6ff;
            transform: scale(1.05);
        }

        .kb-action-edit {
            color: #d97706;
        }

        .kb-action-edit:hover {
            background: #fef3c7;
            border-color: #fde68a;
            transform: scale(1.05);
        }

        .kb-action-delete {
            color: #ef4444;
        }

        .kb-action-delete:hover {
            background: #fef2f2;
            border-color: #fecaca;
            transform: scale(1.05);
        }

        /* ── Card Top ── */
        .kb-card-top {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }

        .kb-entity-tag {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 6px;
            background: #f5f3ff;
            color: #7c3aed;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        /* ── Card Title ── */
        .kb-card-title {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.4;
            letter-spacing: -0.01em;
        }

        /* ── Card Footer ── */
        .kb-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
        }

        .kb-avatar-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .kb-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 9px;
            font-weight: 800;
            box-shadow: 0 2px 6px rgba(99, 102, 241, 0.2);
            flex-shrink: 0;
        }

        .kb-avatar-name {
            font-size: 11px;
            color: #475569;
            font-weight: 500;
        }

        .kb-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #64748b;
            padding: 2px 6px;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #f1f5f9;
        }

        .prio-high-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ef4444;
        }

        .prio-medium-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #f59e0b;
        }

        .prio-low-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
        }

        /* ── Empty State ── */
        .kb-empty {
            text-align: center;
            padding: 24px 12px;
            color: #94a3b8;
        }

        .kb-empty-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #cbd5e1;
        }

        .kb-empty p {
            margin: 0;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }

        /* ── Action Buttons ── */
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
            width: 26px;
            height: 26px;
            border-radius: 6px;
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
    </style>
@endpush

@push('scripts')
    <script>
        function kanbanDragStart(ev) {
            const card = ev.target.closest('.kanban-card');
            ev.dataTransfer.setData('text', card.getAttribute('data-mt-id'));
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

            const mtId = ev.dataTransfer.getData('text');
            const card = document.getElementById('mt-' + mtId);
            if (!card) return;

            const oldColumn = card.closest('.kb-col-body');
            const newColumn = document.getElementById('column-' + newStatus);

            if (oldColumn === newColumn) return;

            // Move card UI
            newColumn.appendChild(card);

            // Update counts UI
            const oldStatus = oldColumn.getAttribute('data-status');
            const oldCountEl = document.querySelector('.kanban-count-' + oldStatus);
            const newCountEl = document.querySelector('.kanban-count-' + newStatus);

            if (oldCountEl) oldCountEl.textContent = parseInt(oldCountEl.textContent) - 1;
            if (newCountEl) newCountEl.textContent = parseInt(newCountEl.textContent) + 1;

            // Handle empty states UI
            if (oldColumn.querySelectorAll('.kanban-card').length === 0) {
                if (!oldColumn.querySelector('.kb-empty')) {
                    const emptyHtml = '<div class="kb-empty"><div class="kb-empty-icon"><i data-lucide="inbox" style="width:28px;height:28px"></i></div><p>No tasks yet</p></div>';
                    oldColumn.insertAdjacentHTML('beforeend', emptyHtml);
                    lucide.createIcons();
                }
            }
            const newEmpty = newColumn.querySelector('.kb-empty');
            if (newEmpty) newEmpty.remove();

            // AJAX call to update status
            fetch('{{ url("/admin/micro-tasks") }}/' + mtId + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error updating status: ' + (data.message || 'Unknown error'));
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Connection error');
                    window.location.reload();
                });
        }

        // Modals
        function openViewMtModal(id) {
            document.getElementById('view-mt-modal-overlay').style.display = 'block';
            document.getElementById('view-mt-modal').style.display = 'block';
            document.getElementById('view-mt-content').innerHTML = 'Loading...';

            fetch("{{ url('/admin/micro-tasks') }}/" + id, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('view-mt-content').innerHTML = `
                                                    <div style="margin-bottom: 8px;"><strong>Title:</strong> ${data.title}</div>
                                                    <div style="margin-bottom: 8px;"><strong>Parent Task:</strong> ${data.task_title}</div>
                                                    <div style="margin-bottom: 8px;"><strong>Status:</strong> <span style="background:#f1f5f9;padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;">${data.status.toUpperCase()}</span></div>
                                                    <div style="margin-bottom: 8px;"><strong>Follow-Up Date:</strong> ${data.follow_up_date ? new Date(data.follow_up_date).toLocaleString() : 'None'}</div>
                                                `;
                }).catch(err => {
                    document.getElementById('view-mt-content').innerHTML = 'Error loading task details.';
                });
        }

        function closeViewMtModal() {
            document.getElementById('view-mt-modal-overlay').style.display = 'none';
            document.getElementById('view-mt-modal').style.display = 'none';
        }

        function openEditMtModal(id) {
            document.getElementById('edit-mt-modal-overlay').style.display = 'block';
            document.getElementById('edit-mt-modal').style.display = 'block';
            document.getElementById('edit-mt-form').action = "{{ url('/admin/micro-tasks') }}/" + id;

            fetch("{{ url('/admin/micro-tasks') }}/" + id, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('edit-mt-title').value = data.title;
                    document.getElementById('edit-mt-status').value = data.status;
                    document.getElementById('edit-mt-date').value = data.follow_up_date ? data.follow_up_date : '';
                    document.getElementById('edit-mt-note').value = ''; // Reset note field for new activity
                });
        }

        function closeEditMtModal() {
            document.getElementById('edit-mt-modal-overlay').style.display = 'none';
            document.getElementById('edit-mt-modal').style.display = 'none';
        }
    </script>
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Re-init Flatpickr properly after Turbo swaps the DOM
        function initMtFlatpickr() {
            let df = document.getElementById('mt-date-from')?.value;
            let dt = document.getElementById('mt-date-to')?.value;
            let defDates = [];
            if (df && dt) defDates = [df, dt];

            const picker = document.getElementById("mt-date-range-picker");
            if (picker && typeof flatpickr !== 'undefined') {
                flatpickr(picker, {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    defaultDate: defDates,
                    onChange: function (selectedDates, dateStr, instance) {
                        const form = document.getElementById('tasks-filter-form');
                        if (!form) return;
                        
                        if (selectedDates.length === 2) {
                            document.getElementById('mt-date-from').value = instance.formatDate(selectedDates[0], "Y-m-d");
                            document.getElementById('mt-date-to').value = instance.formatDate(selectedDates[1], "Y-m-d");
                            if (typeof autoAjaxSearch === 'function') autoAjaxSearch(form);
                        } else if (selectedDates.length === 0) {
                            document.getElementById('mt-date-from').value = '';
                            document.getElementById('mt-date-to').value = '';
                            if (typeof autoAjaxSearch === 'function') autoAjaxSearch(form);
                        }
                    }
                });
            }
        }
        document.addEventListener('DOMContentLoaded', initMtFlatpickr);
        document.addEventListener('turbo:render', initMtFlatpickr);
    </script>
@endpush