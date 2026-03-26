@extends('admin.layouts.app')

@section('title', 'AI Bot Tester')
@section('breadcrumb', 'AI Bot Tester')

@push('styles')
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
    .terminal-info { color: #38bdf8; }
    .terminal-success { color: #34d399; }
    .terminal-error { color: #f87171; }
    .terminal-user { color: #c084fc; font-weight: bold; }
    .terminal-bot { color: #fcd34d; }

    /* Question List */
    .question-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 420px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .question-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 10px 12px;
        transition: all 0.15s;
    }
    .question-item:hover {
        border-color: var(--primary);
        background: color-mix(in srgb, var(--primary) 5%, var(--surface));
    }
    .question-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .question-text {
        flex: 1;
        font-size: 14px;
        color: var(--text);
        word-break: break-word;
    }
    .question-delete {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }
    .question-delete:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    /* Add Question Input */
    .add-question-row {
        display: flex;
        gap: 8px;
        margin-top: 12px;
    }
    .add-question-row input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
        background: var(--surface);
        color: var(--text);
        outline: none;
        transition: border-color 0.15s;
    }
    .add-question-row input:focus {
        border-color: var(--primary);
    }
    .add-question-row input::placeholder {
        color: var(--text-muted);
    }

    /* Section Separator */
    .section-separator {
        border: none;
        border-top: 2px dashed var(--border);
        margin: 32px 0;
    }

    /* Section Header */
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .section-header h2 {
        font-size: 20px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-blue {
        background: #dbeafe;
        color: #2563eb;
    }
    .badge-orange {
        background: #ffedd5;
        color: #ea580c;
    }

    /* Empty state */
    .empty-questions {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-muted);
    }
    .empty-questions i {
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
        opacity: 0.3;
    }

    /* Progress indicator */
    .running-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 20px;
        background: #1e293b;
        color: #34d399;
        font-size: 12px;
        font-weight: 600;
    }
    .running-indicator .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #34d399;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
</style>
@endpush

@section('content')
    {{-- ═══════════════════════════════════════════════════════
         SECTION 1: AI Bot Conversation Test
         ═══════════════════════════════════════════════════════ --}}
    <div class="section-header">
        <div>
            <h2>
                <i data-lucide="message-circle" style="width:24px;height:24px;color:#6366f1;"></i>
                AI Bot Conversation Test
                <span class="section-badge badge-blue">Section 1</span>
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                Add your test questions below. The bot will answer them exactly like on WhatsApp.
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span id="save-indicator" style="display:none; font-size: 13px; color: #10b981; font-weight: 600;"></span>
            <button type="button" onclick="runConversationTest()" id="btn-conv-test" class="btn btn-primary" style="display:flex;align-items:center;gap:6px">
                <i data-lucide="play" style="width:16px;height:16px;"></i> Run Test
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        {{-- Left Column: Questions --}}
        <div class="card">
            <div class="card-content">
                <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 4px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="list-ordered" style="width:16px;height:16px;color:#6366f1;"></i>
                    Test Questions
                    <span id="question-count" style="font-size: 12px; color: var(--text-muted); font-weight: 400;">
                        ({{ count($questions) }} questions)
                    </span>
                </h3>
                <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">
                    Add questions the bot will be asked in order. Saved to database — runs every time.
                </p>

                <div id="question-list" class="question-list">
                    @if($questions->isEmpty())
                        <div class="empty-questions" id="empty-state">
                            <i data-lucide="message-square-plus"></i>
                            <p style="font-size: 14px; font-weight: 600;">No questions yet</p>
                            <p style="font-size: 12px;">Type a question below and press Enter or click +</p>
                        </div>
                    @else
                        @foreach($questions as $i => $q)
                            <div class="question-item" data-index="{{ $i }}">
                                <span class="question-number">{{ $i + 1 }}</span>
                                <span class="question-text">{{ $q->question }}</span>
                                <button class="question-delete" onclick="removeQuestion(this)" title="Remove">
                                    <i data-lucide="x" style="width:14px;height:14px;"></i>
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="add-question-row">
                    <input type="text" id="new-question-input" placeholder="Type a question (e.g., Hi, products dikhao, 1...)" 
                           onkeydown="if(event.key==='Enter'){addQuestion();}">
                    <button type="button" onclick="addQuestion()" class="btn btn-primary" style="padding: 10px 16px;">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Right Column: Conversation Output --}}
        <div class="card">
            <div class="card-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-size: 15px; font-weight: 700; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="terminal" style="width:16px;height:16px;color:#10b981;"></i>
                        Conversation Output
                    </h3>
                    <div style="display: flex; gap: 6px;">
                        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
                        <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span>
                        <span style="width:10px;height:10px;border-radius:50%;background:#10b981;"></span>
                    </div>
                </div>
                <div id="conv-terminal" class="terminal-container">
                    <div style="color: #64748b; font-size: 12px;">
                        # Add questions and click "Run Test" to start...<br>
                        # Bot will answer each question sequentially — same as WhatsApp.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="section-separator">

    {{-- ═══════════════════════════════════════════════════════
         SECTION 2: AI Bot Diagnostic Tester
         ═══════════════════════════════════════════════════════ --}}
    <div class="section-header">
        <div>
            <h2>
                <i data-lucide="stethoscope" style="width:24px;height:24px;color:#ea580c;"></i>
                AI Bot Diagnostic Tester
                <span class="section-badge badge-orange">Section 2</span>
            </h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                Technically tests every step of the bot flow — greeting, product inquiry, confirmation, chatflow progress, language, etc.
            </p>
        </div>
        <div>
            <button type="button" onclick="runDiagnosticTest()" id="btn-diag-test" class="btn btn-primary" style="display:flex;align-items:center;gap:6px;background:linear-gradient(135deg,#ea580c,#f97316);">
                <i data-lucide="play" style="width:16px;height:16px;"></i> Run Diagnostic
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 15px; font-weight: 700; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="activity" style="width:16px;height:16px;color:#ea580c;"></i>
                    Diagnostic Output
                    <span id="diag-status" style="display:none;" class="running-indicator">
                        <span class="dot"></span> Running...
                    </span>
                </h3>
                <div style="display: flex; gap: 6px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
                    <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></span>
                    <span style="width:10px;height:10px;border-radius:50%;background:#10b981;"></span>
                </div>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px; padding: 10px 14px; background: color-mix(in srgb, var(--primary) 5%, var(--surface)); border-radius: 8px; border: 1px solid var(--border);">
                <strong>What this tests:</strong> Config checks → Module health → Greeting detection → Business queries → Product inquiry vs confirmation → Spell correction → Lead/Quote creation → Chatflow progress → Language matching → Optional question handling → Product add/edit/delete → AI Summary Report
            </div>
            <div id="diag-terminal" class="terminal-container" style="height: 600px;">
                <div style="color: #64748b; font-size: 12px;">
                    # Uses questions from Section 1 above.<br>
                    # Click "Run Diagnostic" to start full process flow test...<br>
                    # Each question is classified, sent to bot, then every response is validated.
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // ═══════════════════════════════════════════════════════
    // QUESTION MANAGEMENT
    // ═══════════════════════════════════════════════════════

    function addQuestion() {
        const input = document.getElementById('new-question-input');
        const text = input.value.trim();
        if (!text) return;

        // Remove empty state if exists
        const emptyState = document.getElementById('empty-state');
        if (emptyState) emptyState.remove();

        const list = document.getElementById('question-list');
        const count = list.querySelectorAll('.question-item').length + 1;

        const item = document.createElement('div');
        item.className = 'question-item';
        item.innerHTML = `
            <span class="question-number">${count}</span>
            <span class="question-text">${escapeHtml(text)}</span>
            <button class="question-delete" onclick="removeQuestion(this)" title="Remove">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;
        list.appendChild(item);
        input.value = '';
        input.focus();

        updateQuestionNumbers();
        lucide.createIcons();

        // Auto-save to DB immediately
        autoSaveQuestions();
    }

    function removeQuestion(btn) {
        btn.closest('.question-item').remove();
        updateQuestionNumbers();

        const list = document.getElementById('question-list');
        if (list.querySelectorAll('.question-item').length === 0) {
            list.innerHTML = `
                <div class="empty-questions" id="empty-state">
                    <p style="font-size: 14px; font-weight: 600;">No questions yet</p>
                    <p style="font-size: 12px;">Type a question below and press Enter or click +</p>
                </div>
            `;
        }

        // Auto-save to DB immediately
        autoSaveQuestions();
    }

    function updateQuestionNumbers() {
        const items = document.querySelectorAll('#question-list .question-item');
        items.forEach((item, i) => {
            item.querySelector('.question-number').textContent = i + 1;
        });
        document.getElementById('question-count').textContent = `(${items.length} questions)`;
    }

    function getQuestions() {
        const items = document.querySelectorAll('#question-list .question-item');
        return Array.from(items).map(item => item.querySelector('.question-text').textContent.trim());
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ═══════════════════════════════════════════════════════
    // AUTO-SAVE QUESTIONS (called on every add/remove)
    // ═══════════════════════════════════════════════════════

    let saveTimeout = null;
    function autoSaveQuestions() {
        // Debounce — wait 300ms in case user is rapid-adding
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const questions = getQuestions();
            const saveIndicator = document.getElementById('save-indicator');
            
            if (questions.length === 0) {
                // Delete all from DB
                fetch("{{ route('admin.ai-analytics.test-questions.save') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ questions: ['placeholder'] }) // Will be overwritten
                }).catch(() => {});
                return;
            }

            if (saveIndicator) {
                saveIndicator.style.display = 'inline';
                saveIndicator.textContent = '💾 Saving...';
            }

            fetch("{{ route('admin.ai-analytics.test-questions.save') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ questions: questions })
            }).then(res => res.json())
              .then(data => {
                  if (saveIndicator) {
                      saveIndicator.textContent = '✅ Saved!';
                      setTimeout(() => { saveIndicator.style.display = 'none'; }, 1500);
                  }
              }).catch(err => {
                  console.error('Auto-save failed:', err);
                  if (saveIndicator) {
                      saveIndicator.textContent = '❌ Save failed';
                      setTimeout(() => { saveIndicator.style.display = 'none'; }, 2000);
                  }
              });
        }, 300);
    }

    // Manual save button (kept as backup)
    function saveQuestions() {
        autoSaveQuestions();
    }

    // ═══════════════════════════════════════════════════════
    // TERMINAL HELPER
    // ═══════════════════════════════════════════════════════

    function writeToTerminal(terminalId, text, type = 'info') {
        const terminal = document.getElementById(terminalId);
        const div = document.createElement('div');
        div.className = `terminal-log terminal-${type}`;

        let safeHTML = escapeHtml(text).replace(/\n/g, '<br>');
        const time = new Date().toLocaleTimeString([], {hour12: false});
        div.innerHTML = `<span style="color:#64748b;margin-right:8px;">[${time}]</span> ${safeHTML}`;

        terminal.appendChild(div);
        terminal.scrollTop = terminal.scrollHeight;
    }

    function streamResponse(url, terminalId, btnId, statusId) {
        const terminal = document.getElementById(terminalId);
        const btn = document.getElementById(btnId);
        const status = statusId ? document.getElementById(statusId) : null;

        // Clear terminal
        terminal.innerHTML = '<div style="color: #64748b; font-size: 12px; margin-bottom: 16px;"># Started...</div>';
        btn.disabled = true;
        btn.style.opacity = '0.6';
        if (status) status.style.display = 'inline-flex';

        // Auto-save questions before running
        const questions = getQuestions();
        if (questions.length === 0) {
            writeToTerminal(terminalId, '❌ No test questions! Add questions first.', 'error');
            btn.disabled = false;
            btn.style.opacity = '1';
            if (status) status.style.display = 'none';
            return;
        }

        // Save questions first, then run
        fetch("{{ route('admin.ai-analytics.test-questions.save') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ questions: questions })
        }).then(() => {
            writeToTerminal(terminalId, 'Questions saved. Starting...', 'info');

            fetch(url, {
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
                            writeToTerminal(terminalId, '────────────────────────────────', 'info');
                            writeToTerminal(terminalId, 'Finished.', 'info');
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            if (status) status.style.display = 'none';
                            return;
                        }

                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n');

                        lines.forEach(line => {
                            if (!line.trim()) return;
                            try {
                                const data = JSON.parse(line);
                                if (data.type === 'error') writeToTerminal(terminalId, '❌ ' + data.message, 'error');
                                else if (data.type === 'success') writeToTerminal(terminalId, '✅ ' + data.message, 'success');
                                else if (data.type === 'user') writeToTerminal(terminalId, '🧔 User: ' + data.message, 'user');
                                else if (data.type === 'bot') writeToTerminal(terminalId, '🤖 Bot: ' + data.message, 'bot');
                                else writeToTerminal(terminalId, 'ℹ️ ' + data.message, 'info');
                            } catch (e) {
                                writeToTerminal(terminalId, line, 'info');
                            }
                        });
                        readChunk();
                    });
                }
                readChunk();

            }).catch(error => {
                writeToTerminal(terminalId, 'Connection error.', 'error');
                console.error(error);
                btn.disabled = false;
                btn.style.opacity = '1';
                if (status) status.style.display = 'none';
            });
        }).catch(err => {
            writeToTerminal(terminalId, 'Failed to save questions: ' + err.message, 'error');
            btn.disabled = false;
            btn.style.opacity = '1';
            if (status) status.style.display = 'none';
        });
    }

    // ═══════════════════════════════════════════════════════
    // RUN TESTS
    // ═══════════════════════════════════════════════════════

    function runConversationTest() {
        streamResponse(
            "{{ route('admin.ai-analytics.conversation-test.run') }}",
            'conv-terminal',
            'btn-conv-test',
            null
        );
    }

    function runDiagnosticTest() {
        streamResponse(
            "{{ route('admin.ai-analytics.diagnostic-test.run') }}",
            'diag-terminal',
            'btn-diag-test',
            'diag-status'
        );
    }
</script>
@endpush
