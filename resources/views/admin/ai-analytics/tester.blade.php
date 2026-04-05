@extends('admin.layouts.app')

@section('title', 'AI Bot Tester')
@section('breadcrumb', 'AI Bot Tester')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* ═══ LAYOUT ═══ */
    .tester-grid {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 0;
        height: calc(100vh - 140px);
        min-height: 600px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12);
        border: 1px solid #e2e8f0;
    }

    /* ═══ LEFT PANEL: Question List ═══ */
    .questions-panel {
        background: #fff;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
    }
    .questions-header {
        padding: 16px 18px;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .questions-header h3 {
        font-size: 15px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: white;
    }
    .questions-header .count-badge {
        background: rgba(255,255,255,0.25);
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .questions-body {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
    }
    .question-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        margin-bottom: 6px;
        cursor: default;
        transition: all 0.15s;
        border: 1px solid transparent;
        background: #f8fafc;
    }
    .question-item:hover {
        border-color: #f97316;
        background: #fff7ed;
    }
    .question-number {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800;
        flex-shrink: 0;
    }
    .question-text {
        flex: 1;
        font-size: 13px;
        color: #1e293b;
        word-break: break-word;
        font-family: 'Inter', sans-serif;
    }
    .question-delete {
        width: 26px; height: 26px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.15s;
        flex-shrink: 0;
    }
    .question-delete:hover { background: #fee2e2; color: #ef4444; }

    .questions-footer {
        padding: 12px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .add-row {
        display: flex;
        gap: 6px;
    }
    .add-row input {
        flex: 1;
        padding: 9px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        background: #fff;
        color: #1e293b;
        outline: none;
        transition: border-color 0.15s;
        font-family: 'Inter', sans-serif;
    }
    .add-row input:focus { border-color: #f97316; }
    .add-row input::placeholder { color: #94a3b8; }
    .btn-add {
        width: 38px; height: 38px;
        border-radius: 8px;
        border: none;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: transform 0.1s;
    }
    .btn-add:hover { transform: scale(1.05); }
    .btn-add:active { transform: scale(0.95); }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    .empty-state p:first-child { font-size: 14px; font-weight: 600; }
    .empty-state p:last-child { font-size: 12px; margin-top: 4px; }

    /* Run Test Button */
    .btn-run-test {
        width: 100%;
        padding: 10px;
        border: none;
        border-radius: 8px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 8px;
        transition: all 0.15s;
        font-family: 'Inter', sans-serif;
    }
    .btn-run-test:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .btn-run-test:active { transform: translateY(0); }
    .btn-run-test:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    /* ═══ RIGHT PANEL: WhatsApp Chat ═══ */
    .whatsapp-panel {
        display: flex;
        flex-direction: column;
        background: #efeae2;
    }
    /* WhatsApp Header */
    .wa-header {
        background: linear-gradient(135deg, #075e54, #128c7e);
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: white;
        flex-shrink: 0;
    }
    .wa-avatar {
        width: 42px; height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #25d366, #128c7e);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .wa-info h4 {
        font-size: 15px; font-weight: 700; color: white; margin: 0;
    }
    .wa-info p {
        font-size: 11px; color: rgba(255,255,255,0.8); margin: 2px 0 0 0;
    }
    .wa-header-actions {
        margin-left: auto;
        display: flex;
        gap: 8px;
    }
    .wa-header-btn {
        width: 34px; height: 34px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        border: none;
        color: white;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s;
    }
    .wa-header-btn:hover { background: rgba(255,255,255,0.25); }

    /* Chat Area */
    .wa-chat {
        flex: 1;
        overflow-y: auto;
        padding: 16px 60px;
        background: url("data:image/svg+xml,%3Csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='p' patternUnits='userSpaceOnUse' width='40' height='40'%3E%3Cpath d='M0 20h40M20 0v40' stroke='%23d5cec5' stroke-width='0.3' fill='none'/%3E%3C/pattern%3E%3C/defs%3E%3Crect fill='%23efeae2' width='200' height='200'/%3E%3Crect fill='url(%23p)' width='200' height='200'/%3E%3C/svg%3E");
        display: flex;
        flex-direction: column;
    }
    .wa-chat::-webkit-scrollbar { width: 6px; }
    .wa-chat::-webkit-scrollbar-track { background: transparent; }
    .wa-chat::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 3px; }

    /* Chat Bubbles */
    .wa-msg {
        max-width: 65%;
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 4px;
        position: relative;
        font-size: 13.5px;
        line-height: 1.45;
        font-family: 'Inter', sans-serif;
        word-wrap: break-word;
        animation: msgIn 0.2s ease-out;
        box-shadow: 0 1px 1px rgba(0,0,0,0.08);
    }
    @keyframes msgIn {
        from { opacity: 0; transform: translateY(8px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .wa-msg-user {
        background: #dcf8c6;
        align-self: flex-end;
        border-top-right-radius: 0;
    }
    .wa-msg-bot {
        background: #ffffff;
        align-self: flex-start;
        border-top-left-radius: 0;
    }
    .wa-msg-time {
        font-size: 10px;
        color: #667781;
        text-align: right;
        margin-top: 3px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
    }
    .wa-msg-user .wa-msg-time { color: #4a7c59; }
    .wa-msg .wa-ticks {
        color: #53bdeb;
        font-size: 13px;
    }
    .wa-msg-content { white-space: pre-wrap; }
    .wa-msg-content b, .wa-msg-content strong { font-weight: 700; }

    /* System message */
    .wa-system-msg {
        align-self: center;
        background: #e2dacd;
        color: #54656f;
        padding: 5px 14px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        margin: 12px 0;
        box-shadow: none;
        text-align: center;
        max-width: 80%;
    }

    /* Typing indicator */
    .wa-typing {
        background: #ffffff;
        align-self: flex-start;
        border-top-left-radius: 0;
        padding: 12px 18px;
        display: none;
    }
    .wa-typing-dots {
        display: flex; gap: 4px;
    }
    .wa-typing-dots span {
        width: 7px; height: 7px;
        border-radius: 50%;
        background: #92a0a8;
        animation: typingBounce 1.4s infinite;
    }
    .wa-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .wa-typing-dots span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingBounce {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30% { transform: translateY(-5px); opacity: 1; }
    }

    /* Welcome */
    .wa-welcome {
        align-self: center;
        text-align: center;
        padding: 40px;
        color: #8696a0;
    }
    .wa-welcome-icon {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #25d366, #128c7e);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
        font-size: 36px;
    }
    .wa-welcome h5 { font-size: 16px; font-weight: 700; color: #41525d; margin: 0 0 6px; }
    .wa-welcome p { font-size: 13px; margin: 0; }
</style>
@endpush

@section('content')
    <div class="tester-grid">
        {{-- ═══ LEFT: Questions Panel ═══ --}}
        <div class="questions-panel">
            <div class="questions-header">
                <h3>
                    <i data-lucide="list-ordered" style="width:18px;height:18px"></i>
                    Test Questions
                </h3>
                <span class="count-badge" id="question-count">{{ count($questions) }}</span>
            </div>

            <div class="questions-body" id="question-list">
                @if($questions->isEmpty())
                    <div class="empty-state" id="empty-state">
                        <p>No questions yet</p>
                        <p>Type below and press Enter</p>
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

            <div class="questions-footer">
                <div class="add-row">
                    <input type="text" id="new-question-input"
                           placeholder="Type a question..."
                           onkeydown="if(event.key==='Enter'){addQuestion();}">
                    <button class="btn-add" onclick="addQuestion()" title="Add Question">
                        <i data-lucide="plus" style="width:18px;height:18px;"></i>
                    </button>
                </div>
                <button type="button" class="btn-run-test" id="btn-run" onclick="runTest()">
                    <i data-lucide="play" style="width:16px;height:16px;"></i>
                    Run Test
                </button>
                <span id="save-indicator" style="display:none; font-size: 11px; color: #10b981; font-weight: 600; text-align:center; display:block; margin-top:6px;"></span>
            </div>
        </div>

        {{-- ═══ RIGHT: WhatsApp Chat ═══ --}}
        <div class="whatsapp-panel">
            <div class="wa-header">
                <div class="wa-avatar">🤖</div>
                <div class="wa-info">
                    <h4>AI Bot Tester</h4>
                    <p id="wa-status">Click "Run Test" to start</p>
                </div>
                <div class="wa-header-actions">
                    <button class="wa-header-btn" onclick="clearChat()" title="Clear Chat">
                        <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                    </button>
                </div>
            </div>

            <div class="wa-chat" id="wa-chat">
                <div class="wa-welcome" id="wa-welcome">
                    <div class="wa-welcome-icon">💬</div>
                    <h5>AI Bot Conversation Tester</h5>
                    <p>Add questions on the left, click "Run Test".<br>Bot will answer each one — same as real WhatsApp.</p>
                </div>

                {{-- Typing indicator --}}
                <div class="wa-msg wa-typing" id="wa-typing">
                    <div class="wa-typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // ═══ QUESTION MANAGEMENT ═══
    function addQuestion() {
        const input = document.getElementById('new-question-input');
        const text = input.value.trim();
        if (!text) return;

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
        autoSaveQuestions();
    }

    function removeQuestion(btn) {
        btn.closest('.question-item').remove();
        updateQuestionNumbers();
        const list = document.getElementById('question-list');
        if (list.querySelectorAll('.question-item').length === 0) {
            list.innerHTML = `<div class="empty-state" id="empty-state"><p>No questions yet</p><p>Type below and press Enter</p></div>`;
        }
        autoSaveQuestions();
    }

    function updateQuestionNumbers() {
        const items = document.querySelectorAll('#question-list .question-item');
        items.forEach((item, i) => {
            item.querySelector('.question-number').textContent = i + 1;
        });
        document.getElementById('question-count').textContent = items.length;
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

    // ═══ AUTO-SAVE ═══
    let saveTimeout = null;
    function autoSaveQuestions() {
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const questions = getQuestions();
            const indicator = document.getElementById('save-indicator');

            if (questions.length === 0) {
                fetch("{{ route('admin.ai-analytics.test-questions.save') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ questions: ['placeholder'] })
                }).catch(() => {});
                return;
            }

            if (indicator) { indicator.style.display = 'block'; indicator.textContent = '💾 Saving...'; }

            fetch("{{ route('admin.ai-analytics.test-questions.save') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ questions })
            }).then(res => res.json()).then(() => {
                if (indicator) { indicator.textContent = '✅ Saved!'; setTimeout(() => { indicator.style.display = 'none'; }, 1200); }
            }).catch(() => {
                if (indicator) { indicator.textContent = '❌ Save failed'; setTimeout(() => { indicator.style.display = 'none'; }, 2000); }
            });
        }, 300);
    }

    // ═══ WHATSAPP CHAT HELPERS ═══
    function getTimeStr() {
        return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }).toLowerCase();
    }

    function addUserMessage(text) {
        const chat = document.getElementById('wa-chat');
        const typing = document.getElementById('wa-typing');
        const msg = document.createElement('div');
        msg.className = 'wa-msg wa-msg-user';
        msg.innerHTML = `
            <div class="wa-msg-content">${escapeHtml(text)}</div>
            <div class="wa-msg-time">${getTimeStr()} <span class="wa-ticks">✓✓</span></div>
        `;
        chat.insertBefore(msg, typing);
        chat.scrollTop = chat.scrollHeight;
    }

    function addBotMessage(text) {
        const chat = document.getElementById('wa-chat');
        const typing = document.getElementById('wa-typing');

        // Format WhatsApp style: *bold* → <b>bold</b>
        let formatted = escapeHtml(text)
            .replace(/\*([^*]+)\*/g, '<b>$1</b>')
            .replace(/_([^_]+)_/g, '<i>$1</i>');

        const msg = document.createElement('div');
        msg.className = 'wa-msg wa-msg-bot';
        msg.innerHTML = `
            <div class="wa-msg-content">${formatted}</div>
            <div class="wa-msg-time">${getTimeStr()}</div>
        `;
        chat.insertBefore(msg, typing);
        chat.scrollTop = chat.scrollHeight;
    }

    function addSystemMessage(text) {
        const chat = document.getElementById('wa-chat');
        const typing = document.getElementById('wa-typing');
        const msg = document.createElement('div');
        msg.className = 'wa-msg wa-system-msg';
        msg.textContent = text;
        chat.insertBefore(msg, typing);
        chat.scrollTop = chat.scrollHeight;
    }

    function showTyping(show) {
        const typing = document.getElementById('wa-typing');
        typing.style.display = show ? 'block' : 'none';
        if (show) {
            const chat = document.getElementById('wa-chat');
            chat.scrollTop = chat.scrollHeight;
        }
    }

    function clearChat() {
        const chat = document.getElementById('wa-chat');
        const typing = document.getElementById('wa-typing');
        chat.innerHTML = '';
        chat.appendChild(typing);

        const welcome = document.createElement('div');
        welcome.className = 'wa-welcome';
        welcome.id = 'wa-welcome';
        welcome.innerHTML = `<div class="wa-welcome-icon">💬</div><h5>AI Bot Conversation Tester</h5><p>Add questions on the left, click "Run Test".<br>Bot will answer each one — same as real WhatsApp.</p>`;
        chat.insertBefore(welcome, typing);

        document.getElementById('wa-status').textContent = 'Click "Run Test" to start';
    }

    // ═══ RUN TEST ═══
    function runTest() {
        const questions = getQuestions();
        if (questions.length === 0) {
            alert('Add at least one question first!');
            return;
        }

        const btn = document.getElementById('btn-run');
        btn.disabled = true;
        btn.innerHTML = '<span style="display:inline-flex;gap:4px;align-items:center"><span class="wa-typing-dots"><span></span><span></span><span></span></span> Testing...</span>';

        // Clear chat
        const chat = document.getElementById('wa-chat');
        const typing = document.getElementById('wa-typing');
        chat.innerHTML = '';
        chat.appendChild(typing);

        document.getElementById('wa-status').textContent = 'Testing...';
        addSystemMessage(`🧪 Test started — ${questions.length} questions`);

        // Save questions first
        fetch("{{ route('admin.ai-analytics.test-questions.save') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ questions })
        }).then(() => {
            // Stream test results
            fetch("{{ route('admin.ai-analytics.conversation-test.run') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder("utf-8");
                let questionIndex = 0;

                function readChunk() {
                    reader.read().then(({ done, value }) => {
                        if (done) {
                            showTyping(false);
                            addSystemMessage('✅ Test complete!');
                            document.getElementById('wa-status').textContent = 'Test complete';
                            btn.disabled = false;
                            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> Run Test';
                            lucide.createIcons();
                            return;
                        }

                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n');

                        lines.forEach(line => {
                            if (!line.trim()) return;
                            try {
                                const data = JSON.parse(line);
                                if (data.type === 'user') {
                                    showTyping(false);
                                    questionIndex++;
                                    document.getElementById('wa-status').textContent = `Processing ${questionIndex}/${questions.length}...`;
                                    addUserMessage(data.message);
                                    // Show typing after user message
                                    setTimeout(() => showTyping(true), 200);
                                } else if (data.type === 'bot') {
                                    showTyping(false);
                                    addBotMessage(data.message);
                                } else if (data.type === 'error') {
                                    showTyping(false);
                                    addSystemMessage('❌ ' + data.message);
                                } else if (data.type === 'info') {
                                    addSystemMessage('ℹ️ ' + data.message);
                                } else if (data.type === 'success') {
                                    addSystemMessage('✅ ' + data.message);
                                }
                            } catch (e) {
                                // Non-JSON lines
                            }
                        });
                        readChunk();
                    });
                }
                readChunk();
            }).catch(err => {
                showTyping(false);
                addSystemMessage('❌ Connection error: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> Run Test';
                lucide.createIcons();
            });
        }).catch(err => {
            addSystemMessage('❌ Failed to save questions');
            btn.disabled = false;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg> Run Test';
            lucide.createIcons();
        });
    }
</script>
@endpush
