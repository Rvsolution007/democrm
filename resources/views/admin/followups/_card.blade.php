@php
    $lastFollowup = $lead->followups->first();
    $fuDateForInput = $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d\TH:i') : '';

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

<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;transition:all 0.2s;border-left:3px solid {{ $accentColor }};box-shadow:0 1px 2px rgba(0,0,0,0.02);margin-bottom:8px;"
    onmouseover="this.style.boxShadow='0 3px 10px rgba(0,0,0,0.08)';this.style.borderColor='#cbd5e1'"
    onmouseout="this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';this.style.borderColor='#e2e8f0'">

    {{-- Top Row: Name + Date & Actions --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;gap:8px">
        <div style="display:flex;flex-direction:column;flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                <span
                    style="font-weight:700;font-size:14px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                    title="{{ $lead->name }}">{{ $lead->name }}</span>
                <span
                    style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:1px 6px;border-radius:10px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">{{ $lead->stage }}</span>
            </div>

            <div style="display:flex;align-items:center;gap:4px;font-size:11px;margin-top:2px;">
                <span style="font-weight:600;color:{{ $lead->next_follow_up_at->isPast() ? '#dc2626' : '#2563eb' }}">
                    <i data-lucide="calendar-clock" style="width:11px;height:11px;margin-bottom:-1px;"></i>
                    {{ $lead->next_follow_up_at->format('d M, h:i A') }}
                </span>
                @if($lead->next_follow_up_at->isPast() && ($daysOverdue = intval(now()->diffInDays($lead->next_follow_up_at))) > 0)
                    <span
                        style="background:#fef2f2;color:#ef4444;padding:0 4px;border-radius:4px;font-size:9px;font-weight:700;">{{ $daysOverdue }}d
                        late</span>
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
            <a href="https://wa.me/91{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank"
                style="width:24px;height:24px;border-radius:6px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:#22c55e;text-decoration:none;transition:all 0.15s"
                title="WhatsApp" onmouseover="this.style.background='#dcfce7'"
                onmouseout="this.style.background='#f0fdf4'">
                <i data-lucide="message-circle" style="width:12px;height:12px"></i>
            </a>
            @if(can('leads.write') && $lastFollowup)
                <button
                    onclick="openEditFollowup({{ $lead->id }}, {{ $lastFollowup->id }}, '{{ addslashes($lastFollowup->message) }}', '{{ $fuDateForInput }}')"
                    style="width:24px;height:24px;border-radius:6px;background:#eff6ff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#3b82f6;transition:all 0.15s"
                    title="Edit Follow-up" onmouseover="this.style.background='#dbeafe'"
                    onmouseout="this.style.background='#eff6ff'">
                    <i data-lucide="edit-2" style="width:12px;height:12px"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- Info Row: Phone + Assignee + Products --}}
    <div
        style="display:flex;flex-wrap:wrap;align-items:center;gap:x-4;gap-y:2px;font-size:10px;color:#64748b;margin-bottom:6px">
        <span style="display:flex;align-items:center;gap:3px;margin-right:8px;">
            <i data-lucide="phone" style="width:9px;height:9px"></i> {{ $lead->phone }}
        </span>
        <span style="display:flex;align-items:center;gap:3px;margin-right:8px;">
            <i data-lucide="user" style="width:9px;height:9px"></i> {{ $lead->assignedTo->name ?? '—' }}
        </span>

        @if($lead->products->count() > 0)
            <div style="display:flex;gap:3px;flex-wrap:wrap;">
                @foreach($lead->products as $product)
                    <span
                        style="border:1px solid #e2e8f0;color:#64748b;padding:1px 4px;border-radius:4px;font-size:9px;">{{ $product->name }}</span>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Highlighted Message --}}
    @if($lastFollowup)
        <div
            style="background:#f8fafc;border:1px solid #e2e8f0;border-left:2px solid {{ $accentColor }};padding:6px 8px;border-radius:0 4px 4px 0;">
            <div
                style="font-size:11.5px;color:#334155;line-height:1.3;white-space:pre-wrap;font-weight:500;text-align:left;">
                {{ ltrim($lastFollowup->message) }}
            </div>
            <div style="font-size:9px;color:#94a3b8;margin-top:3px;display:flex;justify-content:space-between;">
                <span>{{ $lastFollowup->created_at->format('d M, h:i A') }}</span>
                <span>{{ $lastFollowup->user->name ?? '' }}</span>
            </div>
        </div>
    @endif
</div>