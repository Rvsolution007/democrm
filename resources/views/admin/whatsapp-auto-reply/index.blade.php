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
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; letter-spacing: -0.5px;
    }
    .btn-create-modern {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none;
        padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 0.5rem;
        box-shadow: 0 4px 15px -3px rgba(245, 158, 11, 0.4); transition: all 0.3s ease; text-decoration: none;
    }
    .btn-create-modern:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -5px rgba(245, 158, 11, 0.5); color: white; }

    .connection-banner {
        padding: 1rem 1.5rem; border-radius: 14px; margin-bottom: 1.5rem;
        display: flex; align-items: center; gap: 1rem; font-weight: 500;
    }
    .connection-connected {
        background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        border: 1px solid #a7f3d0; color: #047857;
    }
    .connection-disconnected {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 1px solid #fecaca; color: #dc2626;
    }

    .stat-card {
        background: white; border-radius: 14px; padding: 1.25rem 1.5rem;
        border: 1px solid #e2e8f0; text-align: center;
        box-shadow: 0 2px 10px -5px rgba(0,0,0,0.05);
    }
    .stat-card .stat-value { font-size: 2rem; font-weight: 800; color: #0f172a; }
    .stat-card .stat-label { font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

    .rule-card {
        background: white; border-radius: 16px; padding: 1.25rem 1.5rem;
        border: 1px solid #e2e8f0; margin-bottom: 1rem;
        transition: all 0.2s ease; box-shadow: 0 2px 10px -5px rgba(0,0,0,0.03);
    }
    .rule-card:hover { box-shadow: 0 8px 30px -10px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .rule-card .rule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
    .rule-card .rule-name { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
    .rule-card .rule-meta { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
    .rule-card .rule-meta span { font-size: 0.8rem; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }

    .match-badge { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .match-exact { background: #eff6ff; color: #3b82f6; border: 1px solid #dbeafe; }
    .match-contains { background: #fdf4ff; color: #d946ef; border: 1px solid #fae8ff; }
    .match-any_message { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
    .match-first_message { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }

    .toggle-switch { position: relative; display: inline-block; width: 48px; height: 26px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #cbd5e1; border-radius: 26px; transition: 0.3s; }
    .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s; }
    .toggle-switch input:checked + .toggle-slider { background: #22c55e; }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(22px); }

    .action-btn {
        width: 34px; height: 34px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        border: none; transition: all 0.2s ease; cursor: pointer; background: #f1f5f9; color: #475569;
    }
    .action-btn:hover { transform: translateY(-2px); }
    .btn-edit { color: #3b82f6; }
    .btn-edit:hover { background: #3b82f6; color: white; }
    .btn-dup { color: #8b5cf6; }
    .btn-dup:hover { background: #8b5cf6; color: white; }
    .btn-del { color: #ef4444; }
    .btn-del:hover { background: #ef4444; color: white; }

    .btn-pause-all {
        background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;
        padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.85rem;
        display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.2s;
    }
    .btn-pause-all:hover { background: #dc2626; color: white; }

    .empty-state { text-align: center; padding: 4rem 2rem; }
    .empty-state-icon {
        width: 80px; height: 80px; background: #fef9c3; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 1.5rem; color: #f59e0b;
    }

    .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
    .filter-tab {
        padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.85rem; font-weight: 600;
        cursor: pointer; border: 1px solid #e2e8f0; background: white; color: #64748b; transition: all 0.2s;
    }
    .filter-tab.active { background: #0f172a; color: white; border-color: #0f172a; }
    .filter-tab:hover { background: #f1f5f9; }
    .filter-tab.active:hover { background: #1e293b; }
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
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color: #22c55e;">{{ $todayStats['active'] }}</div>
                <div class="stat-label">✅ Active Rules</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color: #3b82f6;">{{ $todayStats['total_sent_today'] }}</div>
                <div class="stat-label">📊 Replies Today</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value" style="color: #94a3b8;">{{ $todayStats['paused'] }}</div>
                <div class="stat-label">⏸️ Paused</div>
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
    @forelse($rules as $rule)
        <div class="rule-card" data-status="{{ $rule->is_active ? 'active' : 'paused' }}">
            <div class="rule-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: {{ $rule->is_active ? '#22c55e' : '#ef4444' }};"></span>
                    <span class="rule-name">{{ $rule->name }}</span>
                    <span class="match-badge match-{{ $rule->match_type }}">
                        {{ str_replace('_', ' ', $rule->match_type) }}
                    </span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" {{ $rule->is_active ? 'checked' : '' }} onchange="toggleRule({{ $rule->id }})">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="rule-meta">
                <span>📝 Template: <strong>{{ $rule->template->name ?? 'Not Set' }}</strong></span>
                @if($rule->keywords && count($rule->keywords) > 0)
                    <span>🔑 Keywords: <strong>{{ implode(', ', $rule->keywords) }}</strong></span>
                @endif
                <span>{{ $rule->is_one_time ? '🔒 One-time' : '🔄 Repeat' }}</span>
                <span>⏱️ Cooldown: {{ $rule->cooldown_hours }}h</span>
                <span>⏳ Delay: {{ $rule->reply_delay_seconds }}s</span>
                <span>📊 Priority: {{ $rule->priority }}/10</span>
                @if($rule->business_hours_only)
                    <span>🕐 {{ $rule->business_hours_start }} – {{ $rule->business_hours_end }}</span>
                @endif
                <span style="color: #22c55e; font-weight: 600;">▲ {{ $rule->total_sent }} sent</span>
                <span style="color: #f59e0b; font-weight: 600;">⏭️ {{ $rule->total_skipped }} skipped</span>
            </div>

            <div style="margin-top: 0.75rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <a href="{{ route('admin.whatsapp-auto-reply.edit', $rule->id) }}" class="action-btn btn-edit" title="Edit">
                    <i data-lucide="edit-2" style="width: 15px; height: 15px;"></i>
                </a>
                <form action="{{ route('admin.whatsapp-auto-reply.duplicate', $rule->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="action-btn btn-dup" title="Duplicate">
                        <i data-lucide="copy" style="width: 15px; height: 15px;"></i>
                    </button>
                </form>
                <form action="{{ route('admin.whatsapp-auto-reply.destroy', $rule->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this rule?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="action-btn btn-del" title="Delete">
                        <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="rule-card">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="bot" style="width: 40px; height: 40px; stroke-width: 1.5;"></i>
                </div>
                <h4 style="color: #1e293b; font-weight: 600;">No Auto-Reply Rules Yet</h4>
                <p style="color: #64748b; max-width: 400px; margin: 0.5rem auto;">Create your first rule to automatically respond to incoming WhatsApp messages.</p>
                <a href="{{ route('admin.whatsapp-auto-reply.create') }}" class="btn-create-modern mt-3" style="padding: 0.6rem 1.25rem; font-size: 0.9rem;">
                    Create First Rule
                </a>
            </div>
        </div>
    @endforelse

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
