@extends('admin.layouts.app')

@section('title', 'AI Bot Tester')
@section('breadcrumb', 'AI Bot Tester')

@push('styles')
<style>
    /* ═══ FULL HEIGHT GRID ═══ */
    .tester-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 0;
        height: calc(100vh - 130px);
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    /* ═══ LEFT: Questions Panel ═══ */
    .q-panel {
        background: #fff;
        border-right: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .q-header {
        padding: 14px 16px;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: #fff;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .q-header h3 { font-size: 14px; font-weight: 700; margin: 0; color: #fff; }
    .q-header .badge { background: rgba(255,255,255,.25); padding: 2px 9px; border-radius: 10px; font-size: 11px; font-weight: 700; margin-left: auto; }

    .q-body {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
        min-height: 0;
    }
    .q-body::-webkit-scrollbar { width: 4px; }
    .q-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 2px; }

    .q-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        border-radius: 8px;
        margin-bottom: 4px;
        background: #f8fafc;
        border: 1px solid transparent;
        transition: all .12s;
    }
    .q-item:hover { border-color: #f97316; background: #fff7ed; }
    .q-num {
        width: 24px; height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800; flex-shrink: 0;
    }
    .q-text { flex: 1; font-size: 13px; color: #1e293b; word-break: break-word; }
    .q-text .dyn-tag { background: #dbeafe; color: #1d4ed8; font-size: 11px; padding: 1px 5px; border-radius: 4px; font-weight: 600; font-family: monospace; }
    .q-del {
        width: 22px; height: 22px; border-radius: 5px; border: none;
        background: transparent; color: #a0aec0; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: all .12s;
    }
    .q-del:hover { background: #fee2e2; color: #ef4444; }

    .q-empty { text-align: center; padding: 50px 16px; color: #a0aec0; font-size: 13px; }

    .q-footer {
        padding: 10px;
        border-top: 1px solid #e2e8f0;
        background: #fafafa;
        flex-shrink: 0;
    }
    .q-add { display: flex; gap: 6px; }
    .q-add input {
        flex: 1; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
        font-size: 13px; background: #fff; color: #1e293b; outline: none;
    }
    .q-add input:focus { border-color: #f97316; }
    .q-add input::placeholder { color: #a0aec0; }

    /* Dynamic placeholder chips */
    .q-chips { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px; }
    .q-chip {
        padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
        border: 1px solid #e2e8f0; background: #f8fafc; color: #475569;
        cursor: pointer; transition: all .12s; font-family: monospace;
    }
    .q-chip:hover { background: #dbeafe; color: #1d4ed8; border-color: #93c5fd; }
    .q-chip-help {
        padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 700;
        background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; cursor: help;
    }
    /* Repeat Marker */
    .q-repeat-marker {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 12px; margin-bottom: 4px;
        border: 2px dashed #f97316; border-radius: 8px;
        background: #fff7ed; color: #ea580c;
        font-size: 11px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px;
    }
    .q-repeat-marker .q-del { color: #ea580c; }
    .q-repeat-marker .q-del:hover { background: #fee2e2; color: #ef4444; }
    .q-add-btn {
        width: 36px; height: 36px; border-radius: 8px; border: none;
        background: #f97316; color: #fff; cursor: pointer;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .q-add-btn:hover { background: #ea580c; }

    .q-run {
        width: 100%; padding: 9px; border: none; border-radius: 8px;
        background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff;
        font-weight: 700; font-size: 13px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        margin-top: 8px; transition: all .12s;
    }
    .q-run:hover { filter: brightness(1.08); }
    .q-run:disabled { opacity: .5; cursor: not-allowed; }

    .q-save { font-size: 11px; text-align: center; margin-top: 6px; color: #10b981; font-weight: 600; min-height: 16px; }

    /* ═══ RIGHT: WhatsApp Panel ═══ */
    .wa {
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: #efeae2;
    }

    /* Header - WhatsApp Web style */
    .wa-hdr {
        background: #f0f2f5;
        border-bottom: 1px solid #d1d7db;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    .wa-hdr-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        background: #dfe5e7;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
    }
    .wa-hdr-info h4 { font-size: 15px; font-weight: 600; color: #111b21; margin: 0; }
    .wa-hdr-info p { font-size: 12px; color: #667781; margin: 1px 0 0; }
    .wa-hdr-actions { margin-left: auto; display: flex; gap: 6px; }
    .wa-hdr-btn {
        width: 34px; height: 34px; border-radius: 50%; background: transparent;
        border: none; color: #54656f; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
    }
    .wa-hdr-btn:hover { background: rgba(0,0,0,.05); }

    /* Chat body - CRITICAL: flex + min-height:0 for scroll */
    .wa-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 20px 60px;
        background-color: #efeae2;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 302.63 302.63' fill='%23ddd7cd' opacity='0.4'%3E%3Cpath d='M20 20h10v10H20zM50 10h10v10H50zM80 25h10v10H80zM110 5h10v10h-10zM140 20h10v10h-10zM170 10h10v10h-10zM200 30h10v10h-10zM230 15h10v10h-10zM260 5h10v10h-10zM290 25h10v10h-10zM10 50h10v10H10zM40 70h10v10H40zM70 55h10v10H70zM100 45h10v10h-10zM130 65h10v10h-10zM160 50h10v10h-10zM190 70h10v10h-10zM220 45h10v10h-10zM250 60h10v10h-10zM280 50h10v10h-10z'/%3E%3C/svg%3E");
        display: flex;
        flex-direction: column;
    }
    .wa-body::-webkit-scrollbar { width: 6px; }
    .wa-body::-webkit-scrollbar-track { background: transparent; }
    .wa-body::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 3px; }

    /* Message bubbles */
    .msg {
        max-width: 62%;
        padding: 6px 8px 4px;
        margin-bottom: 2px;
        position: relative;
        font-size: 14.2px;
        line-height: 19px;
        word-wrap: break-word;
        white-space: pre-wrap;
        animation: fadeUp .18s ease-out;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* User (right, green) */
    .msg-u {
        align-self: flex-end;
        background: #d9fdd3;
        border-radius: 8px 0 8px 8px;
        box-shadow: 0 1px .5px rgba(11,20,26,.13);
    }
    .msg-u::before {
        content: '';
        position: absolute;
        top: 0; right: -8px;
        width: 0; height: 0;
        border-left: 8px solid #d9fdd3;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
    }

    /* Bot (left, white) */
    .msg-b {
        align-self: flex-start;
        background: #fff;
        border-radius: 0 8px 8px 8px;
        box-shadow: 0 1px .5px rgba(11,20,26,.13);
    }
    .msg-b::before {
        content: '';
        position: absolute;
        top: 0; left: -8px;
        width: 0; height: 0;
        border-right: 8px solid #fff;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
    }

    .msg-text { color: #111b21; }
    .msg-text b, .msg-text strong { font-weight: 600; }

    .msg-meta {
        float: right;
        margin: -4px 0 -4px 12px;
        padding-top: 4px;
        font-size: 11px;
        color: #667781;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        position: relative;
        top: 4px;
    }
    .msg-u .msg-meta { color: #4a8e5c; }
    .msg-ticks { color: #53bdeb; font-size: 14px; letter-spacing: -3px; }

    /* System (center) */
    .msg-sys {
        align-self: center;
        background: #fdf4c5;
        color: #54656f;
        padding: 5px 14px;
        border-radius: 8px;
        font-size: 12px;
        margin: 8px 0;
        box-shadow: 0 1px .5px rgba(11,20,26,.1);
        text-align: center;
        max-width: 85%;
    }

    /* Typing dots */
    .msg-typing {
        align-self: flex-start;
        background: #fff;
        border-radius: 0 8px 8px 8px;
        padding: 10px 16px;
        display: none;
        box-shadow: 0 1px .5px rgba(11,20,26,.13);
    }
    .msg-typing::before {
        content: '';
        position: absolute;
        top: 0; left: -8px;
        width: 0; height: 0;
        border-right: 8px solid #fff;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
    }
    .dots { display: flex; gap: 3px; }
    .dots span {
        width: 7px; height: 7px; border-radius: 50%;
        background: #8696a0; animation: dotBounce 1.2s infinite;
    }
    .dots span:nth-child(2) { animation-delay: .15s; }
    .dots span:nth-child(3) { animation-delay: .3s; }
    @keyframes dotBounce {
        0%, 60%, 100% { transform: translateY(0); opacity: .4; }
        30% { transform: translateY(-4px); opacity: 1; }
    }

    /* Spacer to push messages up initially */
    .wa-spacer { flex: 1; }
</style>
@endpush

@section('content')
    <div class="tester-grid">
        {{-- LEFT: Questions --}}
        <div class="q-panel">
            <div class="q-header">
                <i data-lucide="list-ordered" style="width:18px;height:18px"></i>
                <h3>Test Questions</h3>
                <span class="badge" id="q-count">{{ count($questions) }}</span>
            </div>
            <div class="q-body" id="q-list">
                @if($questions->isEmpty())
                    <div class="q-empty" id="q-empty">No questions yet.<br>Type below to add.</div>
                @else
                    @foreach($questions as $i => $q)
                        @if(trim($q->question) === '🔄')
                            <div class="q-item q-repeat-marker">
                                <span style="font-size:16px">🔄</span>
                                <span class="q-text" style="color:#ea580c">── REPEAT BELOW PER QUEUE ──</span>
                                <button class="q-del" onclick="delQ(this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                            </div>
                        @else
                            <div class="q-item">
                                <span class="q-num">{{ $i + 1 }}</span>
                                <span class="q-text">{{ $q->question }}</span>
                                <button class="q-del" onclick="delQ(this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
            <div class="q-footer">
                <div class="q-add">
                    <input type="text" id="q-input" placeholder="Type a question or use @{{pick:2}}..." onkeydown="if(event.key==='Enter')addQ()">
                    <button class="q-add-btn" onclick="addQ()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
                </div>
                <div class="q-chips">
                    <span class="q-chip" onclick="insertPlaceholder('@{{pick:1}}')" title="Pick 1 random from bot's list">@{{pick:1}}</span>
                    <span class="q-chip" onclick="insertPlaceholder('@{{pick:2}}')" title="Pick 2 random from bot's list">@{{pick:2}}</span>
                    <span class="q-chip" onclick="insertPlaceholder('@{{first}}')" title="First item from bot's list">@{{first}}</span>
                    <span class="q-chip" onclick="insertPlaceholder('@{{last}}')" title="Last item from bot's list">@{{last}}</span>
                    <span class="q-chip" onclick="insertPlaceholder('@{{yes}}')" title="Confirm">@{{yes}}</span>
                    <span class="q-chip" onclick="insertPlaceholder('@{{no}}')" title="Cancel">@{{no}}</span>
                    <span class="q-chip" onclick="insertPlaceholder('@{{all}}')" title="All items combined">@{{all}}</span>
                    <span class="q-chip" onclick="addRepeatMarker()" title="Everything below this repeats per queue item" style="background:#fff7ed;color:#ea580c;border-color:#fdba74">🔄 Repeat</span>
                    <span class="q-chip-help" title="Dynamic: resolves from bot's last list at runtime">?</span>
                </div>
                <button class="q-run" id="q-run" onclick="runTest()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Run Test
                </button>
                <div class="q-save" id="q-save"></div>
            </div>
        </div>

        {{-- RIGHT: WhatsApp --}}
        <div class="wa">
            <div class="wa-hdr">
                <div class="wa-hdr-avatar">🤖</div>
                <div class="wa-hdr-info">
                    <h4>AI Bot Tester</h4>
                    <p id="wa-status">Click "Run Test" to start</p>
                </div>
                <div class="wa-hdr-actions">
                    <button class="wa-hdr-btn" onclick="clearChat()" title="Clear"><i data-lucide="trash-2" style="width:18px;height:18px"></i></button>
                </div>
            </div>
            <div class="wa-body" id="wa-body">
                <div class="wa-spacer"></div>
                <div class="msg msg-sys">Add questions on the left and click "Run Test" to start 💬</div>
                <div class="msg msg-typing" id="wa-typing"><div class="dots"><span></span><span></span><span></span></div></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // ═══ Placeholder Insert ═══
    function insertPlaceholder(text) {
        let inp = document.getElementById('q-input');
        inp.value = text;
        inp.focus();
    }

    // ═══ Questions ═══
    function addQ(){
        let inp=document.getElementById('q-input'), t=inp.value.trim();
        if(!t)return;
        let e=document.getElementById('q-empty'); if(e)e.remove();
        let l=document.getElementById('q-list'), n=l.querySelectorAll('.q-item:not(.q-repeat-marker)').length+1;
        let d=document.createElement('div'); d.className='q-item';
        d.innerHTML=`<span class="q-num">${n}</span><span class="q-text">${esc(t)}</span><button class="q-del" onclick="delQ(this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>`;
        l.appendChild(d); inp.value=''; inp.focus(); renum(); saveQ();
    }
    function addRepeatMarker(){
        // Only allow one repeat marker
        if(document.querySelector('.q-repeat-marker')) { alert('Only one 🔄 Repeat marker allowed!'); return; }
        let e=document.getElementById('q-empty'); if(e)e.remove();
        let l=document.getElementById('q-list');
        let d=document.createElement('div'); d.className='q-item q-repeat-marker';
        d.innerHTML=`<span style="font-size:16px">🔄</span><span class="q-text" style="color:#ea580c">── REPEAT BELOW PER QUEUE ──</span><button class="q-del" onclick="delQ(this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>`;
        l.appendChild(d); renum(); saveQ();
    }
    function delQ(b){
        b.closest('.q-item').remove(); renum();
        let l=document.getElementById('q-list');
        if(!l.querySelectorAll('.q-item').length) l.innerHTML='<div class="q-empty" id="q-empty">No questions yet.<br>Type below to add.</div>';
        saveQ();
    }
    function renum(){
        let items=document.querySelectorAll('#q-list .q-item');
        let num=1;
        items.forEach(x => {
            if(x.classList.contains('q-repeat-marker')) return;
            let qn = x.querySelector('.q-num');
            if(qn) qn.textContent = num++;
        });
        document.getElementById('q-count').textContent=document.querySelectorAll('#q-list .q-item:not(.q-repeat-marker)').length;
    }
    function getQs(){
        return Array.from(document.querySelectorAll('#q-list .q-item')).map(x => {
            if(x.classList.contains('q-repeat-marker')) return '🔄';
            return x.querySelector('.q-text').textContent.trim();
        });
    }
    function esc(t){ let d=document.createElement('div'); d.textContent=t; return d.innerHTML; }

    // ═══ Auto-save ═══
    let _st=null;
    function saveQ(){
        if(_st)clearTimeout(_st);
        _st=setTimeout(()=>{
            let qs=getQs(), sv=document.getElementById('q-save');
            if(!qs.length){ fetch("{{ route('admin.ai-analytics.test-questions.save') }}",{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},body:JSON.stringify({questions:['_']})}); return; }
            if(sv) sv.textContent='💾 Saving...';
            fetch("{{ route('admin.ai-analytics.test-questions.save') }}",{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({questions:qs})})
            .then(r=>r.json()).then(()=>{ if(sv){sv.textContent='✅ Saved'; setTimeout(()=>sv.textContent='',1200);} })
            .catch(()=>{ if(sv){sv.textContent='❌ Failed'; setTimeout(()=>sv.textContent='',2000);} });
        },300);
    }

    // ═══ Chat helpers ═══
    function timeStr(){ return new Date().toLocaleTimeString([],{hour:'numeric',minute:'2-digit',hour12:true}).toLowerCase(); }

    function addUser(text){
        let c=document.getElementById('wa-body'), t=document.getElementById('wa-typing');
        let m=document.createElement('div'); m.className='msg msg-u';
        m.innerHTML=`<span class="msg-text">${esc(text)}</span><span class="msg-meta">${timeStr()} <span class="msg-ticks">✓✓</span></span>`;
        c.insertBefore(m,t); c.scrollTop=c.scrollHeight;
    }
    function addBot(text){
        let c=document.getElementById('wa-body'), t=document.getElementById('wa-typing');
        let fmt=esc(text).replace(/\*([^*]+)\*/g,'<b>$1</b>').replace(/_([^_]+)_/g,'<i>$1</i>');
        let m=document.createElement('div'); m.className='msg msg-b';
        m.innerHTML=`<span class="msg-text">${fmt}</span><span class="msg-meta">${timeStr()}</span>`;
        c.insertBefore(m,t); c.scrollTop=c.scrollHeight;
    }
    function addSys(text){
        let c=document.getElementById('wa-body'), t=document.getElementById('wa-typing');
        let m=document.createElement('div'); m.className='msg msg-sys';
        m.textContent=text;
        c.insertBefore(m,t); c.scrollTop=c.scrollHeight;
    }
    function showTyping(v){
        let t=document.getElementById('wa-typing'); t.style.display=v?'block':'none';
        if(v) document.getElementById('wa-body').scrollTop=document.getElementById('wa-body').scrollHeight;
    }
    function clearChat(){
        let c=document.getElementById('wa-body'), t=document.getElementById('wa-typing');
        c.innerHTML=''; c.appendChild(document.createElement('div')).className='wa-spacer';
        let s=document.createElement('div'); s.className='msg msg-sys'; s.textContent='Chat cleared 🗑️'; c.appendChild(s);
        c.appendChild(t); t.style.display='none';
        document.getElementById('wa-status').textContent='Click "Run Test" to start';
    }

    // ═══ Run Test (Step-by-Step AJAX with Queue Repeat) ═══
    const HEADERS = {'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'};
    let lastListItems = [];
    let lastBotResponse = '';
    let isRunning = false;

    function resetBtn(){
        let btn=document.getElementById('q-run');
        btn.disabled=false;
        btn.innerHTML='<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg> Run Test';
        isRunning=false;
    }

    // Resolve placeholders client-side (backup for display, server also resolves)
    function resolvePlaceholder(q, items) {
        let lower = q.trim().toLowerCase();
        if (lower === '{{yes}}') return 'yes';
        if (lower === '{{no}}') return 'no';
        let m;
        if ((m = lower.match(/\{\{number:(\d+)\}\}/))) return m[1];
        if (lower.includes('{{first}}')) return items[0] || '1';
        if (lower.includes('{{last}}')) return items[items.length-1] || '1';
        if (lower.includes('{{random}}')) return items.length ? items[Math.floor(Math.random()*items.length)] : '1';
        if ((m = lower.match(/\{\{pick:(\d+)\}\}/))) {
            let n = parseInt(m[1]);
            let shuffled = [...items].sort(() => Math.random()-0.5);
            let picked = shuffled.slice(0, Math.min(n, shuffled.length));
            return picked.length ? picked.join(' and ') : '1';
        }
        if (lower.includes('{{all}}')) return items.length ? items.join(' and ') : '1';
        return q;
    }

    async function sendOneMessage(msg) {
        showTyping(true);
        let res = await fetch("{{ route('admin.ai-analytics.test-step.send') }}", {
            method:'POST', headers: HEADERS,
            body: JSON.stringify({message: msg})
        });
        showTyping(false);
        let data = await res.json();
        return data;
    }

    async function runTest(){
        let allQs = getQs();
        if(!allQs.length){ alert('Add questions first!'); return; }

        let btn=document.getElementById('q-run');
        btn.disabled=true;
        btn.innerHTML='<span class="dots" style="display:inline-flex"><span></span><span></span><span></span></span> Testing...';
        isRunning=true;

        // Clear chat
        let c=document.getElementById('wa-body'), t=document.getElementById('wa-typing');
        c.innerHTML=''; c.appendChild(document.createElement('div')).className='wa-spacer'; c.appendChild(t);

        // Split questions at 🔄 marker
        let repeatIdx = allQs.findIndex(q => q.trim() === '🔄');
        let initQs, repeatQs;
        if (repeatIdx >= 0) {
            initQs = allQs.slice(0, repeatIdx);
            repeatQs = allQs.slice(repeatIdx + 1);
        } else {
            initQs = allQs;
            repeatQs = [];
        }

        // Init session
        document.getElementById('wa-status').textContent='Initializing...';
        await fetch("{{ route('admin.ai-analytics.test-step.init') }}", {method:'POST', headers: HEADERS, body:'{}'});

        addSys('🧪 Test started — ' + allQs.length + ' questions' + (repeatQs.length ? ` (${repeatQs.length} repeat per queue)` : ''));

        lastListItems = [];

        // ═══ PHASE 1: Run init questions ═══
        let totalSent = 0;
        for (let i = 0; i < initQs.length; i++) {
            if (!isRunning) break;
            let resolved = resolvePlaceholder(initQs[i], lastListItems);
            totalSent++;
            document.getElementById('wa-status').textContent = `Init ${totalSent}/${initQs.length}`;
            addUser(resolved);
            if (initQs[i] !== resolved) addSys('🔄 ' + initQs[i] + ' → ' + resolved);

            let data = await sendOneMessage(resolved);
            addBot(data.bot_message);
            if (data.list_items && data.list_items.length) lastListItems = data.list_items;
            if (data.route) addSys('🛤️ ' + data.route);
            if (data.session_state) addSys('📊 State: ' + data.session_state + (data.lead_id ? ' | Lead: #'+data.lead_id : '') + (data.quote_id ? ' | Quote: #'+data.quote_id : ''));

            await new Promise(r => setTimeout(r, 800));
        }

        // ═══ PHASE 2: Run repeat questions for each queue item ═══
        if (repeatQs.length > 0) {
            let queueRound = 1;
            let keepGoing = true;

            while (keepGoing && isRunning) {
                addSys('🔄 Queue Round ' + queueRound + ' — ' + repeatQs.length + ' questions');

                let currentRepeatQs = [...repeatQs]; // copy so user can edit

                // Ask user if they want to edit for this round (except round 1)
                if (queueRound > 1) {
                    let editChoice = await showQueuePrompt(queueRound, currentRepeatQs);
                    if (editChoice === 'stop') { addSys('⏹️ Stopped by user'); break; }
                    if (editChoice !== null) currentRepeatQs = editChoice; // edited questions
                }

                let lastPendingQueue = 0;
                for (let i = 0; i < currentRepeatQs.length; i++) {
                    if (!isRunning) break;
                    let resolved = resolvePlaceholder(currentRepeatQs[i], lastListItems);
                    totalSent++;
                    document.getElementById('wa-status').textContent = `Queue ${queueRound} — ${i+1}/${currentRepeatQs.length}`;
                    addUser(resolved);
                    if (currentRepeatQs[i] !== resolved) addSys('🔄 ' + currentRepeatQs[i] + ' → ' + resolved);

                    let data = await sendOneMessage(resolved);
                    addBot(data.bot_message);
                    if (data.list_items && data.list_items.length) lastListItems = data.list_items;
                    if (data.route) addSys('🛤️ ' + data.route);
                    if (data.session_state) addSys('📊 State: ' + data.session_state + (data.pending_queue ? ' | Queue: '+data.pending_queue+' pending' : ''));
                    lastPendingQueue = data.pending_queue || 0;

                    await new Promise(r => setTimeout(r, 800));
                }

                // Check if more queue items remain based on last response
                if (lastPendingQueue <= 0) {
                    keepGoing = false;
                } else {
                    queueRound++;
                }
            }
        }

        addSys('✅ Test complete! (' + totalSent + ' messages sent)');
        document.getElementById('wa-status').textContent = 'Test complete';

        // Cleanup
        await fetch("{{ route('admin.ai-analytics.test-step.cleanup') }}", {method:'POST', headers:HEADERS, body:'{}'});
        resetBtn();
    }

    // ═══ Queue Edit Prompt ═══
    function showQueuePrompt(round, questions) {
        return new Promise(resolve => {
            let overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center';

            let modal = document.createElement('div');
            modal.style.cssText = 'background:#fff;border-radius:12px;padding:24px;width:420px;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2)';
            modal.innerHTML = `
                <h3 style="margin:0 0 4px;font-size:16px;font-weight:700">🔄 Queue Round ${round}</h3>
                <p style="margin:0 0 16px;font-size:13px;color:#667">Same questions for next product? Edit if needed:</p>
                <div id="qp-list" style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px">
                    ${questions.map((q,i) => `<input type="text" value="${esc(q)}" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;width:100%;box-sizing:border-box" />`).join('')}
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                    <button onclick="this.closest('div[style*=fixed]')._resolve('stop')" style="padding:8px 16px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:13px;font-weight:600">⏹ Stop</button>
                    <button onclick="this.closest('div[style*=fixed]')._resolve(null)" style="padding:8px 16px;border:none;border-radius:8px;background:#22c55e;color:#fff;cursor:pointer;font-size:13px;font-weight:700">▶ Run As-Is</button>
                    <button onclick="let inputs=this.closest('div[style*=fixed]').querySelectorAll('#qp-list input');let qs=Array.from(inputs).map(i=>i.value.trim()).filter(x=>x);this.closest('div[style*=fixed]')._resolve(qs)" style="padding:8px 16px;border:none;border-radius:8px;background:#f97316;color:#fff;cursor:pointer;font-size:13px;font-weight:700">✏ Run Edited</button>
                </div>
            `;
            overlay.appendChild(modal);
            overlay._resolve = (val) => { overlay.remove(); resolve(val); };
            document.body.appendChild(overlay);
        });
    }


</script>
@endpush
