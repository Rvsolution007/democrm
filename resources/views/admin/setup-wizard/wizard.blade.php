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

                {{-- Column Preview Table --}}
                <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:24px;max-height:400px;overflow-y:auto">
                    <table class="column-preview-table" id="columns-table">
                        <thead>
                            <tr>
                                <th style="width:30px">#</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Flags</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody id="columns-tbody">
                            {{-- Populated via JS --}}
                        </tbody>
                    </table>
                </div>

                {{-- Action Buttons --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
                    <button type="button" class="wizard-btn wizard-btn-outline" onclick="downloadColumnsExcel()">
                        <i data-lucide="download" style="width:16px;height:16px"></i>
                        Download Columns Excel
                    </button>
                    <button type="button" class="wizard-btn wizard-btn-primary" id="import-columns-btn" onclick="importColumns()">
                        <i data-lucide="upload" style="width:16px;height:16px"></i>
                        <span id="import-columns-text">Import Columns to System</span>
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
                    AI will now read your catalogue and extract all product data into the columns you just imported.
                </p>

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

                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
                        <button type="button" class="wizard-btn wizard-btn-outline" onclick="downloadProductsExcel()">
                            <i data-lucide="download" style="width:16px;height:16px"></i>
                            Download Products Excel
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="wizard-btn wizard-btn-outline" target="_blank">
                            <i data-lucide="external-link" style="width:16px;height:16px"></i>
                            Go to Product Import
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

    // ═══════════ STEP 1: ANALYZE ═══════════
    function analyzeCatalogue() {
        var btn = document.getElementById('analyze-btn');
        var textEl = document.getElementById('analyze-btn-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> AI is analyzing your catalogue...';

        var formData = new FormData();
        formData.append('source_type', sourceType);
        if (sourceType === 'pdf') {
            formData.append('catalogue_pdf', selectedFile);
        } else {
            formData.append('website_url', document.getElementById('website-url').value.trim());
        }

        fetch('{{ route("admin.setup-wizard.analyze") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: formData
        })
        .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
        .then(function(res) {
            if (res.ok && res.data.success) {
                aiColumns = res.data.columns;
                showAlert('success', res.data.message);
                renderColumnsTable(aiColumns);
                document.getElementById('analysis-summary').textContent = res.data.source_summary;
                document.getElementById('confidence-badge').textContent = res.data.confidence + '% confidence';
                setTimeout(function() { goToStep(2); }, 800);
            } else {
                showAlert('error', res.data.message || 'Analysis failed.');
                btn.disabled = false;
                textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Analysis';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        })
        .catch(function(err) {
            showAlert('error', 'Network error. Please check your connection and try again.');
            btn.disabled = false;
            textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Analysis';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }

    // ═══════════ COLUMN TABLE RENDERING ═══════════
    function renderColumnsTable(columns) {
        var tbody = document.getElementById('columns-tbody');
        tbody.innerHTML = '';
        columns.forEach(function(col, i) {
            var flags = [];
            if (col.is_category) flags.push('<span style="display:inline-block;padding:2px 8px;background:#dbeafe;color:#1d4ed8;border-radius:4px;font-size:11px;font-weight:600">📂 Category</span>');
            if (col.is_unique) flags.push('<span style="display:inline-block;padding:2px 8px;background:#d1fae5;color:#065f46;border-radius:4px;font-size:11px;font-weight:600">🔑 Unique</span>');
            if (col.is_title) flags.push('<span style="display:inline-block;padding:2px 8px;background:#ede9fe;color:#5b21b6;border-radius:4px;font-size:11px;font-weight:600">🏷️ Title</span>');
            if (col.is_combo) flags.push('<span style="display:inline-block;padding:2px 8px;background:#fef3c7;color:#92400e;border-radius:4px;font-size:11px;font-weight:600">🔀 Combo</span>');
            if (col.is_required) flags.push('<span style="display:inline-block;padding:2px 8px;background:#fee2e2;color:#991b1b;border-radius:4px;font-size:11px;font-weight:600">Required</span>');

            var optStr = (col.options && col.options.length > 0) ? col.options.slice(0, 5).join(', ') + (col.options.length > 5 ? '...' : '') : '—';

            var tr = document.createElement('tr');
            tr.innerHTML = '<td style="color:#94a3b8;font-weight:600">' + (i + 1) + '</td>' +
                '<td style="font-weight:600;color:#1e293b">' + col.name + '</td>' +
                '<td><span style="padding:3px 10px;background:#f1f5f9;border-radius:6px;font-size:12px;font-weight:500">' + col.type + '</span></td>' +
                '<td style="display:flex;gap:4px;flex-wrap:wrap">' + (flags.length > 0 ? flags.join(' ') : '<span style="color:#cbd5e1">—</span>') + '</td>' +
                '<td style="font-size:12px;color:#64748b;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + optStr + '</td>';
            tbody.appendChild(tr);
        });
    }

    // ═══════════ STEP 2: DOWNLOAD & IMPORT ═══════════
    function downloadColumnsExcel() {
        window.location.href = '{{ route("admin.setup-wizard.download-columns") }}';
    }

    function importColumns() {
        var btn = document.getElementById('import-columns-btn');
        var textEl = document.getElementById('import-columns-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> Creating columns...';

        fetch('{{ route("admin.setup-wizard.import-columns") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ import_type: 'direct' })
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
    function extractProducts() {
        var btn = document.getElementById('extract-btn');
        var textEl = document.getElementById('extract-btn-text');
        btn.disabled = true;
        textEl.innerHTML = '<span class="wizard-spinner"></span> AI is extracting product data...';

        fetch('{{ route("admin.setup-wizard.extract-products") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('success', data.message);
                document.getElementById('products-preview').style.display = 'block';
                document.getElementById('products-count').textContent = '(' + data.total + ' products found)';
                btn.style.display = 'none';
                renderProductsTable(data.products);
            } else {
                showAlert('error', data.message || 'Extraction failed.');
                btn.disabled = false;
                textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Extraction';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        })
        .catch(function() {
            showAlert('error', 'Request failed. Please try again.');
            btn.disabled = false;
            textEl.innerHTML = '<i data-lucide="sparkles" style="width:18px;height:18px"></i> Retry Extraction';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }

    function renderProductsTable(products) {
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
                if (val.length > 40) val = val.substring(0, 40) + '...';
                return '<td>' + val + '</td>';
            }).join('');
            tbody.appendChild(tr);
        });
    }

    function downloadProductsExcel() {
        window.location.href = '{{ route("admin.setup-wizard.download-products") }}';
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
            renderColumnsTable(aiColumns);
            document.getElementById('analysis-summary').textContent = 'Previously analyzed columns loaded from cache.';
            document.getElementById('confidence-badge').textContent = 'Cached';
        @endif
    });
</script>
@endpush
