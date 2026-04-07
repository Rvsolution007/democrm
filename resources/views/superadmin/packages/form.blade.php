@extends('superadmin.layouts.app')
@section('title', $package ? 'Edit ' . $package->name : 'New Package')

@section('content')
<div class="page-header" style="display:flex;align-items:center;gap:12px;">
    <a href="{{ route('superadmin.packages.index') }}" class="btn btn-ghost btn-icon btn-sm"><i data-lucide="arrow-left"></i></a>
    <div>
        <h1>{{ $package ? 'Edit Package: ' . $package->name : 'Create New Package' }}</h1>
        <p>Configure package pricing, limits, and feature access</p>
    </div>
</div>

<form method="POST" action="{{ $package ? route('superadmin.packages.update', $package->id) : route('superadmin.packages.store') }}">
    @csrf
    @if($package) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        {{-- Left: Basic Info --}}
        <div>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header"><h3 class="card-title" style="font-size:16px;">Package Details</h3></div>
                <div class="card-content">
                    <div style="margin-bottom:12px;">
                        <label class="form-label">Package Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $package->name ?? '') }}" required>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $package->description ?? '') }}</textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label">Monthly Price (₹)</label>
                            <input type="number" name="monthly_price" class="form-control" value="{{ old('monthly_price', $package->monthly_price ?? 0) }}" min="0" step="0.01" required>
                        </div>
                        <div>
                            <label class="form-label">Yearly Price (₹)</label>
                            <input type="number" name="yearly_price" class="form-control" value="{{ old('yearly_price', $package->yearly_price ?? 0) }}" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="form-label">Max Users</label>
                            <input type="number" name="default_max_users" class="form-control" value="{{ old('default_max_users', $package->default_max_users ?? 3) }}" min="1" required>
                        </div>
                        <div>
                            <label class="form-label">Trial Days</label>
                            <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $package->trial_days ?? 14) }}" min="0" required>
                        </div>
                        <div>
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $package->sort_order ?? 1) }}" min="1">
                        </div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active ?? true) ? 'checked' : '' }}
                                style="width:18px;height:18px;accent-color:hsl(var(--primary));">
                            <span class="form-label" style="margin:0;">Active (visible to businesses)</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Module Permissions --}}
        <div>
            <div class="card">
                <div class="card-header" style="flex-direction:row;align-items:center;justify-content:space-between;">
                    <h3 class="card-title" style="font-size:16px;"><i data-lucide="shield-check" style="width:16px;height:16px;margin-right:6px;color:hsl(var(--primary));vertical-align:text-bottom;"></i>Module Access</h3>
                    <div style="display:flex;gap:6px;">
                        <button type="button" onclick="toggleAll(true)" class="btn btn-ghost btn-xs" style="font-size:11px;">Select All</button>
                        <button type="button" onclick="toggleAll(false)" class="btn btn-ghost btn-xs" style="font-size:11px;">Clear All</button>
                    </div>
                </div>
                <div class="card-content" style="padding:12px 16px;">
                    @php
                        $grouped = [];
                        foreach($allModules as $key => $meta) {
                            $grouped[$meta['group']][$key] = $meta;
                        }
                        $enabledModules = $package ? ($package->module_permissions ?? []) : [];
                    @endphp

                    @foreach($grouped as $groupName => $modules)
                    <div style="margin-bottom:16px;">
                        <div style="font-size:11px;font-weight:700;color:hsl(var(--muted-foreground));text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid hsl(var(--border));">
                            {{ $groupName }}
                            <span style="font-weight:400;opacity:0.6;">(Tier {{ $modules[array_key_first($modules)]['tier'] }})</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                            @foreach($modules as $key => $meta)
                            <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;transition:background 0.15s;font-size:13px;"
                                onmouseover="this.style.background='hsl(var(--muted)/0.3)'" onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="modules[]" value="{{ $key }}" class="module-checkbox"
                                    {{ !empty($enabledModules[$key]) ? 'checked' : '' }}
                                    style="width:16px;height:16px;accent-color:hsl(var(--primary));border-radius:4px;">
                                <span>{{ $meta['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;">
        <a href="{{ route('superadmin.packages.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="{{ $package ? 'save' : 'plus' }}" style="width:16px;height:16px;"></i>
            {{ $package ? 'Update Package' : 'Create Package' }}
        </button>
    </div>
</form>

<script>
function toggleAll(state) {
    document.querySelectorAll('.module-checkbox').forEach(cb => cb.checked = state);
}
</script>
@endsection
