@extends('admin.layouts.app')

@push('styles')
<style>
    .page-header-modern {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 1.5rem 2rem; border-radius: 16px;
        box-shadow: 0 4px 20px -10px rgba(0,0,0,0.05); border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .page-title-modern {
        font-size: 1.75rem; font-weight: 700;
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;
    }
    .form-card {
        background: white; border-radius: 20px; padding: 2rem;
        border: 1px solid #e2e8f0; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.03);
    }
    .form-section-title {
        font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        color: #64748b; margin-bottom: 1rem; padding-bottom: 0.5rem;
        border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; gap: 8px;
    }
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { font-weight: 600; color: #334155; margin-bottom: 0.5rem; display: block; font-size: 0.9rem; }
    .form-group .form-control, .form-group .form-select {
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 0.75rem 1rem; font-size: 0.95rem; transition: all 0.2s;
    }
    .form-group .form-control:focus, .form-group .form-select:focus {
        background: #fff; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); outline: none;
    }
    .form-hint { font-size: 0.78rem; color: #94a3b8; margin-top: 0.3rem; }

    .radio-group { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    .radio-option {
        background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px;
        padding: 0.75rem 1.25rem; cursor: pointer; transition: all 0.2s;
        display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 0.9rem;
    }
    .radio-option:hover { border-color: #cbd5e1; }
    .radio-option.selected { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
    .radio-option input { display: none; }

    .checkbox-row {
        display: flex; align-items: center; gap: 10px; padding: 0.75rem 1rem;
        background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 0.5rem;
    }
    .checkbox-row input[type="checkbox"] { width: 18px; height: 18px; accent-color: #f59e0b; }

    .template-preview {
        background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 1rem;
        margin-top: 0.75rem; font-size: 0.85rem; color: #166534; display: none;
    }

    .btn-save {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none;
        padding: 0.85rem 2rem; border-radius: 12px; font-weight: 700; font-size: 1rem;
        box-shadow: 0 4px 15px -3px rgba(245, 158, 11, 0.4); transition: all 0.3s; cursor: pointer;
    }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -5px rgba(245, 158, 11, 0.5); }
    .btn-cancel {
        background: white; color: #475569; border: 1px solid #e2e8f0;
        padding: 0.85rem 2rem; border-radius: 12px; font-weight: 600; font-size: 1rem;
        transition: all 0.2s; text-decoration: none;
    }
    .btn-cancel:hover { background: #f1f5f9; color: #0f172a; }
</style>
@endpush

@section('title', isset($rule) ? 'Edit Auto-Reply Rule' : 'Create Auto-Reply Rule')

@section('content')
<div class="container-fluid" style="padding: 1.5rem; max-width: 900px;">
    <div class="page-header-modern">
        <div>
            <h2 class="page-title-modern">⚡ {{ isset($rule) ? 'Edit Rule' : 'Create Auto-Reply Rule' }}</h2>
            <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Define when and how to auto-reply</p>
        </div>
        <a href="{{ route('admin.whatsapp-auto-reply.index') }}" class="btn-cancel">← Back</a>
    </div>

    @if(!$connectionStatus['connected'])
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; color: #dc2626; display: flex; align-items: center; gap: 12px;">
            <i data-lucide="alert-triangle" style="width: 22px; height: 22px;"></i>
            <div><strong>Warning:</strong> Your WhatsApp is not connected. Rules will only work after connecting.</div>
        </div>
    @endif

    <div class="form-card">
        <form action="{{ isset($rule) ? route('admin.whatsapp-auto-reply.update', $rule->id) : route('admin.whatsapp-auto-reply.store') }}" method="POST">
            @csrf
            @if(isset($rule)) @method('PUT') @endif

            <!-- Rule Name -->
            <div class="form-group">
                <label>Rule Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Welcome New Lead" value="{{ old('name', $rule->name ?? '') }}" required>
            </div>

            <!-- Status -->
            <div class="checkbox-row" style="margin-bottom: 1.5rem;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', isset($rule) ? $rule->is_active : true) ? 'checked' : '' }}>
                <label for="is_active" style="margin: 0; font-weight: 600; cursor: pointer;">Active — Rule is enabled and will process incoming messages</label>
            </div>

            <hr style="border: none; border-top: 2px solid #f1f5f9; margin: 1.5rem 0;">

            <!-- TRIGGER CONDITION -->
            <div class="form-section-title">🎯 Trigger Condition</div>

            <div class="form-group">
                <label>Match Type</label>
                <div class="radio-group">
                    @php $matchType = old('match_type', $rule->match_type ?? 'any_message'); @endphp
                    <label class="radio-option {{ $matchType == 'exact' ? 'selected' : '' }}">
                        <input type="radio" name="match_type" value="exact" {{ $matchType == 'exact' ? 'checked' : '' }} onchange="updateMatchType()">
                        🎯 Exact Match
                    </label>
                    <label class="radio-option {{ $matchType == 'contains' ? 'selected' : '' }}">
                        <input type="radio" name="match_type" value="contains" {{ $matchType == 'contains' ? 'checked' : '' }} onchange="updateMatchType()">
                        🔍 Contains Word
                    </label>
                    <label class="radio-option {{ $matchType == 'any_message' ? 'selected' : '' }}">
                        <input type="radio" name="match_type" value="any_message" {{ $matchType == 'any_message' ? 'checked' : '' }} onchange="updateMatchType()">
                        📩 Any Message
                    </label>
                    <label class="radio-option {{ $matchType == 'first_message' ? 'selected' : '' }}">
                        <input type="radio" name="match_type" value="first_message" {{ $matchType == 'first_message' ? 'checked' : '' }} onchange="updateMatchType()">
                        🆕 First Message Only
                    </label>
                </div>
            </div>

            <div class="form-group" id="keywords-group" style="{{ in_array($matchType, ['exact', 'contains']) ? '' : 'display:none;' }}">
                <label>Keywords (comma separated)</label>
                <input type="text" name="keywords" class="form-control" placeholder="price, rate, cost, kitna"
                    value="{{ old('keywords', isset($rule) && $rule->keywords ? implode(', ', $rule->keywords) : '') }}">
                <div class="form-hint">Enter keywords separated by commas. For "Contains" — if any word is found in the message, rule triggers.</div>
            </div>

            <hr style="border: none; border-top: 2px solid #f1f5f9; margin: 1.5rem 0;">

            <!-- RESPONSE -->
            <div class="form-section-title">💬 Response</div>

            @php
                $officialApiOn = (bool) \App\Models\Setting::getValue('whatsapp', 'official_api_enabled', false);
                $officialCfg = \App\Models\Setting::getValue('whatsapp', 'official_api_config', []);
                $wabaConfigured = !empty($officialCfg['waba_id'] ?? '');
                $showMetaOption = $officialApiOn && $wabaConfigured;
                $currentSource = old('template_source', $rule->template_source ?? 'evolution');
            @endphp

            @if($showMetaOption)
            <!-- Template Source Toggle -->
            <div class="form-group">
                <label>Template Source</label>
                <div class="radio-group">
                    <label class="radio-option {{ $currentSource == 'evolution' ? 'selected' : '' }}">
                        <input type="radio" name="template_source" value="evolution" {{ $currentSource == 'evolution' ? 'checked' : '' }} onchange="toggleTemplateSource()">
                        📱 Evolution Template
                    </label>
                    <label class="radio-option {{ $currentSource == 'meta' ? 'selected' : '' }}" style="{{ $currentSource == 'meta' ? 'background:#f0fdf4;border-color:#22c55e;color:#166534;' : '' }}">
                        <input type="radio" name="template_source" value="meta" {{ $currentSource == 'meta' ? 'checked' : '' }} onchange="toggleTemplateSource()">
                        ✅ Meta Approved Template
                    </label>
                </div>
                <div class="form-hint">Evolution templates are free (QR-based). Meta templates work via Official API (outbound messages).</div>
            </div>
            @else
            <input type="hidden" name="template_source" value="evolution">
            @endif

            <!-- Evolution Template Dropdown -->
            <div class="form-group" id="evolution-template-group" style="{{ $currentSource == 'meta' ? 'display:none;' : '' }}">
                <label>Reply Template (Evolution)</label>
                <select name="template_id" class="form-select" id="evolution-template-select" onchange="previewTemplate(this)" {{ $currentSource == 'meta' ? '' : 'required' }}>
                    <option value="">— Select Template —</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}"
                            data-text="{{ $template->message_text }}"
                            data-type="{{ $template->type }}"
                            {{ old('template_id', $rule->template_id ?? '') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ strtoupper($template->type) }})
                        </option>
                    @endforeach
                </select>
                <div class="template-preview" id="template-preview"></div>
            </div>

            <!-- Meta Template Dropdown -->
            <div class="form-group" id="meta-template-group" style="{{ $currentSource == 'meta' ? '' : 'display:none;' }}">
                <label>Reply Template (Meta Approved)</label>
                <select name="meta_template_id" class="form-select" id="meta-template-select" onchange="previewMetaTemplate(this)" {{ $currentSource == 'meta' ? 'required' : '' }}>
                    <option value="">— Loading Meta Templates... —</option>
                </select>
                <div class="template-preview" id="meta-template-preview" style="display:none;"></div>
                <div class="form-hint">Only ✅ Approved templates appear here. <a href="{{ route('admin.meta-templates.index') }}" target="_blank" style="color:#2563eb;">Manage Meta Templates →</a></div>
            </div>

            <div class="form-group">
                <label>Reply Delay (seconds)</label>
                <input type="number" name="reply_delay_seconds" class="form-control" min="0" max="60"
                    value="{{ old('reply_delay_seconds', $rule->reply_delay_seconds ?? 5) }}">
                <div class="form-hint">3-5 seconds delay feels natural. 0 = instant (may feel bot-like).</div>
            </div>

            <hr style="border: none; border-top: 2px solid #f1f5f9; margin: 1.5rem 0;">

            <!-- ANTI-SPAM -->
            <div class="form-section-title">🛡️ Anti-Spam Controls</div>

            <div class="checkbox-row">
                <input type="hidden" name="is_one_time" value="0">
                <input type="checkbox" name="is_one_time" id="is_one_time" value="1" {{ old('is_one_time', isset($rule) ? $rule->is_one_time : true) ? 'checked' : '' }}>
                <label for="is_one_time" style="margin: 0; cursor: pointer;">One-time reply per number (recommended)</label>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Cooldown (hours)</label>
                        <input type="number" name="cooldown_hours" class="form-control" min="0"
                            value="{{ old('cooldown_hours', $rule->cooldown_hours ?? 24) }}">
                        <div class="form-hint">Don't reply again to same number within this period</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Max replies/day per number</label>
                        <input type="number" name="max_replies_per_day" class="form-control" min="0"
                            value="{{ old('max_replies_per_day', $rule->max_replies_per_day ?? 3) }}">
                    </div>
                </div>
            </div>

            <hr style="border: none; border-top: 2px solid #f1f5f9; margin: 1.5rem 0;">

            <!-- PRIORITY -->
            <div class="form-section-title">🕒 Schedule Limits</div>

            <div class="checkbox-row" style="margin-bottom: 1rem;">
                <input type="hidden" name="business_hours_only" value="0">
                <input type="checkbox" name="business_hours_only" id="business_hours_only" value="1" {{ old('business_hours_only', isset($rule) ? $rule->business_hours_only : false) ? 'checked' : '' }} onchange="toggleBusinessHours()">
                <label for="business_hours_only" style="margin: 0; cursor: pointer;">Only reply during business hours</label>
            </div>

            <div class="row g-3" id="biz-hours-fields" style="{{ old('business_hours_only', isset($rule) && $rule->business_hours_only ? true : false) ? '' : 'display:none;' }}">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="business_hours_start" class="form-control"
                            value="{{ old('business_hours_start', $rule->business_hours_start ?? '09:00') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="business_hours_end" class="form-control"
                            value="{{ old('business_hours_end', $rule->business_hours_end ?? '21:00') }}">
                    </div>
                </div>
            </div>

            <hr style="border: none; border-top: 2px solid #f1f5f9; margin: 1.5rem 0;">

            <!-- AUTO-CREATE LEAD -->
            <div class="form-section-title">📋 Lead Generation</div>

            <div class="checkbox-row" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1.5px solid #bbf7d0; margin-bottom: 0.5rem;">
                <input type="hidden" name="create_lead" value="0">
                <input type="checkbox" name="create_lead" id="create_lead" value="1" {{ old('create_lead', isset($rule) ? $rule->create_lead : false) ? 'checked' : '' }}>
                <label for="create_lead" style="margin: 0; cursor: pointer; font-weight: 600; color: #166534;">
                    🚀 Auto-Create Lead — Automatically create a new lead in CRM for every contact who receives this auto-reply
                </label>
            </div>
            <div class="form-hint" style="margin-bottom: 1rem; padding-left: 0.5rem;">
                When enabled, a new lead will be created with source "WhatsApp" for each new number that receives this auto-reply. Duplicate phone numbers will be skipped automatically.
            </div>

            <hr style="border: none; border-top: 2px solid #f1f5f9; margin: 1.5rem 0;">

            <!-- PRIORITY -->
            <div class="form-section-title">⚙️ Advanced</div>

            <div class="form-group">
                <label>Priority (1-10, higher = checked first)</label>
                <input type="number" name="priority" class="form-control" min="1" max="10"
                    value="{{ old('priority', $rule->priority ?? 5) }}">
                <div class="form-hint">When multiple rules could match, higher priority wins.</div>
            </div>

            <!-- Submit -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <a href="{{ route('admin.whatsapp-auto-reply.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">💾 {{ isset($rule) ? 'Update Rule' : 'Save Rule' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateMatchType() {
        const selected = document.querySelector('input[name="match_type"]:checked').value;
        document.querySelectorAll('.radio-option').forEach(o => o.classList.remove('selected'));
        document.querySelector(`input[value="${selected}"]`).closest('.radio-option').classList.add('selected');

        const keywordsGroup = document.getElementById('keywords-group');
        keywordsGroup.style.display = ['exact', 'contains'].includes(selected) ? '' : 'none';
    }

    function toggleBizHours() {
        document.getElementById('biz-hours-fields').style.display = document.getElementById('biz_hours').checked ? '' : 'none';
    }

    function previewTemplate(select) {
        const option = select.options[select.selectedIndex];
        const preview = document.getElementById('template-preview');
        if (option.dataset.text) {
            preview.style.display = 'block';
            let icon = option.dataset.type === 'text' ? '📝' : option.dataset.type === 'image' ? '🖼️' : option.dataset.type === 'video' ? '🎬' : '📄';
            preview.innerHTML = `<strong>${icon} Preview:</strong><br>${option.dataset.text.substring(0, 200)}${option.dataset.text.length > 200 ? '...' : ''}`;
        } else {
            preview.style.display = 'none';
        }
    }

    function toggleTemplateSource() {
        const source = document.querySelector('input[name="template_source"]:checked')?.value || 'evolution';
        const evoGroup = document.getElementById('evolution-template-group');
        const metaGroup = document.getElementById('meta-template-group');
        const evoSelect = document.getElementById('evolution-template-select');
        const metaSelect = document.getElementById('meta-template-select');

        // Update radio styles
        document.querySelectorAll('input[name="template_source"]').forEach(r => {
            const label = r.closest('.radio-option');
            label.classList.toggle('selected', r.checked);
            if (r.value === 'meta' && r.checked) {
                label.style.background = '#f0fdf4';
                label.style.borderColor = '#22c55e';
                label.style.color = '#166534';
            } else if (r.value === 'meta') {
                label.style.background = '';
                label.style.borderColor = '';
                label.style.color = '';
            }
        });

        if (source === 'meta') {
            evoGroup.style.display = 'none';
            metaGroup.style.display = '';
            if (evoSelect) { evoSelect.removeAttribute('required'); evoSelect.value = ''; }
            if (metaSelect) metaSelect.setAttribute('required', 'required');
            loadMetaTemplates();
        } else {
            evoGroup.style.display = '';
            metaGroup.style.display = 'none';
            if (evoSelect) evoSelect.setAttribute('required', 'required');
            if (metaSelect) { metaSelect.removeAttribute('required'); metaSelect.value = ''; }
        }
    }

    function loadMetaTemplates() {
        const select = document.getElementById('meta-template-select');
        if (!select) return;
        select.innerHTML = '<option value="">— Loading... —</option>';

        fetch('{{ route("admin.meta-templates.approved-json") }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(templates => {
            let html = '<option value="">— Select Meta Template —</option>';
            if (templates.length === 0) {
                html = '<option value="">— No approved templates found —</option>';
            }
            templates.forEach(t => {
                const selected = t.id == {{ old('meta_template_id', $rule->meta_template_id ?? 0) }} ? 'selected' : '';
                html += `<option value="${t.id}" data-body="${t.body_text}" ${selected}>✅ ${t.name} (${t.category} | ${t.language.toUpperCase()})</option>`;
            });
            select.innerHTML = html;

            // Auto-preview if editing
            if (select.value) previewMetaTemplate(select);
        })
        .catch(() => {
            select.innerHTML = '<option value="">— Error loading templates —</option>';
        });
    }

    function previewMetaTemplate(select) {
        const option = select.options[select.selectedIndex];
        const preview = document.getElementById('meta-template-preview');
        if (option.dataset.body) {
            preview.style.display = 'block';
            preview.innerHTML = `<strong>✅ Meta Template Preview:</strong><br>${option.dataset.body.substring(0, 300)}${option.dataset.body.length > 300 ? '...' : ''}`;
        } else {
            preview.style.display = 'none';
        }
    }

    // Init on page load
    document.addEventListener('DOMContentLoaded', function () {
        const templateSelect = document.getElementById('evolution-template-select');
        if (templateSelect && templateSelect.value) previewTemplate(templateSelect);

        // Load Meta templates if meta source is selected
        const source = document.querySelector('input[name="template_source"]:checked')?.value || 'evolution';
        if (source === 'meta') loadMetaTemplates();
    });
</script>
@endpush
