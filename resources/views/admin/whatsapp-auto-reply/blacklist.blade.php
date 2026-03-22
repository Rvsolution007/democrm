@extends('admin.layouts.app')

@push('styles')
<style>
    .page-header-modern {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        padding: 1.5rem 2rem; border-radius: 16px;
        box-shadow: 0 4px 20px -10px rgba(0,0,0,0.05); border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .page-title-modern {
        font-size: 1.75rem; font-weight: 700;
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0;
    }
    .blacklist-card {
        background: white; border-radius: 20px; padding: 2rem;
        border: 1px solid #e2e8f0; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.03);
    }
    .add-form {
        display: flex; gap: 0.75rem; margin-bottom: 1.5rem; padding: 1.25rem;
        background: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0;
    }
    .add-form input {
        flex: 1; background: white; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 0.75rem 1rem; font-size: 0.95rem;
    }
    .add-form input:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); outline: none; }
    .btn-block {
        background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem;
        border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap;
    }
    .btn-block:hover { background: #dc2626; transform: translateY(-1px); }
    .modern-table th {
        background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase;
        font-size: 0.75rem; letter-spacing: 0.5px; padding: 1rem 1.25rem;
    }
    .modern-table td { padding: 1rem 1.25rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .btn-unblock {
        background: #ecfdf5; color: #059669; border: none; padding: 0.4rem 0.8rem;
        border-radius: 8px; font-weight: 600; font-size: 0.8rem; cursor: pointer;
    }
    .btn-unblock:hover { background: #059669; color: white; }
</style>
@endpush

@section('title', 'Auto-Reply Blacklist')

@section('content')
<div class="container-fluid" style="padding: 1.5rem; max-width: 900px;">
    <div class="page-header-modern">
        <div>
            <h2 class="page-title-modern">🚫 Blacklisted Numbers</h2>
            <p class="text-muted mt-1 mb-0" style="font-size: 0.9rem;">Numbers that will never receive auto-replies</p>
        </div>
        <a href="{{ route('admin.whatsapp-auto-reply.index') }}" style="background: white; color: #475569; border: 1px solid #e2e8f0; padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; text-decoration: none;">← Back to Rules</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px; border: none; background: #ecfdf5; color: #047857; padding: 1rem 1.5rem;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="blacklist-card">
        <!-- Add Number Form -->
        <form action="{{ route('admin.whatsapp-auto-reply.blacklist.store') }}" method="POST" class="add-form">
            @csrf
            <input type="text" name="phone_number" placeholder="Phone number (e.g. 9876543210)" required>
            <input type="text" name="reason" placeholder="Reason (optional)">
            <button type="submit" class="btn-block">🚫 Block Number</button>
        </form>

        <!-- Blacklist Table -->
        <div class="table-responsive">
            <table class="table modern-table mb-0">
                <thead>
                    <tr>
                        <th>Phone Number</th>
                        <th>Reason</th>
                        <th>Blocked On</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blacklist as $entry)
                        <tr>
                            <td style="font-weight: 600; font-family: monospace; font-size: 1rem;">{{ $entry->phone_number }}</td>
                            <td style="color: #64748b;">{{ $entry->reason ?? '—' }}</td>
                            <td style="color: #94a3b8; font-size: 0.85rem;">{{ $entry->created_at->format('d M Y, h:i A') }}</td>
                            <td class="text-end">
                                <button type="button" onclick="ajaxDelete('{{ route('admin.whatsapp-auto-reply.blacklist.destroy', $entry->id) }}')" class="btn-unblock">✅ Unblock</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div style="color: #94a3b8;">
                                    <i data-lucide="shield-check" style="width: 40px; height: 40px; stroke-width: 1.5; margin-bottom: 0.75rem;"></i>
                                    <p>No numbers blacklisted. All numbers will receive auto-replies.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
