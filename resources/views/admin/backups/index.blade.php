@extends('admin.layouts.app')

@section('title', 'Backup & Restore')
@section('breadcrumb', 'Backup & Restore')

@section('content')
    <style>
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .bk-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            animation: slideInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .bk-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .bk-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bk-card h3 .icon-box {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bk-card p.sub {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 20px 0;
        }

        /* Status pill */
        .bk-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .bk-status.ok {
            background: #dcfce7;
            color: #16a34a;
        }

        .bk-status.warn {
            background: #fef3c7;
            color: #d97706;
        }

        .bk-status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .bk-status.ok .dot {
            background: #16a34a;
        }

        .bk-status.warn .dot {
            background: #d97706;
        }

        /* Buttons */
        .bk-btn {
            padding: 10px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .bk-btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
        }

        .bk-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }

        .bk-btn-outline {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .bk-btn-outline:hover {
            background: #e2e8f0;
        }

        .bk-btn-green {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
        }

        .bk-btn-green:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35);
        }

        .bk-btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Upload zone */
        .bk-upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 32px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: #fafbfc;
            margin-top: 16px;
        }

        .bk-upload-zone:hover,
        .bk-upload-zone.dragover {
            border-color: #4f46e5;
            background: #f0f0ff;
        }

        .bk-upload-zone .upload-icon {
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .bk-upload-zone p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .bk-upload-zone p strong {
            color: #4f46e5;
        }

        /* File list */
        .bk-file-list {
            animation: slideInUp 0.5s 0.2s cubic-bezier(0.22, 1, 0.36, 1) both;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .bk-file-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .bk-file-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bk-file-count {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
        }

        .bk-file-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.2s;
        }

        .bk-file-item:hover {
            background: rgba(79, 70, 229, 0.02);
        }

        .bk-file-item:last-child {
            border-bottom: none;
        }

        .bk-file-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .bk-file-info {
            flex: 1;
            min-width: 0;
        }

        .bk-file-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .bk-file-meta {
            font-size: 11.5px;
            color: #94a3b8;
            margin: 2px 0 0;
        }

        .bk-file-actions {
            flex-shrink: 0;
        }

        .bk-empty {
            padding: 40px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
        }

        @media(max-width:768px) {
            .bk-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title" style="display:flex;align-items:center;gap:10px;">
                    <div
                        style="width:36px;height:36px;background:linear-gradient(135deg,#4f46e5,#6366f1);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="database" style="width:18px;height:18px;color:#fff;"></i>
                    </div>
                    Backup & Restore
                </h1>
                <p class="page-description">Manage your CRM data backups — daily automated + manual + import/restore</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div
            style="background:#dcfce7;color:#16a34a;padding:14px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:600;border:1px solid #bbf7d0;display:flex;align-items:center;gap:8px;">
            <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div
            style="background:#fee2e2;color:#dc2626;padding:14px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:600;border:1px solid #fecaca;display:flex;align-items:center;gap:8px;">
            <i data-lucide="alert-circle" style="width:16px;height:16px;"></i>
            {{ session('error') }}
        </div>
    @endif

    <!-- Top Row: Run Backup + Import -->
    <div class="bk-grid">
        <!-- Run Backup -->
        <div class="bk-card">
            <h3>
                <div class="icon-box" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);color:#4f46e5;">
                    <i data-lucide="hard-drive" style="width:16px;height:16px;"></i>
                </div>
                Run Backup
            </h3>
            <p class="sub">Export all CRM data to a JSON backup file</p>

            <div class="bk-status {{ $lastBackupDate !== 'Never' ? 'ok' : 'warn' }}">
                <span class="dot"></span>
                Last backup: <strong>{{ $lastBackupDate }}</strong>
            </div>
            <br>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i data-lucide="{{ $driveConfigured ? 'check-circle' : 'alert-triangle' }}"
                    style="width:14px;height:14px;color:{{ $driveConfigured ? '#16a34a' : '#d97706' }};"></i>
                <span style="font-size:12px;color:{{ $driveConfigured ? '#16a34a' : '#d97706' }};font-weight:600;">
                    Google Drive: {{ $driveConfigured ? 'Connected' : 'Not configured' }}
                </span>
            </div>

            <div class="bk-btn-group">
                <form method="POST" action="{{ route('admin.backups.run') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="type" value="auto">
                    <button type="submit" class="bk-btn bk-btn-primary">
                        <i data-lucide="play" style="width:14px;height:14px;"></i> Smart Backup
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.backups.run') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="type" value="full">
                    <button type="submit" class="bk-btn bk-btn-outline">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Full Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Restore Full Database -->
        <div class="bk-card">
            <h3>
                <div class="icon-box" style="background:linear-gradient(135deg,#fef08a,#fde047);color:#ca8a04;">
                    <i data-lucide="database-zap" style="width:16px;height:16px;"></i>
                </div>
                Restore Full Database
            </h3>
            <p class="sub">Upload a Spatie .zip backup file to restore the entire database instantly.</p>

            <form method="POST" action="{{ route('admin.backups.restore') }}" enctype="multipart/form-data" id="restore-form">
                @csrf
                <div class="bk-upload-zone" id="restore-upload-zone" onclick="document.getElementById('restore-file').click();">
                    <i data-lucide="upload-cloud" class="upload-icon" style="width:36px;height:36px;"></i>
                    <p><strong>Click to browse</strong> for a .zip backup file</p>
                    <p style="margin-top:4px;font-size:11px;color:#94a3b8;">.zip or .sql only • Replaces all data</p>
                </div>
                <input type="file" name="backup_file" id="restore-file" accept=".zip,.sql" style="display:none;"
                    onchange="document.getElementById('restore-btn').style.display = this.files.length ? 'inline-flex' : 'none';">

                <div style="margin-top:14px;">
                    <button type="submit" class="bk-btn bk-btn-green" style="display:none;background:linear-gradient(135deg,#ca8a04,#a16207);box-shadow:0 4px 14px rgba(202,138,4,0.3);" id="restore-btn" onclick="return confirm('WARNING: This will overwrite your entire database with the backup data. Are you sure you want to proceed?');">
                        <i data-lucide="alert-triangle" style="width:14px;height:14px;"></i> Restore Now
                    </button>
                </div>
            </form>
        </div>

        <!-- Import Partial Backup -->
        <div class="bk-card">
            <h3>
                <div class="icon-box" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#16a34a;">
                    <i data-lucide="upload" style="width:16px;height:16px;"></i>
                </div>
                Partial JSON Import
            </h3>
            <p class="sub">Upload JSON partial backup files to merge specific module data</p>

            <form method="POST" action="{{ route('admin.backups.import') }}" enctype="multipart/form-data" id="import-form">
                @csrf
                <div class="bk-upload-zone" id="upload-zone" onclick="document.getElementById('backup-files').click();">
                    <i data-lucide="upload-cloud" class="upload-icon" style="width:36px;height:36px;"></i>
                    <p><strong>Click to browse</strong> or drag & drop backup files here</p>
                    <p style="margin-top:4px;font-size:11px;color:#94a3b8;">JSON files only • Max 50MB each</p>
                </div>
                <input type="file" name="backup_files[]" id="backup-files" multiple accept=".json" style="display:none;"
                    onchange="showSelectedFiles(this)">

                <div id="selected-files" style="margin-top:12px;"></div>

                <div style="margin-top:14px;">
                    <button type="submit" class="bk-btn bk-btn-green" style="display:none;" id="import-btn">
                        <i data-lucide="database" style="width:14px;height:14px;"></i> Import Files
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Backup History -->
    <div class="bk-file-list">
        <div class="bk-file-header">
            <h3>
                <span
                    style="width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#6366f1);display:inline-block;"></span>
                Backup History
                <span class="bk-file-count">{{ count($backupFiles) }} Files</span>
            </h3>
        </div>
        @if(empty($backupFiles))
            <div class="bk-empty">
                <i data-lucide="inbox" style="width:36px;height:36px;display:block;margin:0 auto 8px;opacity:0.4;"></i>
                No backup files yet — run your first backup above.
            </div>
        @else
            @foreach($backupFiles as $file)
                <div class="bk-file-item">
                    <div class="bk-file-icon">
                        <i data-lucide="file-json" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="bk-file-info">
                        <p class="bk-file-name">{{ $file['name'] }}</p>
                        <p class="bk-file-meta">{{ $file['size'] }} KB • {{ $file['date'] }}</p>
                    </div>
                    <div class="bk-file-actions">
                        <a href="{{ route('admin.backups.download', $file['name']) }}" class="bk-btn bk-btn-outline"
                            style="padding:6px 14px;font-size:12px;">
                            <i data-lucide="download" style="width:13px;height:13px;"></i> Download
                        </a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Drag & drop
        const dropZone = document.getElementById('upload-zone');
        if (dropZone) {
            dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
            dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
            dropZone.addEventListener('drop', e => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                const input = document.getElementById('backup-files');
                input.files = e.dataTransfer.files;
                showSelectedFiles(input);
            });
        }

        function showSelectedFiles(input) {
            const container = document.getElementById('selected-files');
            const importBtn = document.getElementById('import-btn');
            if (input.files.length === 0) {
                container.innerHTML = '';
                importBtn.style.display = 'none';
                return;
            }
            let html = '<div style="display:flex;flex-wrap:wrap;gap:8px;">';
            for (let f of input.files) {
                html += `<span style="background:#e0e7ff;color:#4f46e5;padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600;">${f.name}</span>`;
            }
            html += '</div>';
            container.innerHTML = html;
            importBtn.style.display = 'inline-flex';
        }
    </script>
@endpush