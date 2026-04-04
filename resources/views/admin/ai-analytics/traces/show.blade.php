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
        flex-wrap: wrap;
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
    .node-card[data-status="warning"] { border-left-color: #f59e0b; }

    .timeline-dot {
        position: absolute;
        left: -37px;
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
    .node-card[data-status="warning"] .timeline-dot { border-color: #f59e0b; }

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
    .badge-database { background: #f0e4ff; color: #7c3aed; }
    .badge-data_update { background: #f0e4ff; color: #7c3aed; }
    .badge-media { background: #fce7f3; color: #9d174d; }
    .badge-followup { background: #fff7ed; color: #c2410c; }
    
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

    .kv-val-true { color: #16a34a; font-weight: 600; }
    .kv-val-false { color: #dc2626; font-weight: 600; }
    .kv-val-number { color: #2563eb; font-weight: 600; }
    .kv-val-null { color: #94a3b8; font-style: italic; }

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

    .msg-type-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .msg-type-text { background: #e0e7ff; color: #4338ca; }
    .msg-type-image { background: #dcfce7; color: #166534; }
    .msg-type-video { background: #fef08a; color: #854d0e; }
    .msg-type-audio { background: #fce7f3; color: #9d174d; }
    .msg-type-document { background: #f0e4ff; color: #7c3aed; }
    .msg-type-other { background: #f1f5f9; color: #475569; }

    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding: 16px 20px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 16px;
    }
    .section-header-sales {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    .section-header-followup {
        background: linear-gradient(135deg, #fff7ed, #ffedd5);
        color: #c2410c;
        border: 1px solid #fed7aa;
    }
    .section-count {
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-left: auto;
    }

    @media (max-width: 768px) {
        .node-wrapper { padding-left: 40px; }
        .timeline-line { left: 28px; }
        .timeline-dot { left: -17px; }
        .message-meta { gap: 8px; }
        .user-message-text { max-width: 250px; }
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
        // Split traces into Sales, Follow-up, and Database sections
        $salesTraces = $traces->whereNotIn('node_group', ['followup', 'database']);
        $followupTraces = $traces->where('node_group', 'followup');
        $dbTraces = $traces->where('node_group', 'database');
        $groupedSalesTraces = $salesTraces->groupBy('message_id')->reverse();
        $groupedDbTraces = $dbTraces->groupBy('message_id')->reverse();
    @endphp

    <!-- ═══ SECTION 1: Sales Trace ═══ -->
    <div class="message-accordion" style="margin-bottom: 30px; border-radius: 12px; overflow: hidden; border: 1px solid #bfdbfe;">
        <div class="section-header section-header-sales" id="header-section-sales" onclick="toggleAccordion('section-sales')" style="cursor: pointer; margin-bottom: 0; border: none; border-radius: 0;">
            <i data-lucide="shopping-cart" style="width:22px;height:22px"></i>
            Sales Trace
            <span class="section-count" style="background:#dbeafe;color:#1e40af">{{ $salesTraces->count() }} nodes</span>
            <i data-lucide="chevron-down" id="icon-section-sales" style="width:20px;height:20px;margin-left:8px;transition: transform 0.2s; transform: rotate(0deg)"></i>
        </div>
        <div id="content-section-sales" style="display: none; padding: 20px; background: #f8fafc; border-top: 1px solid #bfdbfe;">

    @forelse($groupedSalesTraces as $msgId => $msgTraces)
        @php
            $firstTrace = $msgTraces->first();
            $userMessageText = $firstTrace->message->message ?? 'Background Process / System Trigger';
            $userMsgType = $firstTrace->message->message_type ?? 'text';
            $timeMs = $msgTraces->sum('execution_time_ms');
            $errorCount = $msgTraces->where('status', 'error')->count();
            $warningCount = $msgTraces->where('status', 'warning')->count();
            $isFirst = $loop->first;
            if (mb_strlen($userMessageText) > 80) $userMessageText = mb_substr($userMessageText, 0, 80) . '...';
        @endphp

        <div class="message-accordion">
            <div class="message-header {{ $isFirst ? 'active' : '' }}" id="header-{{ $msgId }}" onclick="toggleAccordion('{{ $msgId }}')">
                <div class="message-info">
                    <span class="message-id-badge">
                        <i data-lucide="message-square" style="width:14px;height:14px"></i>
                        ID #{{ $msgId ?: 'N/A' }}
                    </span>
                    @php
                        $typeClass = 'msg-type-other';
                        if ($userMsgType === 'text') $typeClass = 'msg-type-text';
                        elseif ($userMsgType === 'image') $typeClass = 'msg-type-image';
                        elseif ($userMsgType === 'video') $typeClass = 'msg-type-video';
                        elseif ($userMsgType === 'audio') $typeClass = 'msg-type-audio';
                        elseif ($userMsgType === 'document') $typeClass = 'msg-type-document';
                    @endphp
                    <span class="msg-type-badge {{ $typeClass }}">{{ $userMsgType }}</span>
                    <span class="user-message-text">"{!! htmlspecialchars($userMessageText) !!}"</span>
                </div>
                <div class="message-meta">
                    @if($errorCount > 0)
                        <span style="color: #b91c1c; background: #fee2e2; padding: 4px 10px; border-radius: 20px; display:flex; align-items:center; gap:4px">
                            <i data-lucide="alert-circle" style="width:14px;height:14px"></i> {{ $errorCount }} Error(s)
                        </span>
                    @endif
                    @if($warningCount > 0)
                        <span style="color: #92400e; background: #fef3c7; padding: 4px 10px; border-radius: 20px; display:flex; align-items:center; gap:4px">
                            <i data-lucide="alert-triangle" style="width:14px;height:14px"></i> {{ $warningCount }} Warning(s)
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
                            $aiTier = '';
                            if (in_array($trace->node_name, ['Tier2GenerativeAI', 'Tier2GenerativeAI_Retry', 'OutOfContextQuery'])) {
                                $aiTier = 'Tier 2 AI';
                            } elseif (in_array($trace->node_name, ['Tier3ColumnAnalytics'])) {
                                $aiTier = 'Tier 3 AI';
                            } elseif (in_array($trace->node_name, ['ContextualMatchAI', 'SpellCorrection', 'ProductModifyIntentAI']) || str_starts_with($trace->node_name, 'ComboStepAI_') || str_starts_with($trace->node_name, 'CustomStepAI_')) {
                                $aiTier = 'Tier 1 AI';
                            } elseif (in_array($trace->node_name, ['GreetingAIResponse'])) {
                                $aiTier = 'Tier 0 AI';
                            } elseif (in_array($trace->node_name, ['CategoryPHPMultiMatch', 'ProductPHPMultiMatch', 'ProductMatchPHP', 'ColumnFilterSelected', 'CategorySelected', 'ProductSelected', 'PHPProductGroupMatch'])) {
                                $aiTier = 'Tier 0 Logic';
                            }
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
                                            @if($aiTier)
                                                <span class="node-type-badge" style="background:#1e293b; color:white;">{{ $aiTier }}</span>
                                            @endif
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
                                                        <div class="kv-pair">
                                                            <span class="kv-key">{{ $k }}:</span>
                                                            @if(is_bool($v) || $v === true || $v === false)
                                                                <span class="{{ $v ? 'kv-val-true' : 'kv-val-false' }}">{{ $v ? 'true' : 'false' }}</span>
                                                            @elseif(is_null($v))
                                                                <span class="kv-val-null">null</span>
                                                            @elseif(is_numeric($v))
                                                                <span class="kv-val-number">{{ $v }}</span>
                                                            @elseif(is_array($v))
                                                                <span class="text-gray-700" style="font-size:12px">{{ mb_substr(json_encode($v), 0, 150) }}</span>
                                                            @else
                                                                <span class="text-gray-700">{{ mb_substr((string)$v, 0, 200) }}</span>
                                                            @endif
                                                        </div>
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
                                                        <div class="kv-pair">
                                                            <span class="kv-key">{{ $k }}:</span>
                                                            @if(is_bool($v) || $v === true || $v === false)
                                                                <span class="{{ $v ? 'kv-val-true' : 'kv-val-false' }}">{{ $v ? 'true' : 'false' }}</span>
                                                            @elseif(is_null($v))
                                                                <span class="kv-val-null">null</span>
                                                            @elseif(is_numeric($v))
                                                                <span class="kv-val-number">{{ $v }}</span>
                                                            @elseif(is_array($v))
                                                                <span class="text-gray-700" style="font-size:12px">{{ mb_substr(json_encode($v), 0, 150) }}</span>
                                                            @else
                                                                <span class="text-gray-700">{{ mb_substr((string)$v, 0, 200) }}</span>
                                                            @endif
                                                        </div>
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
        <div class="empty-state" style="padding:30px 20px">
            <div style="background:white;padding:24px;border-radius:12px;display:inline-block;box-shadow:0 4px 6px -1px rgb(0 0 0/0.05);border:1px solid #e2e8f0">
                <i data-lucide="shopping-cart" style="width:40px;height:40px;color:#cbd5e1;margin-bottom:12px"></i>
                <h3 style="font-weight:700;font-size:16px;margin-bottom:6px;color:#0f172a">No sales traces</h3>
                <p style="color:#64748b;font-size:13px">No sales interaction traces found for this session.</p>
            </div>
        </div>
    @endforelse
        </div>
    </div>

    <!-- ═══ SECTION 1.5: Database Operations Trace ═══ -->
    <div class="message-accordion" style="margin-bottom: 30px; border-radius: 12px; overflow: hidden; border: 1px solid #c084fc;">
        <div class="section-header" id="header-section-database" onclick="toggleAccordion('section-database')" style="cursor: pointer; margin-bottom: 0; border: none; border-radius: 0; background: linear-gradient(135deg, #faf5ff, #f3e8ff); color: #7e22ce;">
            <i data-lucide="database" style="width:22px;height:22px"></i>
            Database Operations
            <span class="section-count" style="background:#e9d5ff;color:#7e22ce">{{ $dbTraces->count() }} nodes</span>
            <i data-lucide="chevron-down" id="icon-section-database" style="width:20px;height:20px;margin-left:8px;transition: transform 0.2s; transform: rotate(0deg)"></i>
        </div>
        <div id="content-section-database" style="display: none; padding: 20px; background: #faf5ff; border-top: 1px solid #c084fc;">

    @forelse($groupedDbTraces as $msgId => $msgTraces)
        @php
            $firstTrace = $msgTraces->first();
            $userMessageText = $firstTrace->message->message ?? 'Background Process / System Trigger';
            $userMsgType = $firstTrace->message->message_type ?? 'text';
            $timeMs = $msgTraces->sum('execution_time_ms');
            $isFirst = $loop->first;
            if (mb_strlen($userMessageText) > 80) $userMessageText = mb_substr($userMessageText, 0, 80) . '...';
        @endphp

        <div class="message-accordion" style="border-left:3px solid #a855f7">
            <div class="message-header {{ $isFirst ? 'active' : '' }}" id="header-db-{{ $msgId }}" onclick="toggleAccordion('db-{{ $msgId }}')">
                <div class="message-info">
                    <span class="message-id-badge" style="background:#7e22ce">
                        <i data-lucide="database" style="width:14px;height:14px"></i>
                        ID #{{ $msgId ?: 'N/A' }}
                    </span>
                    <span class="user-message-text">"{!! htmlspecialchars($userMessageText) !!}"</span>
                </div>
                <div class="message-meta">
                    <span style="display:flex; align-items:center; gap:4px">
                        <i data-lucide="activity" style="width:14px;height:14px"></i> {{ $msgTraces->count() }} Operations
                    </span>
                    <i data-lucide="chevron-down" id="icon-db-{{ $msgId }}" style="width:20px;height:20px;transition: transform 0.2s; transform: {{ $isFirst ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
                </div>
            </div>
            
            <div class="message-content" id="content-db-{{ $msgId }}" style="display: {{ $isFirst ? 'block' : 'none' }};">
                <div class="flow-container">
                    <div class="timeline-line"></div>
                    
                    @foreach($msgTraces as $traceIndex => $trace)
                        @php $isFirstNode = $traceIndex === 0; @endphp
                        <div class="node-wrapper">
                            <div class="node-card" data-status="{{ $trace->status }}">
                                <div class="timeline-dot"></div>
                                
                                <div class="node-header" onclick="toggleNode('db-trace-{{ $trace->id }}')" style="cursor:pointer; transition: background 0.2s;">
                                    <div class="node-title">
                                        <i data-lucide="{{ $trace->getGroupIcon() }}" style="width:18px;height:18px;color:{{ $trace->getStatusColor() }}"></i>
                                        {{ $trace->node_name }}
                                    </div>
                                    <div style="display:flex; align-items:center; gap:16px">
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                                            <span class="node-type-badge badge-database">database</span>
                                            @if($trace->execution_time_ms > 0)
                                                <span class="node-time"><i data-lucide="clock" style="width:12px;height:12px"></i> {{ $trace->execution_time_ms }}ms</span>
                                            @endif
                                        </div>
                                        <i data-lucide="chevron-down" id="node-icon-db-trace-{{ $trace->id }}" style="width:18px;height:18px;color:#94a3b8;transition: transform 0.2s; transform: {{ $isFirstNode ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
                                    </div>
                                </div>
                                <div class="node-body" id="node-body-db-trace-{{ $trace->id }}" style="display: {{ $isFirstNode ? 'block' : 'none' }};">
                                    <div class="data-grid {{ ($trace->input_data && $trace->output_data) ? 'split' : '' }}">
                                        @if($trace->input_data)
                                            <div>
                                                <div class="data-key text-blue-600">
                                                    <i data-lucide="log-in" style="width:14px;height:14px"></i> Query / Payload
                                                </div>
                                                <div class="data-value">
                                                    @foreach($trace->input_data as $k => $v)
                                                        <div class="kv-pair"><span class="kv-key">{{ $k }}:</span><span class="text-gray-700">{{ is_array($v) ? json_encode($v) : $v }}</span></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        @if($trace->output_data)
                                            <div>
                                                <div class="data-key text-green-600">
                                                    <i data-lucide="log-out" style="width:14px;height:14px"></i> Result
                                                </div>
                                                <div class="data-value">
                                                    @foreach($trace->output_data as $k => $v)
                                                        <div class="kv-pair"><span class="kv-key">{{ $k }}:</span><span class="text-gray-700">{{ is_array($v) ? json_encode($v) : $v }}</span></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state" style="padding:30px 20px">
            <div style="background:white;padding:24px;border-radius:12px;display:inline-block;box-shadow:0 4px 6px -1px rgb(0 0 0/0.05);border:1px solid #e2e8f0">
                <i data-lucide="database" style="width:40px;height:40px;color:#cbd5e1;margin-bottom:12px"></i>
                <h3 style="font-weight:700;font-size:16px;margin-bottom:6px;color:#0f172a">No database actions</h3>
                <p style="color:#64748b;font-size:13px">No Leads or Quotes were created/updated in this session.</p>
            </div>
        </div>
    @endforelse
        </div>
    </div>

    <!-- ═══ SECTION 2: Follow-up Trace ═══ -->
    <div class="message-accordion" style="margin-bottom: 30px; border-radius: 12px; overflow: hidden; border: 1px solid #fed7aa;">
        <div class="section-header section-header-followup" id="header-section-followup" onclick="toggleAccordion('section-followup')" style="cursor: pointer; margin-bottom: 0; border: none; border-radius: 0;">
            <i data-lucide="bell" style="width:22px;height:22px"></i>
            Follow-up Trace
            <span class="section-count" style="background:#ffedd5;color:#c2410c">{{ $followupTraces->count() }} nodes</span>
            <i data-lucide="chevron-down" id="icon-section-followup" style="width:20px;height:20px;margin-left:8px;transition: transform 0.2s; transform: rotate(0deg)"></i>
        </div>
        <div id="content-section-followup" style="display: none; padding: 20px; background: #fffbf5; border-top: 1px solid #fed7aa;">

        @if($followupTraces->isNotEmpty())
            <div class="message-accordion" style="border-left:3px solid #f97316">
                <div class="message-header active" id="header-followups" onclick="toggleAccordion('followups')">
                    <div class="message-info">
                        <span class="message-id-badge" style="background:#c2410c">
                            <i data-lucide="bell" style="width:14px;height:14px"></i>
                            Smart Follow-ups
                        </span>
                        <span class="user-message-text" style="color:#c2410c;font-style:normal">{{ $followupTraces->count() }} follow-up event(s) recorded</span>
                    </div>
                    <div class="message-meta">
                        @php $fuErrors = $followupTraces->where('status', 'error')->count(); @endphp
                        @if($fuErrors > 0)
                            <span style="color: #b91c1c; background: #fee2e2; padding: 4px 10px; border-radius: 20px; display:flex; align-items:center; gap:4px">
                                <i data-lucide="alert-circle" style="width:14px;height:14px"></i> {{ $fuErrors }} Error(s)
                            </span>
                        @endif
                        <span style="display:flex; align-items:center; gap:4px">
                            <i data-lucide="activity" style="width:14px;height:14px"></i> {{ $followupTraces->count() }} Nodes
                        </span>
                        <i data-lucide="chevron-down" id="icon-followups" style="width:20px;height:20px;transition: transform 0.2s; transform: rotate(180deg)"></i>
                    </div>
                </div>
                
                <div class="message-content" id="content-followups" style="display: block;">
                    <div class="flow-container">
                        <div class="timeline-line" style="background:#fed7aa"></div>
                        
                        @foreach($followupTraces as $traceIndex => $trace)
                            @php $isFirstNode = $traceIndex === 0; @endphp
                            <div class="node-wrapper">
                                <div class="node-card" data-status="{{ $trace->status }}" style="border-left-color: {{ $trace->status === 'success' ? '#f97316' : ($trace->status === 'error' ? '#ef4444' : '#f59e0b') }}">
                                    <div class="timeline-dot" style="border-color:{{ $trace->status === 'success' ? '#f97316' : '#ef4444' }}"></div>
                                    
                                    <div class="node-header" onclick="toggleNode('{{ $trace->id }}')" style="cursor:pointer; transition: background 0.2s; background: #fffbf5;">
                                        <div class="node-title">
                                            <i data-lucide="{{ $trace->getGroupIcon() }}" style="width:18px;height:18px;color:{{ $trace->status === 'success' ? '#f97316' : '#ef4444' }}"></i>
                                            {{ $trace->node_name }}
                                        </div>
                                        <div style="display:flex; align-items:center; gap:16px">
                                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                                                <span class="node-type-badge badge-followup">followup</span>
                                                @if($trace->execution_time_ms > 0)
                                                    <span class="node-time"><i data-lucide="clock" style="width:12px;height:12px"></i> {{ $trace->execution_time_ms }}ms</span>
                                                @endif
                                                <span class="node-time"><i data-lucide="calendar" style="width:12px;height:12px"></i> {{ $trace->created_at->format('d M, h:i A') }}</span>
                                            </div>
                                            <i data-lucide="chevron-down" id="node-icon-{{ $trace->id }}" style="width:18px;height:18px;color:#94a3b8;transition: transform 0.2s; transform: {{ $isFirstNode ? 'rotate(180deg)' : 'rotate(0deg)' }}"></i>
                                        </div>
                                    </div>
                                    <div class="node-body" id="node-body-{{ $trace->id }}" style="display: {{ $isFirstNode ? 'block' : 'none' }};">
                                        <div class="data-grid {{ ($trace->input_data && $trace->output_data) ? 'split' : '' }}">
                                            @if($trace->input_data)
                                                <div>
                                                    <div class="data-key" style="color:#c2410c">
                                                        <i data-lucide="log-in" style="width:14px;height:14px"></i> Input Data
                                                    </div>
                                                    <div class="data-value" style="background:#fffbf5;border-color:#fed7aa">
                                                        @foreach($trace->input_data as $k => $v)
                                                            <div class="kv-pair">
                                                                <span class="kv-key">{{ $k }}:</span>
                                                                @if(is_bool($v) || $v === true || $v === false)
                                                                    <span class="{{ $v ? 'kv-val-true' : 'kv-val-false' }}">{{ $v ? 'true' : 'false' }}</span>
                                                                @elseif(is_null($v))
                                                                    <span class="kv-val-null">null</span>
                                                                @elseif(is_numeric($v))
                                                                    <span class="kv-val-number">{{ $v }}</span>
                                                                @elseif(is_array($v))
                                                                    <span class="text-gray-700" style="font-size:12px">{{ mb_substr(json_encode($v), 0, 150) }}</span>
                                                                @else
                                                                    <span class="text-gray-700">{{ mb_substr((string)$v, 0, 200) }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if($trace->output_data)
                                                <div>
                                                    <div class="data-key" style="color:#166534">
                                                        <i data-lucide="log-out" style="width:14px;height:14px"></i> Output Data
                                                    </div>
                                                    <div class="data-value" style="background:#fffbf5;border-color:#fed7aa">
                                                        @foreach($trace->output_data as $k => $v)
                                                            <div class="kv-pair">
                                                                <span class="kv-key">{{ $k }}:</span>
                                                                @if(is_bool($v) || $v === true || $v === false)
                                                                    <span class="{{ $v ? 'kv-val-true' : 'kv-val-false' }}">{{ $v ? 'true' : 'false' }}</span>
                                                                @elseif(is_null($v))
                                                                    <span class="kv-val-null">null</span>
                                                                @elseif(is_numeric($v))
                                                                    <span class="kv-val-number">{{ $v }}</span>
                                                                @elseif(is_array($v))
                                                                    <span class="text-gray-700" style="font-size:12px">{{ mb_substr(json_encode($v), 0, 150) }}</span>
                                                                @else
                                                                    <span class="text-gray-700">{{ mb_substr((string)$v, 0, 200) }}</span>
                                                                @endif
                                                            </div>
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
        @else
            <div class="empty-state" style="padding:30px 20px">
                <div style="background:white;padding:24px;border-radius:12px;display:inline-block;box-shadow:0 4px 6px -1px rgb(0 0 0/0.05);border:1px solid #e2e8f0">
                    <i data-lucide="bell-off" style="width:40px;height:40px;color:#cbd5e1;margin-bottom:12px"></i>
                    <h3 style="font-weight:700;font-size:16px;margin-bottom:6px;color:#0f172a">No follow-up traces yet</h3>
                    <p style="color:#64748b;font-size:13px">Follow-up events will appear here when smart follow-ups are triggered.</p>
                </div>
            </div>
        @endif
        </div>
    </div>

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
