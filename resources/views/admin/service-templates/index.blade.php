@extends('admin.layouts.app')

@section('title', 'Service Templates')
@section('breadcrumb', 'Service Templates')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Service Templates</h1>
                <p class="page-description">Pre-defined micro task lists for each service/product. When a lead is converted
                    to
                    client, parent tasks and their micro tasks will be auto-created from these templates.</p>
            </div>
            <div class="page-actions">
                @if(can('settings.write'))
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i data-lucide="plus" style="width:16px;height:16px"></i> New Template
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

    <!-- Templates List -->
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Template Name</th>
                        <th>Linked Product</th>
                        <th>Micro Tasks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <p class="font-medium" style="margin:0">{{ $template->name }}</p>
                            </td>
                            <td>
                                @if($template->product)
                                    <span class="badge badge-info">{{ $template->product->name }}</span>
                                @else
                                    <span style="color:#999">— Not linked</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:4px">
                                    @foreach($template->getTaskSteps() as $step)
                                        <span
                                            style="background:#f0f4ff;color:#3b82f6;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500">
                                            {{ $step['order'] }}. {{ $step['title'] }}
                                            @if(!empty($step['role_id']))
                                                <span style="color:#8b5cf6;font-weight:600">→
                                                    {{ $roles->firstWhere('id', $step['role_id'])?->name ?? '' }}</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $template->is_active ? 'success' : 'secondary' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    @if(can('settings.write'))
                                        <button class="btn btn-ghost btn-icon btn-sm" title="Edit"
                                            onclick="openEditModal({{ $template->id }})">
                                            <i data-lucide="edit" style="width:16px;height:16px"></i>
                                        </button>
                                    @endif
                                    @if(can('settings.delete'))
                                        <form method="POST" action="{{ route('admin.service-templates.destroy', $template->id) }}"
                                            style="display:inline" onsubmit="return confirm('Delete this template?')">
                                            @csrf @method('DELETE')
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
                            <td colspan="5" style="text-align:center;padding:40px 0;color:#999">
                                <i data-lucide="clipboard-list"
                                    style="width:40px;height:40px;color:#ddd;margin-bottom:12px"></i>
                                <p style="margin:0;font-size:14px">No service templates yet</p>
                                <p style="margin:4px 0 0;font-size:12px;color:#bbb">Create templates to auto-generate micro
                                    tasks when
                                    leads are converted</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="template-modal-overlay" class="overlay" onclick="closeModal()"></div>
    <div id="template-modal" class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">New Service Template</h3>
            <button class="modal-close" onclick="closeModal()"><i data-lucide="x"></i></button>
        </div>
        <div class="modal-body">
            <form id="template-form" method="POST">
                @csrf
                <div id="method-field"></div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label class="form-label">Template Name <span style="color:red">*</span></label>
                        <input type="text" name="name" id="template-name" class="form-input" required
                            placeholder="e.g., Social Media Ad Campaign">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Link to Product</label>
                        <select name="product_id" id="template-product" class="form-input">
                            <option value="">-- No Product Link --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                        <label class="form-label" style="margin:0">Micro Task Steps <span style="color:red">*</span></label>
                        <button type="button" class="btn btn-outline btn-sm" onclick="addTaskRow()"
                            style="font-size:12px;padding:4px 12px">
                            <i data-lucide="plus" style="width:12px;height:12px"></i> Add Step
                        </button>
                    </div>
                    <div id="tasks-container" style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">
                        <div
                            style="display:grid;grid-template-columns:40px 1fr 120px 140px 40px;gap:0;background:#f8fafc;padding:8px 12px;border-bottom:1px solid #e2e8f0">
                            <span style="font-size:11px;font-weight:600;color:#64748b">#</span>
                            <span style="font-size:11px;font-weight:600;color:#64748b">Micro Task Title</span>
                            <span style="font-size:11px;font-weight:600;color:#64748b">Priority</span>
                            <span style="font-size:11px;font-weight:600;color:#64748b">Role</span>
                            <span></span>
                        </div>
                        <div id="tasks-list" style="max-height:300px;overflow-y:auto">
                            <!-- Task rows will be added here -->
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="padding:16px 0 0;margin:0">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submit-btn">Create Template</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        var templateData = {};
        @foreach($templates as $t)
            templateData[{{ $t->id }}] = @json($t);
        @endforeach

                    var taskRowIndex = 0;

        var rolesData = @json($roles);

        function buildRoleOptions(selectedRoleId) {
            var html = '<option value="">-- No Role --</option>';
            rolesData.forEach(function (role) {
                html += '<option value="' + role.id + '"' + (selectedRoleId == role.id ? ' selected' : '') + '>' + escapeHtml(role.name) + '</option>';
            });
            return html;
        }

        function addTaskRow(title, priority, roleId) {
            title = title || '';
            priority = priority || 'medium';
            roleId = roleId || '';
            taskRowIndex++;
            var html = '<div class="task-row" id="task-row-' + taskRowIndex + '" style="display:grid;grid-template-columns:40px 1fr 120px 140px 40px;gap:8px;align-items:center;padding:8px 12px;border-bottom:1px solid #f0f0f0">' +
                '<span style="font-size:13px;font-weight:600;color:#94a3b8" data-order>' + taskRowIndex + '</span>' +
                '<input type="text" name="tasks[' + (taskRowIndex - 1) + '][title]" class="form-input" style="padding:6px 10px;font-size:13px" placeholder="e.g., Ad Account Setup" value="' + escapeHtml(title) + '" required>' +
                '<select name="tasks[' + (taskRowIndex - 1) + '][priority]" class="form-input" style="padding:6px 8px;font-size:12px">' +
                '<option value="low"' + (priority === 'low' ? ' selected' : '') + '>Low</option>' +
                '<option value="medium"' + (priority === 'medium' ? ' selected' : '') + '>Medium</option>' +
                '<option value="high"' + (priority === 'high' ? ' selected' : '') + '>High</option>' +
                '</select>' +
                '<select name="tasks[' + (taskRowIndex - 1) + '][role_id]" class="form-input" style="padding:6px 8px;font-size:12px">' +
                buildRoleOptions(roleId) +
                '</select>' +
                '<button type="button" onclick="removeTaskRow(' + taskRowIndex + ')" style="background:#fef2f2;border:none;border-radius:4px;cursor:pointer;padding:4px;display:flex;align-items:center;justify-content:center;color:#ef4444" title="Remove">' +
                '<i data-lucide="x" style="width:14px;height:14px"></i>' +
                '</button>' +
                '</div>';
            document.getElementById('tasks-list').insertAdjacentHTML('beforeend', html);
            lucide.createIcons();
        }

        function removeTaskRow(index) {
            var row = document.getElementById('task-row-' + index);
            if (row) row.remove();
            reorderRows();
        }

        function reorderRows() {
            var rows = document.querySelectorAll('#tasks-list .task-row');
            rows.forEach(function (row, i) {
                row.querySelector('[data-order]').textContent = i + 1;
                var inputs = row.querySelectorAll('input, select');
                inputs.forEach(function (inp) {
                    inp.name = inp.name.replace(/tasks\[\d+\]/, 'tasks[' + i + ']');
                });
            });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        function openCreateModal() {
            document.getElementById('modal-title').textContent = 'New Service Template';
            document.getElementById('template-form').action = '{{ route("admin.service-templates.store") }}';
            document.getElementById('method-field').innerHTML = '';
            document.getElementById('template-name').value = '';
            document.getElementById('template-product').value = '';
            document.getElementById('tasks-list').innerHTML = '';
            document.getElementById('submit-btn').textContent = 'Create Template';
            taskRowIndex = 0;
            addTaskRow();
            document.getElementById('template-modal').classList.add('active');
            document.getElementById('template-modal-overlay').classList.add('active');
            lucide.createIcons();
        }

        function openEditModal(id) {
            var t = templateData[id];
            if (!t) { alert('Template not found'); return; }

            document.getElementById('modal-title').textContent = 'Edit Service Template';
            document.getElementById('template-form').action = '/admin/service-templates/' + id;
            document.getElementById('method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('template-name').value = t.name;
            document.getElementById('template-product').value = t.product_id || '';
            document.getElementById('tasks-list').innerHTML = '';
            document.getElementById('submit-btn').textContent = 'Update Template';

            taskRowIndex = 0;
            var tasks = t.tasks_json || [];
            tasks.sort(function (a, b) { return (a.order || 0) - (b.order || 0); });
            tasks.forEach(function (task) {
                addTaskRow(task.title, task.priority, task.role_id || '');
            });

            if (tasks.length === 0) addTaskRow();

            document.getElementById('template-modal').classList.add('active');
            document.getElementById('template-modal-overlay').classList.add('active');
            lucide.createIcons();
        }

        function closeModal() {
            document.getElementById('template-modal').classList.remove('active');
            document.getElementById('template-modal-overlay').classList.remove('active');
        }
    </script>
@endpush