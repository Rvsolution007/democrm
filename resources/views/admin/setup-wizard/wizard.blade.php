@extends('admin.layouts.app')

@section('title', 'AI Catalogue Setup Wizard')
@section('breadcrumb', 'Setup Wizard')

@push('styles')
<style>
    /* ═══════════ WIZARD LAYOUT ═══════════ */
    .wizard-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 16px;
    }

    /* Progress Bar */
    .wizard-progress {
        display: flex;
        justify-content: space-between;
        position: relative;
        margin-bottom: 40px;
        padding: 0 20px;
    }
    .wizard-progress::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 60px;
        right: 60px;
        height: 3px;
        background: hsl(var(--border));
        z-index: 0;
    }
    .wizard-progress-fill {
        position: absolute;
        top: 20px;
        left: 60px;
        height: 3px;
        background: linear-gradient(90deg, #8b5cf6, #6d28d9);
        z-index: 1;
        transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 2px;
    }
    .wizard-step-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 2;
        cursor: default;
    }
    .wizard-step-dot {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
        border: 3px solid hsl(var(--border));
        background: hsl(var(--card));
        color: hsl(var(--muted-foreground));
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .wizard-step-dot.active {
        border-color: #8b5cf6;
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        transform: scale(1.05);
    }
    .wizard-step-dot.done {
        border-color: #10b981;
        background: #10b981;
        color: white;
    }
    .wizard-step-label {
        margin-top: 8px;
        font-size: 12px;
        font-weight: 600;
        color: hsl(var(--muted-foreground));
        transition: color 0.3s;
        text-align: center;
        max-width: 100px;
    }
    .wizard-step-indicator.active .wizard-step-label {
        color: #8b5cf6;
    }
    .wizard-step-indicator.done .wizard-step-label {
        color: #10b981;
    }

    /* Step Panels */
    .wizard-panel {
        display: none;
        animation: wizardFadeIn 0.5s ease;
    }
    .wizard-panel.active {
        display: block;
    }
    @keyframes wizardFadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Upload Zone */
    .upload-zone {
        border: 2px dashed hsl(var(--border));
        border-radius: 16px;
        padding: 48px 32px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: hsl(var(--muted)/0.15);
    }
    .upload-zone:hover, .upload-zone.drag-over {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.05);
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(139, 92, 246, 0.1);
    }
    .upload-zone.has-file {
        border-color: #10b981;
        background: rgba(16, 185, 129, 0.05);
    }

    /* Source Toggle */
    .source-toggle {
        display: flex;
        background: hsl(var(--muted)/0.3);
        border-radius: 12px;
        padding: 4px;
        margin-bottom: 24px;
    }
    .source-toggle-btn {
        flex: 1;
        padding: 12px 20px;
        border-radius: 10px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        color: hsl(var(--muted-foreground));
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .source-toggle-btn.active {
        background: white;
        color: #8b5cf6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    /* Action Button */
    .wizard-btn {
        padding: 14px 32px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .wizard-btn-primary {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        box-shadow: 0 4px 14px rgba(139, 92, 246, 0.3);
    }
    .wizard-btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }
    .wizard-btn-primary:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .wizard-btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
    }
    .wizard-btn-success:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    .wizard-btn-outline {
        background: white;
        color: hsl(var(--foreground));
        border: 2px solid hsl(var(--border));
    }
    .wizard-btn-outline:hover {
        border-color: #8b5cf6;
        color: #8b5cf6;
    }

    /* Loading Spinner */
    .wizard-spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: wizardSpin 0.7s linear infinite;
    }
    @keyframes wizardSpin {
        to { transform: rotate(360deg); }
    }

    /* Column Preview Table */
    .column-preview-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }
    .column-preview-table thead th {
        background: #f8fafc;
        padding: 10px 14px;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }
    .column-preview-table tbody td {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .column-preview-table tbody tr:hover {
        background: rgba(139, 92, 246, 0.03);
    }

    /* Confetti */
    .confetti-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 9999;
    }
    .confetti-piece {
        position: absolute;
        width: 10px;
        height: 10px;
        top: -20px;
        animation: confettiFall 3s ease-out forwards;
    }
    @keyframes confettiFall {
        0% { opacity: 1; transform: translateY(0) rotate(0deg); }
        100% { opacity: 0; transform: translateY(100vh) rotate(720deg); }
    }

    /* Alert Box */
    .wizard-alert {
        padding: 14px 18px;
        border-radius: 10px;
        font-size: 14px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 16px;
        animation: wizardFadeIn 0.4s ease;
    }
    .wizard-alert-error {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.15);
        color: #dc2626;
    }
    .wizard-alert-success {
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.15);
        color: #059669;
    }
    .wizard-alert-info {
        background: rgba(59, 130, 246, 0.08);
        border: 1px solid rgba(59, 130, 246, 0.15);
        color: #2563eb;
    }

    /* ═══════ COUNTDOWN TIMER ═══════ */
    .countdown-container {
        display: none;
        text-align: center;
        margin-bottom: 20px;
        animation: wizardFadeIn 0.5s ease;
    }
    .countdown-container.active { display: block; }
    .countdown-ring-wrap {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 16px;
    }
    .countdown-ring-wrap svg {
        transform: rotate(-90deg);
        width: 100px;
        height: 100px;
    }
    .countdown-ring-bg {
        fill: none;
        stroke: #e2e8f0;
        stroke-width: 6;
    }
    .countdown-ring-progress {
        fill: none;
        stroke: url(#countdown-gradient);
        stroke-width: 6;
        stroke-linecap: round;
        stroke-dasharray: 264;
        stroke-dashoffset: 264;
        transition: stroke-dashoffset 1s linear;
    }
    .countdown-time {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 22px;
        font-weight: 800;
        color: #8b5cf6;
        font-variant-numeric: tabular-nums;
    }
    .countdown-stage {
        font-size: 14px;
        font-weight: 600;
        color: hsl(var(--foreground));
        margin-bottom: 4px;
    }
    .countdown-substage {
        font-size: 12px;
        color: hsl(var(--muted-foreground));
    }
    .countdown-steps {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 12px;
        flex-wrap: wrap;
    }
    .countdown-step-pill {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: #f1f5f9;
        color: #94a3b8;
        transition: all 0.3s;
    }
    .countdown-step-pill.active {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }
    .countdown-step-pill.done {
        background: #10b981;
        color: white;
    }

    /* ═══════ EDITABLE COLUMN TABLE ═══════ */
    .col-edit-row {
        display: grid;
        grid-template-columns: 30px 1fr 120px auto auto 40px;
        gap: 8px;
        align-items: center;
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }
    .col-edit-row:hover {
        background: rgba(139, 92, 246, 0.03);
    }
    .col-edit-row .drag-handle {
        cursor: grab;
        color: #cbd5e1;
        font-size: 16px;
        text-align: center;
        user-select: none;
    }
    .col-edit-row .drag-handle:active { cursor: grabbing; }
    .col-edit-input {
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        transition: border-color 0.2s;
        width: 100%;
        background: white;
    }
    .col-edit-input:focus {
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    .col-edit-select {
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 12px;
        color: #475569;
        outline: none;
        background: white;
        cursor: pointer;
        width: 100%;
    }
    .col-edit-select:focus {
        border-color: #8b5cf6;
    }
    .col-flags {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }
    .col-flag-badge {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        cursor: pointer;
        border: 1.5px solid transparent;
        transition: all 0.2s;
        user-select: none;
        white-space: nowrap;
    }
    .col-flag-badge.off {
        background: #f1f5f9;
        color: #94a3b8;
        border-color: #e2e8f0;
        opacity: 0.6;
    }
    .col-flag-badge.on-category { background: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
    .col-flag-badge.on-title { background: #ede9fe; color: #5b21b6; border-color: #c4b5fd; }
    .col-flag-badge.on-unique { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
    .col-flag-badge.on-required { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    .col-flag-badge.on-combo { background: #fef3c7; color: #92400e; border-color: #fcd34d; }
    .col-delete-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: #cbd5e1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .col-delete-btn:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    .col-edit-header {
        display: grid;
        grid-template-columns: 30px 1fr 120px auto auto 40px;
        gap: 8px;
        padding: 10px 14px;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
    }
    .col-options-input {
        margin-top: 6px;
        padding: 5px 8px;
        border: 1px dashed #d1d5db;
        border-radius: 6px;
        font-size: 11px;
        color: #64748b;
        width: 100%;
        outline: none;
        background: #fafafa;
    }
    .col-options-input:focus {
        border-color: #8b5cf6;
        background: white;
    }
    .add-column-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 10px;
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        background: transparent;
        color: #8b5cf6;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 4px;
    }
    .add-column-btn:hover {
        border-color: #8b5cf6;
        background: rgba(139, 92, 246, 0.04);
    }
</style>
@endpush

@section('content')
<div class="wizard-container">

    {{-- Wizard Header --}}
    <div style="text-align:center;margin-bottom:32px">
        <div style="width:64px;height:64px;border-radius:20px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;box-shadow:0 8px 24px rgba(139,92,246,0.3)">
            <i data-lucide="wand-2" style="width:32px;height:32px;color:white"></i>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:hsl(var(--foreground));margin:0 0 8px 0;letter-spacing:-0.02em">
            {{ $tourConfig['welcome_title'] }}
        </h1>
        <p style="color:hsl(var(--muted-foreground));font-size:15px;max-width:600px;margin:0 auto">
            {{ $tourConfig['welcome_subtitle'] }}
        </p>
    </div>

    {{-- Progress Steps --}}
    <div class="wizard-progress" id="wizard-progress">
        <div class="wizard-progress-fill" id="progress-fill" style="width:0%"></div>

        <div class="wizard-step-indicator active" data-step="1">
            <div class="wizard-step-dot active">1</div>
            <div class="wizard-step-label">Upload Catalogue</div>
        </div>
        <div class="wizard-step-indicator" data-step="2">
            <div class="wizard-step-dot">2</div>
            <div class="wizard-step-label">Column Setup</div>
        </div>
        <div class="wizard-step-indicator" data-step="3">
            <div class="wizard-step-dot">3</div>
            <div class="wizard-step-label">Product Data</div>
        </div>
        <div class="wizard-step-indicator" data-step="4">
            <div class="wizard-step-dot">4</div>
            <div class="wizard-step-label">Complete!</div>
        </div>
    </div>

    {{-- Alert Area --}}
    <div id="wizard-alerts"></div>

    {{-- ═══════════ STEP 1: Upload & Analyze ═══════════ --}}
    <div class="wizard-panel active" id="step-1">
        <div class="card" style="border-radius:16px;overflow:hidden">
            <div class="card-content" style="padding:32px">

                <p style="color:hsl(var(--muted-foreground));font-size:14px;margin:0 0 24px 0;line-height:1.6">
                    {{ $tourConfig['intro_message'] }}
                </p>

                {{-- Source Toggle --}}
                <div class="source-toggle">
                    <button type="button" class="source-toggle-btn active" onclick="setSource('pdf')" id="src-pdf-btn">
                        <i data-lucide="file-text" style="width:18px;height:18px"></i>
                        Upload PDF
                    </button>
                    <button type="button" class="source-toggle-btn" onclick="setSource('website')" id="src-web-btn">
                        <i data-lucide="globe" style="width:18px;height:18px"></i>
                        Website URL
                    </button>
                </div>

                {{-- PDF Upload Zone --}}
                <div id="pdf-zone">
                    <div class="upload-zone" id="upload-zone"
                         ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)"
                         onclick="document.getElementById('pdf-input').click()">
                        <input type="file" id="pdf-input" accept=".pdf" style="display:none" onchange="handleFileSelect(this)">
                        <div id="upload-icon" style="margin-bottom:16px">
                            <i data-lucide="upload-cloud" style="width:48px;height:48px;color:#8b5cf6;opacity:0.6"></i>
                        </div>
                        <div id="upload-text">
                            <p style="font-size:16px;font-weight:600;color:hsl(var(--foreground));margin:0 0 4px 0">
                                Drag & drop your catalogue PDF here
                            </p>
                            <p style="font-size:13px;color:hsl(var(--muted-foreground));margin:0">
                                or click to browse • Max 20MB
                            </p>
                        </div>
                        <div id="upload-file-info" style="display:none;margin-top:12px">
                            <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(16,185,129,0.1);border-radius:8px;color:#059669;font-weight:600;font-size:14px">
                                <i data-lucide="file-check" style="width:18px;height:18px"></i>
                                <span id="upload-file-name"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Website URL Input --}}
                <div id="website-zone" style="display:none">
                    <div style="position:relative">
                        <div style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8b5cf6;display:flex">
                            <i data-lucide="link" style="width:20px;height:20px"></i>
                        </div>
                        <input type="url" id="website-url" placeholder="https://www.example.com/products"
                               style="width:100%;padding:16px 16px 16px 46px;border:2px solid hsl(var(--border));border-radius:14px;font-size:15px;outline:none;transition:all 0.3s;background:hsl(var(--card))"
                               onfocus="this.style.borderColor='#8b5cf6';this.style.boxShadow='0 0 0 4px rgba(139,92,246,0.1)'"
                               onblur="this.style.borderColor='hsl(var(--border))';this.style.boxShadow='none'">
                    </div>
                    <p style="margin:8px 0 0 0;font-size:12px;color:hsl(var(--muted-foreground))">
                        <i data-lucide="info" style="width:12px;height:12px;display:inline;vertical-align:middle;margin-right:4px"></i>
                        Enter your product catalogue page URL. The AI will analyze the page content.
                    </p>
                </div>

                {{-- Countdown Timer (shown during analysis) --}}
                <div class="countdown-container" id="countdown-container">
                    <div class="countdown-ring-wrap">
                        <svg viewBox="0 0 90 90">
                            <defs>
                                <linearGradient id="countdown-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#8b5cf6"/>
                                    <stop offset="100%" stop-color="#6d28d9"/>
                                </linearGradient>
                            </defs>
                            <circle class="countdown-ring-bg" cx="45" cy="45" r="42"/>
                            <circle class="countdown-ring-progress" id="countdown-ring" cx="45" cy="45" r="42"/>
                        </svg>
                        <div class="countdown-time" id="countdown-time">--</div>
                    </div>
                    <div class="countdown-stage" id="countdown-stage">Preparing...</div>
                    <div class="countdown-substage" id="countdown-substage">Estimated time for 14MB PDF</div>
                    <div class="countdown-steps">
                        <span class="countdown-step-pill" id="cs-upload">📤 Uploading</span>
                        <span class="countdown-step-pill" id="cs-extract">📄 Extracting</span>
                        <span class="countdown-step-pill" id="cs-analyze">🤖 AI Analyzing</span>
                        <span class="countdown-step-pill" id="cs-build">🏗️ Building</span>
                    </div>
                </div>

                {{-- Analyze Button --}}
                <div style="margin-top:28px;text-align:center">
                    <button type="button" class="wizard-btn wizard-btn-primary" id="analyze-btn" onclick="analyzeCatalogue()" disabled>
                        <i data-lucide="sparkles" style="width:18px;height:18px"></i>
                        <span id="analyze-btn-text">Analyze Catalogue with AI</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ STEP 2: Column Review & Import ═══════════ --}}
    <div class="wizard-panel" id="step-2">
        <div class="card" style="border-radius:16px;overflow:hidden">
            <div class="card-content" style="padding:32px">

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <div>
                        <h2 style="font-size:20px;font-weight:700;margin:0 0 4px 0;color:hsl(var(--foreground))">
                            Column Structure Identified
                        </h2>
                        <p id="analysis-summary" style="font-size:13px;color:hsl(var(--muted-foreground));margin:0"></p>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span id="confidence-badge" style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,0.1);color:#059669"></span>
                    </div>
                </div>

                {{-- Editable Column Editor --}}
                <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:16px">
                    <div class="col-edit-header">
                        <span></span>
                        <span>COLUMN NAME</span>
                        <span>TYPE</span>
                        <span>FLAGS</span>
                        <span>OPTIONS</span>
                        <span></span>
                    </div>
                    <div id="columns-editor">
                        {{-- Populated via JS --}}
                    </div>
                    <button type="button" class="add-column-btn" onclick="addNewColumn()">
                        <i data-lucide="plus" style="width:16px;height:16px"></i>
                        Add Custom Field
                    </button>
                </div>

                <p style="font-size:12px;color:hsl(var(--muted-foreground));margin:0 0 20px 0;text-align:center">
                    💡 Click column names, types, and flags to edit. Changes will be saved when you create columns.
                </p>

                {{-- Action Buttons --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
                    <button type="button" class="wizard-btn wizard-btn-outline" onclick="downloadColumnsExcel()">
                        <i data-lucide="download" style="width:16px;height:16px"></i>
                        Download Columns Excel
                    </button>
                    <button type="button" class="wizard-btn wizard-btn-primary" id="import-columns-btn" onclick="importColumns()">
                        <i data-lucide="upload" style="width:16px;height:16px"></i>
                        <span id="import-columns-text">Create Columns</span>
                    </button>
                </div>

                <div id="import-result" style="display:none;margin-top:16px"></div>

                {{-- Next Step --}}
                <div style="margin-top:24px;text-align:center" id="step2-next" style="display:none">
                    <button type="button" class="wizard-btn wizard-btn-success" onclick="goToStep(3)" style="display:none" id="step2-next-btn">
                        Continue to Product Data
                        <i data-lucide="arrow-right" style="width:16px;height:16px"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ STEP 3: Product Extraction ═══════════ --}}
    <div class="wizard-panel" id="step-3">
        <div class="card" style="border-radius:16px;overflow:hidden">
            <div class="card-content" style="padding:32px">

                <h2 style="font-size:20px;font-weight:700;margin:0 0 8px 0;color:hsl(var(--foreground))">
                    Extract Product Data
                </h2>
                <p style="font-size:14px;color:hsl(var(--muted-foreground));margin:0 0 24px 0">
                    AI will now read your catalogue and extract all product data into the columns you just created.
                </p>

                {{-- Extraction Countdown --}}
                <div class="countdown-container" id="extract-countdown">
                    <div class="countdown-ring-wrap">
                        <svg viewBox="0 0 90 90">
                            <defs>
                                <linearGradient id="extract-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#10b981"/>
                                    <stop offset="100%" stop-color="#059669"/>
                                </linearGradient>
                            </defs>
                            <circle class="countdown-ring-bg" cx="45" cy="45" r="42"/>
                            <circle class="countdown-ring-progress" id="extract-ring" cx="45" cy="45" r="42" style="stroke: url(#extract-gradient)"/>
                        </svg>
                        <div class="countdown-time" id="extract-time">--</div>
                    </div>
                    <div class="countdown-stage" id="extract-stage">Preparing extraction...</div>
                    <div class="countdown-substage" id="extract-substage">Processing catalogue pages</div>
                    <div class="countdown-steps">
                        <span class="countdown-step-pill" id="es-read">📖 Reading</span>
                        <span class="countdown-step-pill" id="es-parse">🔍 Parsing</span>
                        <span class="countdown-step-pill" id="es-map">🗂️ Mapping</span>
                        <span class="countdown-step-pill" id="es-format">📋 Formatting</span>
                    </div>
                </div>

                <div style="text-align:center;margin-bottom:20px">
                    <button type="button" class="wizard-btn wizard-btn-primary" id="extract-btn" onclick="extractProducts()">
                        <i data-lucide="sparkles" style="width:18px;height:18px"></i>
                        <span id="extract-btn-text">Extract Products with AI</span>
                    </button>
                </div>

                {{-- Products Preview --}}
                <div id="products-preview" style="display:none">
                    <h3 style="font-size:16px;font-weight:600;margin:0 0 12px 0;display:flex;align-items:center;gap:8px">
                        <i data-lucide="package" style="width:18px;height:18px;color:#8b5cf6"></i>
                        Product Preview <span id="products-count" style="font-size:13px;color:hsl(var(--muted-foreground));font-weight:400"></span>
                    </h3>

                    <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;max-height:350px;overflow-y:auto;margin-bottom:24px">
                        <table class="column-preview-table" id="products-table">
                            <thead id="products-thead">
                                {{-- Populated via JS --}}
                            </thead>
                            <tbody id="products-tbody">
                                {{-- Populated via JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Import Result --}}
                    <div id="product-import-result" style="display:none;margin-bottom:16px"></div>

                    {{-- Action Buttons --}}
                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center" id="product-action-btns">
                        <button type="button" class="wizard-btn wizard-btn-outline" onclick="downloadProductsExcel()">
                            <i data-lucide="download" style="width:16px;height:16px"></i>
                            Download Products Excel
                        </button>
                        <button type="button" class="wizard-btn wizard-btn-primary" id="import-products-btn" onclick="importProductsToSystem()">
                            <i data-lucide="database" style="width:16px;height:16px"></i>
                            <span id="import-products-text">Import Products to System</span>
                        </button>
                    </div>

                    {{-- Post-Import Actions --}}
                    <div id="post-import-actions" style="display:none;margin-top:16px">
                        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
                            <a href="{{ route('admin.products.index') }}" class="wizard-btn wizard-btn-primary">
                                <i data-lucide="package" style="width:16px;height:16px"></i>
                                Go to Products
                            </a>
                            <button type="button" class="wizard-btn wizard-btn-success" onclick="goToStep(4)">
                                Complete Setup
                                <i data-lucide="party-popper" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ STEP 4: Complete ═══════════ --}}
    <div class="wizard-panel" id="step-4">
        <div class="card" style="border-radius:16px;overflow:hidden">
            <div class="card-content" style="padding:48px 32px;text-align:center">

                <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;margin:0 auto 24px auto;box-shadow:0 8px 30px rgba(16,185,129,0.3)">
                    <i data-lucide="check" style="width:40px;height:40px;color:white"></i>
                </div>

                <h2 style="font-size:28px;font-weight:800;margin:0 0 8px 0;color:hsl(var(--foreground));letter-spacing:-0.02em">
                    Catalogue Setup Complete! 🎉
                </h2>
                <p style="font-size:16px;color:hsl(var(--muted-foreground));margin:0 0 8px 0">
                    Your product catalogue structure is ready.
                </p>
                <p id="completion-summary" style="font-size:14px;color:hsl(var(--muted-foreground));margin:0 0 32px 0"></p>

                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                    <a href="{{ route('admin.products.index') }}" class="wizard-btn wizard-btn-primary">
                        <i data-lucide="package" style="width:16px;height:16px"></i>
                        Go to Products
                    </a>
                    <a href="{{ url('admin/catalogue-custom-columns') }}" class="wizard-btn wizard-btn-outline">
                        <i data-lucide="columns" style="width:16px;height:16px"></i>
                        View Catalogue Columns
                    </a>
                    <button type="button" class="wizard-btn wizard-btn-outline" onclick="resetWizard()">
                        <i data-lucide="rotate-ccw" style="width:16px;height:16px"></i>
                        Run Again
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const CSRF = '{{ csrf_token() }}';
    let currentStep = 1;
    let sourceType = 'pdf';
    let selectedFile = null;
    let aiColumns = @json($cachedColumns ?? []);
    let countdownInterval = null;

    // ═══════════ SOURCE TOGGLE ═══════════
    function setSource(type) {
        sourceType = type;
        document.getElementById('src-pdf-btn').classList.toggle('active', type === 'pdf');
        document.getElementById('src-web-btn').classList.toggle('active', type === 'website');
        document.getElementById('pdf-zone').style.display = type === 'pdf' ? 'block' : 'none';
        document.getElementById('website-zone').style.display = type === 'website' ? 'block' : 'none';
        updateAnalyzeBtn();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ═══════════ FILE HANDLING ═══════════
    function handleDragOver(e) {
        e.preventDefault();
        document.getElementById('upload-zone').classList.add('drag-over');
    }
    function handleDragLeave(e) {
        document.getElementById('upload-zone').classList.remove('drag-over');
    }
    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('upload-zone').classList.remove('drag-over');
        var files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/pdf') {
            setSelectedFile(files[0]);
        } else {
            showAlert('error', 'Please upload a PDF file.');
        }
    }
    function handleFileSelect(input) {
        if (input.files.length > 0) {
            setSelectedFile(input.files[0]);
        }
    }
    function setSelectedFile(file) {
        selectedFile = file;
        document.getElementById('upload-zone').classList.add('has-file');
        document.getElementById('upload-file-info').style.display = 'block';
        document.getElementById('upload-file-name').textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(1) + ' MB)';
        updateAnalyzeBtn();
    }

    function updateAnalyzeBtn() {
        var btn = document.getElementById('analyze-btn');
        if (sourceType === 'pdf') {
            btn.disabled = !selectedFile;
        } else {
            btn.disabled = !document.getElementById('website-url').value.trim();
        }
    }
    document.getElementById('website-url')?.addEventListener('input', updateAnalyzeBtn);

    // ═══════════ COUNTDOWN TIMER ═══════════
    var countdownStages = [
        { id: 'cs-upload',  label: '📤 Uploading PDF...', duration: 0.10 },
        { id: 'cs-extract', label: '📄 Extracting text...', duration: 0.15 },
        { id: 'cs-analyze', label: '🤖 AI analyzing catalogue...', duration: 0.55 },
        { id: 'cs-build',   label: '🏗️ Building column structure...', duration: 0.20 },
    ];

    function startCountdown() {
        var fileSizeMB = selectedFile ? selectedFile.size / 1024 / 1024 : 5;
        // Estimate: Large PDFs need more time via Gemini API
        var totalSeconds = Math.max(60, Math.min(300, Math.round(fileSizeMB * 15)));
        var elapsed = 0;
        var ring = document.getElementById('countdown-ring');
        var timeEl = document.getElementById('countdown-time');
        var stageEl = document.getElementById('countdown-stage');
        var subEl = document.getElementById('countdown-substage');
        var circumference = 2 * Math.PI * 42; // 264

        document.getElementById('countdown-container').classList.add('active');
        subEl.textContent = 'Estimated ~' + totalSeconds + 's for ' + fileSizeMB.toFixed(1) + 'MB PDF';

        // Reset pills
        countdownStages.forEach(function(s) {
            var pill = document.getElementById(s.id);
            pill.classList.remove('active', 'done');
        });

        countdownInterval = setInterval(function() {
            elapsed++;
            var remaining = Math.max(0, totalSeconds - elapsed);
            var progress = Math.min(elapsed / totalSeconds, 1);

            // Update ring
            ring.style.strokeDashoffset = circumference * (1 - progress);

            // Update time display
            if (remaining > 0) {
                timeEl.textContent = remaining + 's';
            } else {
                timeEl.textContent = '⏳';
            }

            // Update stage
            var cumulative = 0;
            for (var i = 0; i < countdownStages.length; i++) {
                var s = countdownStages[i];
                cumulative += s.duration;
                var pill = document.getElementById(s.id);
                if (progress < cumulative) {
                    stageEl.textContent = s.label;
                    pill.classList.add('active');
                    pill.classList.remove('done');
                    break;
                } else {
                    pill.classList.remove('active');
                    pill.classList.add('done');
                }
            }
        }, 1000);
    }

    function stopCountdown(success) {
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        if (success) {
            document.getElementById('countdown-time').textContent = '✓';
            document.getElementById('countdown-stage').textContent = 'Analysis complete!';
            countdownStages.forEach(function(s) {
                document.getElementById(s.id).classList.remove('active');
                document.getElementById(s.id).classList.add('done');
            });
        }
        setTimeout(function() {
            document.getElementById('countdown-container').classList.remove('active');
        }, success ? 1500 : 500);
    }

    // ═══════════ STEP 1: ANALYZE ═══════════
    function analyzeCatalogue() {
        var btn = document.getElementById('analyze-btn');
        var textEl = document.getElementById('analyze-btn-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> AI is analyzing your catalogue...';

        startCountdown();

        var formData = new FormData();
        formData.append('source_type', sourceType);
        if (sourceType === 'pdf') {
            formData.append('catalogue_pdf', selectedFile);
        } else {
            formData.append('website_url', document.getElementById('website-url').value.trim());
        }

        // Timeout: 5 minutes for Gemini PDF analysis
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, 300000);

        fetch('{{ route("admin.setup-wizard.analyze") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: formData,
            signal: controller.signal,
        })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
            clearTimeout(timeoutId);
            if (res.ok && res.data.success) {
                stopCountdown(true);
                aiColumns = res.data.columns;
                showAlert('success', res.data.message);
                renderColumnsEditor(aiColumns);
                document.getElementById('analysis-summary').textContent = res.data.source_summary;
                document.getElementById('confidence-badge').textContent = res.data.confidence + '% confidence';
                setTimeout(function() { goToStep(2); }, 1200);
            } else {
                stopCountdown(false);
                showAlert('error', res.data.message || 'Analysis failed.');
                btn.disabled = false;
                textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Analysis';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        })
        .catch(function(err) {
            clearTimeout(timeoutId);
            stopCountdown(false);
            var msg = err && err.name === 'AbortError' ? 'Analysis timed out. Please try again.' : 'Network error. Please check your connection and try again.';
            showAlert('error', msg);
            btn.disabled = false;
            textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Analysis';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }

    // ═══════════ EDITABLE COLUMN EDITOR ═══════════
    var colTypes = ['text', 'textarea', 'number', 'select', 'multiselect', 'boolean'];
    var colFlags = [
        { key: 'is_category', label: '📂 Category', cls: 'on-category', exclusive: true },
        { key: 'is_title',    label: '🏷️ Title',    cls: 'on-title',    exclusive: true },
        { key: 'is_unique',   label: '🔑 Unique',   cls: 'on-unique',   exclusive: true },
        { key: 'is_required', label: 'Required',     cls: 'on-required', exclusive: false },
        { key: 'is_combo',    label: '🔀 Combo',    cls: 'on-combo',    exclusive: false },
    ];

    function renderColumnsEditor(columns) {
        var container = document.getElementById('columns-editor');
        container.innerHTML = '';
        columns.forEach(function(col, i) {
            container.appendChild(createColumnRow(col, i));
        });
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function createColumnRow(col, index) {
        var row = document.createElement('div');
        row.className = 'col-edit-row';
        row.dataset.index = index;

        // Drag handle
        var drag = document.createElement('div');
        drag.className = 'drag-handle';
        drag.innerHTML = '⠿';
        drag.title = 'Drag to reorder';

        // Name input
        var nameWrap = document.createElement('div');
        var nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.className = 'col-edit-input';
        nameInput.value = col.name || '';
        nameInput.placeholder = 'Column name';
        nameInput.onchange = function() { aiColumns[index].name = this.value; };
        nameWrap.appendChild(nameInput);

        // Options input (shown for select/multiselect)
        if (col.type === 'select' || col.type === 'multiselect') {
            var optInput = document.createElement('input');
            optInput.type = 'text';
            optInput.className = 'col-options-input';
            optInput.value = (col.options || []).join(', ');
            optInput.placeholder = 'Options (comma separated)';
            optInput.onchange = function() {
                aiColumns[index].options = this.value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
            };
            nameWrap.appendChild(optInput);
        }

        // Type select
        var typeSelect = document.createElement('select');
        typeSelect.className = 'col-edit-select';
        colTypes.forEach(function(t) {
            var opt = document.createElement('option');
            opt.value = t;
            opt.textContent = t;
            if (t === col.type) opt.selected = true;
            typeSelect.appendChild(opt);
        });
        typeSelect.onchange = function() {
            aiColumns[index].type = this.value;
            // Re-render this row to show/hide options input
            renderColumnsEditor(aiColumns);
        };

        // Flags
        var flagsDiv = document.createElement('div');
        flagsDiv.className = 'col-flags';
        colFlags.forEach(function(flag) {
            var badge = document.createElement('span');
            badge.className = 'col-flag-badge ' + (col[flag.key] ? flag.cls : 'off');
            badge.textContent = flag.label;
            badge.title = 'Toggle ' + flag.label;
            badge.onclick = function() {
                toggleFlag(index, flag.key, flag.exclusive, flag.cls);
            };
            flagsDiv.appendChild(badge);
        });

        // Options column (display)
        var optsDisplay = document.createElement('div');
        optsDisplay.style.cssText = 'font-size:11px;color:#64748b;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
        if (col.options && col.options.length > 0) {
            optsDisplay.textContent = col.options.length + ' options';
            optsDisplay.title = col.options.join(', ');
        } else {
            optsDisplay.textContent = '—';
        }

        // Delete button
        var delBtn = document.createElement('button');
        delBtn.className = 'col-delete-btn';
        delBtn.innerHTML = '🗑️';
        delBtn.title = 'Remove column';
        delBtn.onclick = function() {
            if (aiColumns.length <= 2) {
                showAlert('error', 'You need at least 2 columns.');
                return;
            }
            aiColumns.splice(index, 1);
            renderColumnsEditor(aiColumns);
        };

        row.appendChild(drag);
        row.appendChild(nameWrap);
        row.appendChild(typeSelect);
        row.appendChild(flagsDiv);
        row.appendChild(optsDisplay);
        row.appendChild(delBtn);

        return row;
    }

    function toggleFlag(index, flagKey, exclusive, cls) {
        var current = !!aiColumns[index][flagKey];

        // If turning ON an exclusive flag, turn it OFF for all other columns
        if (!current && exclusive) {
            aiColumns.forEach(function(col) { col[flagKey] = false; });
        }

        aiColumns[index][flagKey] = !current;
        renderColumnsEditor(aiColumns);
    }

    function addNewColumn() {
        aiColumns.push({
            name: '',
            type: 'text',
            is_unique: false,
            is_required: false,
            is_category: false,
            is_title: false,
            is_combo: false,
            options: [],
            show_in_ai: true,
            sort_order: aiColumns.length + 1
        });
        renderColumnsEditor(aiColumns);
        // Focus the new name input
        var inputs = document.querySelectorAll('#columns-editor .col-edit-input');
        if (inputs.length > 0) inputs[inputs.length - 1].focus();
    }

    // ═══════════ STEP 2: DOWNLOAD & IMPORT ═══════════
    function downloadColumnsExcel() {
        window.location.href = '{{ route("admin.setup-wizard.download-columns") }}';
    }

    function importColumns() {
        // Validate: filter out empty names
        var validColumns = aiColumns.filter(function(c) { return c.name && c.name.trim().length > 0; });
        if (validColumns.length === 0) {
            showAlert('error', 'Please add at least one column with a name.');
            return;
        }
        aiColumns = validColumns;

        var btn = document.getElementById('import-columns-btn');
        var textEl = document.getElementById('import-columns-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> Creating columns...';

        fetch('{{ route("admin.setup-wizard.import-columns") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ import_type: 'direct', columns: aiColumns })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('success', data.message);
                document.getElementById('import-result').style.display = 'block';
                document.getElementById('import-result').innerHTML =
                    '<div class="wizard-alert wizard-alert-success">' +
                    '<i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>' +
                    '<div><strong>' + data.created + ' columns</strong> created in your system.' +
                    (data.categories_created.length > 0 ? '<br>Categories auto-created: ' + data.categories_created.join(', ') : '') +
                    '</div></div>';
                document.getElementById('step2-next-btn').style.display = 'inline-flex';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                showAlert('error', data.message || 'Import failed.');
                btn.disabled = false;
                textEl.textContent = 'Retry Import';
            }
        })
        .catch(function() {
            showAlert('error', 'Import request failed.');
            btn.disabled = false;
            textEl.textContent = 'Retry Import';
        });
    }

    // ═══════════ STEP 3: EXTRACT PRODUCTS ═══════════
    var extractCountdownInterval = null;
    var extractStages = [
        { id: 'es-read',   label: '📖 Splitting PDF into chunks...', duration: 0.10 },
        { id: 'es-parse',  label: '🔍 Extracting products (chunk by chunk)...', duration: 0.50 },
        { id: 'es-map',    label: '🗂️ Merging & deduplicating...', duration: 0.25 },
        { id: 'es-format', label: '📋 Formatting final output...', duration: 0.15 },
    ];

    function startExtractCountdown() {
        var totalSeconds = 300; // Chunked extraction can take 3-5 minutes
        var elapsed = 0;
        var ring = document.getElementById('extract-ring');
        var timeEl = document.getElementById('extract-time');
        var stageEl = document.getElementById('extract-stage');
        var subEl = document.getElementById('extract-substage');
        var circumference = 2 * Math.PI * 42;

        document.getElementById('extract-countdown').classList.add('active');
        subEl.textContent = 'Processing all pages — may take 3-5 minutes for large catalogues';

        extractStages.forEach(function(s) {
            document.getElementById(s.id).classList.remove('active', 'done');
        });

        extractCountdownInterval = setInterval(function() {
            elapsed++;
            var remaining = Math.max(0, totalSeconds - elapsed);
            var progress = Math.min(elapsed / totalSeconds, 1);

            ring.style.strokeDashoffset = circumference * (1 - progress);
            timeEl.textContent = remaining > 0 ? remaining + 's' : '⏳';

            var cumulative = 0;
            for (var i = 0; i < extractStages.length; i++) {
                var s = extractStages[i];
                cumulative += s.duration;
                var pill = document.getElementById(s.id);
                if (progress < cumulative) {
                    stageEl.textContent = s.label;
                    pill.classList.add('active');
                    pill.classList.remove('done');
                    break;
                } else {
                    pill.classList.remove('active');
                    pill.classList.add('done');
                }
            }
        }, 1000);
    }

    function stopExtractCountdown(success) {
        if (extractCountdownInterval) {
            clearInterval(extractCountdownInterval);
            extractCountdownInterval = null;
        }
        if (success) {
            document.getElementById('extract-time').textContent = '✓';
            document.getElementById('extract-stage').textContent = 'Extraction complete!';
            extractStages.forEach(function(s) {
                document.getElementById(s.id).classList.remove('active');
                document.getElementById(s.id).classList.add('done');
            });
        }
        setTimeout(function() {
            document.getElementById('extract-countdown').classList.remove('active');
        }, success ? 1500 : 500);
    }

    function extractProducts() {
        var btn = document.getElementById('extract-btn');
        var textEl = document.getElementById('extract-btn-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> AI is extracting product data...';

        startExtractCountdown();

        // AbortController for timeout
        var controller = new AbortController();
        var timeoutId = setTimeout(function() { controller.abort(); }, 600000); // 10 min timeout

        fetch('{{ route("admin.setup-wizard.extract-products") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            signal: controller.signal,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            clearTimeout(timeoutId);
            if (data.success) {
                stopExtractCountdown(true);
                showAlert('success', data.message);
                document.getElementById('products-preview').style.display = 'block';
                document.getElementById('products-count').textContent = '(' + data.total + ' products found)';
                btn.style.display = 'none';
                renderProductsTable(data.products, data.total);
            } else {
                stopExtractCountdown(false);
                showAlert('error', data.message || 'Extraction failed.');
                btn.disabled = false;
                textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Extraction';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        })
        .catch(function(err) {
            clearTimeout(timeoutId);
            stopExtractCountdown(false);
            var msg = err && err.name === 'AbortError' ? 'Request timed out. The PDF may be too large.' : 'Request failed. Please try again.';
            showAlert('error', msg);
            btn.disabled = false;
            textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Extraction';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }

    function renderProductsTable(products, totalCount) {
        if (!products || products.length === 0) return;

        var keys = Object.keys(products[0]);
        var thead = document.getElementById('products-thead');
        var tbody = document.getElementById('products-tbody');

        thead.innerHTML = '<tr>' + keys.map(function(k) {
            return '<th>' + k + '</th>';
        }).join('') + '</tr>';

        tbody.innerHTML = '';
        products.slice(0, 10).forEach(function(product) {
            var tr = document.createElement('tr');
            tr.innerHTML = keys.map(function(k) {
                var val = product[k] || '';
                if (typeof val === 'string' && val.length > 40) val = val.substring(0, 40) + '...';
                return '<td>' + val + '</td>';
            }).join('');
            tbody.appendChild(tr);
        });

        // Show "showing X of Y" note if more than 10
        if (totalCount && totalCount > 10) {
            var note = document.createElement('tr');
            note.innerHTML = '<td colspan="' + keys.length + '" style="text-align:center;color:#8b5cf6;font-weight:600;padding:12px">Showing 10 of ' + totalCount + ' products (all will be imported)</td>';
            tbody.appendChild(note);
        }
    }

    function downloadProductsExcel() {
        window.location.href = '{{ route("admin.setup-wizard.download-products") }}';
    }

    // ═══════════ IMPORT PRODUCTS TO SYSTEM ═══════════
    function importProductsToSystem() {
        var btn = document.getElementById('import-products-btn');
        var textEl = document.getElementById('import-products-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> Importing products...';

        fetch('{{ route("admin.setup-wizard.import-products") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('success', data.message);

                // Show result
                document.getElementById('product-import-result').style.display = 'block';
                document.getElementById('product-import-result').innerHTML =
                    '<div class="wizard-alert wizard-alert-success">' +
                    '<i data-lucide="check-circle" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>' +
                    '<div><strong>' + data.created + ' products</strong> imported to your system.' +
                    (data.categories_created && data.categories_created.length > 0
                        ? '<br>Categories auto-created: ' + data.categories_created.join(', ')
                        : '') +
                    (data.skipped > 0 ? '<br>' + data.skipped + ' skipped due to errors' : '') +
                    '</div></div>';

                // Hide import buttons, show post-import actions
                document.getElementById('product-action-btns').style.display = 'none';
                document.getElementById('post-import-actions').style.display = 'block';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                showAlert('error', data.message || 'Import failed.');
                btn.disabled = false;
                textEl.textContent = 'Retry Import';
            }
        })
        .catch(function() {
            showAlert('error', 'Import request failed.');
            btn.disabled = false;
            textEl.textContent = 'Retry Import';
        });
    }

    // ═══════════ STEP NAVIGATION ═══════════
    function goToStep(step) {
        // Hide all panels
        document.querySelectorAll('.wizard-panel').forEach(function(p) { p.classList.remove('active'); });

        // Show target panel
        document.getElementById('step-' + step).classList.add('active');

        // Update progress indicators
        document.querySelectorAll('.wizard-step-indicator').forEach(function(ind) {
            var s = parseInt(ind.dataset.step);
            ind.classList.remove('active', 'done');
            ind.querySelector('.wizard-step-dot').classList.remove('active', 'done');
            if (s < step) {
                ind.classList.add('done');
                ind.querySelector('.wizard-step-dot').classList.add('done');
                ind.querySelector('.wizard-step-dot').innerHTML = '✓';
            } else if (s === step) {
                ind.classList.add('active');
                ind.querySelector('.wizard-step-dot').classList.add('active');
            }
        });

        // Update progress bar fill
        var totalSteps = 4;
        var progressPct = ((step - 1) / (totalSteps - 1)) * 100;
        document.getElementById('progress-fill').style.width = progressPct + '%';

        currentStep = step;

        // Step 4: mark complete & confetti
        if (step === 4) {
            markComplete();
            launchConfetti();
        }

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ═══════════ COMPLETION ═══════════
    function markComplete() {
        fetch('{{ route("admin.setup-wizard.complete") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });

        var summary = [];
        if (aiColumns && aiColumns.length > 0) summary.push(aiColumns.length + ' catalogue columns');
        document.getElementById('completion-summary').textContent = summary.length > 0
            ? 'You set up: ' + summary.join(' • ') + '. Now import your products Excel to see them live!'
            : 'Your catalogue structure is configured.';
    }

    function resetWizard() {
        if (!confirm('This will reset the wizard. You can run it again with a different catalogue. Continue?')) return;
        fetch('{{ route("admin.setup-wizard.reset") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        }).then(function() {
            location.reload();
        });
    }

    // ═══════════ CONFETTI 🎉 ═══════════
    function launchConfetti() {
        var container = document.createElement('div');
        container.className = 'confetti-container';
        document.body.appendChild(container);

        var colors = ['#8b5cf6', '#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#ec4899', '#6366f1'];
        for (var i = 0; i < 80; i++) {
            var piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.left = Math.random() * 100 + '%';
            piece.style.background = colors[Math.floor(Math.random() * colors.length)];
            piece.style.animationDelay = Math.random() * 1.5 + 's';
            piece.style.animationDuration = (2 + Math.random() * 2) + 's';
            piece.style.width = (6 + Math.random() * 8) + 'px';
            piece.style.height = (6 + Math.random() * 8) + 'px';
            piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
            container.appendChild(piece);
        }
        setTimeout(function() { container.remove(); }, 4000);
    }

    // ═══════════ ALERTS ═══════════
    function showAlert(type, message) {
        var container = document.getElementById('wizard-alerts');
        var cls = 'wizard-alert wizard-alert-' + type;
        var icon = type === 'error' ? 'alert-circle' : type === 'success' ? 'check-circle' : 'info';
        container.innerHTML = '<div class="' + cls + '">' +
            '<i data-lucide="' + icon + '" style="width:18px;height:18px;flex-shrink:0;margin-top:1px"></i>' +
            '<span>' + message + '</span></div>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(function() { container.innerHTML = ''; }, 8000);
    }

    // ═══════════ INIT ═══════════
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // If we have cached columns from a previous analysis, show step 2
        @if($cachedColumns && count($cachedColumns) > 0)
            renderColumnsEditor(aiColumns);
            document.getElementById('analysis-summary').textContent = 'Previously analyzed columns loaded from cache.';
            document.getElementById('confidence-badge').textContent = 'Cached';
        @endif
    });
</script>
@endpush
