@extends('admin.layouts.app')

@section('title', 'WhatsApp Extension')
@section('breadcrumb', 'WhatsApp Extension')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title" style="display:flex;align-items:center;gap:10px">
                    <i data-lucide="blocks" style="width:28px;height:28px;color:#2563eb"></i>
                    WhatsApp Lead Capture Extension
                </h1>
                <p class="page-description">Install the Chrome Extension to capture leads directly from WhatsApp Web</p>
            </div>
        </div>
    </div>

    <div style="max-width:800px;margin:0 auto">
        {{-- Hero Section --}}
        <div class="card" style="margin-bottom:24px;background:linear-gradient(135deg,#eff6ff 0%,#ffffff 100%);border:1px solid #bfdbfe">
            <div class="card-content" style="padding:32px;display:flex;align-items:center;gap:24px">
                <div style="flex-shrink:0">
                    <div style="width:80px;height:80px;background:white;border-radius:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 25px rgba(37,99,235,0.15)">
                        <i data-lucide="chrome" style="width:40px;height:40px;color:#2563eb"></i>
                    </div>
                </div>
                <div style="flex:1">
                    <h2 style="margin:0 0 8px 0;font-size:22px;font-weight:700;color:#1e3a8a">Capture Leads with 1 Click</h2>
                    <p style="margin:0 0 16px 0;color:#3b82f6;font-size:15px;line-height:1.5">
                        Open any chat on WhatsApp Web, press <kbd style="background:#1e3a8a;color:white;padding:3px 8px;border-radius:4px;font-family:monospace;font-size:13px">Ctrl + G</kbd> and the lead form will open automatically with the phone number pre-filled!
                    </p>
                    <a href="{{ asset('whatsapp-lead-extension.zip') }}" class="btn btn-primary" style="padding:10px 20px;font-size:15px;display:inline-flex;align-items:center;gap:8px" download>
                        <i data-lucide="download" style="width:18px;height:18px"></i> Download Extension
                    </a>
                </div>
            </div>
        </div>

        {{-- Installation Guide --}}
        <h3 style="margin:0 0 16px 0;font-size:18px;color:#111827;display:flex;align-items:center;gap:8px">
            <i data-lucide="help-circle" style="width:20px;height:20px;color:#6b7280"></i> How to Install
        </h3>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px">
            {{-- Step 1 --}}
            <div class="card" style="height:100%">
                <div class="card-content" style="padding:24px">
                    <div style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#374151;margin-bottom:12px">1</div>
                    <h4 style="margin:0 0 8px 0;font-size:16px;font-weight:600">Download & Extract</h4>
                    <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.5">
                        Click the download button above to get the <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:12px">.zip</code> file. Extract the ZIP file to a folder on your computer.
                    </p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="card" style="height:100%">
                <div class="card-content" style="padding:24px">
                    <div style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#374151;margin-bottom:12px">2</div>
                    <h4 style="margin:0 0 8px 0;font-size:16px;font-weight:600">Open Extensions Page</h4>
                    <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.5">
                        Open Google Chrome and type <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;font-size:12px;color:#2563eb">chrome://extensions/</code> in the address bar.
                    </p>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="card" style="height:100%">
                <div class="card-content" style="padding:24px">
                    <div style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#374151;margin-bottom:12px">3</div>
                    <h4 style="margin:0 0 8px 0;font-size:16px;font-weight:600">Enable Developer Mode</h4>
                    <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.5">
                        Turn ON the <strong>Developer mode</strong> switch in the top right corner of the extensions page.
                    </p>
                </div>
            </div>

            {{-- Step 4 --}}
            <div class="card" style="height:100%">
                <div class="card-content" style="padding:24px">
                    <div style="width:32px;height:32px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:#10b981;margin-bottom:12px;box-shadow:0 0 0 2px #10b981 inset">4</div>
                    <h4 style="margin:0 0 8px 0;font-size:16px;font-weight:600">Load Extension</h4>
                    <p style="margin:0;color:#4b5563;font-size:14px;line-height:1.5">
                        Click <strong>"Load unpacked"</strong> and select the folder you extracted in Step 1. You're done! 🎉
                    </p>
                </div>
            </div>
        </div>

        {{-- Troubleshooting --}}
        <div class="card" style="border-left:4px solid #f59e0b">
            <div class="card-content" style="padding:20px 24px">
                <h4 style="margin:0 0 8px 0;font-size:15px;font-weight:600;color:#b45309;display:flex;align-items:center;gap:6px">
                    <i data-lucide="alert-triangle" style="width:16px;height:16px"></i> Usage Note
                </h4>
                <p style="margin:0;color:#78350f;font-size:14px;line-height:1.5">
                    For contacts that are already saved in your phone, the number might be hidden. <strong>Click on their name to open the contact info panel</strong> on the right side, then press Ctrl+G.
                </p>
            </div>
        </div>
    </div>
@endsection
