@extends('superadmin.layouts.app')
@section('title', 'Credit Packs')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
    <div>
        <h1>Credit Packs</h1>
        <p>Configure AI credit recharge packs and pricing</p>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('addPackModal').style.display='flex'" style="white-space:nowrap;">
        <i data-lucide="plus" style="width:16px;height:16px;"></i> Add Pack
    </button>
</div>

{{-- Credit Packs Grid --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
    @foreach($packs as $pack)
    <div class="card" style="padding:0;overflow:hidden;{{ !$pack->is_active ? 'opacity:0.5;' : '' }}{{ $pack->is_popular ? 'border:2px solid hsl(var(--primary));' : '' }}">
        @if($pack->is_popular)
        <div style="background:hsl(var(--primary));color:#fff;text-align:center;padding:4px;font-size:10px;font-weight:700;letter-spacing:1px;">POPULAR</div>
        @endif
        <div style="padding:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <h3 style="font-size:16px;font-weight:700;margin:0;">{{ $pack->name }}</h3>
                @if(!$pack->is_active)
                    <span class="badge badge-destructive" style="font-size:9px;">INACTIVE</span>
                @endif
            </div>
            <p style="font-size:12px;color:hsl(var(--muted-foreground));margin-bottom:14px;line-height:1.5;">{{ $pack->description }}</p>

            <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:14px;">
                <div>
                    <div style="font-size:26px;font-weight:800;">{{ $pack->getCreditsFormatted() }}</div>
                    <div style="font-size:10px;color:hsl(var(--muted-foreground));">credits</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:20px;font-weight:700;color:hsl(var(--primary));">{{ $pack->getPriceFormatted() }}</div>
                    <div style="font-size:10px;color:hsl(var(--muted-foreground));">{{ $pack->getPerCreditLabel() }}</div>
                </div>
            </div>

            <div style="display:flex;gap:8px;">
                <button class="btn btn-outline btn-sm" style="flex:1;" onclick="openEditModal({{ $pack->id }}, {{ json_encode($pack->toArray()) }})">
                    <i data-lucide="pencil" style="width:12px;height:12px;"></i> Edit
                </button>
                <form method="POST" action="{{ route('superadmin.credit-packs.toggle', $pack->id) }}" style="flex:0;">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $pack->is_active ? 'btn-destructive' : 'btn-primary' }}" title="{{ $pack->is_active ? 'Deactivate' : 'Activate' }}">
                        <i data-lucide="{{ $pack->is_active ? 'eye-off' : 'eye' }}" style="width:12px;height:12px;"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Add Pack Modal --}}
<div id="addPackModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div class="card" style="max-width:440px;width:100%;max-height:90vh;overflow-y:auto;">
        <div style="padding:20px 24px;border-bottom:1px solid hsl(var(--border));display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:16px;font-weight:700;margin:0;">Add Credit Pack</h3>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('addPackModal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="{{ route('superadmin.credit-packs.store') }}" style="padding:20px 24px;">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Pack Name</label>
                <input name="name" class="form-control" placeholder="e.g. Mega Pack" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Credits</label>
                    <input name="credits" type="number" class="form-control" min="1" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Price (₹)</label>
                    <input name="price" type="number" step="0.01" min="0" class="form-control" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Description</label>
                <input name="description" class="form-control" placeholder="Short description" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="is_popular" value="1" style="accent-color:hsl(var(--primary));">
                    <span>Mark as Popular</span>
                </label>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Sort Order</label>
                    <input name="sort_order" type="number" value="{{ $packs->count() + 1 }}" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Create Pack</button>
        </form>
    </div>
</div>

{{-- Edit Pack Modal --}}
<div id="editPackModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div class="card" style="max-width:440px;width:100%;max-height:90vh;overflow-y:auto;">
        <div style="padding:20px 24px;border-bottom:1px solid hsl(var(--border));display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:16px;font-weight:700;margin:0;">Edit Credit Pack</h3>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('editPackModal').style.display='none'">&times;</button>
        </div>
        <form id="editPackForm" method="POST" style="padding:20px 24px;">
            @csrf
            @method('PUT')
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Pack Name</label>
                <input name="name" id="edit-name" class="form-control" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Credits</label>
                    <input name="credits" id="edit-credits" type="number" min="1" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Price (₹)</label>
                    <input name="price" id="edit-price" type="number" step="0.01" min="0" required style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Description</label>
                <input name="description" id="edit-description" class="form-control" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;">
                    <input type="checkbox" name="is_popular" id="edit-popular" value="1" style="accent-color:hsl(var(--primary));">
                    <span>Mark as Popular</span>
                </label>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Sort Order</label>
                    <input name="sort_order" id="edit-sort" type="number" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:10px;font-size:14px;background:hsl(var(--background));color:hsl(var(--foreground));">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;">Update Pack</button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditModal(id, data) {
    document.getElementById('editPackForm').action = '/crmdemo/public/superadmin/credit-packs/' + id;
    document.getElementById('edit-name').value = data.name;
    document.getElementById('edit-credits').value = data.credits;
    document.getElementById('edit-price').value = data.price;
    document.getElementById('edit-description').value = data.description || '';
    document.getElementById('edit-popular').checked = data.is_popular;
    document.getElementById('edit-sort').value = data.sort_order || 0;
    document.getElementById('editPackModal').style.display = 'flex';
}
</script>
@endpush
