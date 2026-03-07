@php
    $daysCount = intval(now()->diffInDays($mt->follow_up_date));
    $statusColors = [
        'todo' => ['bg' => '#f1f5f9', 'color' => '#64748b'],
        'doing' => ['bg' => '#dbeafe', 'color' => '#2563eb'],
        'done' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
    ];
    $sc = $statusColors[$mt->status] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];

    // Get contact phone from parent task
    $contactPhone = $mt->task ? $mt->task->contact_phone : null;
@endphp
<div style="background:#fafafa;border:1px solid #f0f0f0;border-radius:10px;padding:14px;transition:all 0.2s;border-left:3px solid {{ $accentColor }}"
    onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';this.style.background='white'"
    onmouseout="this.style.boxShadow='none';this.style.background='#fafafa'">

    {{-- Micro Task Title + Status + Actions --}}
    <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:8px;gap:8px">
        <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:0;flex-wrap:wrap">
            <span
                style="font-weight:700;font-size:13px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $mt->title }}</span>
            <span
                style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:1px 8px;border-radius:10px;font-size:10px;font-weight:600;white-space:nowrap">{{ ucfirst($mt->status) }}</span>
        </div>

        {{-- Action Buttons --}}
        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
            @if($contactPhone)
                <a href="https://wa.me/91{{ preg_replace('/\D/', '', $contactPhone) }}" target="_blank"
                    style="width:26px;height:26px;border-radius:6px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#22c55e;text-decoration:none;transition:all 0.15s"
                    title="WhatsApp" onmouseover="this.style.background='#dcfce7'"
                    onmouseout="this.style.background='#f0fdf4'">
                    <i data-lucide="message-circle" style="width:12px;height:12px"></i>
                </a>
            @endif
            <button
                onclick="openEditMtFollowup({{ $mt->id }}, '{{ $mt->follow_up_date->format('Y-m-d') }}', '{{ addslashes($mt->title) }}', '{{ addslashes($mt->task->title ?? '') }}', '{{ addslashes($mt->task->project->name ?? '') }}')"
                style="width:26px;height:26px;border-radius:6px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all 0.15s"
                title="Edit Follow-up" onmouseover="this.style.background='#dbeafe'"
                onmouseout="this.style.background='#eff6ff'">
                <i data-lucide="edit" style="width:12px;height:12px"></i>
            </button>
        </div>
    </div>

    {{-- Parent Task & Project --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;font-size:11px;color:#64748b;margin-bottom:8px">
        @if($mt->task)
            <span style="display:flex;align-items:center;gap:3px">
                <i data-lucide="check-square" style="width:11px;height:11px"></i> {{ $mt->task->title }}
            </span>
        @endif
        @if($mt->task && $mt->task->project)
            <span style="display:flex;align-items:center;gap:3px">
                <i data-lucide="briefcase" style="width:11px;height:11px"></i> {{ $mt->task->project->name }}
            </span>
        @endif
        @if($contactPhone)
            <span style="display:flex;align-items:center;gap:3px">
                <i data-lucide="phone" style="width:11px;height:11px"></i> {{ $contactPhone }}
            </span>
        @endif
    </div>

    {{-- Follow-up Date --}}
    <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#64748b">
        <i data-lucide="calendar" style="width:11px;height:11px"></i>
        <span style="font-weight:600;color:#334155">{{ $mt->follow_up_date->format('d M Y') }}</span>
        @if($mt->follow_up_date->isPast())
            @if($daysCount > 0)
                <span
                    style="background:#fef2f2;color:#ef4444;padding:1px 6px;border-radius:6px;font-size:10px;font-weight:600;margin-left:4px">{{ $daysCount }}d
                    overdue</span>
            @endif
        @else
            @if($daysCount > 0)
                <span
                    style="background:#eff6ff;color:#3b82f6;padding:1px 6px;border-radius:6px;font-size:10px;font-weight:600;margin-left:4px">in
                    {{ $daysCount }}d</span>
            @endif
        @endif
    </div>
</div>