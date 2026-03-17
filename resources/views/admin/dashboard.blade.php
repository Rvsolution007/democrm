@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('has_charts', true)

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-description">Welcome back! Here's what's happening with your business today.</p>
            </div>
        </div>
    </div>

    <!-- Dashboard Navigation Cards -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px;">
        <!-- Sales Button Card -->
        <a href="{{ route('admin.sales-dashboard') }}" style="text-decoration:none;display:block;">
            <div style="background:linear-gradient(135deg,#4f46e5 0%,#6366f1 40%,#818cf8 100%);border-radius:20px;padding:32px 34px;color:#fff;position:relative;overflow:hidden;transition:all 0.4s cubic-bezier(0.22,1,0.36,1);box-shadow:0 10px 30px -8px rgba(79,70,229,0.4);cursor:pointer;"
                onmouseover="this.style.transform='translateY(-5px) scale(1.01)';this.style.boxShadow='0 20px 40px -10px rgba(79,70,229,0.5)';"
                onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 10px 30px -8px rgba(79,70,229,0.4)';">
                <div
                    style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,rgba(255,255,255,0) 70%);border-radius:50%;">
                </div>
                <div
                    style="position:absolute;bottom:-30px;left:-30px;width:120px;height:120px;background:radial-gradient(circle,rgba(255,255,255,0.08) 0%,rgba(255,255,255,0) 70%);border-radius:50%;">
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1;">
                    <div style="display:flex;align-items:center;gap:18px;">
                        <div
                            style="width:56px;height:56px;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border-radius:16px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                            <i data-lucide="trending-up" style="width:26px;height:26px;color:#fff;stroke-width:2.5;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:22px;font-weight:800;margin:0;letter-spacing:-0.3px;">Sales</h3>
                            <p style="font-size:13px;opacity:0.8;margin:4px 0 0;font-weight:500;">Leads, Orders, Payments &
                                Charts</p>
                        </div>
                    </div>
                    <div
                        style="width:40px;height:40px;background:rgba(255,255,255,0.12);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="arrow-right" style="width:20px;height:20px;color:#fff;"></i>
                    </div>
                </div>
            </div>
        </a>

        <!-- Production Button Card -->
        <a href="{{ route('admin.production-dashboard') }}" style="text-decoration:none;display:block;">
            <div style="background:linear-gradient(135deg,#059669 0%,#10b981 40%,#34d399 100%);border-radius:20px;padding:32px 34px;color:#fff;position:relative;overflow:hidden;transition:all 0.4s cubic-bezier(0.22,1,0.36,1);box-shadow:0 10px 30px -8px rgba(5,150,105,0.4);cursor:pointer;"
                onmouseover="this.style.transform='translateY(-5px) scale(1.01)';this.style.boxShadow='0 20px 40px -10px rgba(5,150,105,0.5)';"
                onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 10px 30px -8px rgba(5,150,105,0.4)';">
                <div
                    style="position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:radial-gradient(circle,rgba(255,255,255,0.1) 0%,rgba(255,255,255,0) 70%);border-radius:50%;">
                </div>
                <div
                    style="position:absolute;bottom:-30px;left:-30px;width:120px;height:120px;background:radial-gradient(circle,rgba(255,255,255,0.08) 0%,rgba(255,255,255,0) 70%);border-radius:50%;">
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1;">
                    <div style="display:flex;align-items:center;gap:18px;">
                        <div
                            style="width:56px;height:56px;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border-radius:16px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                            <i data-lucide="briefcase" style="width:26px;height:26px;color:#fff;stroke-width:2.5;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:22px;font-weight:800;margin:0;letter-spacing:-0.3px;">Production</h3>
                            <p style="font-size:13px;opacity:0.8;margin:4px 0 0;font-weight:500;">Projects, Tasks & Progress
                                Tracking</p>
                        </div>
                    </div>
                    <div
                        style="width:40px;height:40px;background:rgba(255,255,255,0.12);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="arrow-down" style="width:20px;height:20px;color:#fff;"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-2 gap-6">
        <!-- My Tasks -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Tasks</h3>
                <a href="{{ route('admin.tasks.index') }}" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="card-content">
                @forelse($tasks as $task)
                    <div class="flex items-start gap-3 p-3 border rounded mb-2 cursor-pointer transition"
                        onclick="window.location='{{ route('admin.tasks.index') }}'">
                        <div
                            class="w-4 h-4 mt-0.5 rounded-full border-2 {{ $task->status === 'done' ? 'bg-success border-success' : 'border-muted-foreground' }} flex-shrink-0">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $task->title }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="badge badge-{{ $task->priority }}">{{ $task->priority }}</span>
                                <span class="text-xs text-muted">{{ $task->assignedUsers->isNotEmpty() ? $task->assignedUsers->pluck('name')->implode(', ') : 'Unassigned' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted text-center py-4">No pending tasks</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activity</h3>
                <a href="{{ route('admin.activities.index') }}" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="card-content">
                <div class="timeline">
                    @forelse($activities as $i => $activity)
                        <div class="timeline-item">
                            <div class="timeline-indicator">
                                <div class="timeline-dot"></div>
                                @if(!$loop->last)
                                    <div class="timeline-line"></div>
                                @endif
                            </div>
                            <div class="timeline-content">
                                <p class="timeline-text">{{ $activity->summary ?? $activity->description ?? 'Activity' }}</p>
                                <p class="timeline-meta">{{ $activity->createdBy->name ?? 'System' }} •
                                    {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted text-center py-4">No recent activity</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Leads by Stage Chart
            const stageData = @json($leadsByStage);
            const stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
            const stageLabels = stages.map(s => s.charAt(0).toUpperCase() + s.slice(1));
            const stageCounts = stages.map(s => stageData[s] || 0);

            new Chart(document.getElementById('leads-chart'), {
                type: 'bar',
                data: {
                    labels: stageLabels,
                    datasets: [{
                        label: 'Leads',
                        data: stageCounts,
                        backgroundColor: '#f97316',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        });
    </script>
@endpush