@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('has_charts', true)

@section('content')
    <style>
        @keyframes slideInUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
        @keyframes barGrow { from { width:0 !important; } }
        @keyframes fadeIn { from { opacity:0; transform:translateX(-8px); } to { opacity:1; transform:translateX(0); } }
        @keyframes pulseGlow { 0%,100% { box-shadow:0 0 0 0 rgba(79,70,229,0.3); } 50% { box-shadow:0 0 12px 4px rgba(79,70,229,0.15); } }
        @keyframes cardFloat { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-3px); } }
        .chart-card {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(226,232,240,0.8);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.02);
            overflow: hidden;
            animation: slideInUp 0.6s cubic-bezier(0.22,1,0.36,1) both;
            transition: box-shadow 0.4s, transform 0.4s;
        }
        .chart-card:hover {
            box-shadow: 0 20px 50px rgba(79,70,229,0.1), 0 0 0 1px rgba(79,70,229,0.06);
            transform: translateY(-3px);
        }
        .chart-card:nth-child(2) { animation-delay: 0.15s; }
        .chart-card-header {
            padding: 22px 26px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chart-card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulseGlow 2.5s ease-in-out infinite;
            flex-shrink: 0;
        }
        .year-badge {
            padding: 5px 14px;
            background: linear-gradient(135deg, #f0f0ff, #e8e6ff);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #4f46e5;
            letter-spacing: 0.3px;
        }
        .chart-card-body { padding: 18px 22px 24px; }
        .source-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 9px 14px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            cursor: default;
            animation: fadeIn 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .source-row:hover {
            background: rgba(79,70,229,0.04);
            transform: translateX(6px);
        }
        .source-label {
            width: 100px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            text-transform: capitalize;
            flex-shrink: 0;
        }
        .source-bar-bg {
            flex: 1;
            height: 26px;
            background: linear-gradient(90deg, #f8fafc, #f1f5f9);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .source-bar-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 1.2s cubic-bezier(0.22,1,0.36,1);
            animation: barGrow 1.4s cubic-bezier(0.22,1,0.36,1) both;
            position: relative;
            overflow: hidden;
        }
        .source-bar-fill::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.2) 50%, transparent 100%);
            animation: shimmer 2s ease-in-out infinite;
        }
        @keyframes shimmer { 0% { transform:translateX(-100%); } 100% { transform:translateX(100%); } }
        .source-bar-fill::after {
            content: '';
            position: absolute;
            right: 3px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            box-shadow: 0 0 6px rgba(0,0,0,0.1);
        }
        .source-count {
            width: 38px;
            text-align: right;
            font-size: 15px;
            font-weight: 800;
            flex-shrink: 0;
            font-variant-numeric: tabular-nums;
        }
        .source-pct {
            width: 50px;
            text-align: right;
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
            flex-shrink: 0;
        }
        .stat-card-modern {
            border-radius: 18px;
            padding: 22px 24px;
            color: #fff;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
            animation: slideInUp 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .stat-card-modern:hover {
            transform: translateY(-5px) scale(1.02);
        }
        .stat-card-modern:nth-child(1) { animation-delay: 0s; }
        .stat-card-modern:nth-child(2) { animation-delay: 0.08s; }
        .stat-card-modern:nth-child(3) { animation-delay: 0.16s; }
        .stat-card-modern:nth-child(4) { animation-delay: 0.24s; }
        .stat-card-modern .orb {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0) 70%);
        }
    </style>

    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-description">Current month performance overview</p>
            </div>
        </div>
    </div>

    <!-- Current Month Stats Cards -->
    <div style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div style="width:36px;height:36px;background:linear-gradient(135deg,#4f46e5,#3b82f6);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="calendar" style="width:18px;height:18px;color:#fff;"></i>
            </div>
            <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">{{ now()->format('F Y') }}</h2>
            <div style="flex:1;height:1px;background:linear-gradient(90deg,#e2e8f0,transparent);margin-left:8px;"></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;">
            <!-- New Leads -->
            <div class="stat-card-modern" style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 50%,#818cf8 100%);box-shadow:0 12px 28px -6px rgba(79,70,229,0.4);">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="user-plus" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">New Leads</span>
                </div>
                <div style="font-size:34px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">{{ $newLeads }}</div>
            </div>

            <!-- Won Leads -->
            <div class="stat-card-modern" style="background:linear-gradient(135deg,#059669 0%,#10b981 50%,#34d399 100%);box-shadow:0 12px 28px -6px rgba(5,150,105,0.4);">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="trophy" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Won Leads</span>
                </div>
                <div style="font-size:34px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">{{ $wonLeads }}</div>
            </div>

            <!-- Total Order Value -->
            <div class="stat-card-modern" style="background:linear-gradient(135deg,#d97706 0%,#f59e0b 50%,#fbbf24 100%);box-shadow:0 12px 28px -6px rgba(217,119,6,0.4);">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="indian-rupee" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Order Value</span>
                </div>
                <div style="font-size:30px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">₹{{ number_format($totalOrderValue, 2) }}</div>
            </div>

            <!-- Total Payment Received -->
            <div class="stat-card-modern" style="background:linear-gradient(135deg,#ec4899 0%,#f43f5e 50%,#fb7185 100%);box-shadow:0 12px 28px -6px rgba(244,63,94,0.4);">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="wallet" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Payment Received</span>
                </div>
                <div style="font-size:30px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">₹{{ number_format($totalPaymentReceived, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <!-- Monthly Order Value Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h3>
                    <span class="header-dot" style="background:linear-gradient(135deg,#4f46e5,#8b5cf6);"></span>
                    Monthly Order Value
                </h3>
                <span class="year-badge">{{ $currentYear }}</span>
            </div>
            <div class="chart-card-body">
                <canvas id="monthly-orders-chart" height="280"></canvas>
            </div>
        </div>

        <!-- Lead Source Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h3>
                    <span class="header-dot" style="background:linear-gradient(135deg,#059669,#22c55e);"></span>
                    Lead Sources
                </h3>
                <span class="year-badge" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#059669;">All Time</span>
            </div>
            <div class="chart-card-body">
                <div id="lead-source-blocks" style="display:flex;flex-direction:column;gap:4px;"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ====== Monthly Order Value — Premium Gradient Line Chart ======
            const monthlyData = @json($monthlyOrders);
            const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const ctx = document.getElementById('monthly-orders-chart').getContext('2d');

            // Gradient fill under line
            const gradientFill = ctx.createLinearGradient(0, 0, 0, 280);
            gradientFill.addColorStop(0, 'rgba(99, 102, 241, 0.22)');
            gradientFill.addColorStop(0.4, 'rgba(139, 92, 246, 0.08)');
            gradientFill.addColorStop(1, 'rgba(99, 102, 241, 0)');

            // Gradient line stroke
            const gradientLine = ctx.createLinearGradient(0, 0, ctx.canvas.width, 0);
            gradientLine.addColorStop(0, '#4f46e5');
            gradientLine.addColorStop(0.5, '#8b5cf6');
            gradientLine.addColorStop(1, '#6366f1');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [{
                        label: 'Order Value (₹)',
                        data: monthlyData,
                        borderColor: gradientLine,
                        backgroundColor: gradientFill,
                        borderWidth: 3.5,
                        fill: true,
                        tension: 0.45,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 10,
                        pointHoverBackgroundColor: '#6366f1',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 1800, easing: 'easeOutQuart' },
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            titleFont: { size: 13, weight: '600', family: 'inherit' },
                            bodyFont: { size: 15, weight: '700', family: 'inherit' },
                            padding: { top: 14, bottom: 14, left: 18, right: 18 },
                            cornerRadius: 14,
                            borderColor: 'rgba(99, 102, 241, 0.25)',
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                title: function(items) { return items[0].label + ' {{ $currentYear }}'; },
                                label: function(ctx) {
                                    return '₹ ' + ctx.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            ticks: {
                                callback: function(val) { return '₹' + val.toLocaleString('en-IN'); },
                                font: { size: 11, weight: '500' },
                                color: '#94a3b8',
                                padding: 10,
                            },
                            grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false }
                        },
                        x: {
                            border: { display: false },
                            ticks: { font: { size: 12, weight: '600' }, color: '#64748b', padding: 8 },
                            grid: { display: false }
                        }
                    }
                }
            });

            // ====== Lead Source — Animated Horizontal Bars ======
            const sourceData = @json($leadSources);
            const container = document.getElementById('lead-source-blocks');
            const allSources = ['walk-in', 'reference', 'indiamart', 'facebook', 'website', 'whatsapp', 'call', 'other'];

            const sourceColors = {
                'walk-in':  { bg: 'linear-gradient(90deg,#4f46e5,#818cf8)', text: '#4f46e5' },
                'reference': { bg: 'linear-gradient(90deg,#059669,#34d399)', text: '#059669' },
                'indiamart': { bg: 'linear-gradient(90deg,#d97706,#fbbf24)', text: '#d97706' },
                'facebook':  { bg: 'linear-gradient(90deg,#2563eb,#60a5fa)', text: '#2563eb' },
                'website':   { bg: 'linear-gradient(90deg,#7c3aed,#a78bfa)', text: '#7c3aed' },
                'whatsapp':  { bg: 'linear-gradient(90deg,#16a34a,#4ade80)', text: '#16a34a' },
                'call':      { bg: 'linear-gradient(90deg,#db2777,#f472b6)', text: '#db2777' },
                'other':     { bg: 'linear-gradient(90deg,#475569,#94a3b8)', text: '#475569' }
            };

            const total = Object.values(sourceData).reduce((a, b) => a + b, 0);
            const maxCount = Math.max(...allSources.map(s => sourceData[s] || 0), 1);

            allSources.forEach((source, i) => {
                const count = sourceData[source] || 0;
                const pct = total > 0 ? (count / total * 100) : 0;
                const barWidth = count > 0 ? Math.max(6, (count / maxCount) * 100) : 0;
                const colors = sourceColors[source] || sourceColors['other'];
                const label = source.charAt(0).toUpperCase() + source.slice(1).replace('-', ' ');

                const row = document.createElement('div');
                row.className = 'source-row';
                row.style.animationDelay = (i * 0.07) + 's';

                row.innerHTML = `
                    <span class="source-label">${label}</span>
                    <div class="source-bar-bg">
                        <div class="source-bar-fill" style="width:${barWidth}%;background:${colors.bg};animation-delay:${(i * 0.1 + 0.3)}s;"></div>
                    </div>
                    <span class="source-count" style="color:${colors.text};">${count}</span>
                    <span class="source-pct">${pct.toFixed(1)}%</span>
                `;

                row.title = `${label}: ${count} leads (${pct.toFixed(1)}%)`;
                container.appendChild(row);
            });
        });
    </script>
@endpush