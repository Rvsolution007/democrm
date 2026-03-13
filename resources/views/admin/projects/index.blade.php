@extends('admin.layouts.app')

@section('title', 'Projects')
@section('breadcrumb', 'Projects')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Projects</h1>
                <p class="page-description">Manage and track your projects</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 20px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
            <i data-lucide="check-circle" style="width:18px;height:18px"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="table-container">
        <div class="table-toolbar" style="padding:16px 20px">
            <form method="GET" id="project-filter-form" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;width:100%">
                {{-- Search --}}
                <div style="flex:1;min-width:200px;max-width:320px">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px">Search</label>
                    <div style="position:relative">
                        <i data-lucide="search" style="width:15px;height:15px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
                        <input type="text" name="search" id="project-search-input" value="{{ request('search') }}" placeholder="Name, client, number..." autocomplete="off"
                            style="width:100%;padding:8px 10px 8px 34px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;background:#f8fafc;transition:all .2s"
                            onfocus="this.style.borderColor='#3b82f6';this.style.background='#fff'" onblur="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc'"
                            oninput="autoAjaxSearch(this.form)">
                    </div>
                </div>

                {{-- Status --}}
                <div style="min-width:140px">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px">Status</label>
                    <select name="status" class="form-select" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc" onchange="autoAjaxSearch(this.form)">
                        <option value="">All</option>
                        @foreach(['pending', 'in_progress', 'completed', 'on_hold', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Assigned To --}}
                <div style="min-width:150px">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px">Assigned To</label>
                    <select name="assigned_to" class="form-select" style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc" onchange="autoAjaxSearch(this.form)">
                        <option value="">All</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div style="min-width:200px">
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:5px">Date Range</label>
                    <div style="position:relative">
                        <i data-lucide="calendar"
                            style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94a3b8"></i>
                        <input type="text" id="project-date-range-picker" class="form-input"
                            placeholder="Select Date Range" 
                            style="width:100%;padding:8px 10px 8px 32px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc;cursor:pointer">
                        <input type="hidden" name="start_date" id="start-date" value="{{ request('start_date') }}">
                        <input type="hidden" name="due_date" id="due-date" value="{{ request('due_date') }}">
                    </div>
                </div>

                {{-- Buttons --}}
                <div style="display:flex;gap:8px;align-items:center;padding-bottom:1px">
                    @if(request()->hasAny(['search','status','assigned_to','start_date','due_date']))
                        <a href="{{ route('admin.projects.index') }}" style="padding:8px 14px;background:#f1f5f9;color:#64748b;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:4px;transition:all .15s"
                            onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                            <i data-lucide="x" style="width:14px;height:14px"></i> Clear Filters
                        </a>
                    @endif
                </div>
            </form>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px">
                <div style="display:flex;align-items:center;gap:12px">
                    <div id="projects-count-badge" style="background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);border:1px solid #e2e8f0;padding:6px 14px;border-radius:20px;display:flex;align-items:center;gap:8px;box-shadow:inset 0 1px 2px rgba(255,255,255,0.8), 0 1px 2px rgba(0,0,0,0.04)">
                        <div style="width:20px;height:20px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center">
                            <i data-lucide="briefcase" style="width:12px;height:12px;color:#4f46e5"></i>
                        </div>
                        <span style="font-size:13px;font-weight:600;color:#334155;letter-spacing:0.3px">
                            <span id="filtered-projects-count" style="color:#4f46e5;font-weight:700">{{ $projects->total() }}</span> 
                            <span style="color:#64748b;font-weight:500">Projects Found</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>Tasks</th>
                        <th>Start Date</th>
                        <th>Due Date</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="projects-tbody">
                    @forelse($projects as $project)
                        <tr>
                            <td>
                                <a href="{{ route('admin.projects.show', $project->id) }}" class="font-medium" style="color:var(--primary);text-decoration:none">
                                    {{ $project->project_id_code }}
                                </a>
                                <p class="text-xs text-muted" style="margin-top:2px">{{ $project->name }}</p>
                            </td>
                            <td>{{ $project->client->display_name ?? '—' }}</td>
                            <td>
                                @php
                                    $contactPhone = $project->lead->phone ?? $project->client->phone ?? null;
                                @endphp
                                @if($contactPhone)
                                    <a href="tel:{{ $contactPhone }}" style="color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:4px">
                                        <i data-lucide="phone" style="width:14px;height:14px"></i>
                                        {{ $contactPhone }}
                                    </a>
                                @else
                                    <span style="color:#999">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'secondary',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'on_hold' => 'warning',
                                        'cancelled' => 'destructive',
                                    ];
                                @endphp
                                <span class="badge badge-{{ $statusColors[$project->status] ?? 'secondary' }}">
                                    {{ ucwords(str_replace('_', ' ', $project->status)) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $totalTasks = $project->tasks->count();
                                    $doneTasks = $project->tasks->where('status', 'done')->count();
                                    $doingTasks = $project->tasks->whereIn('status', ['doing', 'in_progress'])->count();
                                    $todoTasks = $totalTasks - $doneTasks - $doingTasks;
                                    $pct = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;
                                @endphp
                                @if($totalTasks > 0)
                                    <div style="min-width:120px">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                                            <span style="font-size:11px;font-weight:600;color:#334155">{{ $doneTasks }}/{{ $totalTasks }}</span>
                                            <span style="font-size:10px;color:#94a3b8">{{ $pct }}%</span>
                                        </div>
                                        <div style="width:100%;height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden">
                                            <div style="width:{{ $pct }}%;height:100%;background:{{ $pct === 100 ? '#10b981' : '#3b82f6' }};border-radius:2px;transition:width .3s"></div>
                                        </div>
                                        <div style="display:flex;gap:4px;margin-top:4px">
                                            @if($todoTasks > 0)
                                                <span style="font-size:9px;background:#f1f5f9;color:#64748b;padding:1px 5px;border-radius:3px">{{ $todoTasks }} todo</span>
                                            @endif
                                            @if($doingTasks > 0)
                                                <span style="font-size:9px;background:#dbeafe;color:#2563eb;padding:1px 5px;border-radius:3px">{{ $doingTasks }} doing</span>
                                            @endif
                                            @if($doneTasks > 0)
                                                <span style="font-size:9px;background:#d1fae5;color:#059669;padding:1px 5px;border-radius:3px">{{ $doneTasks }} done</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span style="color:#999;font-size:12px">No tasks</span>
                                @endif
                            </td>
                            <td>{{ $project->start_date ? $project->start_date->format('d M Y') : '—' }}</td>
                            <td>{{ $project->due_date ? $project->due_date->format('d M Y') : '—' }}</td>
                            <td>{{ $project->assignedTo->name ?? 'Unassigned' }}</td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('admin.projects.show', $project->id) }}" class="btn btn-ghost btn-icon btn-sm" title="View">
                                        <i data-lucide="eye" style="width:16px;height:16px"></i>
                                    </a>
                                    @if(can('projects.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" title="Edit" onclick="openEditModal({{ json_encode($project) }})">
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                    @if(can('projects.delete'))
                                        <form method="POST" action="{{ route('admin.projects.destroy', $project->id) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this project and all its tasks?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-ghost btn-icon btn-sm" style="color:var(--destructive)" title="Delete">
                                                <i data-lucide="trash-2" style="width:16px;height:16px"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px 0;color:#999">
                                <i data-lucide="briefcase" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                                <p style="margin:0;font-size:14px">No projects found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-footer" id="projects-table-footer">
            <span id="projects-footer-text">Showing {{ $projects->count() }} of {{ $projects->total() }} entries</span>
            <span id="projects-pagination">{{ $projects->links() }}</span>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="project-modal-overlay" class="overlay" onclick="closeProjectModal()"></div>
    <div id="project-modal" class="modal">
        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;width:100%;">
            <div style="display:flex;align-items:center;gap:12px;">
                <h3 class="modal-title" style="margin:0;">Edit Project</h3>
                <button type="button" id="edit-project-quote-btn" onclick="" 
                        title="View Related Quote" 
                        style="display:none; align-items:center; justify-content:center; width:28px; height:28px; background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe; border-radius:6px; cursor:pointer;"
                        data-quote-btn="true">
                    <i data-lucide="file-text" style="width:14px; height:14px;"></i>
                </button>
            </div>
            <button class="modal-close" onclick="closeProjectModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="project-form" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Project Name <span style="color:red">*</span></label>
                    <input type="text" name="name" id="edit-name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit-description" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <div id="edit-contact-number" style="padding:10px 12px;background:#f9f9f9;border:1px solid #ddd;border-radius:6px;font-size:14px;display:flex;align-items:center;gap:6px;min-height:42px">
                        <span style="color:#999">—</span>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit-status" class="form-input">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="on_hold">On Hold</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assigned To</label>
                        <select name="assigned_to_user_id" id="edit-assigned" class="form-input">
                            <option value="">Unassigned</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="edit-start-date" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" id="edit-due-date" class="form-input">
                    </div>
                </div>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e2e8f0;">
                
                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <label class="form-label" style="margin:0">Tasks (<span id="edit-tasks-count">0</span>)</label>
                    </div>
                    <div class="table-wrapper" style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <table class="table" style="margin: 0;">
                            <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 10;">
                                <tr>
                                    <th style="font-size: 12px; padding: 10px 12px;">Task</th>
                                    <th style="font-size: 12px; padding: 10px 12px;">Desc</th>
                                    <th style="font-size: 12px; padding: 10px 12px;">Status</th>
                                    <th style="font-size: 12px; padding: 10px 12px; text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="edit-tasks-body">
                                <tr>
                                    <td colspan="4" style="text-align:center;padding:20px 0;color:#999;font-size:13px;">
                                        Loading tasks...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="font-size:12px; color:#666; margin-top:8px;">
                        <em>Note: To edit or delete tasks, please open the project details view.</em>
                    </div>
                </div>
                <div class="modal-footer" style="padding:16px 0 0;margin:0">
                    <button type="button" class="btn btn-outline" onclick="closeProjectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Include Flatpickr for Date Range Selection -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let sDate = document.getElementById('start-date').value;
            let dDate = document.getElementById('due-date').value;
            let defDates = [];
            if (sDate && dDate) defDates = [sDate, dDate];

            flatpickr("#project-date-range-picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                defaultDate: defDates,
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        document.getElementById('start-date').value = instance.formatDate(selectedDates[0], "Y-m-d");
                        document.getElementById('due-date').value = instance.formatDate(selectedDates[1], "Y-m-d");
                        document.getElementById('project-filter-form').submit();
                    } else if (selectedDates.length === 0) {
                        document.getElementById('start-date').value = '';
                        document.getElementById('due-date').value = '';
                        document.getElementById('project-filter-form').submit();
                    }
                }
            });
        });
        var projectFormChanged = false;

        document.addEventListener('DOMContentLoaded', () => {
            const projectForm = document.getElementById('project-form');
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

        function openEditModal(project) {
            projectFormChanged = false; // Reset on open

            document.getElementById('project-form').action = '/admin/projects/' + project.id;
            document.getElementById('edit-name').value = project.name;
            
            // Clear auto-generated description if present
            var projectDesc = project.description || '';
            if (projectDesc.includes('Auto-created from lead conversion')) {
                projectDesc = '';
            }
            document.getElementById('edit-description').value = projectDesc;
            
            document.getElementById('edit-status').value = project.status;
            document.getElementById('edit-assigned').value = project.assigned_to_user_id || '';
            document.getElementById('edit-start-date').value = project.start_date ? project.start_date.split('T')[0] : '';
            document.getElementById('edit-due-date').value = project.due_date ? project.due_date.split('T')[0] : '';

            // Contact number from lead or client
            var phone = null;
            if (project.lead && project.lead.phone) {
                phone = project.lead.phone;
            } else if (project.client && project.client.phone) {
                phone = project.client.phone;
            }
            var contactEl = document.getElementById('edit-contact-number');
            if (phone) {
                contactEl.innerHTML = '<i data-lucide="phone" style="width:14px;height:14px;color:#3b82f6"></i> <a href="tel:' + phone + '" style="color:#3b82f6;text-decoration:none">' + phone + '</a>';
            } else {
                contactEl.innerHTML = '<span style="color:#999">—</span>';
            }

            // Populate Tasks if available
            var tasksBody = document.getElementById('edit-tasks-body');
            var tasksCount = document.getElementById('edit-tasks-count');
            
            if (project.tasks && Array.isArray(project.tasks)) {
                tasksCount.textContent = project.tasks.length;
                if (project.tasks.length === 0) {
                    tasksBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px 0;color:#999;font-size:13px;">No tasks in this project</td></tr>';
                } else {
                    var html = '';
                    project.tasks.forEach(function(task) {
                        var statusLabel = task.status;
                        var badgeClass = 'secondary';
                        if (task.status === 'done') { statusLabel = 'Done'; badgeClass = 'success'; }
                        else if (task.status === 'doing') { statusLabel = 'In Progress'; badgeClass = 'info'; }
                        else if (task.status === 'todo') { statusLabel = 'Todo'; badgeClass = 'secondary'; }
                        
                        var descHtml = task.description ? '<span style="color:#3b82f6; font-size:12px;"><i data-lucide="file-text" style="width:12px;height:12px"></i> Yes</span>' : '<span style="color:#999;font-size:12px;">—</span>';
                        
                        html += '<tr>' +
                            '<td style="padding: 10px 12px; font-size:13px; font-weight:500;">' + (task.title || 'Untitled') + '</td>' +
                            '<td style="padding: 10px 12px;">' + descHtml + '</td>' +
                            '<td style="padding: 10px 12px;"><span style="font-size:11px;" class="badge badge-' + badgeClass + '">' + statusLabel + '</span></td>' +
                            '<td style="padding: 10px 12px; text-align:right;">' +
                                '<a href="/admin/projects/' + project.id + '" style="font-size:12px; color:#3b82f6; text-decoration:none;">View <i data-lucide="external-link" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i></a>' +
                            '</td>' +
                        '</tr>';
                    });
                    tasksBody.innerHTML = html;
                }
            } else {
                tasksCount.textContent = '0';
                tasksBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px 0;color:#999;font-size:13px;">Please view the project to see tasks.</td></tr>';
            }

            // Quote button
            var quoteBtn = document.getElementById('edit-project-quote-btn');
            if (project.quote_id) {
                quoteBtn.style.display = 'inline-flex';
                quoteBtn.onclick = function() {
                    openQuoteShortcut('/admin/quotes?open_quote=' + project.quote_id);
                };
            } else {
                quoteBtn.style.display = 'none';
                quoteBtn.onclick = null;
            }

            document.getElementById('project-modal').classList.add('active');
            document.getElementById('project-modal-overlay').classList.add('active');
            lucide.createIcons();
        }

        function closeProjectModal() {
            document.getElementById('project-modal').classList.remove('active');
            document.getElementById('project-modal-overlay').classList.remove('active');
        }

        // ═══════════════════════════════════════
        //  AJAX AUTO-SEARCH
        // ═══════════════════════════════════════
        (function() {
            const searchInput = document.getElementById('project-search-input');
            const tbody = document.getElementById('projects-tbody');
            const footerText = document.getElementById('projects-footer-text');
            const pagination = document.getElementById('projects-pagination');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const canWrite = {{ can('projects.write') ? 'true' : 'false' }};
            const canDelete = {{ can('projects.delete') ? 'true' : 'false' }};
            let debounceTimer = null;

            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => doAjaxSearch(this.value), 600);
            });

            // Prevent form submit on Enter for search — do AJAX instead
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(debounceTimer);
                    doAjaxSearch(this.value);
                }
            });

            function doAjaxSearch(query) {
                // Build URL with all current filter params
                const form = document.getElementById('project-filter-form');
                const formData = new FormData(form);
                formData.set('search', query);
                const params = new URLSearchParams(formData).toString();

                // Show loading
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px 0;color:#94a3b8"><div style="display:flex;align-items:center;justify-content:center;gap:8px"><svg style="animation:spin 1s linear infinite;width:18px;height:18px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"/></svg> Searching...</div></td></tr>';

                fetch('/admin/projects?' + params, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.projects || data.projects.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px 0;color:#999"><i data-lucide="briefcase" style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i><p style="margin:0;font-size:14px">No projects found</p></td></tr>';
                    } else {
                        tbody.innerHTML = data.projects.map(p => buildRow(p)).join('');
                    }

                    footerText.textContent = 'Showing ' + data.showing + ' of ' + data.total + ' entries';
                    if (pagination) pagination.innerHTML = '';
                    
                    const badgeCount = document.getElementById('filtered-projects-count');
                    if (badgeCount) badgeCount.textContent = data.total;

                    if (typeof lucide !== 'undefined') lucide.createIcons();
                })
                .catch(err => {
                    console.error('Search error:', err);
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:30px 0;color:#dc2626">Search failed. Please try again.</td></tr>';
                });
            }

            function buildRow(p) {
                const statusColors = {
                    'pending': 'secondary', 'in_progress': 'info', 'completed': 'success',
                    'on_hold': 'warning', 'cancelled': 'destructive'
                };
                const badge = statusColors[p.status] || 'secondary';
                const statusLabel = p.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

                let phoneTd = '<span style="color:#999">—</span>';
                if (p.contact_phone) {
                    phoneTd = '<a href="tel:' + p.contact_phone + '" style="color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:4px"><i data-lucide="phone" style="width:14px;height:14px"></i>' + p.contact_phone + '</a>';
                }

                let descHtml = '';
                if (p.description) {
                    descHtml = '<p class="text-xs text-muted" style="margin-top:2px">' + escHtml(p.description) + '</p>';
                }

                let actionsBtns = '<a href="/admin/projects/' + p.id + '" class="btn btn-ghost btn-icon btn-sm" title="View"><i data-lucide="eye" style="width:16px;height:16px"></i></a>';
                if (canWrite) {
                    actionsBtns += '<button class="btn btn-ghost btn-icon btn-sm" title="Edit" onclick=\'openEditModal(' + JSON.stringify(p.raw).replace(/'/g, "\\'") + ')\'><i data-lucide="edit" style="width:16px;height:16px"></i></button>';
                }
                if (canDelete) {
                    actionsBtns += '<form method="POST" action="/admin/projects/' + p.id + '" style="display:inline" onsubmit="return confirm(\'Delete this project?\')">' +
                        '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                        '<input type="hidden" name="_method" value="DELETE">' +
                        '<button type="submit" class="btn btn-ghost btn-icon btn-sm" style="color:var(--destructive)" title="Delete"><i data-lucide="trash-2" style="width:16px;height:16px"></i></button></form>';
                }

                return '<tr>' +
                    '<td><a href="/admin/projects/' + p.id + '" class="font-medium" style="color:var(--primary);text-decoration:none">' + escHtml(p.project_id_code || p.name) + '</a><p class="text-xs text-muted" style="margin-top:2px">' + escHtml(p.name) + '</p></td>' +
                    '<td>' + escHtml(p.client_name) + '</td>' +
                    '<td>' + phoneTd + '</td>' +
                    '<td><span class="badge badge-' + badge + '">' + statusLabel + '</span></td>' +
                    '<td><span style="font-size:13px">' + p.tasks_done + '/' + p.tasks_total + '</span></td>' +
                    '<td>' + (p.start_date || '—') + '</td>' +
                    '<td>' + (p.due_date || '—') + '</td>' +
                    '<td>' + escHtml(p.assigned_to) + '</td>' +
                    '<td><div class="table-actions">' + actionsBtns + '</div></td>' +
                '</tr>';
            }

            function escHtml(str) {
                if (!str) return '';
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
        })();
    </script>
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
@endpush