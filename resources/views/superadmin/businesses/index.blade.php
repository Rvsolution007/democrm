@extends('superadmin.layouts.app')
@section('title', 'Businesses')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
        <h1>Businesses</h1>
        <p>Manage all registered businesses and their subscriptions</p>
    </div>
    <a href="{{ route('superadmin.businesses.create') }}" class="btn btn-primary">
        <i data-lucide="plus" style="width:16px;height:16px;"></i>
        Add Business
    </a>
</div>

{{-- Quick Stats --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;">
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">Total</div>
                <div class="stats-card-value" style="font-size:20px;">{{ $totalBusinesses }}</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;"><i data-lucide="building-2" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">Active</div>
                <div class="stats-card-value" style="font-size:20px;color:hsl(var(--success));">{{ $activeCount }}</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;background:hsl(var(--success)/0.1);color:hsl(var(--success));"><i data-lucide="check-circle" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
    <div class="stats-card" style="padding:16px;">
        <div class="stats-card-inner">
            <div class="stats-card-content">
                <div class="stats-card-label" style="font-size:12px;">Expired/None</div>
                <div class="stats-card-value" style="font-size:20px;color:hsl(var(--destructive));">{{ $expiredCount }}</div>
            </div>
            <div class="stats-card-icon" style="width:32px;height:32px;background:hsl(var(--destructive)/0.1);color:hsl(var(--destructive));"><i data-lucide="alert-circle" style="width:16px;height:16px;"></i></div>
        </div>
    </div>
</div>

{{-- Search & Filters --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-content" style="padding:16px;">
        <form method="GET" action="{{ route('superadmin.businesses.index') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="{{ request('search') }}">
            </div>
            <select name="filter" class="form-select" style="width:160px;">
                <option value="">All Status</option>
                <option value="active" {{ request('filter') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="trial" {{ request('filter') === 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="expired" {{ request('filter') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="suspended" {{ request('filter') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="expiring" {{ request('filter') === 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
            </select>
            <select name="package" class="form-select" style="width:160px;">
                <option value="">All Packages</option>
                @foreach($packages as $pkg)
                    <option value="{{ $pkg->id }}" {{ request('package') == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">
                <i data-lucide="search" style="width:14px;height:14px;"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'filter', 'package']))
                <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-ghost btn-sm" style="font-size:12px;">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Business Table --}}
<div class="card">
    <div class="card-content" style="padding:0;">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Owner</th>
                        <th>Package</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Credits</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($businesses as $biz)
                    @php
                        $sub = $biz->activeSubscription() ?? $biz->latestSubscription();
                        $wallet = $biz->wallet;
                        $statusClass = 'badge-muted';
                        $statusLabel = 'No Plan';
                        if ($sub) {
                            $statusClass = match($sub->status) {
                                'active' => 'badge-success',
                                'trial' => 'badge-info',
                                'expired' => 'badge-muted',
                                'suspended' => 'badge-destructive',
                                default => 'badge-secondary',
                            };
                            $statusLabel = ucfirst($sub->status);
                            if ($sub->isExpired()) { $statusClass = 'badge-muted'; $statusLabel = 'Expired'; }
                        }
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $biz->name }}</div>
                            <div style="font-size:12px;color:hsl(var(--muted-foreground));">{{ $biz->email }}</div>
                        </td>
                        <td>
                            <div>{{ $biz->owner->name ?? '—' }}</div>
                            <div style="font-size:12px;color:hsl(var(--muted-foreground));">{{ $biz->owner->email ?? '' }}</div>
                        </td>
                        <td>
                            @if($sub && $sub->package)
                                <span class="badge badge-primary" style="font-size:11px;">{{ $sub->package->name }}</span>
                            @else
                                <span class="badge badge-muted" style="font-size:11px;">None</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td>
                            @if($sub)
                                {{ $biz->getActiveUserCount() }}/{{ $sub->getMaxUsers() }}
                            @else
                                {{ $biz->getActiveUserCount() }}/—
                            @endif
                        </td>
                        <td>
                            @if($wallet)
                                <span style="{{ $wallet->isLowBalance() ? 'color:hsl(var(--destructive));font-weight:600;' : '' }}">
                                    {{ $wallet->getBalanceFormatted() }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($sub && $sub->expires_at)
                                <span style="{{ $sub->isExpired() ? 'color:hsl(var(--destructive));' : ($sub->isExpiringSoon() ? 'color:hsl(var(--warning));' : '') }}">
                                    {{ $sub->expires_at->format('d M Y') }}
                                </span>
                                @if($sub->isExpiringSoon() && !$sub->isExpired())
                                    <div style="font-size:11px;color:hsl(var(--warning));">{{ $sub->daysRemaining() }}d left</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('superadmin.businesses.show', $biz->id) }}" class="btn btn-ghost btn-xs" title="View Details">
                                <i data-lucide="eye" style="width:14px;height:14px;"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:40px;color:hsl(var(--muted-foreground));">
                            <i data-lucide="building-2" style="width:32px;height:32px;margin-bottom:8px;opacity:0.3;"></i>
                            <p>No businesses found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($businesses->hasPages())
    <div class="card-footer" style="justify-content:center;padding:16px;">
        {{ $businesses->links('pagination::simple-bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
