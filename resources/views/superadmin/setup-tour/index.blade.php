@extends('superadmin.layouts.app')

@section('title', 'Setup Tour Manager')
@section('breadcrumb', 'Setup Tour')

@section('content')
<div style="max-width:1100px;margin:0 auto">

    {{-- Page Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:28px">
        <div>
            <h1 style="font-size:24px;font-weight:700;color:hsl(var(--foreground));margin:0 0 6px 0;display:flex;align-items:center;gap:10px">
                <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);display:flex;align-items:center;justify-content:center">
                    <i data-lucide="wand-2" style="width:22px;height:22px;color:white"></i>
                </div>
                AI Catalogue Setup Tour
            </h1>
            <p style="color:hsl(var(--muted-foreground));font-size:14px;margin:0">Configure the onboarding wizard that guides new admins through catalogue setup using AI</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
        <div class="card" style="padding:20px;text-align:center">
            <div style="font-size:28px;font-weight:700;color:#8b5cf6">{{ $stats['total_businesses'] }}</div>
            <div style="font-size:12px;color:hsl(var(--muted-foreground));margin-top:4px">Total Businesses</div>
        </div>
        <div class="card" style="padding:20px;text-align:center">
            <div style="font-size:28px;font-weight:700;color:#10b981">{{ $stats['completed_tour'] }}</div>
            <div style="font-size:12px;color:hsl(var(--muted-foreground));margin-top:4px">Completed Tour</div>
        </div>
        <div class="card" style="padding:20px;text-align:center">
            <div style="font-size:28px;font-weight:700;color:#f59e0b">{{ $stats['pending_tour'] }}</div>
            <div style="font-size:12px;color:hsl(var(--muted-foreground));margin-top:4px">Pending</div>
        </div>
        <div class="card" style="padding:20px;text-align:center">
            <div style="font-size:28px;font-weight:700;color:#3b82f6">{{ $stats['completion_rate'] }}%</div>
            <div style="font-size:12px;color:hsl(var(--muted-foreground));margin-top:4px">Completion Rate</div>
        </div>
    </div>

    {{-- Configuration Form --}}
    <form method="POST" action="{{ route('superadmin.setup-tour.save') }}">
        @csrf

        {{-- Toggle Card --}}
        <div class="card" style="margin-bottom:20px">
            <div class="card-content" style="padding:24px">
                <h3 style="font-size:16px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                    <i data-lucide="toggle-left" style="width:18px;height:18px;color:#8b5cf6"></i>
                    Tour Toggles
                </h3>
                <div style="display:flex;gap:32px">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 16px;background:hsl(var(--muted)/0.3);border-radius:10px;border:1px solid hsl(var(--border))">
                        <input type="checkbox" name="enabled" {{ $tourConfig['enabled'] ? 'checked' : '' }}
                               style="width:18px;height:18px;accent-color:#8b5cf6">
                        <div>
                            <div style="font-weight:600;font-size:14px">Tour Enabled</div>
                            <div style="font-size:12px;color:hsl(var(--muted-foreground))">Show setup wizard to admin users</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 16px;background:hsl(var(--muted)/0.3);border-radius:10px;border:1px solid hsl(var(--border))">
                        <input type="checkbox" name="auto_show" {{ $tourConfig['auto_show'] ? 'checked' : '' }}
                               style="width:18px;height:18px;accent-color:#3b82f6">
                        <div>
                            <div style="font-weight:600;font-size:14px">Auto-Show for New Business</div>
                            <div style="font-size:12px;color:hsl(var(--muted-foreground))">Automatically prompt fresh accounts</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Welcome Messages --}}
        <div class="card" style="margin-bottom:20px">
            <div class="card-content" style="padding:24px">
                <h3 style="font-size:16px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                    <i data-lucide="message-square-text" style="width:18px;height:18px;color:#10b981"></i>
                    Welcome Content
                </h3>

                <div style="margin-bottom:16px">
                    <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px;color:hsl(var(--foreground))">Welcome Title</label>
                    <input type="text" name="welcome_title" value="{{ $tourConfig['welcome_title'] }}"
                           class="form-input" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:8px;font-size:14px">
                </div>

                <div style="margin-bottom:16px">
                    <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px;color:hsl(var(--foreground))">Welcome Subtitle</label>
                    <input type="text" name="welcome_subtitle" value="{{ $tourConfig['welcome_subtitle'] }}"
                           class="form-input" style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:8px;font-size:14px">
                </div>

                <div>
                    <label style="font-weight:600;font-size:13px;display:block;margin-bottom:6px;color:hsl(var(--foreground))">Intro Message</label>
                    <textarea name="intro_message" rows="3"
                              style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:8px;font-size:14px;resize:vertical;font-family:inherit">{{ $tourConfig['intro_message'] }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══ AI Column Analysis Prompt ═══ --}}
        <div class="card" style="margin-bottom:20px">
            <div class="card-content" style="padding:24px">
                <h3 style="font-size:16px;font-weight:600;margin:0 0 6px 0;display:flex;align-items:center;gap:8px">
                    <i data-lucide="bot" style="width:18px;height:18px;color:#f59e0b"></i>
                    AI Column Analysis Prompt
                </h3>
                <p style="font-size:13px;color:hsl(var(--muted-foreground));margin:0 0 12px 0">
                    This prompt is used when AI scans the PDF to identify the database column structure (Step 1: Upload Catalogue).
                </p>

                {{-- Default Prompt (Read-Only, Collapsible) --}}
                <details style="margin-bottom:14px;border:1px solid hsl(var(--border));border-radius:8px;overflow:hidden">
                    <summary style="padding:10px 14px;cursor:pointer;font-size:13px;font-weight:600;color:#6366f1;background:hsl(var(--muted)/0.3);user-select:none;display:flex;align-items:center;gap:6px">
                        <i data-lucide="eye" style="width:14px;height:14px"></i>
                        View Current Default Prompt (Read-Only)
                    </summary>
                    <pre style="padding:14px;margin:0;font-size:11px;font-family:'JetBrains Mono',Consolas,monospace;line-height:1.5;background:hsl(var(--muted)/0.15);white-space:pre-wrap;word-wrap:break-word;max-height:500px;overflow-y:auto;color:hsl(var(--foreground))">{{ $tourConfig['default_column_prompt'] }}</pre>
                </details>

                {{-- Custom Override --}}
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:6px;color:hsl(var(--muted-foreground))">
                    Custom Override (leave empty to use default above)
                </label>
                <textarea name="column_analysis_prompt" rows="6"
                          placeholder="Paste your custom column analysis prompt here..."
                          style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:8px;font-size:12px;resize:vertical;font-family:'JetBrains Mono',Consolas,monospace;line-height:1.5;background:hsl(var(--muted)/0.2)">{{ $tourConfig['column_analysis_prompt'] }}</textarea>
            </div>
        </div>

        {{-- ═══ AI Product Extraction Prompt ═══ --}}
        <div class="card" style="margin-bottom:20px">
            <div class="card-content" style="padding:24px">
                <h3 style="font-size:16px;font-weight:600;margin:0 0 6px 0;display:flex;align-items:center;gap:8px">
                    <i data-lucide="database" style="width:18px;height:18px;color:#3b82f6"></i>
                    AI Product Data Extraction Prompt
                </h3>
                <p style="font-size:13px;color:hsl(var(--muted-foreground));margin:0 0 4px 0">
                    This prompt is used when AI extracts individual product rows from the catalogue (Step 3: Product Data).
                </p>
                <p style="font-size:11px;color:hsl(var(--muted-foreground));margin:0 0 12px 0;font-style:italic">
                    Note: This prompt is dynamic — the column list section changes based on each business's catalogue columns. Below shows a sample with 3 demo columns.
                </p>

                {{-- Default Prompt (Read-Only, Collapsible) --}}
                <details style="margin-bottom:14px;border:1px solid hsl(var(--border));border-radius:8px;overflow:hidden">
                    <summary style="padding:10px 14px;cursor:pointer;font-size:13px;font-weight:600;color:#3b82f6;background:hsl(var(--muted)/0.3);user-select:none;display:flex;align-items:center;gap:6px">
                        <i data-lucide="eye" style="width:14px;height:14px"></i>
                        View Current Default Prompt (Read-Only — Sample Columns)
                    </summary>
                    <pre style="padding:14px;margin:0;font-size:11px;font-family:'JetBrains Mono',Consolas,monospace;line-height:1.5;background:hsl(var(--muted)/0.15);white-space:pre-wrap;word-wrap:break-word;max-height:500px;overflow-y:auto;color:hsl(var(--foreground))">{{ $tourConfig['default_product_prompt'] }}</pre>
                </details>

                {{-- Custom Override --}}
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:6px;color:hsl(var(--muted-foreground))">
                    Custom Override (leave empty to use default above)
                </label>
                <textarea name="product_extraction_prompt" rows="6"
                          placeholder="Paste your custom product extraction prompt here..."
                          style="width:100%;padding:10px 14px;border:1px solid hsl(var(--border));border-radius:8px;font-size:12px;resize:vertical;font-family:'JetBrains Mono',Consolas,monospace;line-height:1.5;background:hsl(var(--muted)/0.2)">{{ $tourConfig['product_extraction_prompt'] }}</textarea>
            </div>
        </div>

        {{-- Save Button --}}
        <div style="display:flex;justify-content:flex-end;gap:12px">
            <button type="submit" class="btn btn-primary" style="padding:10px 28px;font-weight:600;font-size:14px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);border:none;cursor:pointer">
                <i data-lucide="save" style="width:16px;height:16px;margin-right:6px"></i>
                Save Tour Settings
            </button>
        </div>
    </form>

    {{-- Recent Completions --}}
    @if($recentCompletions->count() > 0)
    <div class="card" style="margin-top:28px">
        <div class="card-content" style="padding:24px">
            <h3 style="font-size:16px;font-weight:600;margin:0 0 16px 0;display:flex;align-items:center;gap:8px">
                <i data-lucide="check-circle-2" style="width:18px;height:18px;color:#10b981"></i>
                Recent Completions
            </h3>
            <div style="display:flex;flex-direction:column;gap:8px">
                @foreach($recentCompletions as $completion)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:hsl(var(--muted)/0.2);border-radius:8px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:8px;height:8px;border-radius:50%;background:#10b981"></div>
                        <span style="font-weight:500;font-size:14px">{{ $completion['company_name'] }}</span>
                    </div>
                    <span style="font-size:12px;color:hsl(var(--muted-foreground))">{{ $completion['completed_at'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
