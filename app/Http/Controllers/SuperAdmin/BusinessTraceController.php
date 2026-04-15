<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\AiChatSession;
use App\Models\AiChatTrace;
use Illuminate\Http\Request;

class BusinessTraceController extends Controller
{
    /**
     * Get sessions list for a business, filtered by bot_type.
     * Returns JSON for AJAX loading.
     */
    public function sessions(Company $company, Request $request)
    {
        $botType = $request->input('bot_type', 'ai_bot');
        $page = (int) $request->input('page', 1);
        $perPage = 10;

        $query = AiChatSession::where('company_id', $company->id)
            ->where('bot_type', $botType)
            ->withCount([
                'messages',
                'traces',
                'traces as error_traces_count' => function ($q) {
                    $q->where('status', 'error');
                },
            ])
            ->orderBy('last_message_at', 'desc');

        $total = $query->count();
        $sessions = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $data = $sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'phone_number' => $s->phone_number,
                'conversation_state' => $s->conversation_state ?? 'new',
                'status' => $s->status,
                'messages_count' => $s->messages_count,
                'traces_count' => $s->traces_count,
                'error_traces_count' => $s->error_traces_count,
                'lead_id' => $s->lead_id,
                'quote_id' => $s->quote_id,
                'last_message_at' => $s->last_message_at?->diffForHumans(),
                'last_message_at_full' => $s->last_message_at?->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'sessions' => $data,
            'total' => $total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page,
        ]);
    }

    /**
     * Get all traces for a specific session.
     * Returns JSON for AJAX inline trace viewer.
     */
    public function traces(Company $company, $sessionId)
    {
        $session = AiChatSession::where('company_id', $company->id)
            ->findOrFail($sessionId);

        $traces = AiChatTrace::with('message')
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Group traces by message_id for chronological flow
        $grouped = [];
        foreach ($traces as $trace) {
            $msgId = $trace->message_id ?? 0;
            if (!isset($grouped[$msgId])) {
                $grouped[$msgId] = [
                    'message_id' => $msgId,
                    'user_message' => $trace->message->message ?? 'System / Background',
                    'message_type' => $trace->message->message_type ?? 'text',
                    'traces' => [],
                ];
            }
            $grouped[$msgId]['traces'][] = [
                'id' => $trace->id,
                'node_name' => $trace->node_name,
                'node_group' => $trace->node_group,
                'status' => $trace->status,
                'status_color' => $trace->getStatusColor(),
                'group_icon' => $trace->getGroupIcon(),
                'input_data' => $trace->input_data,
                'output_data' => $trace->output_data,
                'error_message' => $trace->error_message,
                'execution_time_ms' => $trace->execution_time_ms,
                'created_at' => $trace->created_at->format('h:i:s A'),
            ];
        }

        return response()->json([
            'session' => [
                'id' => $session->id,
                'phone_number' => $session->phone_number,
                'conversation_state' => $session->conversation_state,
                'status' => $session->status,
                'lead_id' => $session->lead_id,
                'quote_id' => $session->quote_id,
                'bot_type' => $session->bot_type,
            ],
            'messages' => array_values($grouped),
            'total_traces' => $traces->count(),
        ]);
    }
}
