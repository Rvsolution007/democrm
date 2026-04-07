@extends('superadmin.layouts.app')
@section('title', 'Packages')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
        <h1>Subscription Packages</h1>
        <p>Configure tiers, pricing, and feature access for each plan</p>
    </div>
    <a href="{{ route('superadmin.packages.create') }}" class="btn btn-primary">
        <i data-lucide="plus" style="width:16px;height:16px;"></i> New Package
    </a>
</div>

{{-- Package Cards --}}
<div style="display:grid;grid-template-columns:repeat({{ count($packages) > 3 ? 3 : count($packages) }},1fr);gap:20px;margin-bottom:24px;">
    @foreach($packages as $pkg)
    <div class="card" style="position:relative;border:2px solid {{ $pkg->is_active ? 'hsl(var(--primary)/0.3)' : 'hsl(var(--border))' }};{{ !$pkg->is_active ? 'opacity:0.6;' : '' }}">
        {{-- Status badge --}}
        <div style="position:absolute;top:12px;right:12px;">
            <span class="badge {{ $pkg->is_active ? 'badge-success' : 'badge-muted' }}">{{ $pkg->is_active ? 'Active' : 'Inactive' }}</span>
        </div>

        <div class="card-header" style="padding-bottom:8px;">
            <h3 class="card-title" style="font-size:20px;">{{ $pkg->name }}</h3>
        </div>
        <div class="card-content">
            <p style="font-size:12px;color:hsl(var(--muted-foreground));margin-bottom:12px;min-height:36px;">{{ $pkg->description }}</p>

            {{-- Pricing --}}
            <div style="display:flex;gap:16px;margin-bottom:16px;">
                <div style="flex:1;padding:10px;background:hsl(var(--muted)/0.3);border-radius:8px;text-align:center;">
                    <div style="font-size:11px;color:hsl(var(--muted-foreground));">Monthly</div>
                    <div style="font-size:20px;font-weight:700;color:hsl(var(--primary));">{{ $pkg->getPriceLabel('monthly') }}</div>
                </div>
                <div style="flex:1;padding:10px;background:hsl(var(--muted)/0.3);border-radius:8px;text-align:center;">
                    <div style="font-size:11px;color:hsl(var(--muted-foreground));">Yearly</div>
                    <div style="font-size:20px;font-weight:700;color:hsl(var(--accent));">{{ $pkg->getPriceLabel('yearly') }}</div>
                </div>
            </div>

            {{-- Limits --}}
            <div style="display:flex;gap:12px;margin-bottom:16px;">
                <div style="flex:1;padding:8px;background:hsl(var(--info)/0.08);border-radius:6px;text-align:center;">
                    <div style="font-size:10px;color:hsl(var(--muted-foreground));">Max Users</div>
                    <div style="font-size:16px;font-weight:600;">{{ $pkg->default_max_users }}</div>
                </div>
                <div style="flex:1;padding:8px;background:hsl(var(--warning)/0.08);border-radius:6px;text-align:center;">
                    <div style="font-size:10px;color:hsl(var(--muted-foreground));">Trial Days</div>
                    <div style="font-size:16px;font-weight:600;">{{ $pkg->trial_days }}</div>
                </div>
                <div style="flex:1;padding:8px;background:hsl(var(--success)/0.08);border-radius:6px;text-align:center;">
                    <div style="font-size:10px;color:hsl(var(--muted-foreground));">Subscribers</div>
                    <div style="font-size:16px;font-weight:600;">{{ $pkg->active_subscribers }}</div>
                </div>
            </div>

            {{-- Feature count by group --}}
            @php
                $enabledFeatures = $pkg->getEnabledFeatures();
                $groups = [];
                foreach($allModules as $key => $meta) {
                    if(in_array($key, $enabledFeatures)) {
                        $groups[$meta['group']] = ($groups[$meta['group']] ?? 0) + 1;
                    }
                }
            @endphp
            <div style="margin-bottom:16px;">
                <div style="font-size:11px;font-weight:600;color:hsl(var(--muted-foreground));margin-bottom:6px;">FEATURES ({{ count($enabledFeatures) }}/{{ count($allModules) }})</div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
                    @foreach($groups as $group => $count)
                    <span class="badge badge-secondary" style="font-size:10px;">{{ $group }}: {{ $count }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:8px;">
                <a href="{{ route('superadmin.packages.edit', $pkg->id) }}" class="btn btn-outline btn-sm" style="flex:1;">
                    <i data-lucide="edit-2" style="width:14px;height:14px;"></i> Edit
                </a>
                <form method="POST" action="{{ route('superadmin.packages.toggle-active', $pkg->id) }}" style="flex:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" title="{{ $pkg->is_active ? 'Deactivate' : 'Activate' }}">
                        <i data-lucide="{{ $pkg->is_active ? 'eye-off' : 'eye' }}" style="width:14px;height:14px;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Feature Comparison Grid --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title" style="font-size:16px;"><i data-lucide="grid-3x3" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>Feature Comparison</h3>
    </div>
    <div class="card-content" style="padding:0;">
        <div class="table-container" style="border:none;">
            <table>
                <thead>
                    <tr>
                        <th style="width:200px;">Feature</th>
                        <th style="width:120px;">Group</th>
                        @foreach($packages as $pkg)
                        <th style="text-align:center;">{{ $pkg->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($allModules as $key => $meta)
                    <tr>
                        <td style="font-weight:500;">{{ $meta['label'] }}</td>
                        <td><span class="badge badge-secondary" style="font-size:10px;">{{ $meta['group'] }}</span></td>
                        @foreach($packages as $pkg)
                        <td style="text-align:center;">
                            @if($pkg->hasFeature($key))
                                <i data-lucide="check-circle" style="width:18px;height:18px;color:hsl(var(--success));"></i>
                            @else
                                <i data-lucide="x-circle" style="width:18px;height:18px;color:hsl(var(--muted-foreground));opacity:0.3;"></i>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
