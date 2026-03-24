<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiTokenLog;
use App\Models\AiChatSession;
use App\Models\AiChatMessage;
use App\Models\Setting;
use App\Services\AiSimulationService;
use Illuminate\Http\Request;

class AiAnalyticsController extends Controller
{
    /**
     * Token Analytics Dashboard
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $range = $request->get('range', '7');

        $startDate = match ($range) {
            '1' => now()->startOfDay(),
            '30' => now()->subDays(30)->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        $logs = AiTokenLog::where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->get();

        // Summary stats
        $totalTokens = $logs->sum('total_tokens');
        $totalCalls = $logs->count();
        $avgPerMessage = $totalCalls > 0 ? round($totalTokens / $totalCalls) : 0;

        $tier1Logs = $logs->where('tier', 1);
        $tier2Logs = $logs->where('tier', 2);
        $tier1Calls = $tier1Logs->count();
        $tier2Calls = $tier2Logs->count();
        $tier1Avg = $tier1Calls > 0 ? round($tier1Logs->avg('total_tokens')) : 0;
        $tier2Avg = $tier2Calls > 0 ? round($tier2Logs->avg('total_tokens')) : 0;

        // Client-wise breakdown
        $clientStats = $logs->groupBy('phone_number')->map(function ($group, $phone) {
            return [
                'phone' => $phone ?? 'Unknown',
                'total_calls' => $group->count(),
                'tier1_calls' => $group->where('tier', 1)->count(),
                'tier2_calls' => $group->where('tier', 2)->count(),
                'total_tokens' => $group->sum('total_tokens'),
                'last_active' => $group->max('created_at'),
            ];
        })->sortByDesc('total_tokens')->values();

        return view('admin.ai-analytics.index', compact(
            'totalTokens', 'totalCalls', 'avgPerMessage',
            'tier1Calls', 'tier2Calls', 'tier1Avg', 'tier2Avg',
            'clientStats', 'range'
        ));
    }

    /**
     * Chat Sessions List
     */
    public function chats(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $sessions = AiChatSession::where('company_id', $companyId)
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('admin.ai-analytics.chats', compact('sessions'));
    }

    /**
     * Chat Detail — view messages for a session
     */
    public function chatDetail(int $id)
    {
        $session = AiChatSession::where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $messages = AiChatMessage::where('session_id', $session->id)
            ->orderBy('created_at')
            ->get();

        return view('admin.ai-analytics.chat-detail', compact('session', 'messages'));
    }

    /**
     * AI Bot Tester UI
     */
    public function tester()
    {
        $companyId = auth()->user()->company_id;
        $defaultRules = "<ul><li>Start with greetings.</li><li>If user asks for products, send the <strong>Catalogue List</strong> without AI formatting.</li><li>Understand <strong>Spelling Mistakes</strong> (e.g. 'cebnet hndle' -&gt; Cabinet Handle).</li><li>Do NOT show prices if they are hidden in Catalogue Columns.</li><li>Follow the <strong>Chatflow</strong> (ask for combo, size, finish).</li><li>Respect the Admin <strong>Language</strong> setting.</li></ul>";
        $testerRules = Setting::getValue('ai_bot', 'tester_rules', $defaultRules, $companyId);

        return view('admin.ai-analytics.tester', compact('testerRules'));
    }

    /**
     * Save AI Bot Tester Rules
     */
    public function saveTesterRules(Request $request)
    {
        $request->validate(['rules' => 'required|string']);
        $companyId = auth()->user()->company_id;
        
        Setting::setValue('ai_bot', 'tester_rules', $request->rules, 'string', $companyId);

        return response()->json(['success' => true]);
    }

    /**
     * Run AI Bot Simulation (Streamed Response)
     */
    public function runSimulation()
    {
        $companyId = auth()->user()->company_id;
        $userId = auth()->id();
        $rules = Setting::getValue('ai_bot', 'tester_rules', '', $companyId);

        return response()->stream(function () use ($companyId, $userId, $rules) {
            $simulator = new AiSimulationService($companyId, $userId);
            $simulator->run($rules, function($type, $message) {
                echo json_encode(['type' => $type, 'message' => $message]) . "\n";
                ob_flush();
                flush();
            });
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type'  => 'text/event-stream',
        ]);
    }
}
