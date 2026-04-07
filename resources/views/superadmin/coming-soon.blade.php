@extends('superadmin.layouts.app')
@section('title', $page ?? 'Coming Soon')

@section('content')
<div class="page-header">
    <h1>{{ $page ?? 'Coming Soon' }}</h1>
    <p>{{ $desc ?? 'This feature is being developed.' }}</p>
</div>

<div class="card" style="text-align:center;padding:60px 32px;">
    <div style="width:64px;height:64px;margin:0 auto 20px;background:hsl(var(--primary)/0.1);border-radius:16px;display:flex;align-items:center;justify-content:center;">
        <i data-lucide="construction" style="width:32px;height:32px;color:hsl(var(--primary));"></i>
    </div>
    <h2 style="font-size:20px;font-weight:600;margin-bottom:8px;">Under Construction</h2>
    <p style="font-size:14px;color:hsl(var(--muted-foreground));max-width:400px;margin:0 auto 24px;">
        {{ $desc ?? 'This section will be available in the next development session.' }}
    </p>
    <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary">
        <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
        Back to Dashboard
    </a>
</div>
@endsection
