@extends('admin.layouts.app')

@section('title', 'Meta WhatsApp Templates')

@push('styles')
<style>
    .meta-tmpl-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 24px;
    }
    .meta-tmpl-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--foreground);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .meta-tmpl-actions {
        display: flex;
        gap: 10px;
    }

    /* Stats Cards */
    .tmpl-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .tmpl-stat-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .tmpl-stat-count {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1;
    }
    .tmpl-stat-label {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-top: 4px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Info Note */
    .tmpl-info-note {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #93c5fd;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 0.85rem;
        color: #1e40af;
        line-height: 1.5;
    }
    .tmpl-info-note i { flex-shrink: 0; margin-top: 2px; }

    /* Filter Tabs */
    .tmpl-filter-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .tmpl-filter-tab {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid var(--border);
        background: var(--card-bg);
        color: var(--muted-foreground);
        cursor: pointer;
        transition: all 0.2s;
    }
    .tmpl-filter-tab.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .tmpl-filter-tab:hover:not(.active) {
        border-color: var(--primary);
        color: var(--primary);
    }

    /* Template Cards */
    .tmpl-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 16px;
    }
    .tmpl-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 20px;
        transition: all 0.25s ease;
        position: relative;
    }
    .tmpl-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    .tmpl-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .tmpl-card-name {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--foreground);
        word-break: break-word;
    }
    .tmpl-card-meta {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .tmpl-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .tmpl-card-body {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 0.8rem;
        color: #475569;
        line-height: 1.5;
        margin-bottom: 12px;
        max-height: 80px;
        overflow: hidden;
        position: relative;
    }
    .tmpl-card-body::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 24px;
        background: linear-gradient(transparent, #f8fafc);
    }
    .tmpl-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 10px;
        border-top: 1px solid #f0f0f0;
    }
    .tmpl-card-time {
        font-size: 0.7rem;
        color: var(--muted-foreground);
    }
    .tmpl-card-actions {
        display: flex;
        gap: 6px;
    }
    .tmpl-card-actions .btn {
        padding: 4px 10px;
        font-size: 0.7rem;
    }

    /* Rejected Reason */
    .tmpl-rejected-reason {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 0.75rem;
        color: #dc2626;
        margin-bottom: 10px;
    }

    /* Empty State */
    .tmpl-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--muted-foreground);
    }
    .tmpl-empty i { margin-bottom: 12px; opacity: 0.3; }
    .tmpl-empty h3 { font-size: 1.1rem; margin-bottom: 6px; color: var(--foreground); }
    .tmpl-empty p { font-size: 0.85rem; margin-bottom: 16px; }

    /* Sync animation */
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="page-content">
    <!-- Header -->
    <div class="meta-tmpl-header">
        <h1>
            <i data-lucide="file-check-2" style="color:#22c55e;width:28px;height:28px;"></i>
            Meta WhatsApp Templates
        </h1>
        <div class="meta-tmpl-actions">
            <button class="btn btn-outline btn-sm" onclick="syncAllTemplates()" id="sync-all-btn">
                <i data-lucide="refresh-cw" style="width:14px;height:14px;" id="sync-icon"></i>
                Sync All
            </button>
            <a href="{{ route('admin.meta-templates.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" style="width:14px;height:14px;"></i>
                Create Template
            </a>
        </div>
    </div>

    <!-- Info Note -->
    <div class="tmpl-info-note">
        <i data-lucide="info" style="width:20px;height:20px;"></i>
        <div>
            <strong>Meta Templates sirf tab chahiye jab aap customer ko pehla message bhejna chahte ho (outbound).</strong><br>
            Agar customer pehle message kare aur aap reply karte ho (24-hour session window), to koi template ki zaroorat nahi — free text bhej sakte ho. Ye templates bulk sending aur auto-reply ke liye hain. AI Bot replies ke liye template ki zaroorat nahi hai.
        </div>
    </div>

    <!-- Stats -->
    <div class="tmpl-stats">
        <div class="tmpl-stat-card">
            <div class="tmpl-stat-count" style="color:#3b82f6;">{{ $stats['total'] }}</div>
            <div class="tmpl-stat-label">Total</div>
        </div>
        <div class="tmpl-stat-card" style="border-color:#bbf7d0;">
            <div class="tmpl-stat-count" style="color:#22c55e;">{{ $stats['approved'] }}</div>
            <div class="tmpl-stat-label">Approved</div>
        </div>
        <div class="tmpl-stat-card" style="border-color:#fde68a;">
            <div class="tmpl-stat-count" style="color:#f59e0b;">{{ $stats['pending'] }}</div>
            <div class="tmpl-stat-label">Pending</div>
        </div>
        <div class="tmpl-stat-card" style="border-color:#fecaca;">
            <div class="tmpl-stat-count" style="color:#ef4444;">{{ $stats['rejected'] }}</div>
            <div class="tmpl-stat-label">Rejected</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="tmpl-filter-tabs">
        <button class="tmpl-filter-tab active" data-filter="all" onclick="filterTemplates('all', this)">All ({{ $stats['total'] }})</button>
        <button class="tmpl-filter-tab" data-filter="APPROVED" onclick="filterTemplates('APPROVED', this)">✅ Approved ({{ $stats['approved'] }})</button>
        <button class="tmpl-filter-tab" data-filter="PENDING" onclick="filterTemplates('PENDING', this)">⏳ Pending ({{ $stats['pending'] }})</button>
        <button class="tmpl-filter-tab" data-filter="REJECTED" onclick="filterTemplates('REJECTED', this)">❌ Rejected ({{ $stats['rejected'] }})</button>
    </div>

    <!-- Template Grid -->
    @if($templates->count() > 0)
        <div class="tmpl-grid" id="templates-grid">
            @foreach($templates as $tmpl)
                <div class="tmpl-card" data-status="{{ $tmpl->status }}" id="tmpl-card-{{ $tmpl->id }}">
                    <div class="tmpl-card-header">
                        <div class="tmpl-card-name">{{ $tmpl->name }}</div>
                        <span class="tmpl-badge" style="background:{{ $tmpl->status_bg }};color:{{ $tmpl->status_color }};border:1px solid {{ $tmpl->status_color }}20;">
                            @if($tmpl->isApproved()) ✅ @elseif($tmpl->isPending()) ⏳ @elseif($tmpl->isRejected()) ❌ @else 📝 @endif
                            {{ $tmpl->status }}
                        </span>
                    </div>

                    <div class="tmpl-card-meta">
                        <span class="tmpl-badge" style="background:{{ $tmpl->category_color }}15;color:{{ $tmpl->category_color }};border:1px solid {{ $tmpl->category_color }}30;">
                            {{ $tmpl->category }}
                        </span>
                        <span class="tmpl-badge" style="background:#f0f9ff;color:#0284c7;border:1px solid #bae6fd;">
                            🌐 {{ strtoupper($tmpl->language) }}
                        </span>
                        @if($tmpl->variable_count > 0)
                        <span class="tmpl-badge" style="background:#faf5ff;color:#7c3aed;border:1px solid #e9d5ff;">
                            {{ $tmpl->variable_count }} vars
                        </span>
                        @endif
                    </div>

                    <!-- Body Preview -->
                    <div class="tmpl-card-body">{{ $tmpl->body_text }}</div>

                    @if($tmpl->isRejected() && $tmpl->rejected_reason)
                        <div class="tmpl-rejected-reason">
                            ❌ <strong>Rejection Reason:</strong> {{ $tmpl->rejected_reason }}
                        </div>
                    @endif

                    <div class="tmpl-card-footer">
                        <div class="tmpl-card-time">
                            @if($tmpl->last_synced_at)
                                Synced {{ $tmpl->last_synced_at->diffForHumans() }}
                            @else
                                Created {{ $tmpl->created_at->diffForHumans() }}
                            @endif
                        </div>
                        <div class="tmpl-card-actions">
                            <a href="{{ route('admin.meta-templates.show', $tmpl->id) }}" class="btn btn-outline btn-sm" title="View">
                                <i data-lucide="eye" style="width:12px;height:12px;"></i>
                            </a>
                            <button class="btn btn-outline btn-sm" onclick="syncOneTemplate({{ $tmpl->id }})" title="Sync Status">
                                <i data-lucide="refresh-cw" style="width:12px;height:12px;" id="sync-icon-{{ $tmpl->id }}"></i>
                            </button>
                            <button class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca;" onclick="deleteTemplate({{ $tmpl->id }}, '{{ $tmpl->name }}')" title="Delete">
                                <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="tmpl-empty">
            <i data-lucide="file-plus-2" style="width:48px;height:48px;"></i>
            <h3>No Templates Yet</h3>
            <p>Create your first Meta WhatsApp template to start sending outbound messages.</p>
            <a href="{{ route('admin.meta-templates.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus" style="width:14px;height:14px;"></i>
                Create Your First Template
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const CSRF = '{{ csrf_token() }}';

    function filterTemplates(status, btn) {
        document.querySelectorAll('.tmpl-filter-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.tmpl-card').forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function syncAllTemplates() {
        const btn = document.getElementById('sync-all-btn');
        const icon = document.getElementById('sync-icon');
        btn.disabled = true;
        icon.classList.add('spin');

        fetch('{{ route("admin.meta-templates.sync") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Sync complete');
                location.reload();
            } else {
                alert('Sync failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(() => alert('Request failed'))
        .finally(() => {
            btn.disabled = false;
            icon.classList.remove('spin');
        });
    }

    function syncOneTemplate(id) {
        const icon = document.getElementById('sync-icon-' + id);
        icon.classList.add('spin');

        fetch(`/admin/meta-templates/${id}/sync`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Sync failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(() => alert('Request failed'))
        .finally(() => icon.classList.remove('spin'));
    }

    function deleteTemplate(id, name) {
        if (!confirm(`Are you sure you want to delete template "${name}"? This will also delete it from Meta.`)) return;

        fetch(`/admin/meta-templates/${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('tmpl-card-' + id)?.remove();
            } else {
                alert('Delete failed');
            }
        })
        .catch(() => alert('Request failed'));
    }
</script>
@endpush
