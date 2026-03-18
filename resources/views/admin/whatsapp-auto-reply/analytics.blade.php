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
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;
    }
    .period-tabs { display: flex; gap: 0.5rem; }
    .period-tab {
        padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.85rem; font-weight: 600;
        cursor: pointer; border: 1px solid #e2e8f0; background: white; color: #64748b;
        transition: all 0.2s; text-decoration: none;
    }
    .period-tab.active { background: #8b5cf6; color: white; border-color: #8b5cf6; }
    .period-tab:hover { background: #f1f5f9; color: #0f172a; }
    .period-tab.active:hover { background: #7c3aed; color: white; }

    .stat-card {
        background: white; border-radius: 16px; padding: 1.5rem;
        border: 1px solid #e2e8f0; text-align: left;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        display: flex; align-items: center; justify-content: space-between;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04); }
    .stat-card-info { display: flex; flex-direction: column; }
    .stat-card .stat-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 0.25rem; }
    .stat-card .stat-label { font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; }

    .analytics-card {
        background: white; border-radius: 20px; padding: 1.5rem;
        border: 1px solid #e2e8f0; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.03);
        margin-bottom: 1.5rem;
    }
    .analytics-card .card-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
    .modern-table th {
        background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase;
        font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0;
    }
    .modern-table td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }
    .modern-table tr:last-child td { border-bottom: none; }
    .modern-table tbody tr:hover { background: #f8fafc; }

    .log-entry {
        display: flex; align-items: center; gap: 1rem; padding: 0.85rem 0;
        border-bottom: 1px solid #f1f5f9; font-size: 0.85rem;
    }
    .log-entry:last-child { border-bottom: none; }
    .log-time { color: #94a3b8; font-weight: 500; min-width: 80px; }
    .log-phone { color: #0f172a; font-weight: 600; min-width: 130px; }
    .log-message { color: #64748b; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
    .log-status { font-weight: 700; font-size: 0.8rem; padding: 0.3rem 0.6rem; border-radius: 20px; }
    .log-sent { background: #ecfdf5; color: #059669; }
    .log-skipped { background: #fffbeb; color: #d97706; }
    .log-failed { background: #fef2f2; color: #dc2626; }

    .chart-bar-container { display: flex; align-items: flex-end; gap: 4px; height: 120px; padding: 0 0.5rem; }
    .chart-bar {
        flex: 1; background: linear-gradient(180deg, #8b5cf6 0%, #a78bfa 100%);
        border-radius: 4px 4px 0 0; min-height: 2px; transition: all 0.3s; position: relative;
    }
    .chart-bar:hover { background: linear-gradient(180deg, #7c3aed 0%, #8b5cf6 100%); }
    .chart-bar:hover::after {
        content: attr(data-count); position: absolute; top: -22px; left: 50%; transform: translateX(-50%);
        background: #0f172a; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;
    }
    .chart-labels { display: flex; gap: 4px; padding: 0.5rem 0.5rem 0; }
    .chart-labels span {
        flex: 1; text-align: center; font-size: 0.65rem; color: #94a3b8; font-weight: 500;
    }
</style>
@endpush

@section('title', 'Auto-Reply Analytics')

@section('content')
<div class="container-fluid" style="padding: 1.5rem;">
    <div class="page-header-modern">
        <div>
            <h2 class="page-title-modern">📊 Auto-Reply Analytics</h2>
            <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Track your auto-reply performance</p>
        </div>
        <div class="period-tabs">
            <a href="{{ route('admin.whatsapp-auto-reply.analytics', ['period' => 'today']) }}" class="period-tab {{ $period == 'today' ? 'active' : '' }}">Today</a>
            <a href="{{ route('admin.whatsapp-auto-reply.analytics', ['period' => 'week']) }}" class="period-tab {{ $period == 'week' ? 'active' : '' }}">7 Days</a>
            <a href="{{ route('admin.whatsapp-auto-reply.analytics', ['period' => 'month']) }}" class="period-tab {{ $period == 'month' ? 'active' : '' }}">30 Days</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div style="display: flex; gap: 15px; margin-bottom: 24px;">
        <div style="flex: 1;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-value">{{ $stats['total_received'] }}</div>
                    <div class="stat-label">Total Triggered</div>
                </div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb; box-shadow: 0 4px 10px rgba(37,99,235,0.15);">📨</div>
            </div>
        </div>
        <div style="flex: 1;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-value">{{ $stats['total_sent'] }}</div>
                    <div class="stat-label">Replies Sent</div>
                </div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669; box-shadow: 0 4px 10px rgba(5,150,105,0.15);">✅</div>
            </div>
        </div>
        <div style="flex: 1;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-value">{{ $stats['total_skipped'] }}</div>
                    <div class="stat-label">Skipped</div>
                </div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #d97706; box-shadow: 0 4px 10px rgba(217,119,6,0.15);">⏭️</div>
            </div>
        </div>
        <div style="flex: 1;">
            <div class="stat-card">
                <div class="stat-card-info">
                    <div class="stat-value">{{ $stats['total_failed'] }}</div>
                    <div class="stat-label">Failed</div>
                </div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #dc2626; box-shadow: 0 4px 10px rgba(220,38,38,0.15);">❌</div>
            </div>
        </div>
    </div>

    <!-- Hourly Chart -->
    <div class="analytics-card">
        <div class="card-title">📈 Replies Sent by Hour</div>
        @php $maxVal = max(1, max($chartData)); @endphp
        <div class="chart-bar-container">
            @foreach($chartData as $hour => $count)
                <div class="chart-bar" style="height: {{ ($count / $maxVal) * 100 }}%;" data-count="{{ $count }}" title="{{ $hour }}:00 — {{ $count }} replies"></div>
            @endforeach
        </div>
        <div class="chart-labels">
            @for($i = 0; $i < 24; $i++)
                <span>{{ $i }}</span>
            @endfor
        </div>
    </div>

    <div class="row g-3">
        <!-- Rule Performance -->
        <div class="col-lg-6">
            <div class="analytics-card" style="height: 100%;">
                <div class="card-title">📋 Rule Performance</div>
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>Rule</th>
                                <th>Hits</th>
                                <th>Sent</th>
                                <th>Skipped</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rulePerformance as $rp)
                                <tr>
                                    <td style="font-weight: 600;">{{ $rp->name }}</td>
                                    <td>{{ $rp->total_triggered }}</td>
                                    <td style="color: #22c55e; font-weight: 600;">{{ $rp->total_sent }}</td>
                                    <td style="color: #f59e0b;">{{ $rp->total_skipped }}</td>
                                    <td>
                                        <span style="width: 8px; height: 8px; border-radius: 50%; display: inline-block; background: {{ $rp->is_active ? '#22c55e' : '#ef4444' }};"></span>
                                        {{ $rp->is_active ? 'Active' : 'Paused' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No rules created yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="col-lg-6">
            <div class="analytics-card" style="height: 100%;">
                <div class="card-title">📜 Recent Activity Log</div>
                <div style="max-height: 400px; overflow-y: auto;">
                    @forelse($recentLogs as $log)
                        <div class="log-entry">
                            <span class="log-time">{{ $log->created_at->format('h:i A') }}</span>
                            <span class="log-phone">{{ $log->phone_number }}</span>
                            <span class="log-message" title="{{ $log->incoming_message }}">
                                "{{ Str::limit($log->incoming_message, 25) }}"
                            </span>
                            <span class="log-status log-{{ $log->status }}">
                                @if($log->status == 'sent') ✅ {{ $log->rule->name ?? 'Sent' }}
                                @elseif($log->status == 'skipped') ⏭️ {{ $log->skip_reason ?? 'Skipped' }}
                                @else ❌ Failed
                                @endif
                            </span>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 3rem 1rem; color: #94a3b8;">
                            <i data-lucide="inbox" style="width: 40px; height: 40px; stroke-width: 1.5; margin-bottom: 1rem; display: block; margin: 0 auto 1rem;"></i>
                            No auto-reply activity yet
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
