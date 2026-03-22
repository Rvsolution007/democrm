@extends('admin.layouts.app')

@section('title', 'System & Error Logs - RV CRM')

@section('header', 'System & Error Logs')

@section('content')
<div class="content-body" style="padding: 24px; max-width: 1400px; margin: 0 auto;">
    
    <div class="card" style="box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border-radius: 12px; border: 1px solid #e2e8f0; background: #fff;">
        <div class="card-header" style="border-bottom: 1px solid #f1f5f9; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 12px 12px 0 0;">
            <div>
                <h3 class="card-title" style="margin: 0; font-size: 18px; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="terminal" style="width: 20px; height: 20px; color: #64748b;"></i> 
                    Application Logs Viewer
                </h3>
                <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Showing the latest system errors, AI bot issues, and API logs</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="{{ route('admin.system-logs.index') }}" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #475569; font-weight: 500; text-decoration: none; transition: all 0.2s;">
                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i> Refresh
                </a>
                <form action="{{ route('admin.system-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete all logs?');" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="display: flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; background: #ef4444; border: none; color: white; font-weight: 500; cursor: pointer; transition: background 0.2s;">
                        <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Clear Logs
                    </button>
                </form>
            </div>
        </div>

        <div class="card-content" style="padding: 0;">
            @if(session('success'))
                <div style="background: #dcfce7; color: #166534; padding: 12px 24px; font-size: 13px; font-weight: 500; border-bottom: 1px solid #bbf7d0;">
                    {{ session('success') }}
                </div>
            @endif

            <div style="background: #0f172a; color: #38bdf8; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; line-height: 1.6; padding: 24px; max-height: 70vh; overflow-y: auto; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word;">
@if(empty($logs))
<span style="color: #64748b;">No logs found. The system is running smoothly.</span>
@else
@foreach($logs as $log)
@php
    $color = '#38bdf8'; // Info/Debug
    if (strpos($log, 'local.ERROR:') !== false || strpos($log, '[error]') !== false || strpos($log, 'Exception') !== false) {
        $color = '#f87171'; // Red for errors
    } elseif (strpos($log, 'local.WARNING:') !== false) {
        $color = '#fbbf24'; // Yellow for warnings
    }
@endphp<div style="color: {{ $color }}; margin-bottom: 8px; border-bottom: 1px solid #1e293b; padding-bottom: 8px;">{{ trim($log) }}</div>
@endforeach
@endif
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // Create icons
    lucide.createIcons();
    
    // Auto-scroll logic if needed
    window.onload = function() {
        var logContainer = document.querySelector('.card-content > div:last-child');
        if(logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    };
</script>
@endpush
@endsection
