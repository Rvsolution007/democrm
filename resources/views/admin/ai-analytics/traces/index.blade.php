@extends('admin.layouts.app')

@section('title', 'AI Node Traces')
@section('breadcrumb', 'AI Node Traces')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">AI Node Traces</h1>
                <p class="page-description">Diagnostic view of bot routing, intents, and AI execution.</p>
            </div>
            <div class="page-actions" style="display:flex;gap:8px">
                <a href="{{ route('admin.ai-analytics.index') }}" class="btn btn-outline">
                    <i data-lucide="bar-chart-3" style="width:16px;height:16px"></i> Analytics
                </a>
            </div>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="card">
        <div class="card-content" style="padding:0">
            <table class="data-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Session (Phone)</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Messages</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Nodes Traced</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Status</th>
                        <th style="padding:12px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Last Activity</th>
                        <th style="padding:12px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                        @php
                            $hasErrors = $s->error_traces_count > 0;
                        @endphp
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:12px 16px;font-weight:500">
                                <div style="display:flex;align-items:center;gap:6px">
                                    <i data-lucide="phone" style="width:14px;height:14px;color:var(--primary)"></i>
                                    <span style="font-size:14px;font-weight:600">{{ $s->phone_number ?: 'Session #' . $s->id }}</span>
                                </div>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;padding-left:20px">State: {{ ucfirst(str_replace('_', ' ', $s->conversation_state ?? 'new')) }}</div>
                            </td>
                            <td style="padding:12px 16px;text-align:center">{{ $s->messages_count }}</td>
                            <td style="padding:12px 16px;text-align:center">{{ $s->traces_count }}</td>
                            <td style="padding:12px 16px;text-align:center">
                                @if($hasErrors)
                                    <span style="background:#fef2f2;color:#ef4444;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600">Errors Detected</span>
                                @else
                                    <span style="background:#f0fdf4;color:#16a34a;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600">Healthy</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:right;font-size:13px;color:var(--text-muted)">
                                {{ $s->last_message_at ? \Carbon\Carbon::parse($s->last_message_at)->diffForHumans() : 'N/A' }}
                            </td>
                            <td style="padding:12px 16px;text-align:right">
                                <a href="{{ route('admin.ai-analytics.traces.show', $s->id) }}" class="btn btn-outline" style="padding:4px 8px;font-size:12px">
                                    <i data-lucide="git-merge" style="width:14px;height:14px"></i> Flow
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">
                                <i data-lucide="activity" style="width:48px;height:48px;color:#ccc;display:block;margin:0 auto 12px"></i>
                                No AI traces found. Start chatting via WhatsApp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
        <div class="card-footer">
            {{ $sessions->links() }}
        </div>
        @endif
    </div>
@endsection
