@extends('admin.layouts.app')

@section('title', 'Create Meta Template')

@push('styles')
<style>
    .tmpl-create-container {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .tmpl-create-container { grid-template-columns: 1fr; }
    }

    /* Form Panel */
    .tmpl-form-panel {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .tmpl-form-panel h2 {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Form Groups */
    .tmpl-form-group {
        margin-bottom: 18px;
    }
    .tmpl-form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--foreground);
        margin-bottom: 5px;
    }
    .tmpl-form-group .hint {
        font-size: 0.72rem;
        color: var(--muted-foreground);
        margin-top: 3px;
    }
    .tmpl-form-group input,
    .tmpl-form-group select,
    .tmpl-form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 0.85rem;
        background: var(--card-bg);
        color: var(--foreground);
        font-family: inherit;
    }
    .tmpl-form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    .tmpl-form-group input:focus,
    .tmpl-form-group select:focus,
    .tmpl-form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(37,99,235,0.15);
    }

    /* Section Divider */
    .tmpl-section-divider {
        margin: 24px 0;
        padding: 0;
        border: none;
        border-top: 1px dashed var(--border);
    }

    /* Variable Buttons */
    .var-btn-row {
        display: flex;
        gap: 6px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    .var-btn {
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        border: 1px solid #e9d5ff;
        background: #faf5ff;
        color: #7c3aed;
        cursor: pointer;
        transition: all 0.2s;
    }
    .var-btn:hover {
        background: #7c3aed;
        color: white;
    }

    /* Buttons Section */
    .tmpl-buttons-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .tmpl-button-row {
        display: grid;
        grid-template-columns: 120px 1fr 1fr auto;
        gap: 8px;
        align-items: center;
        padding: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    .tmpl-button-row select,
    .tmpl-button-row input {
        padding: 6px 8px;
        font-size: 0.8rem;
        border-radius: 6px;
        border: 1px solid var(--border);
    }

    /* Example Values */
    .example-values-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .example-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .example-row .label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #7c3aed;
        flex-shrink: 0;
        width: 50px;
    }
    .example-row input {
        flex: 1;
        padding: 6px 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.8rem;
    }

    /* Preview Panel */
    .tmpl-preview-panel {
        position: sticky;
        top: 20px;
    }
    .tmpl-preview-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
    }
    .tmpl-preview-card h3 {
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .wa-preview {
        background: #e5ddd5;
        border-radius: 12px;
        padding: 16px;
        min-height: 200px;
    }
    .wa-bubble {
        background: white;
        border-radius: 0 10px 10px 10px;
        padding: 10px 14px;
        max-width: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        font-size: 0.83rem;
        line-height: 1.5;
        color: #1a1a1a;
    }
    .wa-bubble-header {
        font-weight: 700;
        color: #111;
        margin-bottom: 6px;
        padding-bottom: 6px;
        border-bottom: 1px solid #f0f0f0;
    }
    .wa-bubble-body {
        white-space: pre-wrap;
        word-break: break-word;
    }
    .wa-bubble-footer {
        color: #8696a0;
        font-size: 0.75rem;
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px solid #f0f0f0;
    }
    .wa-bubble-buttons {
        margin-top: 8px;
    }
    .wa-bubble-btn {
        display: block;
        text-align: center;
        padding: 8px;
        color: #1e90ff;
        font-weight: 600;
        font-size: 0.8rem;
        border-top: 1px solid #f0f0f0;
    }
    .wa-time {
        text-align: right;
        margin-top: 6px;
        font-size: 0.65rem;
        color: #8696a0;
    }

    /* Error display */
    .tmpl-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 16px;
        color: #dc2626;
        font-size: 0.85rem;
    }
    .tmpl-error ul { margin: 4px 0 0 16px; }
</style>
@endpush

@section('content')
<div class="page-content">
    <!-- Header -->
    <div style="margin-bottom:20px;">
        <a href="{{ route('admin.meta-templates.index') }}" style="font-size:0.85rem;color:var(--primary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back to Templates
        </a>
        <h1 style="font-size:1.4rem;font-weight:700;margin-top:8px;display:flex;align-items:center;gap:10px;">
            <i data-lucide="plus-circle" style="color:#22c55e;width:24px;height:24px;"></i>
            Create Meta Template
        </h1>
    </div>

    @if(session('error'))
        <div class="tmpl-error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="tmpl-error">
            <strong>Please fix the following errors:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.meta-templates.store') }}" method="POST" id="template-form">
        @csrf
        <div class="tmpl-create-container">
            <!-- Left: Form Fields -->
            <div class="tmpl-form-panel">
                <h2><i data-lucide="file-text" style="width:18px;height:18px;color:#3b82f6;"></i> Template Details</h2>

                <!-- Row: Name + Category + Language -->
                <div style="display:grid;grid-template-columns:1fr 150px 100px;gap:12px;">
                    <div class="tmpl-form-group">
                        <label>Template Name *</label>
                        <input type="text" name="name" id="tmpl-name" value="{{ old('name') }}" placeholder="e.g. order_update_v1" required
                            pattern="^[a-z][a-z0-9_]*$" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,''); updatePreview();">
                        <div class="hint">Lowercase letters, numbers & underscores only. Must start with a letter.</div>
                    </div>
                    <div class="tmpl-form-group">
                        <label>Category *</label>
                        <select name="category" id="tmpl-category" required onchange="updatePreview()">
                            <option value="UTILITY" {{ old('category') == 'UTILITY' ? 'selected' : '' }}>Utility</option>
                            <option value="MARKETING" {{ old('category') == 'MARKETING' ? 'selected' : '' }}>Marketing</option>
                            <option value="AUTHENTICATION" {{ old('category') == 'AUTHENTICATION' ? 'selected' : '' }}>Authentication</option>
                        </select>
                    </div>
                    <div class="tmpl-form-group">
                        <label>Language *</label>
                        <select name="language" id="tmpl-language" required>
                            <option value="en" {{ old('language', 'en') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="hi" {{ old('language') == 'hi' ? 'selected' : '' }}>Hindi</option>
                            <option value="en_US" {{ old('language') == 'en_US' ? 'selected' : '' }}>en_US</option>
                        </select>
                    </div>
                </div>

                <hr class="tmpl-section-divider">

                <!-- Header -->
                <h2 style="font-size:0.95rem;"><i data-lucide="heading" style="width:16px;height:16px;color:#6366f1;"></i> Header (Optional)</h2>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:12px;">
                    <div class="tmpl-form-group">
                        <label>Type</label>
                        <select name="header_type" id="tmpl-header-type" onchange="toggleHeader(); updatePreview();">
                            <option value="NONE" {{ old('header_type', 'NONE') == 'NONE' ? 'selected' : '' }}>None</option>
                            <option value="TEXT" {{ old('header_type') == 'TEXT' ? 'selected' : '' }}>Text</option>
                        </select>
                    </div>
                    <div class="tmpl-form-group" id="header-text-group" style="display:none;">
                        <label>Header Text</label>
                        <input type="text" name="header_text" id="tmpl-header-text" value="{{ old('header_text') }}" placeholder="e.g. Order Update" maxlength="60" oninput="updatePreview()">
                        <div class="hint">Max 60 characters</div>
                    </div>
                </div>

                <hr class="tmpl-section-divider">

                <!-- Body -->
                <h2 style="font-size:0.95rem;"><i data-lucide="type" style="width:16px;height:16px;color:#f59e0b;"></i> Body * (Message Text)</h2>
                <div class="tmpl-form-group">
                    <div class="var-btn-row">
                        <span style="font-size:0.72rem;color:var(--muted-foreground);padding-top:4px;">Insert Variable:</span>
                        <button type="button" class="var-btn" onclick="insertVariable(1)">{{1}}</button>
                        <button type="button" class="var-btn" onclick="insertVariable(2)">{{2}}</button>
                        <button type="button" class="var-btn" onclick="insertVariable(3)">{{3}}</button>
                        <button type="button" class="var-btn" onclick="insertVariable(4)">{{4}}</button>
                        <button type="button" class="var-btn" onclick="insertVariable(5)">{{5}}</button>
                    </div>
                    <textarea name="body_text" id="tmpl-body" placeholder="Hello {{1}}, your order #{{2}} has been successfully placed! We'll notify you once it ships." required maxlength="1024" oninput="updatePreview(); updateExampleFields();">{{ old('body_text') }}</textarea>
                    <div class="hint">Max 1024 characters. Use {{1}}, {{2}}, etc. for dynamic values.</div>
                </div>

                <hr class="tmpl-section-divider">

                <!-- Footer -->
                <h2 style="font-size:0.95rem;"><i data-lucide="bookmark" style="width:16px;height:16px;color:#0ea5e9;"></i> Footer (Optional)</h2>
                <div class="tmpl-form-group">
                    <input type="text" name="footer_text" id="tmpl-footer" value="{{ old('footer_text') }}" placeholder="e.g. RV Solutions Pvt Ltd" maxlength="60" oninput="updatePreview()">
                    <div class="hint">Max 60 characters. Shown in lighter text at bottom.</div>
                </div>

                <hr class="tmpl-section-divider">

                <!-- Buttons -->
                <h2 style="font-size:0.95rem;">
                    <i data-lucide="mouse-pointer-click" style="width:16px;height:16px;color:#ec4899;"></i> Buttons (Optional)
                    <button type="button" class="btn btn-outline btn-sm" style="margin-left:auto;font-size:0.7rem;" onclick="addButton()">+ Add Button</button>
                </h2>
                <div class="tmpl-buttons-list" id="buttons-container">
                    <!-- Buttons added dynamically -->
                </div>
                <div class="hint" style="margin-top:6px;">Max 3 buttons. Types: URL (link), Phone Number, Quick Reply.</div>

                <hr class="tmpl-section-divider">

                <!-- Example Values for Variables -->
                <h2 style="font-size:0.95rem;"><i data-lucide="test-tube-2" style="width:16px;height:16px;color:#7c3aed;"></i> Example Values (Required for Variables)</h2>
                <div class="hint" style="margin-bottom:10px;">Meta requires example values for each variable in your template body. These are used during the review process.</div>
                <div class="example-values-grid" id="example-values-container">
                    <p style="color:var(--muted-foreground);font-size:0.8rem;">Add variables to the body text to see example fields here.</p>
                </div>

                <hr class="tmpl-section-divider">

                <!-- Submit -->
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <a href="{{ route('admin.meta-templates.index') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i data-lucide="send" style="width:14px;height:14px;"></i>
                        Submit for Review
                    </button>
                </div>
            </div>

            <!-- Right: Live Preview -->
            <div class="tmpl-preview-panel">
                <div class="tmpl-preview-card">
                    <h3><i data-lucide="smartphone" style="width:16px;height:16px;color:#25D366;"></i> WhatsApp Preview</h3>
                    <div class="wa-preview">
                        <div class="wa-bubble" id="wa-preview-bubble">
                            <div class="wa-bubble-header" id="preview-header" style="display:none;"></div>
                            <div class="wa-bubble-body" id="preview-body">Your template message will appear here...</div>
                            <div class="wa-bubble-footer" id="preview-footer" style="display:none;"></div>
                            <div class="wa-bubble-buttons" id="preview-buttons"></div>
                        </div>
                        <div class="wa-time">{{ now()->format('h:i A') }}</div>
                    </div>
                </div>

                <!-- Category Info -->
                <div style="margin-top:16px;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:16px;">
                    <h3 style="font-size:0.85rem;font-weight:700;margin-bottom:10px;">📋 Category Guide</h3>
                    <div style="font-size:0.75rem;line-height:1.6;color:var(--muted-foreground);">
                        <p><strong style="color:#3b82f6;">UTILITY</strong> — Order updates, OTPs, receipts<br><em>Fast approval (minutes-hours)</em></p>
                        <p style="margin-top:6px;"><strong style="color:#8b5cf6;">MARKETING</strong> — Promotions, offers<br><em>Slower approval (hours-days)</em></p>
                        <p style="margin-top:6px;"><strong style="color:#0ea5e9;">AUTHENTICATION</strong> — Login OTPs<br><em>Fast approval</em></p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let buttonCount = 0;

    function toggleHeader() {
        const type = document.getElementById('tmpl-header-type').value;
        document.getElementById('header-text-group').style.display = type === 'TEXT' ? '' : 'none';
    }

    function insertVariable(num) {
        const textarea = document.getElementById('tmpl-body');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const varText = `{{${num}}}`;
        textarea.value = text.substring(0, start) + varText + text.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + varText.length, start + varText.length);
        updatePreview();
        updateExampleFields();
    }

    function updatePreview() {
        // Header
        const headerType = document.getElementById('tmpl-header-type').value;
        const headerEl = document.getElementById('preview-header');
        if (headerType === 'TEXT') {
            headerEl.style.display = '';
            headerEl.textContent = document.getElementById('tmpl-header-text').value || 'Header text...';
        } else {
            headerEl.style.display = 'none';
        }

        // Body
        let body = document.getElementById('tmpl-body').value || 'Your template message will appear here...';
        // Replace variables with styled placeholders
        body = body.replace(/\{\{(\d+)\}\}/g, '<span style="background:#dcfce7;color:#15803d;padding:1px 4px;border-radius:3px;font-weight:600;">{{$1}}</span>');
        document.getElementById('preview-body').innerHTML = body;

        // Footer
        const footer = document.getElementById('tmpl-footer').value;
        const footerEl = document.getElementById('preview-footer');
        if (footer) {
            footerEl.style.display = '';
            footerEl.textContent = footer;
        } else {
            footerEl.style.display = 'none';
        }

        // Buttons
        updateButtonsPreview();
    }

    function updateExampleFields() {
        const body = document.getElementById('tmpl-body').value;
        const matches = [...new Set((body.match(/\{\{(\d+)\}\}/g) || []).map(m => m.replace(/[{}]/g, '')))];
        const container = document.getElementById('example-values-container');

        if (matches.length === 0) {
            container.innerHTML = '<p style="color:var(--muted-foreground);font-size:0.8rem;">Add variables to the body text to see example fields here.</p>';
            return;
        }

        container.innerHTML = matches.map(num => `
            <div class="example-row">
                <span class="label">{&lbrace;${num}}}</span>
                <input type="text" name="example_values[]" placeholder="Example value for variable ${num}" required>
            </div>
        `).join('');
    }

    function addButton() {
        if (buttonCount >= 3) {
            alert('Maximum 3 buttons allowed by Meta.');
            return;
        }
        buttonCount++;
        const container = document.getElementById('buttons-container');
        const idx = buttonCount - 1;
        const row = document.createElement('div');
        row.className = 'tmpl-button-row';
        row.id = `btn-row-${idx}`;
        row.innerHTML = `
            <select name="buttons[${idx}][type]" onchange="toggleBtnFields(${idx})">
                <option value="URL">URL</option>
                <option value="PHONE_NUMBER">Phone</option>
                <option value="QUICK_REPLY">Quick Reply</option>
            </select>
            <input type="text" name="buttons[${idx}][text]" placeholder="Button text" maxlength="25" required oninput="updateButtonsPreview()">
            <input type="text" name="buttons[${idx}][url]" id="btn-url-${idx}" placeholder="https://example.com" oninput="updateButtonsPreview()">
            <button type="button" class="btn btn-icon btn-ghost btn-sm" style="color:#dc2626;" onclick="removeButton(${idx})">
                <i data-lucide="x" style="width:14px;height:14px;"></i>
            </button>
        `;
        container.appendChild(row);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        updateButtonsPreview();
    }

    function toggleBtnFields(idx) {
        const type = document.querySelector(`#btn-row-${idx} select`).value;
        const urlInput = document.getElementById(`btn-url-${idx}`);
        if (type === 'URL') {
            urlInput.placeholder = 'https://example.com/{{1}}';
            urlInput.style.display = '';
            urlInput.name = `buttons[${idx}][url]`;
        } else if (type === 'PHONE_NUMBER') {
            urlInput.placeholder = '+919876543210';
            urlInput.style.display = '';
            urlInput.name = `buttons[${idx}][phone_number]`;
        } else {
            urlInput.style.display = 'none';
            urlInput.value = '';
        }
    }

    function removeButton(idx) {
        const row = document.getElementById(`btn-row-${idx}`);
        if (row) {
            row.remove();
            buttonCount--;
        }
        updateButtonsPreview();
    }

    function updateButtonsPreview() {
        const container = document.getElementById('preview-buttons');
        const rows = document.querySelectorAll('.tmpl-button-row');
        let html = '';
        rows.forEach(row => {
            const text = row.querySelector('input[type="text"]')?.value || 'Button';
            const type = row.querySelector('select')?.value || 'URL';
            const icon = type === 'URL' ? '🔗' : type === 'PHONE_NUMBER' ? '📞' : '⚡';
            html += `<div class="wa-bubble-btn">${icon} ${text}</div>`;
        });
        container.innerHTML = html;
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        toggleHeader();
        updatePreview();
        updateExampleFields();
    });
</script>
@endpush
