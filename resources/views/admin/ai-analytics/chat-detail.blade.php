@extends('admin.layouts.app')

@section('title', 'Chat: ' . $session->phone_number)
@section('breadcrumb', 'Chat Detail')

@push('styles')
<style>
    .chat-container {
        max-width: 600px;
        margin: 0 auto;
        background: #ece5dd;
        border-radius: 12px;
        padding: 20px;
        min-height: 400px;
        max-height: 70vh;
        overflow-y: auto;
    }
    .chat-bubble {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 12px;
        margin-bottom: 8px;
        font-size: 14px;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-line;
        position: relative;
        color: #1f2937; /* Force dark text regardless of global theme */
    }
    .chat-bubble.user {
        background: #dcf8c6;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }
    .chat-bubble.bot {
        background: white;
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }
    .chat-time {
        font-size: 11px;
        color: #999;
        text-align: right;
        margin-top: 4px;
    }
    .chat-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .chat-label.user { color: #075e54; }
    .chat-label.bot { color: #6b7280; }
</style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div>
                <h1 class="page-title" style="display:flex;align-items:center;gap:12px">
                    <a href="{{ route('admin.ai-analytics.chats') }}" style="color:var(--text-muted)">
                        <i data-lucide="arrow-left" style="width:20px;height:20px"></i>
                    </a>
                    Chat: {{ $session->phone_number }}
                </h1>
                <p class="page-description">
                    Status: {{ ucfirst($session->status) }} |
                    State: {{ ucfirst(str_replace('_', ' ', $session->conversation_state ?? 'new')) }} |
                    Messages: {{ $messages->count() }}
                </p>
            </div>
        </div>
    </div>

    <!-- Session Meta -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:24px">
        @if($session->lead_id)
        <div class="card">
            <div class="card-content" style="padding:14px">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600">Lead</div>
                <div style="font-size:15px;font-weight:700;margin-top:4px">
                    <a href="{{ route('admin.leads.show', $session->lead_id) }}">#{{ $session->lead_id }}</a>
                </div>
            </div>
        </div>
        @endif
        @if($session->quote_id)
        <div class="card">
            <div class="card-content" style="padding:14px">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600">Quote</div>
                <div style="font-size:15px;font-weight:700;margin-top:4px">#{{ $session->quote_id }}</div>
            </div>
        </div>
        @endif
        @if($session->catalogue_sent)
        <div class="card">
            <div class="card-content" style="padding:14px">
                <div style="font-size:12px;color:var(--text-muted);font-weight:600">Catalogue</div>
                <div style="font-size:15px;font-weight:700;margin-top:4px;color:#16a34a">✅ Sent</div>
            </div>
        </div>
        @endif
    </div>

    <!-- Chat Messages -->
    <div class="chat-container" id="chat-container">
        @forelse($messages as $msg)
            <div style="display:flex;flex-direction:column;align-items:{{ $msg->role === 'user' ? 'flex-end' : 'flex-start' }}">
                <div class="chat-label {{ $msg->role }}">{{ $msg->role === 'user' ? '👤 Customer' : '🤖 Bot' }}</div>
                <div class="chat-bubble {{ $msg->role }}">
                    {{ $msg->message }}
                    <div class="chat-time">{{ $msg->created_at->format('h:i A') }}</div>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:40px;color:#999">
                No messages in this session.
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
<script>
    // Auto-scroll to bottom
    document.addEventListener('DOMContentLoaded', function() {
        var container = document.getElementById('chat-container');
        if (container) container.scrollTop = container.scrollHeight;
    });
</script>
@endpush
