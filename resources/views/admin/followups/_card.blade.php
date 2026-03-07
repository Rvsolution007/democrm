@php
    $lastFollowup = $lead->followups->first();
    $fuDateForInput = $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d\TH:i') : '';
@endphp
<div style="background:#fafafa;border:1px solid #f0f0f0;border-radius:10px;padding:14px;transition:all 0.2s;border-left:3px solid {{ $accentColor }}"
    onmouseover="this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';this.style.background='white'"
    onmouseout="this.style.boxShadow='none';this.style.background='#fafafa'">

    {{-- Lead Name + Stage + Actions --}}
    <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:8px;gap:8px">
        <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:0;flex-wrap:wrap">
            <span
                style="font-weight:700;font-size:13px;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lead->name }}</span>
            @php
                $stageColors = [
                    'new' => ['bg' => '#eff6ff', 'color' => '#2563eb'],
                    'contacted' => ['bg' => '#fdf4ff', 'color' => '#a855f7'],
                    'qualified' => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
                    'proposal' => ['bg' => '#fffbeb', 'color' => '#d97706'],
                    'negotiation' => ['bg' => '#fef2f2', 'color' => '#dc2626'],
                    'won' => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
                    'lost' => ['bg' => '#fef2f2', 'color' => '#dc2626'],
                ];
                $sc = $stageColors[$lead->stage] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
            @endphp
            <span
                style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:1px 8px;border-radius:10px;font-size:10px;font-weight:600;white-space:nowrap">{{ ucfirst($lead->stage) }}</span>
        </div>

        {{-- Action Buttons --}}
        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
            <a href="https://wa.me/91{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank"
                style="width:26px;height:26px;border-radius:6px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#22c55e;text-decoration:none;transition:all 0.15s"
                title="WhatsApp" onmouseover="this.style.background='#dcfce7'"
                onmouseout="this.style.background='#f0fdf4'">
                <i data-lucide="message-circle" style="width:12px;height:12px"></i>
            </a>
            @if(can('leads.write') && $lastFollowup)
                <button
                    onclick="openEditFollowup({{ $lead->id }}, {{ $lastFollowup->id }}, '{{ addslashes($lastFollowup->message) }}', '{{ $fuDateForInput }}')"
                    style="width:26px;height:26px;border-radius:6px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all 0.15s"
                    title="Edit Follow-up" onmouseover="this.style.background='#dbeafe'"
                    onmouseout="this.style.background='#eff6ff'">
                    <i data-lucide="edit" style="width:12px;height:12px"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- Contact Info --}}
    <div style="display:flex;flex-wrap:wrap;gap:8px;font-size:11px;color:#64748b;margin-bottom:8px">
        <span style="display:flex;align-items:center;gap:3px">
            <i data-lucide="phone" style="width:11px;height:11px"></i> {{ $lead->phone }}
        </span>
        <span style="display:flex;align-items:center;gap:3px">
            <i data-lucide="user" style="width:11px;height:11px"></i> {{ $lead->assignedTo->name ?? '—' }}
        </span>
    </div>

    {{-- Follow-up Date --}}
    <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#64748b;margin-bottom:8px">
        <i data-lucide="calendar" style="width:11px;height:11px"></i>
        <span style="font-weight:600;color:#334155">{{ $lead->next_follow_up_at->format('d M Y, h:i A') }}</span>
        @if($lead->next_follow_up_at->isPast())
            @php $daysOverdue = intval(now()->diffInDays($lead->next_follow_up_at)); @endphp
            @if($daysOverdue > 0)
                <span
                    style="background:#fef2f2;color:#ef4444;padding:1px 6px;border-radius:6px;font-size:10px;font-weight:600;margin-left:4px">{{ $daysOverdue }}d
                    overdue</span>
            @endif
        @endif
    </div>

    {{-- Products --}}
    @if($lead->products->count() > 0)
        <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px">
            @foreach($lead->products as $product)
                <span
                    style="background:#f1f5f9;color:#475569;padding:2px 7px;border-radius:6px;font-size:10px;font-weight:500">{{ $product->name }}</span>
            @endforeach
        </div>
    @endif

    {{-- Last Followup Message --}}
    @if($lastFollowup)
        <div
            style="background:#f8fafc;border-left:2px solid {{ $accentColor }};padding:6px 10px;border-radius:4px;margin-bottom:4px">
            <div style="font-size:12px;color:#475569;line-height:1.4;white-space:pre-wrap">{{ $lastFollowup->message }}
            </div>
            <div style="font-size:10px;color:#94a3b8;margin-top:2px">{{ $lastFollowup->created_at->format('d M Y, h:i A') }}
                — {{ $lastFollowup->user->name ?? '' }}</div>
        </div>
    @endif
</div>