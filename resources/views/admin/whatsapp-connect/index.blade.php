@extends('admin.layouts.app')

@section('title', 'WhatsApp Connect')
@section('breadcrumb', 'WhatsApp Connect')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title" style="display:flex;align-items:center;gap:10px">
                    <i data-lucide="smartphone" style="width:28px;height:28px;color:#25D366"></i>
                    WhatsApp Connect
                </h1>
                <p class="page-description">Scan QR code to connect your WhatsApp — {{ $userName }}</p>
            </div>
        </div>
    </div>

    @if(!$isConfigured)
        {{-- Not Configured State --}}
        <div class="card" style="max-width:600px;margin:40px auto;text-align:center">
            <div class="card-content" style="padding:48px 32px">
                <div
                    style="width:80px;height:80px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                    <i data-lucide="alert-triangle" style="width:36px;height:36px;color:#f59e0b"></i>
                </div>
                <h3 style="font-size:20px;font-weight:700;margin-bottom:8px;color:#111">API Not Configured</h3>
                <p style="color:#666;font-size:15px;margin-bottom:24px;line-height:1.6">
                    Admin ko pehle Evolution API credentials configure karne honge Settings mein.
                </p>
                @if(can('settings.manage'))
                    <a href="{{ route('admin.settings.index') }}" class="btn btn-primary">
                        <i data-lucide="settings" style="width:16px;height:16px"></i> Go to Settings
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- Main Connect UI --}}
        <div style="max-width:900px;margin:0 auto">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                {{-- QR Code Card --}}
                <div class="card" id="qr-card">
                    <div class="card-header" style="text-align:center">
                        <h3 class="card-title" style="display:flex;align-items:center;justify-content:center;gap:8px">
                            <i data-lucide="qr-code" style="width:20px;height:20px;color:#25D366"></i>
                            Scan QR Code
                        </h3>
                    </div>
                    <div class="card-content" style="text-align:center;padding:24px">
                        {{-- Loading State --}}
                        <div id="qr-loading" style="padding:40px 0">
                            <div class="wa-spinner"
                                style="width:48px;height:48px;border:4px solid #e5e7eb;border-top-color:#25D366;border-radius:50%;animation:wa-spin 1s linear infinite;margin:0 auto 16px">
                            </div>
                            <p style="color:#888;font-size:14px">Loading QR code...</p>
                        </div>

                        {{-- QR Code Display --}}
                        <div id="qr-display" style="display:none">
                            <div
                                style="background:#fff;border:2px solid #e5e7eb;border-radius:12px;padding:16px;display:inline-block;margin-bottom:16px">
                                <img id="qr-image" src="" alt="WhatsApp QR Code" style="width:256px;height:256px;display:block">
                            </div>
                            <p style="color:#666;font-size:13px;line-height:1.6;margin-bottom:12px">
                                Open <strong>WhatsApp</strong> → <strong>Linked Devices</strong> → <strong>Link a
                                    Device</strong>
                            </p>
                            <div
                                style="display:flex;align-items:center;gap:8px;justify-content:center;color:#888;font-size:12px">
                                <div class="wa-spinner"
                                    style="width:14px;height:14px;border:2px solid #e5e7eb;border-top-color:#25D366;border-radius:50%;animation:wa-spin 1s linear infinite">
                                </div>
                                QR refreshes automatically...
                            </div>
                        </div>

                        {{-- Connected State --}}
                        <div id="qr-connected" style="display:none;padding:20px 0">
                            <div
                                style="width:72px;height:72px;background:linear-gradient(135deg,#dcfce7,#bbf7d0);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                                <i data-lucide="check-circle" style="width:36px;height:36px;color:#16a34a"></i>
                            </div>
                            <h3 style="font-size:18px;font-weight:700;color:#16a34a;margin-bottom:4px">Connected!</h3>
                            <p style="color:#666;font-size:14px;margin-bottom:20px">Your WhatsApp is linked and ready to send
                                messages</p>
                            <button class="btn" onclick="disconnectWhatsapp()" id="disconnect-btn"
                                style="background:#fee2e2;color:#dc2626;border:1px solid #fecaca;font-weight:600">
                                <i data-lucide="unplug" style="width:16px;height:16px"></i> Disconnect
                            </button>
                        </div>

                        {{-- Error State --}}
                        <div id="qr-error" style="display:none;padding:20px 0">
                            <div
                                style="width:72px;height:72px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                                <i data-lucide="x-circle" style="width:36px;height:36px;color:#dc2626"></i>
                            </div>
                            <h3 style="font-size:18px;font-weight:700;color:#dc2626;margin-bottom:4px">Connection Error</h3>
                            <p id="qr-error-msg" style="color:#666;font-size:14px;margin-bottom:20px"></p>
                            <button class="btn btn-primary" onclick="initConnection()">
                                <i data-lucide="refresh-cw" style="width:16px;height:16px"></i> Retry
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Status & Info Card --}}
                <div>
                    {{-- Connection Status --}}
                    <div class="card" style="margin-bottom:20px">
                        <div class="card-header">
                            <h3 class="card-title">Connection Status</h3>
                        </div>
                        <div class="card-content">
                            <div
                                style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f0f0f0">
                                <div id="status-dot"
                                    style="width:12px;height:12px;border-radius:50%;background:#e5e7eb;flex-shrink:0"></div>
                                <div>
                                    <div id="status-label" style="font-weight:600;font-size:15px;color:#333">Checking...</div>
                                    <div id="status-detail" style="font-size:13px;color:#888;margin-top:2px">Connecting to
                                        server</div>
                                </div>
                            </div>
                            <div style="padding:12px 0;display:flex;align-items:center;gap:8px">
                                <i data-lucide="user" style="width:14px;height:14px;color:#888"></i>
                                <span style="font-size:13px;color:#666"><strong>{{ $userName }}</strong></span>
                            </div>
                            <div style="padding:4px 0;display:flex;align-items:center;gap:8px">
                                <i data-lucide="server" style="width:14px;height:14px;color:#888"></i>
                                <span style="font-size:12px;color:#999;font-family:monospace">{{ $instanceName }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Instructions --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">How to Connect</h3>
                        </div>
                        <div class="card-content">
                            <ol style="margin:0;padding-left:20px;font-size:14px;color:#555;line-height:2">
                                <li>Open <strong>WhatsApp</strong> on your phone</li>
                                <li>Tap <strong>⋮ Menu</strong> (Android) or <strong>Settings</strong> (iPhone)</li>
                                <li>Tap <strong>Linked Devices</strong></li>
                                <li>Tap <strong>Link a Device</strong></li>
                                <li>Point your phone at the QR code</li>
                            </ol>

                            <div
                                style="margin-top:16px;padding:12px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:13px;color:#1e40af;display:flex;align-items:flex-start;gap:8px">
                                <i data-lucide="info" style="width:16px;height:16px;flex-shrink:0;margin-top:1px"></i>
                                <span>Aapka WhatsApp number automatically Bulk Sender campaigns ke liye use hoga. Har user apna
                                    alag WhatsApp connect kar sakta hai.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        @keyframes wa-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const CSRF = '{{ csrf_token() }}';
        let statusPollTimer = null;
        let qrPollTimer = null;
        let currentState = '';

        function showSection(id) {
            ['qr-loading', 'qr-display', 'qr-connected', 'qr-error'].forEach(function (s) {
                var el = document.getElementById(s);
                if (el) el.style.display = 'none';
            });
            var target = document.getElementById(id);
            if (target) target.style.display = 'block';
            lucide.createIcons();
        }

        function updateStatus(state, label, detail, color) {
            var dot = document.getElementById('status-dot');
            var labelEl = document.getElementById('status-label');
            var detailEl = document.getElementById('status-detail');
            if (dot) dot.style.background = color;
            if (labelEl) {
                labelEl.textContent = label;
                labelEl.style.color = color === '#22c55e' ? '#16a34a' : (color === '#ef4444' ? '#dc2626' : '#333');
            }
            if (detailEl) detailEl.textContent = detail;
        }

        function checkStatus() {
            fetch('{{ route("admin.whatsapp-connect.status") }}')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var state = (data.state || '').toLowerCase();
                    currentState = state;

                    if (state === 'open' || state === 'connected') {
                        showSection('qr-connected');
                        updateStatus(state, 'Connected', 'WhatsApp is linked and active', '#22c55e');
                        stopQrPolling();
                    } else if (state === 'close' || state === 'closed' || state === 'not_connected') {
                        showSection('qr-loading');
                        updateStatus(state, 'Disconnected', 'Scan QR code to connect', '#f59e0b');
                        fetchQrCode();
                        startQrPolling();
                    } else if (state === 'connecting') {
                        updateStatus(state, 'Connecting...', 'Waiting for QR scan', '#3b82f6');
                        if (!qrPollTimer) startQrPolling();
                    } else if (state === 'not_configured') {
                        updateStatus(state, 'Not Configured', 'Admin needs to configure API in Settings', '#ef4444');
                        showSection('qr-error');
                        document.getElementById('qr-error-msg').textContent = 'API credentials not configured.';
                    } else {
                        updateStatus(state, 'Unknown: ' + state, 'Attempting to connect...', '#888');
                        fetchQrCode();
                        if (!qrPollTimer) startQrPolling();
                    }
                })
                .catch(function (err) {
                    console.error('Status check error:', err);
                    updateStatus('error', 'Error', 'Could not reach server', '#ef4444');
                });
        }

        function fetchQrCode() {
            fetch('{{ route("admin.whatsapp-connect.qr") }}')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && data.qrcode) {
                        var img = document.getElementById('qr-image');
                        if (data.qrcode.startsWith('data:')) {
                            img.src = data.qrcode;
                        } else {
                            img.src = 'data:image/png;base64,' + data.qrcode;
                        }
                        showSection('qr-display');
                        updateStatus('scanning', 'Waiting for Scan', 'Scan the QR code with your phone', '#3b82f6');
                    } else if (data.error) {
                        checkStatus();
                    }
                })
                .catch(function (err) {
                    console.error('QR fetch error:', err);
                    showSection('qr-error');
                    document.getElementById('qr-error-msg').textContent = 'Failed to load QR code.';
                    updateStatus('error', 'Error', 'Could not load QR code', '#ef4444');
                });
        }

        function startQrPolling() {
            stopQrPolling();
            statusPollTimer = setInterval(function () { checkStatus(); }, 10000);
            qrPollTimer = setInterval(function () {
                if (currentState !== 'open' && currentState !== 'connected') {
                    fetchQrCode();
                }
            }, 30000);
        }

        function stopQrPolling() {
            if (statusPollTimer) { clearInterval(statusPollTimer); statusPollTimer = null; }
            if (qrPollTimer) { clearInterval(qrPollTimer); qrPollTimer = null; }
        }

        function disconnectWhatsapp() {
            if (!confirm('Are you sure you want to disconnect WhatsApp?')) return;

            var btn = document.getElementById('disconnect-btn');
            btn.textContent = 'Disconnecting...';
            btn.disabled = true;

            fetch('{{ route("admin.whatsapp-connect.disconnect") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        showSection('qr-loading');
                        updateStatus('close', 'Disconnected', 'Scan QR code to reconnect', '#f59e0b');
                        setTimeout(function () { initConnection(); }, 2000);
                    } else {
                        alert('Failed to disconnect: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(function (err) { alert('Error: ' + err.message); })
                .finally(function () {
                    btn.innerHTML = '<i data-lucide="unplug" style="width:16px;height:16px"></i> Disconnect';
                    btn.disabled = false;
                    lucide.createIcons();
                });
        }

        function initConnection() {
            showSection('qr-loading');
            updateStatus('checking', 'Checking...', 'Connecting to server', '#888');
            checkStatus();
            startQrPolling();
        }

        @if($isConfigured)
            document.addEventListener('DOMContentLoaded', function () {
                initConnection();
            });
        @endif
    </script>
@endpush