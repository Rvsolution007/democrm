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
    <div style="background:linear-gradient(135deg,#eef2ff,#faf5ff);border:1px solid #c7d2fe;border-radius:10px;padding:12px 18px;margin-bottom:20px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <span style="font-size:12px;color:#6366f1;font-weight:700;letter-spacing:0.3px">📊 DETECTED COLUMNS</span>
        <div style="padding:4px 14px;background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.2);border-radius:20px;font-size:12px;font-weight:600;color:#4f46e5">
            🏷️ Unique: {{ $uniqueColumn->name }}
        </div>
        @foreach($filterableColumns as $fc)
        <div style="padding:4px 14px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:20px;font-size:12px;font-weight:500;color:#047857">
            📊 {{ $fc->name }}
        </div>
        @endforeach
        <div style="font-size:11px;color:var(--text-muted);margin-left:auto">
            Based on <a href="{{ route('admin.catalogue-columns.index') }}" style="color:#6366f1;text-decoration:underline;font-weight:500">Catalogue Settings</a>
        </div>
    </div>
    @endif

    {{-- Flow Preview --}}
    <div style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#3730a3 100%);border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 4px 20px rgba(30,27,75,0.3)">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
            <div style="width:8px;height:8px;border-radius:50%;background:#a5b4fc;animation:pulse 2s infinite"></div>
            <span style="font-size:13px;font-weight:600;color:#c7d2fe;letter-spacing:0.5px;text-transform:uppercase">Live Flow Preview</span>
        </div>
        <div id="flow-preview" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;min-height:38px">
            @forelse($steps as $index => $step)
                <div style="display:flex;align-items:center;gap:6px">
                    @if($index > 0)
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="flex-shrink:0">
                            <path d="M6 10h8M11 6l4 4-4 4" stroke="#818cf8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    @endif
                    @php
                        $flowColors = [
                            'ask_category' => ['bg' => 'rgba(236,72,153,0.15)', 'border' => 'rgba(236,72,153,0.4)', 'text' => '#f9a8d4', 'icon' => '📂'],
                            'ask_unique_column' => ['bg' => 'rgba(59,130,246,0.15)', 'border' => 'rgba(59,130,246,0.4)', 'text' => '#93c5fd', 'icon' => '🏷️'],
                            'ask_column' => ['bg' => 'rgba(16,185,129,0.15)', 'border' => 'rgba(16,185,129,0.4)', 'text' => '#6ee7b7', 'icon' => '📊'],
                            'ask_combo' => ['bg' => 'rgba(245,158,11,0.15)', 'border' => 'rgba(245,158,11,0.4)', 'text' => '#fcd34d', 'icon' => '🎨'],
                            'ask_optional' => ['bg' => 'rgba(139,92,246,0.15)', 'border' => 'rgba(139,92,246,0.4)', 'text' => '#c4b5fd', 'icon' => '📝'],
                            'ask_custom' => ['bg' => 'rgba(139,92,246,0.15)', 'border' => 'rgba(139,92,246,0.4)', 'text' => '#c4b5fd', 'icon' => '📝'],
                            'send_summary' => ['bg' => 'rgba(34,197,94,0.15)', 'border' => 'rgba(34,197,94,0.4)', 'text' => '#86efac', 'icon' => '📋'],
                        ];
                        $fc = $flowColors[$step->step_type] ?? ['bg' => 'rgba(148,163,184,0.15)', 'border' => 'rgba(148,163,184,0.4)', 'text' => '#cbd5e1', 'icon' => '⚙️'];
                    @endphp
                    <div style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $fc['bg'] }};border:1px solid {{ $fc['border'] }};color:{{ $fc['text'] }};white-space:nowrap;backdrop-filter:blur(10px)">
                        {{ $fc['icon'] }} {{ $step->name }}
                        @if($step->linkedColumn) <span style="opacity:0.6;font-weight:400">· {{ $step->linkedColumn->name }}</span> @endif
                    </div>
                </div>
            @empty
                <p style="margin:0;color:#818cf8;font-style:italic;font-size:13px">No steps defined. Click "Add Step" to build your chatflow.</p>
            @endforelse
        </div>
    </div>

    {{-- Steps Table --}}
    <div class="card" style="border-radius:12px;overflow:hidden">
        <div class="card-content" style="padding:0">
            <table class="data-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:var(--bg-sidebar, #f8fafc)">
                        <th style="padding:14px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;width:30px">⠿</th>
                        <th style="padding:14px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px;width:40px">#</th>
                        <th style="padding:14px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Step Name</th>
                        <th style="padding:14px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Type</th>
                        <th style="padding:14px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Details</th>
                        <th style="padding:14px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Retries</th>
                        <th style="padding:14px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:700;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Actions</th>
                    </tr>
                </thead>
                <tbody id="steps-tbody">
                    @forelse($steps as $step)
                        <tr data-id="{{ $step->id }}" style="border-bottom:1px solid var(--border);transition:background 0.15s ease" onmouseover="this.style.background='var(--bg-hover, #f8fafc)'" onmouseout="this.style.background=''">
                            <td style="padding:14px 16px;cursor:grab;color:var(--text-muted);font-size:16px">⠿</td>
                            <td style="padding:14px 16px;text-align:center">
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;font-size:12px;font-weight:700">{{ $step->sort_order }}</span>
                            </td>
                            <td style="padding:14px 16px;font-weight:600;font-size:14px">{{ $step->name }}</td>
                            <td style="padding:14px 16px;text-align:center">
                                @php
                                    $typeConfig = [
                                        'ask_category' => ['label' => 'Category', 'bg' => '#fce7f3', 'color' => '#9d174d', 'icon' => '📂'],
                                        'ask_product' => ['label' => 'Unique Column', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '🏷️'],
                                        'ask_unique_column' => ['label' => 'Unique Column', 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => '🏷️'],
                                        'ask_column' => ['label' => 'Column Filter', 'bg' => '#d1fae5', 'color' => '#065f46', 'icon' => '📊'],
                                        'ask_combo' => ['label' => 'Combo', 'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => '🎨'],
                                        'ask_optional' => ['label' => 'Optional', 'bg' => '#ede9fe', 'color' => '#5b21b6', 'icon' => '📝'],
                                        'ask_custom' => ['label' => 'Optional', 'bg' => '#ede9fe', 'color' => '#5b21b6', 'icon' => '📝'],
                                        'send_summary' => ['label' => 'Summary', 'bg' => '#dcfce7', 'color' => '#166534', 'icon' => '📋'],
                                    ];
                                    $tc = $typeConfig[$step->step_type] ?? ['label' => $step->step_type, 'bg' => '#f3f4f6', 'color' => '#374151', 'icon' => '⚙️'];
                                @endphp
                                <span style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $tc['bg'] }};color:{{ $tc['color'] }}">
                                    {{ $tc['icon'] }} {{ $tc['label'] }}
                                </span>
                            </td>
                            <td style="padding:14px 16px;font-size:13px;color:var(--text-muted)">
                                @if($step->linkedColumn)
                                    <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.15);border-radius:6px;font-size:12px;font-weight:500;color:#4f46e5">
                                        🔗 {{ $step->linkedColumn->name }}
                                    </span>
                                @endif
                                @if($step->question_text)
                                    <span style="color:var(--text-muted);font-style:italic">"{{ Str::limit($step->question_text, 45) }}"</span>
                                @endif
                                @if($step->field_key)
                                    <span style="padding:2px 8px;background:#f3f4f6;border-radius:4px;font-size:11px;font-family:monospace;color:#6b7280">{{ $step->field_key }}</span>
                                @endif
                                @if(!$step->linkedColumn && !$step->question_text && !$step->field_key)
                                    <span style="color:#d1d5db">—</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px;text-align:center">
                                <span style="display:inline-flex;align-items:center;gap:3px;padding:3px 10px;border-radius:6px;background:#f3f4f6;font-size:11px;font-weight:600;color:#6b7280">
                                    🔄 {{ $step->max_retries }}
                                </span>
                            </td>
                            <td style="padding:14px 16px;text-align:right">
                                <div style="display:flex;gap:6px;justify-content:flex-end">
                                    <button class="btn btn-outline btn-sm" onclick='editStep(@json($step))' style="padding:6px 12px;font-size:12px;border-radius:8px;font-weight:500">
                                        <i data-lucide="edit" style="width:13px;height:13px"></i> Edit
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="deleteStep({{ $step->id }})" style="color:var(--destructive);padding:6px 12px;font-size:12px;border-radius:8px">
                                        <i data-lucide="trash-2" style="width:13px;height:13px"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="7" style="text-align:center;padding:60px 20px">
                                <div style="max-width:300px;margin:0 auto">
                                    <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#eef2ff,#faf5ff);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px">🤖</div>
                                    <p style="font-weight:600;font-size:15px;margin:0 0 6px">No steps yet</p>
                                    <p class="text-muted" style="font-size:13px;margin:0">Click "Add Step" to start building your bot's conversation flow</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Add/Edit Step Modal --}}
    <div id="step-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center">
        <div style="background:var(--bg-card,white);border-radius:16px;width:95%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.3)">
            {{-- Modal Header --}}
            <div style="padding:20px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:16px 16px 0 0;display:flex;justify-content:space-between;align-items:center">
                <div>
                    <h3 id="step-modal-title" style="margin:0;color:white;font-size:18px;font-weight:700">Add Step</h3>
                    <p style="margin:4px 0 0;color:rgba(255,255,255,0.7);font-size:12px">Configure your chatflow step</p>
                </div>
                <button onclick="closeStepModal()" style="background:rgba(255,255,255,0.15);border:none;width:32px;height:32px;border-radius:8px;color:white;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s" onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">&times;</button>
            </div>

            <form id="step-form" onsubmit="saveStep(event)">
                <input type="hidden" id="step-id" value="">
                <div style="padding:24px">
                    {{-- Step Name --}}
                    <div style="margin-bottom:20px">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">Step Name <span style="color:#ef4444">*</span></label>
                        <input type="text" id="step-name" required placeholder="e.g., Ask Material, Select Finish" style="width:100%;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;transition:border 0.2s;background:var(--bg-input,white);color:var(--text-primary)" onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'" onblur="this.style.borderColor='var(--border,#e2e8f0)';this.style.boxShadow='none'">
                    </div>

                    {{-- Step Type --}}
                    <div style="margin-bottom:20px">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">Step Type <span style="color:#ef4444">*</span></label>
                        <select id="step-type" onchange="toggleStepFields()" style="width:100%;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;background:var(--bg-input,white);color:var(--text-primary);cursor:pointer">
                            <option value="ask_category">📂 Ask Category — Show category list first</option>
                            <option value="ask_unique_column">🏷️ Ask Unique Column — {{ $uniqueColumn ? $uniqueColumn->name : 'Model' }} list</option>
                            <option value="ask_combo">🎨 Ask Combo — Ask a combo/variation dimension</option>
                            <option value="ask_column">📊 Ask Column — Filter by catalogue column</option>
                            <option value="ask_optional">📝 Ask Optional — Custom optional question</option>
                            <option value="send_summary">📋 Send Summary — Order summary</option>
                        </select>
                    </div>

                    {{-- Linked Combo Column (for ask_combo) --}}
                    <div id="combo-column-group" style="margin-bottom:20px;display:none">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">Linked Combo Column <span style="color:#ef4444">*</span></label>
                        <select id="step-linked-column-combo" style="width:100%;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;background:var(--bg-input,white);color:var(--text-primary)">
                            <option value="">— Select Combo Column —</option>
                            @foreach($comboColumns as $col)
                                <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->slug }})</option>
                            @endforeach
                        </select>
                        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Select which combo/variation column this step asks about</small>
                    </div>

                    {{-- Linked Filter Column (for ask_column) --}}
                    <div id="filter-column-group" style="margin-bottom:20px;display:none">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">Linked Catalogue Column <span style="color:#ef4444">*</span></label>
                        <select id="step-linked-column-filter" style="width:100%;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;background:var(--bg-input,white);color:var(--text-primary)">
                            <option value="">— Select Column —</option>
                            @foreach($filterableColumns as $col)
                                <option value="{{ $col->id }}">{{ $col->name }} ({{ $col->slug }})</option>
                            @endforeach
                        </select>
                        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Select which catalogue column to filter products by</small>
                    </div>

                    {{-- Question Text --}}
                    <div id="question-group" style="margin-bottom:20px">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">
                            Question Text
                            <span style="font-weight:400;color:var(--text-muted);font-size:11px;margin-left:4px">(AI enhances this)</span>
                        </label>
                        <input type="text" id="step-question" placeholder="e.g., Kaunsa finish chahiye?" style="width:100%;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;background:var(--bg-input,white);color:var(--text-primary)" onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'" onblur="this.style.borderColor='var(--border,#e2e8f0)';this.style.boxShadow='none'">
                        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Bot will use this as a guideline and make it conversational</small>
                    </div>

                    {{-- Field Key (for ask_optional) --}}
                    <div id="field-key-group" style="margin-bottom:20px;display:none">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">Field Key <span style="color:#ef4444">*</span></label>
                        <input type="text" id="step-field-key" placeholder="e.g., city, name, business" style="width:100%;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;background:var(--bg-input,white);color:var(--text-primary)" onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.1)'" onblur="this.style.borderColor='var(--border,#e2e8f0)';this.style.boxShadow='none'">
                        <small style="color:var(--text-muted);font-size:11px;margin-top:4px;display:block">Maps to Lead data field for storing the answer</small>
                    </div>

                    {{-- Max Retries --}}
                    <div style="margin-bottom:8px">
                        <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:var(--text-primary)">Max Retries</label>
                        <div style="display:flex;align-items:center;gap:10px">
                            <input type="number" id="step-retries" value="2" min="1" max="5" style="width:80px;padding:10px 14px;border:1px solid var(--border,#e2e8f0);border-radius:10px;font-size:14px;text-align:center;background:var(--bg-input,white);color:var(--text-primary)">
                            <small style="color:var(--text-muted);font-size:12px">How many times bot re-asks if user's reply doesn't match</small>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div style="padding:16px 24px;border-top:1px solid var(--border,#e2e8f0);display:flex;justify-content:flex-end;gap:10px;background:var(--bg-subtle,#f8fafc);border-radius:0 0 16px 16px">
                    <button type="button" class="btn btn-outline" onclick="closeStepModal()" style="border-radius:10px;padding:10px 20px;font-weight:500">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="border-radius:10px;padding:10px 24px;font-weight:600;background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;gap:6px">
                        <i data-lucide="check" style="width:15px;height:15px"></i> Save Step
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    </style>
@endsection

@push('scripts')
<script>
    function openAddStep() {
        document.getElementById('step-modal-title').textContent = 'Add Step';
        document.getElementById('step-id').value = '';
        document.getElementById('step-name').value = '';
        document.getElementById('step-type').value = 'ask_category';
        document.getElementById('step-linked-column-combo').value = '';
        document.getElementById('step-linked-column-filter').value = '';
        document.getElementById('step-question').value = '';
        document.getElementById('step-field-key').value = '';
        document.getElementById('step-retries').value = 2;
        toggleStepFields();
        document.getElementById('step-modal').style.display = 'flex';
    }

    function editStep(step) {
        document.getElementById('step-modal-title').textContent = 'Edit Step';
        document.getElementById('step-id').value = step.id;
        document.getElementById('step-name').value = step.name;
        // Map legacy types
        var stepType = step.step_type === 'ask_product' ? 'ask_unique_column' : step.step_type;
        stepType = stepType === 'ask_custom' ? 'ask_optional' : stepType;
        stepType = stepType === 'ask_base_column' ? 'ask_column' : stepType; // migrate legacy
        document.getElementById('step-type').value = stepType;

        // Set linked columns based on type
        if (stepType === 'ask_combo') {
            document.getElementById('step-linked-column-combo').value = step.linked_column_id || '';
            document.getElementById('step-linked-column-filter').value = '';
        } else if (stepType === 'ask_column') {
            document.getElementById('step-linked-column-filter').value = step.linked_column_id || '';
            document.getElementById('step-linked-column-combo').value = '';
        } else {
            document.getElementById('step-linked-column-combo').value = '';
            document.getElementById('step-linked-column-filter').value = '';
        }

        document.getElementById('step-question').value = step.question_text || '';
        document.getElementById('step-field-key').value = step.field_key || '';
        document.getElementById('step-retries').value = step.max_retries || 2;
        toggleStepFields();
        document.getElementById('step-modal').style.display = 'flex';
    }

    function closeStepModal() { document.getElementById('step-modal').style.display = 'none'; }

    function toggleStepFields() {
        var type = document.getElementById('step-type').value;
        document.getElementById('combo-column-group').style.display = type === 'ask_combo' ? 'block' : 'none';
        document.getElementById('filter-column-group').style.display = type === 'ask_column' ? 'block' : 'none';
        document.getElementById('field-key-group').style.display = type === 'ask_optional' ? 'block' : 'none';
    }

    function saveStep(e) {
        e.preventDefault();
        var id = document.getElementById('step-id').value;
        var url = id ? '{{ url("admin/chatflow") }}/' + id : '{{ route("admin.chatflow.store") }}';
        var method = id ? 'PUT' : 'POST';
        var type = document.getElementById('step-type').value;

        // Get linked_column_id based on step type
        var linkedColumnId = null;
        if (type === 'ask_combo') {
            linkedColumnId = document.getElementById('step-linked-column-combo').value || null;
        } else if (type === 'ask_column') {
            linkedColumnId = document.getElementById('step-linked-column-filter').value || null;
        }

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('step-name').value,
                step_type: type,
                linked_column_id: linkedColumnId,
                question_text: document.getElementById('step-question').value || null,
                field_key: document.getElementById('step-field-key').value || null,
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
        var bg = type === 'success' ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : 'linear-gradient(135deg,#fee2e2,#fecaca)';
        var color = type === 'success' ? '#065f46' : '#991b1b';
        var icon = type === 'success' ? '✅' : '❌';
        document.getElementById('alert-container').innerHTML = '<div style="padding:14px 20px;background:'+bg+';color:'+color+';border-radius:10px;margin-bottom:20px;font-weight:500;display:flex;align-items:center;gap:8px">'+icon+' '+msg+'</div>';
    }

    document.getElementById('step-modal').addEventListener('click', function(e) { if (e.target === this) closeStepModal(); });
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeStepModal(); });

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
                setTimeout(function() { row.style.opacity = '0.4'; row.style.background = '#eef2ff'; }, 0);
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
                if (e.clientY < midY) { row.style.borderTop = '3px solid #6366f1'; }
                else { row.style.borderBottom = '3px solid #6366f1'; }
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
                saveOrder();
            });
        });

        function saveOrder() {
            var ids = getRows().map(function(r) { return parseInt(r.dataset.id); });
            getRows().forEach(function(r, i) {
                r.style.transition = 'background 0.3s ease';
                r.style.background = '#f0fdf4';
                var idxTd = r.querySelector('td:nth-child(2) span');
                if (idxTd) idxTd.textContent = i + 1;
                setTimeout(function() { r.style.background = ''; }, 600);
            });

            fetch('{{ route("admin.chatflow.reorder") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ order: ids })
            }).then(r => r.json()).then(data => {
                if (data.success) { showAlert('success', 'Flow order updated'); setTimeout(() => location.reload(), 600); }
            }).catch(() => showAlert('error', 'Failed to save order'));
        }
    })();
</script>
@endpush
