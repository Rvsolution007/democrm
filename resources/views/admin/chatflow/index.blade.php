@extends('admin.layouts.app')

@section('title', 'Chatflow Builder')
@section('breadcrumb', 'Chatflow Builder')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Chatflow Builder</h1>
                <p class="page-description">Define the conversation flow for your AI WhatsApp bot. Steps are followed in order.</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="openAddStep()"><i data-lucide="plus" style="width:16px;height:16px"></i> Add Step</button>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    {{-- Dynamic Column Info Banner --}}
    @if($uniqueColumn || $baseColumn)
    <div style="background:linear-gradient(135deg,#eef2ff,#faf5ff);border:1px solid #c7d2fe;border-radius:8px;padding:14px 20px;margin-bottom:20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:6px">
            <span style="font-size:13px;color:#6366f1;font-weight:600">📊 Detected Columns:</span>
        </div>
        @if($baseColumn)
        <div style="padding:4px 12px;background:#dbeafe;border-radius:16px;font-size:12px;font-weight:500;color:#1e40af">
            📦 Base: {{ $baseColumn->name }}
        </div>
        @endif
        @if($uniqueColumn)
        <div style="padding:4px 12px;background:#fef3c7;border-radius:16px;font-size:12px;font-weight:500;color:#92400e">
            🏷️ Unique: {{ $uniqueColumn->name }}
        </div>
        @endif
        <div style="font-size:11px;color:var(--text-muted);margin-left:auto">
            Based on your <a href="{{ route('admin.catalogue-columns.index') }}" style="color:#6366f1;text-decoration:underline">Catalogue Settings</a>
        </div>
    </div>
    @endif

    <!-- Flow Preview -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-content">
            <h3 style="margin:0 0 16px 0;font-size:16px">Flow Preview</h3>
            <div id="flow-preview" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;min-height:40px">
                @forelse($steps as $index => $step)
                    <div style="display:flex;align-items:center;gap:8px">
                        @if($index > 0)
                            <i data-lucide="arrow-right" style="width:16px;height:16px;color:var(--text-muted)"></i>
                        @endif
                        <div style="padding:6px 12px;border-radius:20px;font-size:13px;font-weight:500;
                            @if($step->step_type === 'ask_category') background:#fce7f3;color:#9d174d;
                            @elseif($step->step_type === 'ask_product' || $step->step_type === 'ask_unique_column') background:#dbeafe;color:#1e40af;
                            @elseif($step->step_type === 'ask_base_column') background:#e0e7ff;color:#3730a3;
                            @elseif($step->step_type === 'ask_combo') background:#fef3c7;color:#92400e;
                            @elseif($step->step_type === 'ask_optional') background:#e0e7ff;color:#3730a3;
                            @elseif($step->step_type === 'send_summary') background:#d1fae5;color:#065f46;
                            @else background:#f3f4f6;color:#374151;
                            @endif">
                            {{ $step->name }}
                            @if($step->is_optional) <small>(opt)</small> @endif
                            @if($step->hasMedia()) <span title="Has attached media">📎</span> @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted" style="margin:0">No steps defined. Add steps to build your chatflow.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Steps Table -->
    <div class="card">
        <div class="card-content" style="padding:0">
            <table class="data-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase;width:30px">⠿</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase;width:40px">#</th>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase">Name</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase">Type</th>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase">Question / Details</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase">Flags</th>
                        <th style="padding:12px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:600;color:var(--text-muted);font-size:12px;text-transform:uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody id="steps-tbody">
                    @forelse($steps as $step)
                        <tr data-id="{{ $step->id }}" style="border-bottom:1px solid var(--border)">
                            <td style="padding:12px 16px;cursor:grab;color:var(--text-muted)">⠿</td>
                            <td style="padding:12px 16px;text-align:center;font-weight:600;color:var(--text-muted)">{{ $step->sort_order }}</td>
                            <td style="padding:12px 16px;font-weight:600">{{ $step->name }}</td>
                            <td style="padding:12px 16px;text-align:center">
                                @php
                                    $typeColors = [
                                        'ask_category' => 'badge-primary',
                                        'ask_product' => 'badge-info',
                                        'ask_unique_column' => 'badge-info',
                                        'ask_base_column' => 'badge-secondary',
                                        'ask_combo' => 'badge-warning',
                                        'ask_optional' => 'badge-outline',
                                        'ask_custom' => 'badge-outline',
                                        'send_summary' => 'badge-success',
                                    ];
                                    $typeLabels = [
                                        'ask_category' => 'ask category',
                                        'ask_product' => 'ask unique column',
                                        'ask_unique_column' => 'ask unique column',
                                        'ask_base_column' => 'ask base column',
                                        'ask_combo' => 'ask combo',
                                        'ask_optional' => 'ask optional',
                                        'ask_custom' => 'ask optional',
                                        'send_summary' => 'send summary',
                                    ];
                                @endphp
                                <span class="badge {{ $typeColors[$step->step_type] ?? 'badge-outline' }}">{{ $typeLabels[$step->step_type] ?? str_replace('_', ' ', $step->step_type) }}</span>
                            </td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--text-muted)">
                                @if($step->question_text)
                                    "{{ Str::limit($step->question_text, 60) }}"
                                @elseif($step->linkedColumn)
                                    Column: {{ $step->linkedColumn->name }}
                                @elseif($step->field_key)
                                    Field: {{ $step->field_key }}
                                @else
                                    —
                                @endif
                                @if($step->media_path)
                                    <span style="margin-left:6px;color:#6366f1" title="{{ $step->media_path }}">📎 Media</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                @if($step->is_optional) <span class="badge badge-outline" style="margin:0 2px">Optional</span> @endif
                                <span style="font-size:11px;color:var(--text-muted)">max {{ $step->max_retries }} retries</span>
                            </td>
                            <td style="padding:12px 16px;text-align:right">
                                <div style="display:flex;gap:4px;justify-content:flex-end">
                                    <button class="btn btn-outline btn-sm" onclick='editStep({{ json_encode($step) }})' style="padding:4px 10px;font-size:12px">
                                        <i data-lucide="edit" style="width:13px;height:13px"></i>
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="deleteStep({{ $step->id }})" style="color:var(--destructive);padding:4px 10px;font-size:12px">
                                        <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="7" style="text-align:center;padding:40px">
                                <i data-lucide="git-branch" style="width:48px;height:48px;color:#ccc;margin:0 auto 16px;display:block"></i>
                                <p class="text-muted">No chatflow steps defined. Click "Add Step" to start building your bot's conversation flow.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Step Modal -->
    <div id="step-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
        <div style="background:white;border-radius:8px;width:95%;max-width:650px;max-height:92vh;overflow-y:auto">
            <div style="padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center">
                <h3 id="step-modal-title" style="margin:0">Add Step</h3>
                <button onclick="closeStepModal()" style="background:none;border:none;font-size:24px;cursor:pointer">&times;</button>
            </div>
            <form id="step-form" onsubmit="saveStep(event)">
                <input type="hidden" id="step-id" value="">
                <div style="padding:20px">
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Step Name *</label>
                        <input type="text" id="step-name" required placeholder="e.g., Ask Product, Ask Finish" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Step Type *</label>
                        <select id="step-type" onchange="toggleStepFields()" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="ask_category">📂 Ask Category — Show category list first</option>
                            <option value="ask_base_column">📦 Ask Base Column — {{ $baseColumn ? $baseColumn->name : 'Product Name' }} selection</option>
                            <option value="ask_unique_column">🏷️ Ask Unique Column — {{ $uniqueColumn ? $uniqueColumn->name : 'Model' }} list</option>
                            <option value="ask_combo">🎨 Ask Combo — Ask a combo dimension</option>
                            <option value="ask_optional">📝 Ask Optional — Optional question</option>
                            <option value="send_summary">📋 Send Summary — Order summary</option>
                        </select>
                    </div>
                    <div id="combo-column-group" style="margin-bottom:16px;display:none">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Linked Combo Column *</label>
                        <select id="step-linked-column" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                            <option value="">— Select —</option>
                            @foreach($comboColumns as $col)
                                <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="question-group" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Question Text <small style="color:var(--text-muted)">(AI will use this as reference & enhance it)</small></label>
                        <input type="text" id="step-question" placeholder="e.g., Kaunsa finish chahiye?" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <small style="color:var(--text-muted)">Bot will take this as a guideline and make it smart & friendly</small>
                    </div>
                    <div id="media-group" style="margin-bottom:16px">
                        <label style="display:block;margin-bottom:4px;font-weight:500">📎 Attach Media to this Step <small style="color:var(--text-muted)">(Optional)</small></label>
                        <input type="text" id="step-media" placeholder="e.g., /uploads/brochure.pdf or image URL" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <small style="color:var(--text-muted)">Image/PDF/Video URL — will be sent before the question on WhatsApp</small>
                    </div>
                    <div id="field-key-group" style="margin-bottom:16px;display:none">
                        <label style="display:block;margin-bottom:4px;font-weight:500">Field Key * (where to save answer)</label>
                        <input type="text" id="step-field-key" placeholder="e.g., city, name, business" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px">
                        <small style="color:var(--text-muted)">This maps to Lead data field</small>
                    </div>
                    <div style="display:flex;gap:20px;margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                            <input type="checkbox" id="step-optional"> Optional (ask only once)
                        </label>
                        <div>
                            <label style="display:block;margin-bottom:4px;font-weight:500">Max Retries</label>
                            <input type="number" id="step-retries" value="2" min="1" max="5" style="width:70px;padding:6px;border:1px solid #ddd;border-radius:4px">
                        </div>
                    </div>
                </div>
                <div style="padding:20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:10px">
                    <button type="button" class="btn btn-outline" onclick="closeStepModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Step</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openAddStep() {
        document.getElementById('step-modal-title').textContent = 'Add Step';
        document.getElementById('step-id').value = '';
        document.getElementById('step-name').value = '';
        document.getElementById('step-type').value = 'ask_category';
        document.getElementById('step-linked-column').value = '';
        document.getElementById('step-question').value = '';
        document.getElementById('step-media').value = '';
        document.getElementById('step-field-key').value = '';
        document.getElementById('step-optional').checked = false;
        document.getElementById('step-retries').value = 2;
        toggleStepFields();
        document.getElementById('step-modal').style.display = 'flex';
    }

    function editStep(step) {
        document.getElementById('step-modal-title').textContent = 'Edit Step';
        document.getElementById('step-id').value = step.id;
        document.getElementById('step-name').value = step.name;
        // Map legacy ask_product to ask_unique_column for display
        var stepType = step.step_type === 'ask_product' ? 'ask_unique_column' : step.step_type;
        // Map legacy ask_custom to ask_optional for display
        stepType = stepType === 'ask_custom' ? 'ask_optional' : stepType;
        document.getElementById('step-type').value = stepType;
        document.getElementById('step-linked-column').value = step.linked_column_id || '';
        document.getElementById('step-question').value = step.question_text || '';
        document.getElementById('step-media').value = step.media_path || '';
        document.getElementById('step-field-key').value = step.field_key || '';
        document.getElementById('step-optional').checked = step.is_optional;
        document.getElementById('step-retries').value = step.max_retries || 2;
        toggleStepFields();
        document.getElementById('step-modal').style.display = 'flex';
    }

    function closeStepModal() { document.getElementById('step-modal').style.display = 'none'; }

    function toggleStepFields() {
        var type = document.getElementById('step-type').value;
        document.getElementById('combo-column-group').style.display = type === 'ask_combo' ? 'block' : 'none';
        document.getElementById('field-key-group').style.display = type === 'ask_optional' ? 'block' : 'none';
    }

    function saveStep(e) {
        e.preventDefault();
        var id = document.getElementById('step-id').value;
        var url = id ? '{{ url("admin/chatflow") }}/' + id : '{{ route("admin.chatflow.store") }}';
        var method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('step-name').value,
                step_type: document.getElementById('step-type').value,
                linked_column_id: document.getElementById('step-linked-column').value || null,
                question_text: document.getElementById('step-question').value || null,
                media_path: document.getElementById('step-media').value || null,
                field_key: document.getElementById('step-field-key').value || null,
                is_optional: document.getElementById('step-optional').checked ? 1 : 0,
                max_retries: parseInt(document.getElementById('step-retries').value) || 2,
            })
        }).then(r => r.json()).then(data => {
            if (data.success) { showAlert('success', data.message); setTimeout(() => location.reload(), 500); }
            else { showAlert('error', data.message || 'Error'); }
        }).catch(err => showAlert('error', 'Request failed'));
    }

    function deleteStep(id) {
        if (!confirm('Delete this chatflow step?')) return;
        fetch('{{ url("admin/chatflow") }}/' + id, {
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
        document.getElementById('alert-container').innerHTML = '<div style="padding:12px 20px;background:'+bg+';color:'+color+';border-radius:4px;margin-bottom:20px">'+msg+'</div>';
    }

    document.getElementById('step-modal').addEventListener('click', function(e) { if (e.target === this) closeStepModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeStepModal(); });
</script>
@endpush
