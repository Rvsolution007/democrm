@extends('admin.layouts.app')

@section('title', 'Template: ' . $template->name)

@push('styles')
<style>
    .tmpl-show-container {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .tmpl-show-container { grid-template-columns: 1fr; }
    }

    .tmpl-detail-panel {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 24px;
    }
    .tmpl-detail-panel h2 {
        font-size: 1.15rem;
        font-weight: 700;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tmpl-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .tmpl-detail-row:last-child { border-bottom: none; }
    .tmpl-detail-label {
        font-size: 0.8rem;
        color: var(--muted-foreground);
        font-weight: 500;
    }
    .tmpl-detail-value {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--foreground);
    }

    .tmpl-body-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        font-size: 0.85rem;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
        margin: 12px 0;
        color: #334155;
    }

    .tmpl-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .tmpl-rejected-box {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 14px;
        margin: 12px 0;
    }
    .tmpl-rejected-box strong { color: #dc2626; }
    .tmpl-rejected-box p { color: #b91c1c; font-size: 0.85rem; margin-top: 4px; }

    .tmpl-buttons-list-show {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin: 8px 0;
    }
    .tmpl-button-show {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        font-size: 0.8rem;
    }

    /* Preview */
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
    .wa-bubble-header { font-weight: 700; color: #111; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #f0f0f0; }
    .wa-bubble-body { white-space: pre-wrap; word-break: break-word; }
    .wa-bubble-footer { color: #8696a0; font-size: 0.75rem; margin-top: 8px; padding-top: 6px; border-top: 1px solid #f0f0f0; }
    .wa-bubble-btn { display: block; text-align: center; padding: 8px; color: #1e90ff; font-weight: 600; font-size: 0.8rem; border-top: 1px solid #f0f0f0; }
    .wa-time { text-align: right; margin-top: 6px; font-size: 0.65rem; color: #8696a0; }

    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="page-content">
    <!-- Header -->
    <div style="margin-bottom:20px;">
        <a href="{{ route('admin.meta-templates.index') }}" style="font-size:0.85rem;color:var(--primary);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back to Templates
        </a>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-top:8px;">
            <h1 style="font-size:1.4rem;font-weight:700;display:flex;align-items:center;gap:10px;">
                <i data-lucide="file-check-2" style="color:#22c55e;width:24px;height:24px;"></i>
                {{ $template->name }}
            </h1>
            <div style="display:flex;gap:8px;">
                @if($template->isDraft())
                    <a href="{{ route('admin.meta-templates.retry', $template->id) }}" class="btn btn-primary btn-sm" onclick="return confirm('Re-submit this template to Meta for review?')">
                        <i data-lucide="send" style="width:14px;height:14px;"></i> Retry Submit
                    </a>
                @endif
                <button class="btn btn-outline btn-sm" onclick="syncThisTemplate()" id="sync-btn">
                    <i data-lucide="refresh-cw" style="width:14px;height:14px;" id="sync-icon"></i>
                    Sync Status
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#166534;font-size:0.85rem;">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:0.85rem;">
            ❌ {{ session('error') }}
        </div>
    @endif

    <div class="tmpl-show-container">
        <!-- Left: Details -->
        <div class="tmpl-detail-panel">
            <h2><i data-lucide="info" style="width:18px;height:18px;color:#3b82f6;"></i> Template Details</h2>

            <!-- Status -->
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Status</span>
                <span class="tmpl-badge" id="status-badge" style="background:{{ $template->status_bg }};color:{{ $template->status_color }};border:1px solid {{ $template->status_color }}20;">
                    @if($template->isApproved()) ✅ @elseif($template->isPending()) ⏳ @elseif($template->isRejected()) ❌ @else 📝 @endif
                    {{ $template->status }}
                </span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Category</span>
                <span class="tmpl-badge" style="background:{{ $template->category_color }}15;color:{{ $template->category_color }};border:1px solid {{ $template->category_color }}30;">
                    {{ $template->category }}
                </span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Language</span>
                <span class="tmpl-detail-value">{{ strtoupper($template->language) }}</span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Variables</span>
                <span class="tmpl-detail-value">{{ $template->variable_count }}</span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Meta Template ID</span>
                <span class="tmpl-detail-value" style="font-size:0.75rem;color:var(--muted-foreground);">{{ $template->meta_template_id ?: 'Not assigned yet' }}</span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Last Synced</span>
                <span class="tmpl-detail-value" style="font-size:0.8rem;">{{ $template->last_synced_at ? $template->last_synced_at->diffForHumans() : 'Never' }}</span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Created</span>
                <span class="tmpl-detail-value" style="font-size:0.8rem;">{{ $template->created_at->format('d M Y, h:i A') }}</span>
            </div>
            <div class="tmpl-detail-row">
                <span class="tmpl-detail-label">Created By</span>
                <span class="tmpl-detail-value" style="font-size:0.8rem;">{{ $template->user->name ?? 'Unknown' }}</span>
            </div>

            @if($template->isRejected() && $template->rejected_reason)
                <div class="tmpl-rejected-box">
                    <strong>❌ Rejection Reason</strong>
                    <p>{{ $template->rejected_reason }}</p>
                </div>
            @endif

            <!-- Header -->
            @if($template->header_type === 'TEXT' && $template->header_text)
                <h3 style="font-size:0.9rem;font-weight:700;margin-top:16px;margin-bottom:6px;">📌 Header</h3>
                <div class="tmpl-body-box" style="background:#eff6ff;border-color:#bfdbfe;">{{ $template->header_text }}</div>
            @endif

            <!-- Body -->
            <h3 style="font-size:0.9rem;font-weight:700;margin-top:16px;margin-bottom:6px;">💬 Body</h3>
            <div class="tmpl-body-box">{!! preg_replace('/\{\{(\d+)\}\}/', '<span style="background:#dcfce7;color:#15803d;padding:1px 4px;border-radius:3px;font-weight:600;">{{$1}}</span>', e($template->body_text)) !!}</div>

            <!-- Footer -->
            @if($template->footer_text)
                <h3 style="font-size:0.9rem;font-weight:700;margin-top:16px;margin-bottom:6px;">📎 Footer</h3>
                <div class="tmpl-body-box" style="background:#f8fafc;color:#64748b;font-size:0.8rem;">{{ $template->footer_text }}</div>
            @endif

            <!-- Buttons -->
            @if(!empty($template->buttons))
                <h3 style="font-size:0.9rem;font-weight:700;margin-top:16px;margin-bottom:6px;">🔘 Buttons</h3>
                <div class="tmpl-buttons-list-show">
                    @foreach($template->buttons as $btn)
                        <div class="tmpl-button-show">
                            @if($btn['type'] === 'URL') 🔗
                            @elseif($btn['type'] === 'PHONE_NUMBER') 📞
                            @else ⚡
                            @endif
                            <strong>{{ $btn['text'] }}</strong>
                            @if(!empty($btn['url'])) <span style="color:var(--muted-foreground);font-size:0.75rem;">→ {{ $btn['url'] }}</span>
                            @elseif(!empty($btn['phone_number'])) <span style="color:var(--muted-foreground);font-size:0.75rem;">→ {{ $btn['phone_number'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Example Values -->
            @if(!empty($template->example_values))
                <h3 style="font-size:0.9rem;font-weight:700;margin-top:16px;margin-bottom:6px;">🧪 Example Values</h3>
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($template->example_values as $idx => $val)
                        <span style="background:#faf5ff;color:#7c3aed;border:1px solid #e9d5ff;padding:3px 10px;border-radius:6px;font-size:0.75rem;font-weight:600;">
                            {{'{{'}}{{ $idx + 1 }}{{'}}'}} = {{ $val }}
                        </span>
                    @endforeach
                </div>
            @endif

            <!-- Delete -->
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--border);text-align:right;">
                <form action="{{ route('admin.meta-templates.destroy', $template->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this template? This will also remove it from Meta.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#fecaca;">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Delete Template
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Preview -->
        <div style="position:sticky;top:20px;">
            <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:20px;">
                <h3 style="font-size:0.9rem;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:6px;">
                    <i data-lucide="smartphone" style="width:16px;height:16px;color:#25D366;"></i> WhatsApp Preview
                </h3>
                <div class="wa-preview">
                    <div class="wa-bubble">
                        @if($template->header_type === 'TEXT' && $template->header_text)
                            <div class="wa-bubble-header">{{ $template->header_text }}</div>
                        @endif
                        <div class="wa-bubble-body">{!! preg_replace('/\{\{(\d+)\}\}/', '<span style="background:#dcfce7;color:#15803d;padding:1px 4px;border-radius:3px;font-weight:600;">{{$1}}</span>', e($template->body_text)) !!}</div>
                        @if($template->footer_text)
                            <div class="wa-bubble-footer">{{ $template->footer_text }}</div>
                        @endif
                        @if(!empty($template->buttons))
                            <div class="wa-bubble-buttons">
                                @foreach($template->buttons as $btn)
                                    <div class="wa-bubble-btn">
                                        @if($btn['type'] === 'URL') 🔗
                                        @elseif($btn['type'] === 'PHONE_NUMBER') 📞
                                        @else ⚡
                                        @endif
                                        {{ $btn['text'] }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="wa-time">{{ $template->created_at->format('h:i A') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function syncThisTemplate() {
        const btn = document.getElementById('sync-btn');
        const icon = document.getElementById('sync-icon');
        btn.disabled = true;
        icon.classList.add('spin');

        fetch('{{ route("admin.meta-templates.sync-one", $template->id) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
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
        .finally(() => {
            btn.disabled = false;
            icon.classList.remove('spin');
        });
    }
</script>
@endpush
