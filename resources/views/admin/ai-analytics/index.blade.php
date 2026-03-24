@extends('admin.layouts.app')

@section('title', 'AI Token Analytics')
@section('breadcrumb', 'AI Token Analytics')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">AI Token Analytics</h1>
                <p class="page-description">Track AI API usage, token consumption & cost optimization</p>
            </div>
            <div class="page-actions" style="display:flex;gap:8px">
                <a href="{{ route('admin.ai-analytics.chats') }}" class="btn btn-outline">
                    <i data-lucide="message-square" style="width:16px;height:16px"></i> Chat History
                </a>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div style="display:flex;gap:8px;margin-bottom:24px">
        <a href="?range=1" class="btn {{ $range == '1' ? 'btn-primary' : 'btn-outline' }}" style="font-size:13px;padding:6px 16px">Today</a>
        <a href="?range=7" class="btn {{ $range == '7' ? 'btn-primary' : 'btn-outline' }}" style="font-size:13px;padding:6px 16px">7 Days</a>
        <a href="?range=30" class="btn {{ $range == '30' ? 'btn-primary' : 'btn-outline' }}" style="font-size:13px;padding:6px 16px">30 Days</a>
    </div>

    <!-- Summary Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-bottom:32px">
        <!-- Total Tokens -->
        <div class="card" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none">
            <div class="card-content" style="padding:24px">
                <div style="font-size:13px;opacity:0.85;font-weight:500">Total Tokens Used</div>
                <div style="font-size:32px;font-weight:800;margin:8px 0">{{ number_format($totalTokens) }}</div>
                <div style="font-size:12px;opacity:0.75">
                    Prompt: {{ number_format($totalTokens * 0.7) }} | Completion: {{ number_format($totalTokens * 0.3) }}
                </div>
            </div>
        </div>

        <!-- Avg Per Message -->
        <div class="card" style="background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:white;border:none">
            <div class="card-content" style="padding:24px">
                <div style="font-size:13px;opacity:0.85;font-weight:500">Avg Tokens / Message</div>
                <div style="font-size:32px;font-weight:800;margin:8px 0">{{ number_format($avgPerMessage) }}</div>
                <div style="font-size:12px;opacity:0.75">
                    Tier 1: ~{{ $tier1Avg }} avg | Tier 2: ~{{ $tier2Avg }} avg
                </div>
            </div>
        </div>

        <!-- Total Calls -->
        <div class="card" style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%);color:white;border:none">
            <div class="card-content" style="padding:24px">
                <div style="font-size:13px;opacity:0.85;font-weight:500">Total AI Calls</div>
                <div style="font-size:32px;font-weight:800;margin:8px 0">{{ number_format($totalCalls) }}</div>
                <div style="font-size:12px;opacity:0.75">
                    ⚡ Tier 1: {{ $tier1Calls }} | 🧠 Tier 2: {{ $tier2Calls }}
                </div>
            </div>
        </div>
    </div>

    <!-- Tier Distribution Bar -->
    @if($totalCalls > 0)
    <div class="card" style="margin-bottom:24px">
        <div class="card-content" style="padding:20px">
            <div style="font-weight:600;margin-bottom:12px;font-size:14px">Tier Distribution</div>
            <div style="display:flex;height:28px;border-radius:14px;overflow:hidden;background:#f1f5f9">
                @php $tier1Pct = round(($tier1Calls / $totalCalls) * 100); @endphp
                <div style="width:{{ $tier1Pct }}%;background:linear-gradient(90deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:700;min-width:40px">
                    ⚡ {{ $tier1Pct }}%
                </div>
                <div style="width:{{ 100 - $tier1Pct }}%;background:linear-gradient(90deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:700;min-width:40px">
                    🧠 {{ 100 - $tier1Pct }}%
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:var(--text-muted)">
                <span>⚡ Tier 1 (Lightweight) = Low cost</span>
                <span>🧠 Tier 2 (Full AI) = Higher cost</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Client-wise Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Client-wise Token Consumption</h3>
        </div>
        <div class="card-content" style="padding:0">
            <table class="data-table" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="padding:12px 16px;text-align:left;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Phone</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Total Calls</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">⚡ Tier 1</th>
                        <th style="padding:12px 16px;text-align:center;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">🧠 Tier 2</th>
                        <th style="padding:12px 16px;text-align:right;border-bottom:2px solid var(--border);font-weight:600;font-size:12px;text-transform:uppercase">Total Tokens</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientStats as $client)
                        <tr style="border-bottom:1px solid var(--border)">
                            <td style="padding:12px 16px;font-weight:500">{{ $client['phone'] }}</td>
                            <td style="padding:12px 16px;text-align:center">{{ $client['total_calls'] }}</td>
                            <td style="padding:12px 16px;text-align:center">
                                <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600">{{ $client['tier1_calls'] }}</span>
                            </td>
                            <td style="padding:12px 16px;text-align:center">
                                <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:600">{{ $client['tier2_calls'] }}</span>
                            </td>
                            <td style="padding:12px 16px;text-align:right;font-weight:600">{{ number_format($client['total_tokens']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">
                                <i data-lucide="bar-chart-3" style="width:48px;height:48px;color:#ccc;display:block;margin:0 auto 12px"></i>
                                No AI calls recorded yet. Start chatting via WhatsApp to see data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
