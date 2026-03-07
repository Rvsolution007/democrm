@extends('admin.layouts.app')

@section('title', 'Production Dashboard')
@section('breadcrumb', 'Production Dashboard')
@section('has_charts', true)

@push('styles')
    <style>
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes barGrow {
            from {
                width: 0 !important;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-8px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulseGlow {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.3);
            }

            50% {
                box-shadow: 0 0 12px 4px rgba(79, 70, 229, 0.15);
            }
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            animation: slideInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
            transition: box-shadow 0.4s, transform 0.4s;
        }

        .chart-card:hover {
            box-shadow: 0 20px 50px rgba(79, 70, 229, 0.1), 0 0 0 1px rgba(79, 70, 229, 0.06);
            transform: translateY(-3px);
        }

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

        .chart-card-body {
            padding: 18px 22px 24px;
        }

        .source-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 9px 14px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: default;
            animation: fadeIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .source-row:hover {
            background: rgba(79, 70, 229, 0.04);
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
            transition: width 1.2s cubic-bezier(0.22, 1, 0.36, 1);
            animation: barGrow 1.4s cubic-bezier(0.22, 1, 0.36, 1) both;
            position: relative;
            overflow: hidden;
        }

        .source-bar-fill::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
            animation: shimmer 2s ease-in-out infinite;
        }

        .source-bar-fill::after {
            content: '';
            position: absolute;
            right: 3px;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
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
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            animation: slideInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .stat-card-modern:hover {
            transform: translateY(-5px) scale(1.02);
        }

        .stat-card-modern .orb {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Production Dashboard</h1>
                <p class="page-description">Project and task tracking overview</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline"
                    style="display:flex;align-items:center;gap:6px;">
                    <i data-lucide="arrow-left" style="width:16px;height:16px"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Production Metrics Section -->
    <div id="production-metrics" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <div
                style="width:36px;height:36px;background:linear-gradient(135deg,#059669,#10b981);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i data-lucide="activity" style="width:18px;height:18px;color:#fff;"></i>
            </div>
            <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">Production Overview</h2>
            <div style="flex:1;height:1px;background:linear-gradient(90deg,#e2e8f0,transparent);margin-left:8px;"></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px;">
            <!-- Total Projects -->
            <div class="stat-card-modern"
                style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 50%,#818cf8 100%);box-shadow:0 12px 28px -6px rgba(79,70,229,0.4);">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div
                        style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="folder" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span
                        style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Total
                        Projects</span>
                </div>
                <div
                    style="font-size:34px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">
                    {{ $totalProjects ?? 0 }}</div>
            </div>

            <!-- Completed Projects -->
            <div class="stat-card-modern"
                style="background:linear-gradient(135deg,#059669 0%,#10b981 50%,#34d399 100%);box-shadow:0 12px 28px -6px rgba(5,150,105,0.4);animation-delay:0.08s;">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div
                        style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="check-circle-2" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span
                        style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Complete
                        Projects</span>
                </div>
                <div
                    style="font-size:34px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">
                    {{ $completedProjects ?? 0 }}</div>
            </div>

            <!-- Total Tasks -->
            <div class="stat-card-modern"
                style="background:linear-gradient(135deg,#d97706 0%,#f59e0b 50%,#fbbf24 100%);box-shadow:0 12px 28px -6px rgba(217,119,6,0.4);animation-delay:0.16s;">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div
                        style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="check-square" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span
                        style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Total
                        Tasks</span>
                </div>
                <div
                    style="font-size:34px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">
                    {{ $totalTasks ?? 0 }}</div>
            </div>

            <!-- Completed Tasks -->
            <div class="stat-card-modern"
                style="background:linear-gradient(135deg,#ec4899 0%,#f43f5e 50%,#fb7185 100%);box-shadow:0 12px 28px -6px rgba(244,63,94,0.4);animation-delay:0.24s;">
                <div class="orb" style="width:120px;height:120px;top:-30px;right:-30px;"></div>
                <div class="orb" style="width:80px;height:80px;bottom:-20px;left:-20px;"></div>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                    <div
                        style="width:42px;height:42px;background:rgba(255,255,255,0.15);backdrop-filter:blur(4px);border-radius:12px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                        <i data-lucide="check-check" style="width:18px;height:18px;color:#fff;stroke-width:2.5;"></i>
                    </div>
                    <span
                        style="font-size:12px;font-weight:600;opacity:0.85;text-transform:uppercase;letter-spacing:0.5px;">Complete
                        Tasks</span>
                </div>
                <div
                    style="font-size:34px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 2px 10px rgba(0,0,0,0.1);position:relative;z-index:1;">
                    {{ $completedTasks ?? 0 }}</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <!-- Project Status Chart -->
            <div class="chart-card">
                <div class="chart-card-header">
                    <h3>
                        <span class="header-dot" style="background:linear-gradient(135deg,#4f46e5,#8b5cf6);"></span>
                        Project Status Tracker
                    </h3>
                </div>
                <div class="chart-card-body">
                    <div id="project-status-blocks" style="display:flex;flex-direction:column;gap:4px;"></div>
                </div>
            </div>

            <!-- Task Status Chart -->
            <div class="chart-card" style="animation-delay: 0.15s;">
                <div class="chart-card-header">
                    <h3>
                        <span class="header-dot" style="background:linear-gradient(135deg,#059669,#22c55e);"></span>
                        Task Status Tracker
                    </h3>
                </div>
                <div class="chart-card-body">
                    <div id="task-status-blocks" style="display:flex;flex-direction:column;gap:4px;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ====== Projects & Tasks Status — Animated Horizontal Bars ======
            const projectsData = @json($projectsByStatus ?? []);
            const tasksDataInfo = @json($tasksByStatus ?? []);

            const projectStatusColors = {
                'pending': { bg: 'linear-gradient(90deg,#94a3b8,#cbd5e1)', text: '#64748b' },
                'in_progress': { bg: 'linear-gradient(90deg,#3b82f6,#60a5fa)', text: '#2563eb' },
                'completed': { bg: 'linear-gradient(90deg,#10b981,#34d399)', text: '#059669' },
                'on_hold': { bg: 'linear-gradient(90deg,#f59e0b,#fbbf24)', text: '#d97706' },
                'cancelled': { bg: 'linear-gradient(90deg,#ef4444,#f87171)', text: '#dc2626' }
            };

            const taskStatusColors = {
                'todo': { bg: 'linear-gradient(90deg,#94a3b8,#cbd5e1)', text: '#64748b' },
                'doing': { bg: 'linear-gradient(90deg,#3b82f6,#60a5fa)', text: '#2563eb' },
                'done': { bg: 'linear-gradient(90deg,#10b981,#34d399)', text: '#059669' }
            };

            const renderBars = (dataObj, colorsObj, containerId) => {
                const container = document.getElementById(containerId);
                if (!container) return;

                const statuses = Object.keys(dataObj);
                if (statuses.length === 0) {
                    container.innerHTML = '<p style="color:#94a3b8; font-size:13px; text-align:center; padding:20px;">No data available</p>';
                    return;
                }

                const total = Object.values(dataObj).reduce((a, b) => a + b, 0);
                const maxCount = Math.max(...Object.values(dataObj), 1);

                let i = 0;
                for (const [status, count] of Object.entries(dataObj)) {
                    if (count <= 0) continue;
                    const pct = total > 0 ? (count / total * 100) : 0;
                    const barWidth = count > 0 ? Math.max(6, (count / maxCount) * 100) : 0;
                    const colors = colorsObj[status] || { bg: 'linear-gradient(90deg,#94a3b8,#cbd5e1)', text: '#64748b' };
                    const label = status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

                    const row = document.createElement('div');
                    row.className = 'source-row';
                    row.style.animationDelay = (i * 0.07) + 's';

                    row.innerHTML = `
                            <span class="source-label" style="width:110px;">${label}</span>
                            <div class="source-bar-bg">
                                <div class="source-bar-fill" style="width:${barWidth}%;background:${colors.bg};animation-delay:${(i * 0.1 + 0.3)}s;"></div>
                            </div>
                            <span class="source-count" style="color:${colors.text};">${count}</span>
                            <span class="source-pct">${pct.toFixed(1)}%</span>
                        `;

                    row.title = `${label}: ${count} (${pct.toFixed(1)}%)`;
                    container.appendChild(row);
                    i++;
                }
            };

            renderBars(projectsData, projectStatusColors, 'project-status-blocks');
            renderBars(tasksDataInfo, taskStatusColors, 'task-status-blocks');
        });
    </script>
@endpush