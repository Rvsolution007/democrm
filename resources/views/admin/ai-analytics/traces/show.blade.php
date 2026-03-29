@extends('admin.layouts.app')

@section('title', 'AI Trace Flow')
@section('breadcrumb')
    <a href="{{ route('admin.ai-analytics.traces.index') }}" class="text-gray-500 hover:text-gray-700">Traces</a> <span class="mx-2">/</span> Flow
@endsection

@push('styles')
<style>
    .flow-container {
        padding: 40px 20px;
        background: #f8fafc;
        border-radius: 12px;
        position: relative;
    }
    
    .timeline-line {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 50%;
        width: 4px;
        background: #e2e8f0;
        transform: translateX(-50%);
        z-index: 1;
    }

    .node-wrapper {
        position: relative;
        z-index: 2;
        display: flex;
        justify-content: center;
        margin-bottom: 40px;
        width: 100%;
    }

    .node-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        width: 380px;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #94a3b8;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .node-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    .node-card[data-status="success"] { border-left-color: #22c55e; }
    .node-card[data-status="error"] { border-left-color: #ef4444; }
    .node-card[data-status="skipped"] { border-left-color: #f59e0b; }

    .node-header {
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
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
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 700;
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
    }

    .node-body {
        padding: 16px;
    }

    .data-key {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .data-value {
        font-size: 13px;
        color: #0f172a;
        margin-bottom: 12px;
        background: #f1f5f9;
        padding: 8px;
        border-radius: 6px;
        word-break: break-all;
    }

    .error-msg {
        background: #fef2f2;
        color: #991b1b;
        padding: 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        margin-top: 8px;
        border-left: 3px solid #ef4444;
    }

    .message-marker {
        width: 100%;
        text-align: center;
        position: relative;
        z-index: 2;
        margin-bottom: 30px;
        margin-top: 20px;
    }
    
    .marker-label {
        background: #334155;
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Trace Flow: {{ $session->phone }}</h1>
                <p class="page-description">Visual representation of routing and AI evaluation logic</p>
            </div>
        </div>
    </div>

    <!-- Overall State -->
    <div class="card" style="margin-bottom:24px">
        <div class="card-content" style="display:flex;gap:32px;padding:20px">
            <div>
                <div style="font-size:12px;color:var(--text-muted);font-weight:600">Conversation State</div>
                <div style="font-size:16px;font-weight:500;margin-top:4px">{{ ucfirst(str_replace('_', ' ', $session->conversation_state)) }}</div>
            </div>
            @if($session->lead)
                <div>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:600">Lead Matched</div>
                    <div style="font-size:16px;font-weight:500;margin-top:4px">{{ $session->lead->name ?? 'Lead ID: '.$session->lead_id }}</div>
                </div>
            @endif
            @if($session->current_step_id)
                <div>
                    <div style="font-size:12px;color:var(--text-muted);font-weight:600">Current Step</div>
                    <div style="font-size:16px;font-weight:500;margin-top:4px">{{ \App\Models\ChatflowStep::find($session->current_step_id)?->name ?? 'Unknown' }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="flow-container">
        <div class="timeline-line"></div>
        
        @php $currentMsgId = -1; @endphp
        
        @foreach($traces as $trace)
            @if($trace->message_id !== $currentMsgId)
                @php $currentMsgId = $trace->message_id; @endphp
                <div class="message-marker">
                    <span class="marker-label">
                        <i data-lucide="message-square" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                        Message ID #{{ $trace->message_id ?? 'Unknown' }}
                    </span>
                </div>
            @endif

            <div class="node-wrapper">
                <div class="node-card" data-status="{{ $trace->status }}">
                    <div class="node-header">
                        <div class="node-title">
                            <i data-lucide="{{ $trace->getGroupIcon() }}" style="width:16px;height:16px;color:{{ $trace->getStatusColor() }}"></i>
                            {{ $trace->node_name }}
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                            <span class="node-type-badge badge-{{ $trace->node_group }}">{{ $trace->node_group }}</span>
                            @if($trace->execution_time_ms > 0)
                                <span class="node-time"><i data-lucide="clock" style="width:12px;height:12px"></i> {{ $trace->execution_time_ms }}ms</span>
                            @endif
                        </div>
                    </div>
                    <div class="node-body">
                        @if($trace->input_data)
                            <div class="data-key" style="display:flex;align-items:center;gap:4px">
                                <i data-lucide="log-in" style="width:12px;height:12px"></i> Input
                            </div>
                            <div class="data-value">
                                @foreach($trace->input_data as $k => $v)
                                    <div><strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if($trace->output_data)
                            <div class="data-key" style="display:flex;align-items:center;gap:4px">
                                <i data-lucide="log-out" style="width:12px;height:12px"></i> Output
                            </div>
                            <div class="data-value">
                                @foreach($trace->output_data as $k => $v)
                                    <div><strong>{{ $k }}:</strong> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($trace->error_message)
                            <div class="error-msg">
                                <i data-lucide="alert-circle" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px"></i>
                                {{ $trace->error_message }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        
        @if($traces->isEmpty())
            <div style="text-align:center;padding:40px;position:relative;z-index:2">
                <div style="background:white;padding:24px;border-radius:12px;display:inline-block;box-shadow:0 10px 15px -3px rgb(0 0 0/0.1)">
                    <i data-lucide="ghost" style="width:48px;height:48px;color:#cbd5e1;margin-bottom:12px"></i>
                    <h3 style="font-weight:600;margin-bottom:4px">No traces found</h3>
                    <p style="color:#64748b;font-size:14px">Tracing wasn't active or logs were purged.</p>
                </div>
            </div>
        @endif
        
    </div>
@endsection
