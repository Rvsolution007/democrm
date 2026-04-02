@extends('admin.layouts.app')

@section('title', 'Chatflow Builder')
@section('breadcrumb', 'Chatflow Builder')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Chatflow Builder</h1>
                <p class="page-description">Design the conversation flow for your AI WhatsApp bot</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-primary" onclick="openAddStep()" style="gap:6px;font-weight:600">
                    <i data-lucide="plus" style="width:16px;height:16px"></i> Add Step
                </button>
            </div>
        </div>
    </div>

    <div id="alert-container"></div>

    {{-- Detected Columns Info --}}
    @if($uniqueColumn)
    <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:12px 18px;margin-bottom:20px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <span style="font-size:12px;color:var(--primary);font-weight:700;letter-spacing:0.3px">📊 DETECTED COLUMNS</span>
        <div style="padding:4px 14px;background:rgba(249,115,22,0.08);border:1px solid rgba(249,115,22,0.2);border-radius:20px;font-size:12px;font-weight:600;color:var(--primary)">
            🏷️ Unique: {{ $uniqueColumn->name }}
        </div>
        @foreach($filterableColumns as $fc)
        <div style="padding:4px 14px;background:rgba(20,184,166,0.08);border:1px solid rgba(20,184,166,0.2);border-radius:20px;font-size:12px;font-weight:500;color:var(--accent)">
            📊 {{ $fc->name }}
        </div>
        @endforeach
        <div style="font-size:11px;color:var(--muted-foreground);margin-left:auto">
            Based on <a href="{{ route('admin.catalogue-columns.index') }}" style="color:var(--primary);text-decoration:underline;font-weight:500">Catalogue Settings</a>
        </div>
    </div>
    @endif

    {{-- Flow Preview --}}
    <div style="background:var(--sidebar-bg);border-radius:var(--radius);padding:24px;margin-bottom:24px;box-shadow:var(--shadow-md)">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:cfPulse 2s infinite"></div>
            <span style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.7);letter-spacing:0.5px;text-transform:uppercase">Live Flow Preview</span>
        </div>
        <div id="flow-preview" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;min-height:38px">
            @forelse($steps as $index => $step)
                <div style="display:flex;align-items:center;gap:6px">
                    @if($index > 0)
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="flex-shrink:0">
                            <path d="M6 10h8M11 6l4 4-4 4" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @endif
                    @php
                        $flowColors = [
                            'ask_category' => ['bg' => 'rgba(249,115,22,0.15)', 'border' => 'rgba(249,115,22,0.4)', 'text' => '#fdba74', 'icon' => '📂'],
                            'ask_unique_column' => ['bg' => 'rgba(14,165,233,0.15)', 'border' => 'rgba(14,165,233,0.4)', 'text' => '#7dd3fc', 'icon' => '🏷️'],
                            'ask_column' => ['bg' => 'rgba(20,184,166,0.15)', 'border' => 'rgba(20,184,166,0.4)', 'text' => '#5eead4', 'icon' => '📊'],
                            'ask_combo' => ['bg' => 'rgba(245,158,11,0.15)', 'border' => 'rgba(245,158,11,0.4)', 'text' => '#fcd34d', 'icon' => '🎨'],
                            'ask_optional' => ['bg' => 'rgba(168,85,247,0.15)', 'border' => 'rgba(168,85,247,0.4)', 'text' => '#c4b5fd', 'icon' => '📝'],
                            'ask_custom' => ['bg' => 'rgba(168,85,247,0.15)', 'border' => 'rgba(168,85,247,0.4)', 'text' => '#c4b5fd', 'icon' => '📝'],
                            'send_summary' => ['bg' => 'rgba(34,197,94,0.15)', 'border' => 'rgba(34,197,94,0.4)', 'text' => '#86efac', 'icon' => '📋'],
                        ];
                        $fci = $flowColors[$step->step_type] ?? ['bg' => 'rgba(148,163,184,0.15)', 'border' => 'rgba(148,163,184,0.4)', 'text' => '#cbd5e1', 'icon' => '⚙️'];
                    @endphp
                    <div style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $fci['bg'] }};border:1px solid {{ $fci['border'] }};color:{{ $fci['text'] }};white-space:nowrap">
                        {{ $fci['icon'] }} {{ $step->name }}
                        @if($step->linkedColumn) <span style="opacity:0.6;font-weight:400">· {{ $step->linkedColumn->name }}</span> @endif
                    </div>
                </div>
            @empty
                <p style="margin:0;color:rgba(255,255,255,0.5);font-style:italic;font-size:13px">No steps defined. Click "Add Step" to build your chatflow.</p>
            @endforelse
        </div>
    </div>

    {{-- Steps Table --}}
    <div class="table-container">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:30px">⠿</th>
                        <th style="width:40px;text-align:center">#</th>
                        <th>Step Name</th>
                        <th style="text-align:center">Type</th>
                        <th>Details</th>
                        <th style="text-align:center">Retries</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody id="steps-tbody">
                    @forelse($steps as $step)
                        <tr data-id="{{ $step->id }}">
                            <td style="cursor:grab;color:var(--muted-foreground);font-size:16px">⠿</td>
                            <td style="text-align:center">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius-sm);background:linear-gradient(135deg,var(--primary),var(--accent));color:white;font-size:12px;font-weight:700">{{ $step->sort_order }}</span>
                            </td>
                            <td style="font-weight:600;font-size:14px">{{ $step->name }}</td>
                            <td style="text-align:center">
                                @php
                                    $typeConfig = [
                                        'ask_category' => ['label' => 'Category', 'class' => 'badge-qualified', 'icon' => '📂'],
                                        'ask_product' => ['label' => 'Unique Column', 'class' => 'badge-info', 'icon' => '🏷️'],
                                        'ask_unique_column' => ['label' => 'Unique Column', 'class' => 'badge-info', 'icon' => '🏷️'],
                                        'ask_column' => ['label' => 'Column Filter', 'class' => 'badge-success', 'icon' => '📊'],
                                        'ask_combo' => ['label' => 'Combo', 'class' => 'badge-warning', 'icon' => '🎨'],
                                        'ask_optional' => ['label' => 'Optional', 'class' => 'badge-contacted', 'icon' => '📝'],
                                        'ask_custom' => ['label' => 'Custom', 'class' => 'badge-contacted', 'icon' => '📝'],
                                        'send_summary' => ['label' => 'Summary', 'class' => 'badge-done', 'icon' => '📋'],
                                    ];
                                    $tc = $typeConfig[$step->step_type] ?? ['label' => $step->step_type, 'class' => 'badge-secondary', 'icon' => '⚙️'];
                                @endphp
                                <span class="badge {{ $tc['class'] }}" style="gap:4px;font-weight:600">
                                    {{ $tc['icon'] }} {{ $tc['label'] }}
                                </span>
                            </td>
                            <td style="font-size:13px;color:var(--muted-foreground)">
                                @if($step->linkedColumn)
                                    <span class="badge badge-qualified" style="font-size:11px;font-weight:500;gap:3px">
                                        🔗 {{ $step->linkedColumn->name }}
                                    </span>
                                @endif
                                @if($step->question_text)
                                    <span style="color:var(--muted-foreground);font-style:italic">"{{ Str::limit($step->question_text, 45) }}"</span>
                                @endif
                                @if($step->field_key)
                                    <span style="padding:2px 8px;background:var(--muted);border-radius:4px;font-size:11px;font-family:monospace;color:var(--muted-foreground)">{{ $step->field_key }}</span>
                                @endif
                                @if(!$step->linkedColumn && !$step->question_text && !$step->field_key)
                                    <span style="color:var(--muted-foreground)">—</span>
                                @endif
                            </td>
                            <td style="text-align:center">
                                <span class="badge badge-secondary" style="font-size:11px;font-weight:600;gap:3px">
                                    🔄 {{ $step->max_retries }}
                                </span>
                            </td>
                            <td style="text-align:right">
                                <div style="display:flex;gap:6px;justify-content:flex-end">
                                    <button class="btn btn-outline btn-sm" onclick='cfEditStep({{ $step->id }})'>
                                        <i data-lucide="edit" style="width:13px;height:13px"></i> Edit
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="cfDeleteStep({{ $step->id }})" style="color:var(--destructive)">
                                        <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="7" style="text-align:center;padding:60px 20px">
                                <div style="max-width:300px;margin:0 auto">
                                    <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,rgba(249,115,22,0.1),rgba(20,184,166,0.1));display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px">🤖</div>
                                    <p style="font-weight:600;font-size:15px;margin:0 0 6px">No steps yet</p>
                                    <p style="font-size:13px;margin:0;color:var(--muted-foreground)">Click "Add Step" to start building your bot's conversation flow</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add/Edit Step Modal — uses standard CRM modal system --}}
    <div id="cf-step-modal" class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 class="modal-title" id="cf-modal-title">Add Step</h3>
            <button class="modal-close" onclick="closeModal('cf-step-modal')"><i data-lucide="x"></i></button>
        </div>
        <form id="cf-step-form" onsubmit="cfSaveStep(event)" data-rv-no-intercept="true">
            <div class="modal-body">
                <input type="hidden" id="cf-step-id" value="">

                <div class="form-group">
                    <label class="form-label required">Step Name</label>
                    <input type="text" id="cf-step-name" class="form-input" required placeholder="e.g., Ask Material, Select Finish">
                </div>

                <div class="form-group">
                    <label class="form-label required">Step Type</label>
                    <select id="cf-step-type" class="form-select" onchange="cfToggleFields()">
                        <option value="ask_category">📂 Ask Category — Show category list first</option>
                        <option value="ask_unique_column">🏷️ Ask Unique Column — {{ $uniqueColumn ? $uniqueColumn->name : 'Model' }} list</option>
                        <option value="ask_combo">🎨 Ask Combo — Ask a combo/variation dimension</option>
                        <option value="ask_column">📊 Ask Column — Filter by catalogue column</option>
                        <option value="ask_optional">📝 Ask Optional — Custom optional question</option>
                        <option value="send_summary">📋 Send Summary — Order summary</option>
                    </select>
                </div>

                <div id="cf-combo-group" class="form-group" style="display:none">
                    <label class="form-label required">Linked Combo Column</label>
                    <select id="cf-linked-combo" class="form-select">
                        <option value="">— Select Combo Column —</option>
                        @foreach($comboColumns as $col)
                            <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->slug }})</option>
                        @endforeach
                    </select>
                    <small style="color:var(--muted-foreground);font-size:11px;margin-top:4px;display:block">Select which combo/variation column this step asks about</small>
                </div>

                <div id="cf-filter-group" class="form-group" style="display:none">
                    <label class="form-label required">Linked Catalogue Column</label>
                    <select id="cf-linked-filter" class="form-select">
                        <option value="">— Select Column —</option>
                        @foreach($filterableColumns as $col)
                            <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->slug }})</option>
                        @endforeach
                    </select>
                    <small style="color:var(--muted-foreground);font-size:11px;margin-top:4px;display:block">Select which catalogue column to filter products by</small>
                </div>

                <div id="cf-question-group" class="form-group">
                    <label class="form-label">
                        Question Text
                        <span style="font-weight:400;color:var(--muted-foreground);font-size:11px;margin-left:4px">(AI enhances this)</span>
                    </label>
                    <input type="text" id="cf-step-question" class="form-input" placeholder="e.g., Kaunsa finish chahiye?">
                    <small style="color:var(--muted-foreground);font-size:11px;margin-top:4px;display:block">Bot will use this as a guideline and make it conversational</small>
                </div>

                <div id="cf-fieldkey-group" class="form-group" style="display:none">
                    <label class="form-label required">Field Key</label>
                    <input type="text" id="cf-step-fieldkey" class="form-input" placeholder="e.g., city, name, business">
                    <small style="color:var(--muted-foreground);font-size:11px;margin-top:4px;display:block">Maps to Lead data field for storing the answer</small>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Max Retries</label>
                    <div style="display:flex;align-items:center;gap:10px">
                        <input type="number" id="cf-step-retries" class="form-input" value="2" min="1" max="5" style="width:80px;text-align:center">
                        <small style="color:var(--muted-foreground);font-size:12px">How many times bot re-asks if user's reply doesn't match</small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('cf-step-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="gap:6px;font-weight:600">
                    <i data-lucide="check" style="width:15px;height:15px"></i> Save Step
                </button>
            </div>
        </form>
    </div>

    <style>
        @keyframes cfPulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    </style>
@endsection

@push('scripts')
<script>
    // Store all steps data for edit lookups
    var cfStepsData = @json($steps->keyBy('id'));

    function openAddStep() {
        document.getElementById('cf-modal-title').textContent = 'Add Step';
        document.getElementById('cf-step-id').value = '';
        document.getElementById('cf-step-name').value = '';
        document.getElementById('cf-step-type').value = 'ask_category';
        document.getElementById('cf-linked-combo').value = '';
        document.getElementById('cf-linked-filter').value = '';
        document.getElementById('cf-step-question').value = '';
        document.getElementById('cf-step-fieldkey').value = '';
        document.getElementById('cf-step-retries').value = 2;
        cfToggleFields();
        openModal('cf-step-modal');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function cfEditStep(stepId) {
        var step = cfStepsData[stepId];
        if (!step) { alert('Step not found'); return; }

        document.getElementById('cf-modal-title').textContent = 'Edit Step';
        document.getElementById('cf-step-id').value = step.id;
        document.getElementById('cf-step-name').value = step.name;

        var stepType = step.step_type === 'ask_product' ? 'ask_unique_column' : step.step_type;
        stepType = stepType === 'ask_custom' ? 'ask_optional' : stepType;
        stepType = stepType === 'ask_base_column' ? 'ask_column' : stepType;
        document.getElementById('cf-step-type').value = stepType;

        if (stepType === 'ask_combo') {
            document.getElementById('cf-linked-combo').value = step.linked_column_id || '';
            document.getElementById('cf-linked-filter').value = '';
        } else if (stepType === 'ask_column') {
            document.getElementById('cf-linked-filter').value = step.linked_column_id || '';
            document.getElementById('cf-linked-combo').value = '';
        } else {
            document.getElementById('cf-linked-combo').value = '';
            document.getElementById('cf-linked-filter').value = '';
        }

        document.getElementById('cf-step-question').value = step.question_text || '';
        document.getElementById('cf-step-fieldkey').value = step.field_key || '';
        document.getElementById('cf-step-retries').value = step.max_retries || 2;
        cfToggleFields();
        openModal('cf-step-modal');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function cfToggleFields() {
        var type = document.getElementById('cf-step-type').value;
        document.getElementById('cf-combo-group').style.display = type === 'ask_combo' ? 'block' : 'none';
        document.getElementById('cf-filter-group').style.display = type === 'ask_column' ? 'block' : 'none';
        document.getElementById('cf-fieldkey-group').style.display = type === 'ask_optional' ? 'block' : 'none';
    }

    function cfSaveStep(e) {
        e.preventDefault();
        var id = document.getElementById('cf-step-id').value;
        var url = id ? '{{ url("admin/chatflow") }}/' + id : '{{ route("admin.chatflow.store") }}';
        var method = id ? 'PUT' : 'POST';
        var type = document.getElementById('cf-step-type').value;

        var linkedColumnId = null;
        if (type === 'ask_combo') {
            linkedColumnId = document.getElementById('cf-linked-combo').value || null;
        } else if (type === 'ask_column') {
            linkedColumnId = document.getElementById('cf-linked-filter').value || null;
        }

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('cf-step-name').value,
                step_type: type,
                linked_column_id: linkedColumnId,
                question_text: document.getElementById('cf-step-question').value || null,
                field_key: document.getElementById('cf-step-fieldkey').value || null,
                max_retries: parseInt(document.getElementById('cf-step-retries').value) || 2,
            })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                closeModal('cf-step-modal');
                showToast(data.message || 'Step saved', 'success');
                setTimeout(function() { location.reload(); }, 500);
            } else {
                showToast(data.message || 'Error saving step', 'error');
            }
        }).catch(function(err) {
            showToast('Request failed', 'error');
        });
    }

    function cfDeleteStep(id) {
        if (!confirm('Delete this chatflow step?')) return;
        fetch('{{ url("admin/chatflow") }}/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) { showToast(data.message || 'Step deleted', 'success'); setTimeout(function() { location.reload(); }, 500); }
            else { showToast(data.message || 'Error', 'error'); }
        });
    }

    // ═══════════ DRAG & DROP REORDER ═══════════
    (function() {
        var tbody = document.getElementById('steps-tbody');
        if (!tbody) return;
        var dragRow = null;

        function getRows() { return Array.from(tbody.querySelectorAll('tr[data-id]')); }

        getRows().forEach(function(row) {
            var handle = row.querySelector('td:first-child');
            if (!handle) return;
            handle.style.cursor = 'grab';
            handle.setAttribute('draggable', 'true');

            handle.addEventListener('dragstart', function(e) {
                dragRow = row;
                setTimeout(function() { row.style.opacity = '0.4'; row.style.background = 'var(--muted)'; }, 0);
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', row.dataset.id);
            });

            handle.addEventListener('dragend', function() {
                dragRow.style.opacity = '1';
                dragRow.style.background = '';
                dragRow = null;
                getRows().forEach(function(r) { r.style.borderTop = ''; r.style.borderBottom = ''; });
            });

            row.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (!dragRow || dragRow === row) return;
                var rect = row.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;
                getRows().forEach(function(r) { r.style.borderTop = ''; r.style.borderBottom = ''; });
                if (e.clientY < midY) { row.style.borderTop = '3px solid var(--primary)'; }
                else { row.style.borderBottom = '3px solid var(--primary)'; }
            });

            row.addEventListener('dragleave', function() { row.style.borderTop = ''; row.style.borderBottom = ''; });

            row.addEventListener('drop', function(e) {
                e.preventDefault();
                if (!dragRow || dragRow === row) return;
                var rect = row.getBoundingClientRect();
                var midY = rect.top + rect.height / 2;
                if (e.clientY < midY) { tbody.insertBefore(dragRow, row); }
                else { tbody.insertBefore(dragRow, row.nextSibling); }
                getRows().forEach(function(r) { r.style.borderTop = ''; r.style.borderBottom = ''; });
                cfSaveOrder();
            });
        });

        function cfSaveOrder() {
            var ids = getRows().map(function(r) { return parseInt(r.dataset.id); });
            getRows().forEach(function(r, i) {
                r.style.transition = 'background 0.3s ease';
                r.style.background = 'rgba(34,197,94,0.05)';
                var idxTd = r.querySelector('td:nth-child(2) span');
                if (idxTd) idxTd.textContent = i + 1;
                setTimeout(function() { r.style.background = ''; }, 600);
            });

            fetch('{{ route("admin.chatflow.reorder") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ order: ids })
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.success) { showToast('Flow order updated', 'success'); setTimeout(function() { location.reload(); }, 600); }
            }).catch(function() { showToast('Failed to save order', 'error'); });
        }
    })();
</script>
@endpush
