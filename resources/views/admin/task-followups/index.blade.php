@extends('admin.layouts.app')

@section('title', 'Micro Task Follow-ups')
@section('breadcrumb', 'Micro Task Follow-ups')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">Micro Task Follow-ups</h1>
                <p class="page-description">Track and manage your micro task follow-up schedule</p>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    @php
        $todayDate = now()->format('Y-m-d');
        $tomorrowDate = now()->addDay()->format('Y-m-d');
    @endphp
    <div
        style="background:white;border-radius:12px;padding:14px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.08);margin-bottom:20px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <a href="{{ route('admin.task-followups.index') }}"
                    style="padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;transition:all 0.15s;{{ !isset($filterDate) || !$filterDate ? 'background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;box-shadow:0 2px 8px rgba(37,99,235,0.3)' : 'background:#f1f5f9;color:#64748b' }}">
                    All
                </a>
                <a href="{{ route('admin.task-followups.index', ['date' => $todayDate]) }}"
                    style="padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;transition:all 0.15s;{{ isset($filterDate) && $filterDate == $todayDate ? 'background:linear-gradient(135deg,#f59e0b,#d97706);color:white;box-shadow:0 2px 8px rgba(217,119,6,0.3)' : 'background:#f1f5f9;color:#64748b' }}">
                    📅 Today
                </a>
                <a href="{{ route('admin.task-followups.index', ['date' => $tomorrowDate]) }}"
                    style="padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;transition:all 0.15s;{{ isset($filterDate) && $filterDate == $tomorrowDate ? 'background:linear-gradient(135deg,#22c55e,#16a34a);color:white;box-shadow:0 2px 8px rgba(22,163,74,0.3)' : 'background:#f1f5f9;color:#64748b' }}">
                    📆 Tomorrow
                </a>
                <span style="width:1px;height:24px;background:#e2e8f0;margin:0 4px"></span>
                <form method="GET" action="{{ route('admin.task-followups.index') }}"
                    style="display:flex;align-items:center;gap:8px">
                    <div style="position:relative;display:flex;align-items:center;cursor:pointer"
                        onclick="this.querySelector('input').showPicker()">
                        <input type="date" name="date" id="followup-date-filter" value="{{ $filterDate ?? '' }}"
                            style="padding:8px 14px;padding-left:36px;border:1.5px solid #e2e8f0;border-radius:20px;font-size:13px;outline:none;transition:all 0.15s;width:170px;background:white;color:#334155;font-weight:500;cursor:pointer{{ isset($filterDate) && $filterDate && $filterDate != $todayDate && $filterDate != $tomorrowDate ? ';border-color:#3b82f6;background:#eff6ff' : '' }}"
                            onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'"
                            onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'"
                            onchange="this.form.submit()">
                        <i data-lucide="calendar"
                            style="width:14px;height:14px;position:absolute;left:12px;color:#94a3b8;pointer-events:none"></i>
                    </div>
                </form>
            </div>
            @if(isset($filterDate) && $filterDate)
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:13px;color:#64748b">
                        Showing: <strong
                            style="color:#1e293b">{{ \Carbon\Carbon::parse($filterDate)->format('d M Y, l') }}</strong>
                    </span>
                    <a href="{{ route('admin.task-followups.index') }}"
                        style="width:28px;height:28px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#ef4444;text-decoration:none;transition:all 0.15s;border:1px solid #fecaca"
                        onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'"
                        title="Clear filter">
                        <i data-lucide="x" style="width:14px;height:14px"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if(isset($filterDate) && $filterDate)
        <!-- Filtered Results by Date -->
        <div style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden">
            <div
                style="padding:14px 20px;border-bottom:1px solid #f1f1f1;display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,#eff6ff,#fff)">
                <div style="width:8px;height:8px;border-radius:50%;background:#3b82f6"></div>
                <h3 style="margin:0;font-size:15px;font-weight:600;color:#3b82f6">
                    Follow-ups for {{ \Carbon\Carbon::parse($filterDate)->format('d M Y') }}
                    ({{ $filteredFollowups->count() }})
                </h3>
            </div>
            <div style="display:flex;flex-direction:column;gap:0;padding:16px;">
                @forelse($filteredFollowups as $mt)
                    @include('admin.task-followups._card', ['mt' => $mt, 'accentColor' => '#3b82f6'])
                @empty
                    <div style="padding:44px;text-align:center">
                        <div
                            style="width:48px;height:48px;border-radius:12px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                            <i data-lucide="calendar-x" style="width:24px;height:24px;color:#94a3b8"></i>
                        </div>
                        <p style="color:#64748b;font-size:14px;margin:0">No follow-ups for this date</p>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- 3-Column Kanban Layout -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;align-items:start">
        <!-- Overdue Column -->
        <div
            style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;border-top:3px solid #ef4444">
            <div
                style="padding:14px 16px;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;justify-content:space-between;background:#fffbfb">
                <div style="display:flex;align-items:center;gap:8px">
                    <div
                        style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#fef2f2,#fee2e2);display:flex;align-items:center;justify-content:center">
                        <i data-lucide="alert-circle" style="width:14px;height:14px;color:#ef4444"></i>
                    </div>
                    <span style="font-weight:600;font-size:14px;color:#dc2626">Overdue</span>
                </div>
                <span
                    style="background:#fef2f2;color:#ef4444;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">{{ $overdueFollowups->count() }}</span>
            </div>
            <div style="padding:8px;display:flex;flex-direction:column;gap:8px;max-height:70vh;overflow-y:auto">
                @forelse($overdueFollowups as $mt)
                    @include('admin.task-followups._card', ['mt' => $mt, 'accentColor' => '#ef4444'])
                @empty
                    <div style="padding:32px 16px;text-align:center">
                        <div
                            style="width:40px;height:40px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 8px">
                            <i data-lucide="check-circle" style="width:20px;height:20px;color:#22c55e"></i>
                        </div>
                        <p style="color:#94a3b8;font-size:12px;margin:0">No overdue follow-ups 🎉</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Today Column -->
        <div
            style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;border-top:3px solid #f59e0b">
            <div
                style="padding:14px 16px;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;justify-content:space-between;background:#fffef5">
                <div style="display:flex;align-items:center;gap:8px">
                    <div
                        style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#fffbeb,#fef3c7);display:flex;align-items:center;justify-content:center">
                        <i data-lucide="clock" style="width:14px;height:14px;color:#f59e0b"></i>
                    </div>
                    <span style="font-weight:600;font-size:14px;color:#d97706">Today</span>
                </div>
                <span
                    style="background:#fffbeb;color:#d97706;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">{{ $todayFollowups->count() }}</span>
            </div>
            <div style="padding:8px;display:flex;flex-direction:column;gap:8px;max-height:70vh;overflow-y:auto">
                @forelse($todayFollowups as $mt)
                    @include('admin.task-followups._card', ['mt' => $mt, 'accentColor' => '#f59e0b'])
                @empty
                    <div style="padding:32px 16px;text-align:center">
                        <div
                            style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 8px">
                            <i data-lucide="coffee" style="width:20px;height:20px;color:#94a3b8"></i>
                        </div>
                        <p style="color:#94a3b8;font-size:12px;margin:0">No follow-ups today</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Upcoming Column -->
        <div
            style="background:white;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;border-top:3px solid #22c55e">
            <div
                style="padding:14px 16px;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;justify-content:space-between;background:#f6fff9">
                <div style="display:flex;align-items:center;gap:8px">
                    <div
                        style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:flex;align-items:center;justify-content:center">
                        <i data-lucide="calendar-check" style="width:14px;height:14px;color:#22c55e"></i>
                    </div>
                    <span style="font-weight:600;font-size:14px;color:#16a34a">Upcoming</span>
                </div>
                <span
                    style="background:#f0fdf4;color:#16a34a;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700">{{ $upcomingFollowups->count() }}</span>
            </div>
            <div style="padding:8px;display:flex;flex-direction:column;gap:8px;max-height:70vh;overflow-y:auto">
                @forelse($upcomingFollowups as $mt)
                    @include('admin.task-followups._card', ['mt' => $mt, 'accentColor' => '#22c55e'])
                @empty
                    <div style="padding:32px 16px;text-align:center">
                        <div
                            style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;margin:0 auto 8px">
                            <i data-lucide="calendar-plus" style="width:20px;height:20px;color:#94a3b8"></i>
                        </div>
                        <p style="color:#94a3b8;font-size:12px;margin:0">No upcoming follow-ups</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Follow-up Modal -->
    <div id="edit-mt-followup-modal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px)">
        <div style="background:white;border-radius:14px;width:95%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,0.15)">
            <div
                style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#fff);border-radius:14px 14px 0 0">
                <div style="display:flex;align-items:center;gap:10px">
                    <div
                        style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center">
                        <i data-lucide="edit-3" style="width:16px;height:16px;color:white"></i>
                    </div>
                    <h3 style="margin:0;font-size:16px;font-weight:600;color:#1a1a2e">Edit Follow-up</h3>
                </div>
                <button onclick="closeEditMtFollowup()"
                    style="background:#f1f5f9;border:none;font-size:18px;cursor:pointer;padding:0;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#64748b;transition:all 0.15s"
                    onmouseover="this.style.background='#e2e8f0'"
                    onmouseout="this.style.background='#f1f5f9'">&times;</button>
            </div>
            <div style="padding:20px">
                <input type="hidden" id="edit-mt-fu-id">
                <div style="margin-bottom:12px">
                    <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;color:#64748b">Micro Task</label>
                    <div id="edit-mt-fu-title" style="font-size:14px;font-weight:600;color:#1e293b;padding:4px 0"></div>
                </div>
                <div style="display:flex;gap:16px;margin-bottom:16px">
                    <div style="flex:1">
                        <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;color:#64748b">Parent Task</label>
                        <div id="edit-mt-fu-task" style="font-size:13px;color:#334155;padding:4px 0;display:flex;align-items:center;gap:4px">
                            <i data-lucide="check-square" style="width:12px;height:12px;color:#94a3b8"></i>
                            <span></span>
                        </div>
                    </div>
                    <div style="flex:1">
                        <label style="display:block;margin-bottom:4px;font-size:12px;font-weight:600;color:#64748b">Project</label>
                        <div id="edit-mt-fu-project" style="font-size:13px;color:#334155;padding:4px 0;display:flex;align-items:center;gap:4px">
                            <i data-lucide="briefcase" style="width:12px;height:12px;color:#94a3b8"></i>
                            <span></span>
                        </div>
                    </div>
                </div>
                <div style="margin-bottom:16px">
                    <label style="display:block;margin-bottom:6px;font-weight:600;font-size:13px;color:#374151">Follow-up
                        Date</label>
                    <input type="date" id="edit-mt-fu-date"
                        style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:14px;outline:none;transition:border-color 0.15s;box-sizing:border-box"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>
            </div>
            <div
                style="padding:14px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;background:#fafbfc;border-radius:0 0 14px 14px">
                <button type="button" onclick="closeEditMtFollowup()"
                    style="padding:9px 18px;border:1.5px solid #e2e8f0;background:white;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#64748b;transition:all 0.15s"
                    onmouseover="this.style.borderColor='#cbd5e1'"
                    onmouseout="this.style.borderColor='#e2e8f0'">Cancel</button>
                <button type="button" onclick="saveMtFollowup()"
                    style="padding:9px 18px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:all 0.15s;box-shadow:0 2px 8px rgba(37,99,235,0.3)"
                    onmouseover="this.style.boxShadow='0 4px 12px rgba(37,99,235,0.4)'"
                    onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,0.3)'">Save Changes</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openEditMtFollowup(id, date, title, taskName, projectName) {
            document.getElementById('edit-mt-fu-id').value = id;
            document.getElementById('edit-mt-fu-date').value = date || '';
            document.getElementById('edit-mt-fu-title').textContent = title || '';
            document.getElementById('edit-mt-fu-task').querySelector('span').textContent = taskName || '—';
            document.getElementById('edit-mt-fu-project').querySelector('span').textContent = projectName || '—';
            document.getElementById('edit-mt-followup-modal').style.display = 'flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeEditMtFollowup() {
            document.getElementById('edit-mt-followup-modal').style.display = 'none';
        }

        function saveMtFollowup() {
            var id = document.getElementById('edit-mt-fu-id').value;
            var date = document.getElementById('edit-mt-fu-date').value;

            fetch('{{ url("admin/micro-tasks") }}/' + id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    follow_up_date: date || null
                })
            })
                .then(function (r) {
                    if (!r.ok) throw new Error('Failed');
                    return r.json();
                })
                .then(function () {
                    closeEditMtFollowup();
                    location.reload();
                })
                .catch(function (err) {
                    console.error(err);
                    alert('Error updating follow-up date');
                });
        }
    </script>
@endpush