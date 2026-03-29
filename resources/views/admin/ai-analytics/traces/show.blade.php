@extends('admin.layouts.app')

@section('title', 'AI Trace Flow')
@section('breadcrumb')
    <a href="{{ route('admin.ai-analytics.traces.index') }}" class="text-gray-500 hover:text-gray-700">Traces</a> <span class="mx-2">/</span> Flow
@endsection

@push('styles')
<style>
    .message-accordion {
        margin-bottom: 16px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .message-header {
        padding: 16px 20px;
        background: #f8fafc;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease;
        border-bottom: 1px solid transparent;
        user-select: none;
    }

    .message-header:hover {
        background: #f1f5f9;
    }

    .message-header.active {
        border-bottom-color: #e2e8f0;
        background: #f8fafc;
    }

    .message-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .message-id-badge {
        background: #334155;
        color: white;
        font-size: 13px;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgb(0 0 0 / 0.1);
    }

    .user-message-text {
        font-size: 15px;
        font-weight: 500;
        color: #1e293b;
        max-width: 600px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-style: italic;
    }

    .message-meta {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 13px;
        color: #64748b;
        font-weight: 600;
    }

    .flow-container {
        padding: 30px;
        background: #ffffff;
        position: relative;
    }
    
    .timeline-line {
        position: absolute;
        top: 30px;
        bottom: 30px;
        left: 48px;
        width: 2px;
        background: #e2e8f0;
        z-index: 1;
    }

    .node-wrapper {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: flex-start;
        margin-bottom: 24px;
        padding-left: 80px;
        width: 100%;
    }

    .node-wrapper:last-child {
        margin-bottom: 0;
    }

    .node-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
        width: 100%;
        max-width: 700px;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #94a3b8;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .node-card:hover {
        transform: translateX(4px);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.05);
    }

    .node-card[data-status="success"] { border-left-color: #22c55e; }
    .node-card[data-status="error"] { border-left-color: #ef4444; }
    .node-card[data-status="skipped"] { border-left-color: #f59e0b; }

    .timeline-dot {
        position: absolute;
        left: -37px; /* Matches timeline center */
        top: 20px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: white;
        border: 3px solid #94a3b8;
        z-index: 3;
    }

    .node-card[data-status="success"] .timeline-dot { border-color: #22c55e; }
    .node-card[data-status="error"] .timeline-dot { border-color: #ef4444; }
    .node-card[data-status="skipped"] .timeline-dot { border-color: #f59e0b; }

    .node-header {
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 6px 6px 0 0;
    }

    .node-title {
        font-weight: 600;
        font-size: 14px;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .node-type-badge {
        font-size: 10px;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 6px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .badge-routing { background: #e0e7ff; color: #4338ca; }
    .badge-ai_call { background: #fef08a; color: #854d0e; }
    .badge-delivery { background: #dcfce7; color: #166534; }
    
    .node-time {
        font-size: 11px;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
    }

    .node-body {
        padding: 16px;
    }

    .data-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    @media (min-width: 1024px) {
        .data-grid.split { grid-template-columns: 1fr 1fr; }
    }

    .data-key {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .data-value {
        font-size: 13px;
        color: #0f172a;
        background: #f1f5f9;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        word-break: break-all;
    }

    .kv-pair {
        margin-bottom: 6px;
    }
    .kv-pair:last-child {
        margin-bottom: 0;
    }
    .kv-key {
        font-weight: 600;
        color: #475569;
        margin-right: 4px;
    }

    .error-msg {
        background: #fef2f2;
        color: #b91c1c;
        padding: 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        margin-top: 16px;
        border-left: 4px solid #ef4444;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title" style="display:flex;align-items:center;gap:8px">
                    Trace Flow: 
                    <i data-lucide="phone" style="width:20px;height:20px;color:var(--primary)"></i>
                    {{ $session->phone_number ?: 'Session #' . $session->id }}
                </h1>
                <p class="page-description">Visual representation of routing and AI evaluation logic</p>
            </div>
        </div>
    </div>

    <!-- Overall State -->
    <div class="card" style="margin-bottom:32px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0/0.05)">
        <div class="card-content" style="display:flex;gap:40px;padding:24px;flex-wrap:wrap">
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase">Phone Number</div>
                <div style="font-size:16px;font-weight:600;margin-top:6px;display:flex;align-items:center;gap:8px;color:#0f172a">
                    <i data-lucide="phone" style="width:16px;height:16px;color:var(--primary)"></i>
                    {{ $session->phone_number ?: 'N/A' }}
                </div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase">Conversation State</div>
                <div style="font-size:16px;font-weight:600;margin-top:6px;color:#0f172a">
                    <span style="background:#f1f5f9;padding:4px 10px;border-radius:20px">{{ ucfirst(str_replace('_', ' ', $session->conversation_state)) }}</span>
                </div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase">Total Messages</div>
                <div style="font-size:16px;font-weight:600;margin-top:6px;color:#0f172a">{{ $session->messages()->count() }}</div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase">Total Traces</div>
                <div style="font-size:16px;font-weight:600;margin-top:6px;color:#0f172a">{{ $traces->count() }}</div>
            </div>
            @if($session->lead)
                <div>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase">Lead Matched</div>
                    <div style="font-size:16px;font-weight:600;margin-top:6px;color:#0f172a">{{ $session->lead->name ?? 'Lead ID: '.$session->lead_id }}</div>
                </div>
            @endif
            @if($session->current_step_id)
                <div>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase">Current Step</div>
                    <div style="font-size:16px;font-weight:600;margin-top:6px;color:#0f172a">{{ \App\Models\ChatflowStep::find($session->current_step_id)?->name ?? 'Unknown' }}</div>
                </div>
            @endif
        </div>
    </div>

    @php
        $groupedTraces = $traces->groupBy('message_id')->reverse();
    @endphp

    @forelse($groupedTraces as $msgId => $msgTraces)
        @php
            $firstTrace = $msgTraces->first();
            $userMessageText = $firstTrace->message->message ?? 'Background Process / System Trigger';
            $timeMs = $msgTraces->sum('execution_time_ms');
            $errorCount = $msgTraces->where('status', 'error')->count();
            $isFirst = $loop->first;
            // Removed undefined function use Str::limit
            if (mb_strlen($userMessageText) > 80) $userMessageText = mb_substr($userMessageText, 0, 80) . '...';
        @endphp

        <div class="message-accordion">
            <div class="message-header {{ $isFirst ? 'active' : '' }}" id="header-{{ $msgId }}" onclick="toggleAccordion('{{ $msgId }}')">
                <div class="message-info">
                    <span class="message-id-badge">
                        <i data-lucide="message-square" style="width:14px;height:14px"></i>
                        ID #{{ $msgId ?: 'N/A' }}
                    </span>
                    <span class="user-message-text">"{!! htmlspecialchars($userMessageText) !!}"</span>
                </div>
                <div class="message-meta">
                    @if($errorCount > 0)
                        <span style="color: #b91c1c; background: #fee2e2; padding: 4px 10px; border-radius: 20px; display:flex; align-items:center; gap:4px">
                            <i data-lucide="alert-circle" style="width:14px;height:14px"></i> {{ $errorCount }} Error(s)
                        </span>
                    @endif
                    <span style="display:flex; align-items:center; gap:4px">
                        <i data-lucide="activity" style="width:14px;height:14px"></i> {{ $msgTraces->count() }} Nodes
                    </span>
                    @if($timeMs > 0)
                    <span style="display:flex; align-items:center; gap:4px">
                        <i data-lucide="clock" style="width:14px;height:14px"></i> {{ $timeMs }}ms
                    </span>
                    @endif
                    <i data-lucide="chevron-down" id="icon-{{ $msgId }}" style="width:20px;height:20px;transition: transform 0.2s; transform: {{ $isFirst ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
                </div>
            </div>
            
            <div class="message-content" id="content-{{ $msgId }}" style="display: {{ $isFirst ? 'block' : 'none' }};">
                <div class="flow-container">
                    <div class="timeline-line"></div>
                    
                    @foreach($msgTraces as $traceIndex => $trace)
                        @php
                            $isFirstNode = $traceIndex === 0;
                        @endphp
                        <div class="node-wrapper">
                            <div class="node-card" data-status="{{ $trace->status }}">
                                <div class="timeline-dot"></div>
                                
                                <div class="node-header" onclick="toggleNode('{{ $trace->id }}')" style="cursor:pointer; transition: background 0.2s;">
                                    <div class="node-title">
                                        <i data-lucide="{{ $trace->getGroupIcon() }}" style="width:18px;height:18px;color:{{ $trace->getStatusColor() }}"></i>
                                        {{ $trace->node_name }}
                                    </div>
                                    <div style="display:flex; align-items:center; gap:16px">
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                                            <span class="node-type-badge badge-{{ $trace->node_group }}">{{ $trace->node_group }}</span>
                                            @if($trace->execution_time_ms > 0)
                                                <span class="node-time"><i data-lucide="clock" style="width:12px;height:12px"></i> {{ $trace->execution_time_ms }}ms</span>
                                            @endif
                                        </div>
                                        <i data-lucide="chevron-down" id="node-icon-{{ $trace->id }}" style="width:18px;height:18px;color:#94a3b8;transition: transform 0.2s; transform: {{ $isFirstNode ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
                                    </div>
                                </div>
                                <div class="node-body" id="node-body-{{ $trace->id }}" style="display: {{ $isFirstNode ? 'block' : 'none' }};">
                                    <div class="data-grid {{ ($trace->input_data && $trace->output_data) ? 'split' : '' }}">
                                        @if($trace->input_data)
                                            <div>
                                                <div class="data-key text-blue-600">
                                                    <i data-lucide="log-in" style="width:14px;height:14px"></i> Input Data
                                                </div>
                                                <div class="data-value">
                                                    @foreach($trace->input_data as $k => $v)
                                                        <div class="kv-pair"><span class="kv-key">{{ $k }}:</span> <span class="text-gray-700">{{ is_array($v) ? json_encode($v) : $v }}</span></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if($trace->output_data)
                                            <div>
                                                <div class="data-key text-green-600">
                                                    <i data-lucide="log-out" style="width:14px;height:14px"></i> Output Data
                                                </div>
                                                <div class="data-value">
                                                    @foreach($trace->output_data as $k => $v)
                                                        <div class="kv-pair"><span class="kv-key">{{ $k }}:</span> <span class="text-gray-700">{{ is_array($v) ? json_encode($v) : $v }}</span></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    @if($trace->error_message)
                                        <div class="error-msg">
                                            <i data-lucide="alert-triangle" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"></i>
                                            <div>{{ $trace->error_message }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div style="background:white;padding:32px;border-radius:12px;display:inline-block;box-shadow:0 10px 15px -3px rgb(0 0 0/0.05);border:1px solid #e2e8f0">
                <i data-lucide="ghost" style="width:56px;height:56px;color:#cbd5e1;margin-bottom:16px"></i>
                <h3 style="font-weight:700;font-size:18px;margin-bottom:8px;color:#0f172a">No traces found</h3>
                <p style="color:#64748b;font-size:15px">Tracing wasn't active, the message errored out completely, or logs were purged.</p>
            </div>
        </div>
    @endforelse

@endsection

@push('scripts')
<script>
    function toggleAccordion(msgId) {
        const content = document.getElementById('content-' + msgId);
        const header = document.getElementById('header-' + msgId);
        const icon = document.getElementById('icon-' + msgId);
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            header.classList.add('active');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.style.display = 'none';
            header.classList.remove('active');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    function toggleNode(nodeId) {
        const body = document.getElementById('node-body-' + nodeId);
        const icon = document.getElementById('node-icon-' + nodeId);
        
        if (body.style.display === 'none') {
            body.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        } else {
            body.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endpush
