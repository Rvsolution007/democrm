@extends('admin.layouts.app')

@push('styles')
<style>
    .page-header-modern {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;
        background: #ffffff; padding: 1rem 1.5rem; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    }
    .page-title-modern {
        font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0;
    }
    .btn-create-modern {
        background: #0f172a; color: white; border: none;
        padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem;
        display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; transition: background 0.2s;
    }
    .btn-create-modern:hover { background: #1e293b; color: white; }

    .connection-banner {
        padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;
        display: flex; align-items: center; gap: 0.75rem; font-weight: 500; font-size: 0.9rem;
    }
    .connection-connected { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .connection-disconnected { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }

    .stat-card {
        background: white; border-radius: 8px; padding: 1rem;
        border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem;
    }
    .stat-card .stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1; }
    .stat-card .stat-label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .rules-container {
        background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;
    }
    
    .rule-list-item {
        padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; transition: background 0.15s;
    }
    .rule-list-item:last-child { border-bottom: none; }
    .rule-list-item:hover { background: #f8fafc; }

    .rule-main { display: flex; align-items: center; gap: 1rem; flex: 1; }
    .rule-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .rule-status-dot.active { background: #22c55e; box-shadow: 0 0 0 3px #dcfce7; }
    .rule-status-dot.paused { background: #94a3b8; }
    
    .rule-info { flex: 1; display: flex; flex-direction: column; gap: 0.2rem; }
    .rule-title-row { display: flex; align-items: center; gap: 0.75rem; }
    .rule-name { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
    
    .rule-meta-row { display: flex; align-items: center; gap: 1.25rem; font-size: 0.75rem; color: #64748b; flex-wrap: wrap; }
    .meta-item { display: inline-flex; align-items: center; gap: 0.3rem; }
    .meta-label { font-weight: 600; color: #94a3b8; text-transform: uppercase; font-size: 0.65rem; letter-spacing: 0.5px; }
    
    .match-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .match-exact { background: #eff6ff; color: #3b82f6; }
    .match-contains { background: #fdf4ff; color: #d946ef; }
    .match-any_message { background: #f0fdf4; color: #16a34a; }
    .match-first_message { background: #fff7ed; color: #ea580c; }

    .rule-actions { display: flex; align-items: center; gap: 1rem; }
    
    .toggle-switch { position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #cbd5e1; border-radius: 20px; transition: 0.2s; }
    .toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background: white; border-radius: 50%; transition: 0.2s; }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(16px); }

    .action-group { display: flex; align-items: center; gap: 0.25rem; }
    .action-btn {
        width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center;
        border: none; background: transparent; color: #64748b; cursor: pointer; transition: all 0.15s;
    }
    .btn-edit:hover { background: #eff6ff; color: #3b82f6; }
    .btn-dup:hover { background: #f5f3ff; color: #8b5cf6; }
    .btn-del:hover { background: #fef2f2; color: #ef4444; }

    .btn-pause-all {
        background: white; color: #dc2626; border: 1px solid #fecaca;
        padding: 0.4rem 0.8rem; border-radius: 6px; font-weight: 600; font-size: 0.8rem;
        display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer; transition: background 0.15s;
    }
    .btn-pause-all:hover { background: #fef2f2; }

    .filter-tabs { display: flex; gap: 0.4rem; margin-bottom: 1rem; }
    .filter-tab {
        padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600;
        cursor: pointer; border: 1px solid #e2e8f0; background: white; color: #64748b; transition: all 0.1s;
    }
    .filter-tab.active { background: #0f172a; color: white; border-color: #0f172a; }
    .filter-tab:hover:not(.active) { background: #f8fafc; }
    
    .empty-state { text-align: center; padding: 3rem 1rem; }
</style>
@endpush

@section('title', 'WhatsApp Auto-Reply Rules')

@section('content')
<div class="container-fluid" style="padding: 1.5rem;">
    <!-- Header -->
    <div class="page-header-modern">
        <div>
            <h2 class="page-title-modern">⚡ Auto-Reply Rules</h2>
            <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Automatically reply to incoming WhatsApp messages</p>
        </div>
        <a href="{{ route('admin.whatsapp-auto-reply.create') }}" class="btn-create-modern">
            <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
            <span>New Rule</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: #ecfdf5; color: #047857; padding: 1rem 1.5rem;">
            <div class="d-flex align-items-center">
                <i data-lucide="check-circle" style="width: 20px; height: 20px; margin-right: 12px;"></i>
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; background: #fef2f2; color: #dc2626; padding: 1rem 1.5rem;">
            <div class="d-flex align-items-center">
                <i data-lucide="alert-circle" style="width: 20px; height: 20px; margin-right: 12px;"></i>
                {{ session('error') }}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Connection Status Banner -->
    @if($connectionStatus['connected'])
        <div class="connection-banner connection-connected">
            <i data-lucide="wifi" style="width: 22px; height: 22px;"></i>
            <div>
                <strong>WhatsApp Connected</strong> — Instance: <code style="background: rgba(0,0,0,0.08); padding: 2px 8px; border-radius: 6px;">{{ $instanceName }}</code>
                <span style="opacity: 0.7; margin-left: 8px;">Auto-reply rules are active</span>
            </div>
        </div>
    @else
        <div class="connection-banner connection-disconnected">
            <i data-lucide="wifi-off" style="width: 22px; height: 22px;"></i>
            <div style="flex:1;">
                <strong>WhatsApp Not Connected!</strong> — Connect your WhatsApp first to use auto-reply.
            </div>
            <a href="{{ route('admin.whatsapp-connect.index') }}" style="background: #dc2626; color: white; padding: 0.5rem 1rem; border-radius: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">
                Connect WhatsApp →
            </a>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color: #22c55e;">{{ $todayStats['active'] }}</div>
                <div class="stat-label">Active Rules</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color: #3b82f6;">{{ $todayStats['total_sent_today'] }}</div>
                <div class="stat-label">Replies Today</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color: #94a3b8;">{{ $todayStats['paused'] }}</div>
                <div class="stat-label">Paused Rules</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <div class="filter-tab active" onclick="filterRules('all')">🟢 All ({{ $rules->count() }})</div>
        <div class="filter-tab" onclick="filterRules('active')">⚡ Active ({{ $rules->where('is_active', true)->count() }})</div>
        <div class="filter-tab" onclick="filterRules('paused')">⏸️ Paused ({{ $rules->where('is_active', false)->count() }})</div>
    </div>

    <!-- Rules List -->
    <div class="rules-container">
        @forelse($rules as $rule)
            <div class="rule-list-item rule-card" data-status="{{ $rule->is_active ? 'active' : 'paused' }}">
                <div class="rule-main">
                    <div class="rule-status-dot {{ $rule->is_active ? 'active' : 'paused' }}" title="{{ $rule->is_active ? 'Active' : 'Paused' }}"></div>
                    
                    <div class="rule-info">
                        <div class="rule-title-row">
                            <span class="rule-name">{{ $rule->name }}</span>
                            <span class="match-badge match-{{ $rule->match_type }}">{{ str_replace('_', ' ', $rule->match_type) }}</span>
                        </div>
                        
                        <div class="rule-meta-row">
                            <span class="meta-item"><span class="meta-label">TPL:</span> <strong style="color:#0f172a">{{ $rule->template->name ?? 'None' }}</strong></span>
                            @if($rule->keywords && count($rule->keywords) > 0)
                                <span class="meta-item"><span class="meta-label">KWD:</span> <strong style="color:#0f172a">{{ implode(', ', $rule->keywords) }}</strong></span>
                            @endif
                            <span class="meta-item"><i data-lucide="{{ $rule->is_one_time ? 'lock' : 'repeat' }}" style="width:12px; height:12px;"></i> {{ $rule->is_one_time ? 'One-time' : 'Repeat' }}</span>
                            <span class="meta-item"><span class="meta-label">Delay:</span> {{ $rule->reply_delay_seconds }}s</span>
                            @if($rule->cooldown_hours > 0)
                                <span class="meta-item"><span class="meta-label">Cooldown:</span> {{ $rule->cooldown_hours }}h</span>
                            @endif
                            @if($rule->business_hours_only)
                                <span class="meta-item"><i data-lucide="clock" style="width:12px; height:12px;"></i> {{ \Carbon\Carbon::parse($rule->business_hours_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($rule->business_hours_end)->format('H:i') }}</span>
                            @endif
                            <span class="meta-item" style="color:#16a34a; font-weight:600;"><i data-lucide="send" style="width:12px; height:12px;"></i> {{ $rule->total_sent }} sent</span>
                            @if($rule->total_skipped > 0)
                                <span class="meta-item" style="color:#f59e0b; font-weight:600;"><i data-lucide="skip-forward" style="width:12px; height:12px;"></i> {{ $rule->total_skipped }} skipped</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="rule-actions">
                    <label class="toggle-switch">
                        <input type="checkbox" {{ $rule->is_active ? 'checked' : '' }} onchange="toggleRule({{ $rule->id }})">
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="action-group">
                        <a href="{{ route('admin.whatsapp-auto-reply.edit', $rule->id) }}" class="action-btn btn-edit" title="Edit">
                            <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                        </a>
                        <form action="{{ route('admin.whatsapp-auto-reply.duplicate', $rule->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="action-btn btn-dup" title="Duplicate">
                                <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.whatsapp-auto-reply.destroy', $rule->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this rule?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="action-btn btn-del" title="Delete">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i data-lucide="message-square-dashed" style="width: 48px; height: 48px; color: #94a3b8; margin-bottom: 1rem;"></i>
                <h5 style="color: #334155; font-weight: 600; margin-bottom: 0.25rem;">No rules found</h5>
                <p style="color: #64748b; font-size: 0.9rem;">Auto-reply keeps your business running 24/7 without delays.</p>
                <a href="{{ route('admin.whatsapp-auto-reply.create') }}" class="btn-create-modern mt-2" style="padding: 0.5rem 1rem;">
                    Create Rule
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pause All Button -->
    @if($rules->where('is_active', true)->count() > 0)
        <div style="text-align: center; margin-top: 1.5rem;">
            <button class="btn-pause-all" onclick="pauseAll()">
                <i data-lucide="pause-circle" style="width: 18px; height: 18px;"></i>
                Pause All Auto-Replies
            </button>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleRule(id) {
        fetch(`{{ url('admin/whatsapp-auto-reply') }}/${id}/toggle`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }

    function pauseAll() {
        if (!confirm('Pause ALL active auto-reply rules?')) return;
        fetch('{{ route("admin.whatsapp-auto-reply.pause-all") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }

    function filterRules(filter) {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');

        document.querySelectorAll('.rule-card[data-status]').forEach(card => {
            if (filter === 'all') {
                card.style.display = '';
            } else {
                card.style.display = card.dataset.status === filter ? '' : 'none';
            }
        });
    }
</script>
@endpush
