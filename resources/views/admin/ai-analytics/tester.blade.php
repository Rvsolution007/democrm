@extends('admin.layouts.app')

@section('title', 'AI Bot Tester')
@section('breadcrumb', 'AI Bot Tester')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .terminal-container {
        background-color: #0f172a;
        border-radius: 8px;
        border: 1px solid #1e293b;
        color: #cbd5e1;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 13px;
        line-height: 1.6;
        height: 500px;
        overflow-y: auto;
        padding: 16px;
    }
    .terminal-log { margin-bottom: 8px; }
    .terminal-info { color: #38bdf8; } /* light blue */
    .terminal-success { color: #34d399; } /* emerald */
    .terminal-error { color: #f87171; } /* red */
    .terminal-user { color: #c084fc; font-weight: bold; } /* purple */
    .terminal-bot { color: #fcd34d; } /* amber */
    
    .note-editor.note-frame {
        border: 1px solid var(--border) !important;
        border-radius: 8px;
        overflow: hidden;
    }
    .note-editor .note-toolbar {
        background: var(--surface);
        border-bottom: 1px solid var(--border);
    }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title">AI Bot Diagnostic Tester</h1>
                <p class="page-description">Define conditions and simulate a customer chat to verify if the bot obeys your rules.</p>
            </div>
            <div class="page-actions" style="display: flex; gap: 10px;">
                <button type="button" onclick="saveRules()" class="btn btn-outline" style="display:flex;align-items:center;gap:6px">
                    <i data-lucide="save" style="width:16px;height:16px;"></i> Save Conditions
                </button>
                <button type="button" onclick="runSimulation()" class="btn btn-primary" style="display:flex;align-items:center;gap:6px">
                    <i data-lucide="play" style="width:16px;height:16px;"></i> Run Simulation
                </button>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        
        <!-- Left Column: Rules -->
        <div class="card">
            <div class="card-content">
                <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 8px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="file-text" style="width:18px;height:18px;color:#6366f1;"></i>
                    Testing Conditions
                </h2>
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                    Write exactly how the bot should behave in simple language. The Tester AI reads this prompt to simulate a customer test.
                </p>
                <div id="tester_rules" class="tester-rules-editor">{!! $testerRules !!}</div>
            </div>
        </div>

        <!-- Right Column: Terminal -->
        <div class="card">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h2 style="font-size: 16px; font-weight: 700; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="terminal" style="width:18px;height:18px;color:#10b981;"></i>
                        Diagnostic Output
                    </h2>
                    <div style="display: flex; gap: 6px;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
                        <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span>
                        <span style="width:10px;height:10px;border-radius:50%;background:#10b981;"></span>
                    </div>
                </div>
                
                <div id="terminal-output" class="terminal-container">
                    <div style="color: #64748b; font-size: 12px; margin-bottom: 16px;"># Waiting for simulation to start...</div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

<script>
    function initSummernote() {
        if ($('#tester_rules').length && !$('#tester_rules').next('.note-editor').length) {
            $('#tester_rules').summernote({
                placeholder: 'Write your bot testing instructions here...',
                tabsize: 2,
                height: 400,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        }
    }

    $(document).ready(initSummernote);
    document.addEventListener("turbo:load", initSummernote);

    function saveRules() {
        const rules = $('#tester_rules').summernote('code');
        fetch("{{ route('admin.ai-analytics.tester-rules.save') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ rules: rules })
        }).then(res => res.json())
          .then(data => {
              if (data.success) {
                  alert('Testing conditions saved successfully!');
              } else {
                  alert('Error saving conditions.');
              }
          }).catch(err => {
              console.error(err);
              alert('Error saving conditions.');
          });
    }

    function writeTerminal(text, type = 'info') {
        const terminal = document.getElementById('terminal-output');
        const div = document.createElement('div');
        div.className = `terminal-log terminal-${type}`;
        
        // Convert newlines to html breaks
        let safeHTML = text.replace(/\n/g, '<br>');
        
        // Add timestamp
        const time = new Date().toLocaleTimeString([], {hour12: false});
        div.innerHTML = `<span style="color:#64748b;margin-right:8px;">[${time}]</span> ${safeHTML}`;
        
        terminal.appendChild(div);
        terminal.scrollTop = terminal.scrollHeight;
    }

    function runSimulation() {
        const terminal = document.getElementById('terminal-output');
        terminal.innerHTML = '<div style="color: #64748b; font-size: 12px; margin-bottom: 16px;"># Simulation started...</div>';
        writeTerminal('Connecting to AI Test Runner...', 'info');

        const rules = $('#tester_rules').summernote('code');
        
        // Attempt to save rules before running
        fetch("{{ route('admin.ai-analytics.tester-rules.save') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ rules: rules })
        }).then(() => {
            // Initiate the simulation socket
            fetch("{{ route('admin.ai-analytics.tester.run') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder("utf-8");

                function readChunk() {
                    reader.read().then(({ done, value }) => {
                        if (done) {
                            writeTerminal('--------------------------------', 'info');
                            writeTerminal('Simulation Finished.', 'info');
                            return;
                        }

                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n');
                        
                        lines.forEach(line => {
                            if (!line.trim()) return;
                            try {
                                const data = JSON.parse(line);
                                if (data.type === 'error') writeTerminal('❌ ' + data.message, 'error');
                                else if (data.type === 'success') writeTerminal('✅ ' + data.message, 'success');
                                else if (data.type === 'user') writeTerminal('🧔 User: ' + data.message, 'user');
                                else if (data.type === 'bot') writeTerminal('🤖 Bot: ' + data.message, 'bot');
                                else writeTerminal('ℹ️ ' + data.message, 'info');
                            } catch (e) {
                                writeTerminal(line, 'info');
                            }
                        });
                        readChunk();
                    });
                }
                readChunk();
                
            }).catch(error => {
                writeTerminal('Connection error to test runner.', 'error');
                console.error(error);
            });
        });
    }
</script>
@endpush
