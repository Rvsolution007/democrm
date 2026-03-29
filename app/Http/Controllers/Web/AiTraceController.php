<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiChatSession;
use App\Models\AiChatTrace;
use Illuminate\Http\Request;

class AiTraceController extends Controller
{
    /**
     * Display a paginated list of AI sessions with basic trace metrics.
     */
    public function index(Request $request)
    {
        if (!can('settings.manage')) {
            abort(403, 'Unauthorized');
        }

        $sessions = AiChatSession::withCount(['messages', 'traces', 'traces as error_traces_count' => function ($query) {
                $query->where('status', 'error');
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return view('admin.ai-analytics.traces.index', compact('sessions'));
    }

    /**
     * Show the detailed n8n-style flow chart of traces for a specific session.
     */
    public function show($sessionId)
    {
        if (!can('settings.manage')) {
            abort(403, 'Unauthorized');
        }

        $session = AiChatSession::with(['lead', 'quote'])->findOrFail($sessionId);

        // Get traces grouped by message ID for chronological visual flow
        $traces = AiChatTrace::with('message')
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('admin.ai-analytics.traces.show', compact('session', 'traces'));
    }
}
