@extends('admin.layouts.app')

@section('title', $project->name)
@section('breadcrumb', 'Projects')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <a href="{{ route('admin.projects.index') }}"
                        style="color:var(--muted-foreground);text-decoration:none;display:flex;align-items:center;gap:4px;font-size:13px">
                        <i data-lucide="arrow-left" style="width:14px;height:14px"></i> Projects
                    </a>
                </div>
                <h1 class="page-title">{{ $project->name }}</h1>
                @if($project->client)
                    <p class="page-description">Client: {{ $project->client->display_name }}</p>
                @endif
            </div>
            <div class="page-actions" style="display:flex;gap:8px">
                @if(can('projects.write'))
                    <button class="btn btn-primary" onclick="openEditProjectModal()">
                        <i data-lucide="edit" style="width:16px;height:16px"></i> Edit Project
                    </button>
                @endif
                @if(can('projects.delete'))
                    <button type="button" onclick="ajaxDelete('{{ route('admin.projects.destroy', $project->id) }}')" class="btn btn-outline"
                            style="color:var(--destructive);border-color:var(--destructive)">
                            <i data-lucide="trash-2" style="width:16px;height:16px"></i> Delete
                        </button>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
            <i data-lucide="check-circle" style="width:18px;height:18px"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Project Info Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:24px">
        <div class="card" style="padding:20px">
            <p class="text-xs text-muted" style="margin-bottom:4px">Status</p>
            @php
                $statusColors = ['pending' => 'secondary', 'in_progress' => 'info', 'completed' => 'success', 'on_hold' => 'warning', 'cancelled' => 'destructive'];
            @endphp
            <span class="badge badge-{{ $statusColors[$project->status] ?? 'secondary' }}" style="font-size:13px">
                {{ ucwords(str_replace('_', ' ', $project->status)) }}
            </span>
        </div>
        <div class="card" style="padding:20px">
            <p class="text-xs text-muted" style="margin-bottom:4px">Task Progress</p>
            <p class="font-medium" style="font-size:18px">{{ $project->tasks->where('status', 'done')->count() }} /
                {{ $project->tasks->count() }}
            </p>
            @if($project->tasks->count() > 0)
                <div style="width:100%;height:6px;background:#eee;border-radius:3px;margin-top:8px;overflow:hidden">
                    @php $pct = $project->tasks->count() > 0 ? round(($project->tasks->where('status', 'done')->count() / $project->tasks->count()) * 100) : 0; @endphp
                    <div style="width:{{ $pct }}%;height:100%;background:var(--primary);border-radius:3px;transition:width .3s">
                    </div>
                </div>
            @endif
        </div>
        <div class="card" style="padding:20px">
            <p class="text-xs text-muted" style="margin-bottom:4px">Assigned To</p>
            <p class="font-medium">{{ $project->assignedUsers->isNotEmpty() ? $project->assignedUsers->pluck('name')->implode(', ') : 'Unassigned' }}</p>
        </div>
        <div class="card" style="padding:20px">
            <p class="text-xs text-muted" style="margin-bottom:4px">Budget</p>
            <p class="font-medium">₹{{ number_format($project->budget_in_rupees, 2) }}</p>
        </div>
        <div class="card" style="padding:20px">
            <p class="text-xs text-muted" style="margin-bottom:4px">Contact Number</p>
            @php
                $projectPhone = $project->lead->phone ?? $project->client->phone ?? null;
            @endphp
            @if($projectPhone)
                <a href="tel:{{ $projectPhone }}"
                    style="color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:4px;font-weight:500">
                    <i data-lucide="phone" style="width:14px;height:14px"></i>
                    {{ $projectPhone }}
                </a>
            @else
                <span style="color:#999">—</span>
            @endif
        </div>
    </div>

    @if($project->description)
        <div class="card" style="padding:20px;margin-bottom:24px">
            <p class="text-xs text-muted" style="margin-bottom:4px">Description</p>
            <p style="margin:0">{{ $project->description }}</p>
        </div>
    @endif

    <!-- Tasks Section -->
    <div class="card" style="margin-bottom:24px">
        <div
            style="padding:20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="font-weight:600;font-size:16px;margin:0">Tasks ({{ $project->tasks->count() }})</h3>
        </div>
        <div class="table-wrapper">
            <table class="table" id="tasks-table">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                        <th>Updates</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($project->tasks as $task)
                        <tr>
                            <td>
                                <p class="font-medium" style="margin:0">{{ $task->title }}</p>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:4px">
                                    <span
                                        style="flex:1;padding:4px;font-size:13px;color:#666;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:200px;display:inline-block">{{ $task->description ? Str::limit(str_replace("\n", ' ', $task->description), 40) : '—' }}</span>
                                    @if($task->description)
                                        <button type="button" onclick="viewTaskDescription({{ $task->id }})"
                                            style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;cursor:pointer;padding:4px 6px;display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0"
                                            title="View Description">
                                            <i data-lucide="eye" style="width:12px;height:12px"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span
                                    class="badge badge-{{ $task->status === 'done' ? 'success' : ($task->status === 'doing' ? 'info' : 'secondary') }}">
                                    {{ $task->status === 'doing' ? 'In Progress' : ucfirst($task->status) }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge badge-{{ $task->priority === 'high' ? 'destructive' : ($task->priority === 'medium' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </td>
                            <td>{{ $task->assignedUsers->isNotEmpty() ? $task->assignedUsers->pluck('name')->implode(', ') : 'Unassigned' }}</td>
                            <td class="{{ $task->isOverdue() ? 'text-destructive font-medium' : '' }}">
                                {{ $task->due_at ? $task->due_at->format('d M Y') : '—' }}
                                @if($task->isOverdue())
                                    <span style="font-size:11px"> (Overdue)</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-ghost btn-icon btn-sm" title="View"
                                        onclick="openViewTaskModal({{ $task->id }})">
                                        <i data-lucide="eye" style="width:16px;height:16px"></i>
                                    </button>
                                    @if(can('tasks.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" title="Edit"
                                            onclick="openEditTaskModal({{ $task->id }})">
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                    @if(can('tasks.delete'))
                                        <button type="button" onclick="ajaxDelete('{{ route('admin.projects.tasks.destroy', [$project->id, $task->id]) }}')" class="btn btn-ghost btn-icon btn-sm"
                                                style="color:var(--destructive)" title="Delete">
                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                            </button>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span
                                    style="background:#f0f4ff;color:#3b82f6;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;cursor:pointer"
                                    onclick="openEditTaskModal({{ $task->id }})" title="View Activities">
                                    {{ $task->activities->count() }}
                                    {{ $task->activities->count() === 1 ? 'update' : 'updates' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:40px 0;color:#999">
                                <i data-lucide="check-square" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                                <p style="margin:0;font-size:14px">No tasks in this project</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Project Modal -->
    <div id="project-modal-overlay" class="overlay" onclick="closeProjectModal()"></div>
    <div id="project-modal" class="modal">
        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;width:100%;">
            <div style="display:flex;align-items:center;gap:12px;">
                <h3 class="modal-title" style="margin:0;">Edit Project</h3>
                @if($project->quote_id)
                    <button type="button"
                        onclick="openQuoteShortcut('{{ route('admin.quotes.index') }}?open_quote={{ $project->quote_id }}')"
                        title="View Related Quote"
                        style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe; border-radius:6px; cursor:pointer;"
                        data-quote-btn="true">
                        <i data-lucide="file-text" style="width:14px; height:14px;"></i>
                    </button>
                @endif
            </div>
            <button class="modal-close" onclick="closeProjectModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="project-edit-form" method="POST" action="{{ route('admin.projects.update', $project->id) }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Project Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-input" required value="{{ $project->name }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="project-edit-description" class="form-textarea"
                        rows="3">{{ $project->description }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    @php
                        $editPhone = $project->lead->phone ?? $project->client->phone ?? null;
                    @endphp
                    <div
                        style="padding:10px 12px;background:#f9f9f9;border:1px solid #ddd;border-radius:6px;font-size:14px;display:flex;align-items:center;gap:6px;min-height:42px">
                        @if($editPhone)
                            <i data-lucide="phone" style="width:14px;height:14px;color:#3b82f6"></i>
                            <a href="tel:{{ $editPhone }}" style="color:#3b82f6;text-decoration:none">{{ $editPhone }}</a>
                        @else
                            <span style="color:#999">—</span>
                        @endif
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            @foreach(['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'] as $s)
                                <option value="{{ $s }}" {{ $project->status === $s ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to_user_id" class="form-input">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $project->assigned_to_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-input"
                            value="{{ $project->start_date?->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-input"
                            value="{{ $project->due_date?->format('Y-m-d') }}">
                    </div>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">

                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <label class="form-label" style="margin:0">Tasks ({{ $project->tasks->count() }})</label>
                    </div>
                    <div class="table-wrapper"
                        style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <table class="table" style="margin: 0;">
                            <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 10;">
                                <tr>
                                    <th style="font-size: 12px; padding: 10px 12px;">Task</th>
                                    <th style="font-size: 12px; padding: 10px 12px;">Desc</th>
                                    <th style="font-size: 12px; padding: 10px 12px;">Status</th>
                                    <th style="font-size: 12px; padding: 10px 12px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($project->tasks as $task)
                                    <tr>
                                        <td style="padding: 10px 12px;">
                                            <p class="font-medium" style="margin:0; font-size: 13px;">{{ $task->title }}</p>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            @if($task->description)
                                                <button type="button"
                                                    onclick="event.preventDefault(); viewTaskDescription({{ $task->id }})"
                                                    style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;cursor:pointer;padding:4px 6px;display:inline-flex;align-items:center;justify-content:center;color:#3b82f6;"
                                                    title="View Description">
                                                    <i data-lucide="eye" style="width:12px;height:12px"></i>
                                                </button>
                                            @else
                                                <span style="color:#999;font-size:12px;">—</span>
                                            @endif
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <span style="font-size: 11px;"
                                                class="badge badge-{{ $task->status === 'done' ? 'success' : ($task->status === 'doing' ? 'info' : 'secondary') }}">
                                                {{ $task->status === 'doing' ? 'In Progress' : ucfirst($task->status) }}
                                            </span>
                                        </td>
                                        <td style="padding: 10px 12px;">
                                            <div style="display:flex;gap:4px">
                                                @if(can('tasks.write'))
                                                    <button type="button" onclick="openEditTaskModal({{ $task->id }})"
                                                        style="background:#fffbeb;border:none;border-radius:4px;cursor:pointer;padding:6px;display:flex;align-items:center;justify-content:center;color:#f59e0b"
                                                        title="Edit Task">
                                                        <i data-lucide="edit" style="width:14px;height:14px"></i>
                                                    </button>
                                                @endif
                                                @if(can('tasks.delete'))
                                                    <button type="button"
                                                        onclick="if(confirm('Delete this task?')) { document.getElementById('delete-task-{{ $task->id }}').submit(); }"
                                                        style="background:#fef2f2;border:none;border-radius:4px;cursor:pointer;padding:6px;display:flex;align-items:center;justify-content:center;color:#ef4444"
                                                        title="Delete Task">
                                                        <i data-lucide="trash-2" style="width:14px;height:14px"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="text-align:center;padding:20px 0;color:#999;font-size:13px;">
                                            No tasks in this project
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer" style="padding:16px 0 0;margin:0">
                    <button type="button" class="btn btn-outline" onclick="closeProjectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div id="task-modal-overlay" class="overlay" onclick="closeTaskModal()"></div>
    <div id="task-modal" class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit Task</h3>
            <button class="modal-close" onclick="closeTaskModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="task-form" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="_old_status" id="task-old-status" value="">
                <div class="form-group">
                    <label class="form-label">Title <span style="color:red">*</span></label>
                    <input type="text" name="title" id="task-title" class="form-input" required>
                </div>
                @if(can('tasks.global') || auth()->user()->isAdmin())
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="task-description" class="form-textarea" rows="3"></textarea>
                    </div>
                @else
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="task-description" class="form-textarea" rows="3" readonly
                            style="background-color: #e0f2fe; color: #334155; cursor: not-allowed;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Add New Description (Optional)</label>
                        <textarea name="additional_description" id="task-additional-description" class="form-textarea" rows="2"
                            placeholder="Write new description here to append..."></textarea>
                    </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <div id="task-contact-view"
                        style="padding:10px 12px;background:#f9f9f9;border:1px solid #ddd;border-radius:6px;font-size:14px;display:flex;align-items:center;gap:6px;min-height:42px">
                        <span style="color:#999">—</span>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="task-status" class="form-input">
                            @foreach(\App\Models\Task::getDynamicStatuses() as $statusVal)
                                <option value="{{ $statusVal }}">{{ ucfirst(str_replace('_', ' ', $statusVal)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" id="task-priority" class="form-input">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_at" id="task-due" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to_user_id" id="task-assigned" class="form-input">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="padding:16px 0 0;margin:0">
                    <button type="button" class="btn btn-outline" onclick="closeTaskModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
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
            </form>
        </div>
    </div>

    <!-- Task Description View Popup -->
    <div id="tdesc-view-popup"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div
            style="background:white;border-radius:12px;width:95%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden">
            <div
                style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff)">
                <h3 style="margin:0;font-size:16px;font-weight:600;color:#1a1a2e">Task Description</h3>
                <button onclick="closeTDescPopup()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b">&times;</button>
            </div>
            <div style="padding:20px">
                <textarea id="tdesc-view-textarea" rows="8" readonly
                    style="width:100%;padding:12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;resize:vertical;outline:none;font-family:inherit;line-height:1.6;background:#f8fafc;color:#333;"></textarea>
            </div>
            <div
                style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc">
                <button type="button" onclick="closeTDescPopup()"
                    style="padding:8px 18px;background:linear-gradient(135deg,#64748b,#475569);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;box-shadow:0 2px 8px rgba(100,116,139,0.3)">Close</button>
            </div>
        </div>
    </div>

    <!-- View Task Modal -->
    <div id="view-task-modal-overlay" class="overlay" onclick="closeViewTaskModal()"></div>
    <div id="view-task-modal" class="modal">
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

@push('scripts')
    <script>
        // Store current user info for micro task permissions
        var currentUserRoleId = {{ auth()->user()->role_id ?? 'null' }};
        var currentUserIsAdmin = {{ auth()->user()->isAdmin() ? 'true' : 'false' }};

        var projectFormChanged = false;

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') lucide.createIcons();

            // Track changes in project edit form
            const projectForm = document.getElementById('project-edit-form');
            if (projectForm) {
                const inputs = projectForm.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('change', () => { projectFormChanged = true; });
                    input.addEventListener('input', () => { projectFormChanged = true; });
                });
            }
        });

        function openQuoteShortcut(url) {
            if (projectFormChanged) {
                alert("Please save your changes before navigating to the quote.");
            } else {
                window.open(url, '_blank');
            }
        }

        function openEditProjectModal() {
            projectFormChanged = false; // reset on open

            // Clear auto-generated description if present
            const descEl = document.getElementById('project-edit-description');
            if (descEl && descEl.value.includes('Auto-created from lead conversion')) {
                descEl.value = '';
            }

            document.getElementById('project-modal').classList.add('active');
            document.getElementById('project-modal-overlay').classList.add('active');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeProjectModal() {
            document.getElementById('project-modal').classList.remove('active');
            document.getElementById('project-modal-overlay').classList.remove('active');
        }

        // Task data map for edit modal
        var projectTasksData = {};
        var currentEditTaskId = null;
        @foreach($project->tasks as $t)
            projectTasksData[{{ $t->id }}] = @json($t->load('activities.user', 'microTasks'));
        @endforeach

            function openEditTaskModal(taskId) {
                var task = projectTasksData[taskId];
                if (!task) { alert('Task not found'); return; }
                currentEditTaskId = taskId;
                document.getElementById('task-form').action = '/admin/projects/{{ $project->id }}/tasks/' + task.id;
                document.getElementById('task-title').value = task.title;
                document.getElementById('task-description').value = task.description || '';
                document.getElementById('task-old-status').value = task.status;
                var addDesc = document.getElementById('task-additional-description');
                if (addDesc) addDesc.value = '';

                var contactEl = document.getElementById('task-contact-view');
                if (contactEl) {
                    var displayPhone = task.contact_phone || task.contact_number;
                    if (displayPhone) {
                        contactEl.innerHTML = '<i data-lucide="phone" style="width:14px;height:14px;color:#3b82f6"></i> <a href="tel:' + displayPhone + '" style="color:#3b82f6;text-decoration:none">' + displayPhone + '</a>';
                    } else {
                        contactEl.innerHTML = '<span style="color:#999">—</span>';
                    }
                }

                document.getElementById('task-status').value = task.status;
                document.getElementById('task-priority').value = task.priority;
                document.getElementById('task-due').value = task.due_at ? task.due_at.split('T')[0] : '';
                document.getElementById('task-assigned').value = task.assigned_to_user_id || '';

                // Render micro tasks
                renderMicroTasksList(task.micro_tasks || []);

                // Render activity timeline
                renderActivityTimeline(task.activities || []);

                document.getElementById('task-modal').classList.add('active');
                document.getElementById('task-modal-overlay').classList.add('active');
                lucide.createIcons();
            }

        function renderMicroTasksList(microTasks) {
            var container = document.getElementById('task-micro-tasks-list');
            var countBadge = document.getElementById('micro-task-count-badge');

            // Show all micro tasks (no role-based hiding)
            var visibleMicroTasks = microTasks;

            countBadge.textContent = visibleMicroTasks.length;

            if (visibleMicroTasks.length === 0) {
                container.innerHTML = '<div style="padding:12px;text-align:center;color:#999;font-size:12px;background:#f8fafc;">No micro tasks available.</div>';
                return;
            }

            // Sort micro tasks by sort_order
            visibleMicroTasks.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

            var html = '';
            visibleMicroTasks.forEach(function (mt, index) {
                var isDone = mt.status === 'done';
                var sDate = mt.follow_up_date ? mt.follow_up_date.split('T')[0] : '';

                // Editability: Admin can edit all. User can ONLY edit matched roles. Unassigned is read-only for users.
                var canEditThisMt = currentUserIsAdmin || (mt.role_id && mt.role_id == currentUserRoleId);

                html += '<div class="micro-task-row" style="display:grid;grid-template-columns:1fr 100px 120px 30px;gap:8px;padding:8px 12px;border-bottom:1px solid #e2e8f0;background:' + (isDone ? '#f8fafc' : '#fff') + ';align-items:center;">';

                // Title
                html += '<div style="font-size:13px;font-weight:500;color:' + (isDone ? '#94a3b8;text-decoration:line-through' : '#334155') + '">';
                html += (index + 1) + '. ' + escapeHtml(mt.title);
                if (mt.role) {
                    html += ' <span style="display:inline-flex;align-items:center;background:#fff7ed;color:#ea580c;font-size:10px;padding:2px 6px;border-radius:10px;border:1px solid #fed7aa;margin-left:6px;" title="Role: ' + escapeHtml(mt.role.name) + '"><i data-lucide="shield" style="width:10px;height:10px;margin-right:3px;"></i>' + escapeHtml(mt.role.name) + '</span>';
                }
                html += '</div>';

                if (canEditThisMt) {
                    // Status
                    html += '<select onchange="updateMicroTask(' + mt.id + ', \'status\', this.value)" style="padding:4px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:11px;background:' + (isDone ? '#f0fdf4' : '#fff') + '">';
                    html += '<option value="todo" ' + (mt.status === 'todo' ? 'selected' : '') + '>Todo</option>';
                    html += '<option value="doing" ' + (mt.status === 'doing' ? 'selected' : '') + '>Doing</option>';
                    html += '<option value="done" ' + (mt.status === 'done' ? 'selected' : '') + '>Done</option>';
                    html += '</select>';

                    // Follow up date
                    html += '<input type="date" value="' + sDate + '" onchange="updateMicroTask(' + mt.id + ', \'follow_up_date\', this.value)" style="padding:4px;border:1px solid #e2e8f0;border-radius:4px;font-size:11px;color:#64748b" title="Follow Up Date">';

                    // Delete
                    html += '<button type="button" onclick="deleteMicroTask(' + mt.id + ')" style="background:none;border:none;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center" title="Delete"><i data-lucide="trash-2" style="width:14px;height:14px"></i></button>';
                } else {
                    html += '<span style="padding:4px 8px;font-size:11px;color:#64748b;background:#f8fafc;border-radius:4px;text-align:center;">' + (mt.status.charAt(0).toUpperCase() + mt.status.slice(1)) + '</span>';
                    html += '<span style="font-size:11px;color:#94a3b8;padding:4px;text-align:center;">' + (sDate || '—') + '</span>';
                    html += '<span style="display:flex;align-items:center;justify-content:center" title="Not authorized to edit"><i data-lucide="lock" style="width:12px;height:12px;color:#cbd5e1"></i></span>';
                }

                html += '</div>';
            });

            container.innerHTML = html;
        }

        function addNewMicroTaskUI() {
            var title = prompt("Enter new micro task description:");
            if (!title) return;
            // API call to create micro task
            fetch('/admin/projects/{{ $project->id }}/tasks/' + currentEditTaskId + '/micro-tasks', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ title: title })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        var mtList = projectTasksData[currentEditTaskId].micro_tasks || [];
                        mtList.push(data.micro_task);
                        projectTasksData[currentEditTaskId].micro_tasks = mtList;
                        renderMicroTasksList(mtList);
                        lucide.createIcons();
                    } else {
                        alert(data.message || 'Error adding micro task');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred');
                });
        }

        function updateMicroTask(id, field, value) {
            fetch('/admin/projects/{{ $project->id }}/tasks/' + currentEditTaskId + '/micro-tasks/' + id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ [field]: value })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Update local data
                        var mtList = projectTasksData[currentEditTaskId].micro_tasks;
                        var mt = mtList.find(m => m.id === id);
                        if (mt) mt[field] = value;
                        renderMicroTasksList(mtList);
                        lucide.createIcons();
                    } else {
                        alert(data.message || 'Error updating micro task');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred');
                });
        }

        function deleteMicroTask(id) {
            if (!confirm('Delete this micro task?')) return;
            fetch('/admin/projects/{{ $project->id }}/tasks/' + currentEditTaskId + '/micro-tasks/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        var mtList = projectTasksData[currentEditTaskId].micro_tasks.filter(m => m.id !== id);
                        projectTasksData[currentEditTaskId].micro_tasks = mtList;
                        renderMicroTasksList(mtList);
                        lucide.createIcons();
                    }
                });
        }

        function renderActivityTimeline(activities) {
            var container = document.getElementById('task-activities-timeline');
            var countBadge = document.getElementById('activity-count-badge');
            countBadge.textContent = activities.length;

            if (activities.length === 0) {
                container.innerHTML = '<p style="text-align:center;color:#999;font-size:12px;margin:12px 0">No activities yet</p>';
                return;
            }

            var typeColors = {
                'status_change': '#3b82f6',
                'note': '#8b5cf6',
                'client_reply': '#10b981',
                'revision': '#f59e0b',
                'file_upload': '#6366f1'
            };

            var typeLabels = {
                'status_change': 'Status Changed',
                'note': 'Note',
                'client_reply': 'Client Reply',
                'revision': 'Revision',
                'file_upload': 'File Upload'
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
                    '</div>' +
                    '</div>';
            });

            container.innerHTML = html;
        }

        function submitActivity() {
            var type = document.getElementById('activity-type').value;
            var message = document.getElementById('activity-message').value.trim();
            var mentionEl = document.getElementById('activity-mention');
            var notified_user_id = mentionEl ? mentionEl.value : '';

            if (!message) { alert('Please enter an activity message'); return; }
            if (!currentEditTaskId) { alert('No task selected'); return; }

            // Create and submit a hidden form
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/projects/{{ $project->id }}/tasks/' + currentEditTaskId + '/activities';

            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrf);

            var typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type';
            typeInput.value = type;
            form.appendChild(typeInput);

            var msgInput = document.createElement('input');
            msgInput.type = 'hidden';
            msgInput.name = 'message';
            msgInput.value = message;
            form.appendChild(msgInput);

            if (notified_user_id) {
                var mentionInput = document.createElement('input');
                mentionInput.type = 'hidden';
                mentionInput.name = 'notified_user_id';
                mentionInput.value = notified_user_id;
                form.appendChild(mentionInput);
            }

            document.body.appendChild(form);
            form.submit();
        }

        function closeTaskModal() {
            document.getElementById('task-modal').classList.remove('active');
            document.getElementById('task-modal-overlay').classList.remove('active');
        }

        // View Task Description Popup
        function viewTaskDescription(taskId) {
            var task = projectTasksData[taskId];
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
            var task = projectTasksData[taskId];
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
            lucide.createIcons();
        }

        function closeViewTaskModal() {
            document.getElementById('view-task-modal').classList.remove('active');
            document.getElementById('view-task-modal-overlay').classList.remove('active');
        }
    </script>
@endpush