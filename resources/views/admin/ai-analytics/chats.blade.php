@extends('admin.layouts.app')

@section('title', 'AI Chat History')
@section('breadcrumb', 'AI Chat History')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">AI Chat History</h1>
                <p class="page-description">View all AI bot conversations with customers</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.ai-analytics.index') }}" class="btn btn-outline">
                    <i data-lucide="bar-chart-3" style="width:16px;height:16px"></i> Token Analytics
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-content" style="padding:0">
            <table class="data-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Phone</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Messages</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Status</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">State</th>
                        <th style="padding:12px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Last Active</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:12px 16px;font-weight:600">
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;color:white;font-size:14px;font-weight:700">
                                        {{ substr($session->phone_number, -2) }}
                                    </div>
                                    {{ $session->phone_number }}
                                </div>
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                <span style="background:#eff6ff;color:#1e40af;padding:4px 10px;border-radius:10px;font-size:13px;font-weight:600">{{ $session->messages_count }}</span>
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                @if($session->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($session->status === 'completed')
                                    <span class="badge badge-info">Completed</span>
                                @else
                                    <span class="badge badge-outline">{{ ucfirst($session->status) }}</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;text-align:center;font-size:12px;color:var(--text-muted)">
                                {{ ucfirst(str_replace('_', ' ', $session->conversation_state ?? 'new')) }}
                            </td>
                            <td style="padding:12px 16px;text-align:right;font-size:13px;color:var(--text-muted)">
                                {{ $session->last_message_at ? $session->last_message_at->diffForHumans() : '—' }}
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                <a href="{{ route('admin.ai-analytics.chat-detail', $session->id) }}" class="btn btn-outline btn-sm" style="padding:4px 12px">
                                    <i data-lucide="eye" style="width:14px;height:14px"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted)">
                                <i data-lucide="message-square" style="width:48px;height:48px;color:#ccc;display:block;margin:0 auto 12px"></i>
                                No AI chat sessions yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($sessions->hasPages())
        <div style="margin-top:20px;display:flex;justify-content:center">
            {{ $sessions->appends(request()->query())->links() }}
        </div>
    @endif
@endsection
