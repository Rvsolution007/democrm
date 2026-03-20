<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAutoReplyRule;
use App\Models\WhatsappAutoReplyLog;
use App\Models\WhatsappAutoReplyBlacklist;
use App\Models\WhatsappTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappAutoReplyController extends Controller
{
    /**
     * Get instance name for current user (same logic as WhatsappConnectController)
     */
    private function getInstanceName()
    {
        $user = auth()->user();
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
        return 'rvcrm_' . $cleanName . '_' . $user->id;
    }

    /**
     * Get Evolution API config
     */
    private function getServerConfig()
    {
        return Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], auth()->user()->company_id);
    }

    /**
     * Check WhatsApp connection status for current user
     */
    private function checkConnectionStatus()
    {
        $config = $this->getServerConfig();
        if (empty($config['api_url']) || empty($config['api_key'])) {
            return ['connected' => false, 'reason' => 'not_configured'];
        }

        $instanceName = $this->getInstanceName();

        try {
            $response = Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->get("{$config['api_url']}/instance/connectionState/{$instanceName}");

            if ($response->successful()) {
                $data = $response->json();
                $state = $data['instance']['state'] ?? $data['state'] ?? 'unknown';
                return ['connected' => $state === 'open', 'state' => $state];
            }

            return ['connected' => false, 'reason' => 'api_error'];
        } catch (\Exception $e) {
            return ['connected' => false, 'reason' => 'connection_error'];
        }
    }

    /**
     * List all auto-reply rules for the current user
     */
    public function index()
    {
        $user = auth()->user();
        $instanceName = $this->getInstanceName();
        $connectionStatus = $this->checkConnectionStatus();

        $rules = WhatsappAutoReplyRule::where('user_id', $user->id)
            ->where('instance_name', $instanceName)
            ->orderByDesc('priority')
            ->get();

        // Today's stats
        $todayStats = [
            'active' => $rules->where('is_active', true)->count(),
            'paused' => $rules->where('is_active', false)->count(),
            'total_sent_today' => WhatsappAutoReplyLog::where('user_id', $user->id)
                ->where('status', 'sent')
                ->whereDate('created_at', today())
                ->count(),
            'total_failed_today' => WhatsappAutoReplyLog::where('user_id', $user->id)
                ->where('status', 'failed')
                ->whereDate('created_at', today())
                ->count(),
        ];

        return view('admin.whatsapp-auto-reply.index', compact('rules', 'connectionStatus', 'instanceName', 'todayStats'));
    }

    /**
     * Show create rule form
     */
    public function create()
    {
        $templates = WhatsappTemplate::latest()->get();
        $connectionStatus = $this->checkConnectionStatus();

        return view('admin.whatsapp-auto-reply.create', compact('templates', 'connectionStatus'));
    }

    /**
     * Store a new rule
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,any_message,first_message',
            'keywords' => 'nullable|string',
            'template_id' => 'required|exists:whatsapp_templates,id',
            'reply_delay_seconds' => 'nullable|integer|min:0|max:60',
            'is_one_time' => 'nullable',
            'cooldown_hours' => 'nullable|integer|min:0',
            'business_hours_only' => 'nullable',
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'max_replies_per_day' => 'nullable|integer|min:0',
            'priority' => 'nullable|integer|min:1|max:10',
            'is_active' => 'nullable',
        ]);

        $user = auth()->user();
        $instanceName = $this->getInstanceName();

        // Parse keywords (comma-separated string to array)
        $keywords = null;
        if ($request->keywords) {
            $keywords = array_map('trim', explode(',', $request->keywords));
            $keywords = array_filter($keywords); // Remove empty
            $keywords = array_values($keywords); // Re-index
        }

        WhatsappAutoReplyRule::create([
            'company_id' => $user->company_id ?? 1,
            'user_id' => $user->id,
            'instance_name' => $instanceName,
            'name' => $request->name,
            'match_type' => $request->match_type,
            'keywords' => $keywords,
            'template_id' => $request->template_id,
            'reply_delay_seconds' => $request->reply_delay_seconds ?? 5,
            'is_one_time' => $request->boolean('is_one_time'),
            'cooldown_hours' => $request->cooldown_hours ?? 24,
            'business_hours_only' => $request->boolean('business_hours_only'),
            'business_hours_start' => $request->business_hours_start,
            'business_hours_end' => $request->business_hours_end,
            'max_replies_per_day' => $request->max_replies_per_day ?? 3,
            'priority' => $request->priority ?? 5,
            'is_active' => $request->boolean('is_active'),
        ]);

        // Ensure webhook is registered for this instance
        $this->ensureWebhookRegistered($instanceName);

        return redirect()->route('admin.whatsapp-auto-reply.index')
            ->with('success', 'Auto-reply rule created successfully! ✅');
    }

    /**
     * Show edit rule form
     */
    public function edit($id)
    {
        $rule = WhatsappAutoReplyRule::where('user_id', auth()->id())->findOrFail($id);
        $templates = WhatsappTemplate::latest()->get();
        $connectionStatus = $this->checkConnectionStatus();

        return view('admin.whatsapp-auto-reply.edit', compact('rule', 'templates', 'connectionStatus'));
    }

    /**
     * Update a rule
     */
    public function update(Request $request, $id)
    {
        $rule = WhatsappAutoReplyRule::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,any_message,first_message',
            'keywords' => 'nullable|string',
            'template_id' => 'required|exists:whatsapp_templates,id',
            'reply_delay_seconds' => 'nullable|integer|min:0|max:60',
            'is_one_time' => 'nullable',
            'cooldown_hours' => 'nullable|integer|min:0',
            'business_hours_only' => 'nullable',
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'max_replies_per_day' => 'nullable|integer|min:0',
            'priority' => 'nullable|integer|min:1|max:10',
            'is_active' => 'nullable',
        ]);

        // Parse keywords
        $keywords = null;
        if ($request->keywords) {
            $keywords = array_map('trim', explode(',', $request->keywords));
            $keywords = array_filter($keywords);
            $keywords = array_values($keywords);
        }

        $rule->update([
            'name' => $request->name,
            'match_type' => $request->match_type,
            'keywords' => $keywords,
            'template_id' => $request->template_id,
            'reply_delay_seconds' => $request->reply_delay_seconds ?? 5,
            'is_one_time' => $request->boolean('is_one_time'),
            'cooldown_hours' => $request->cooldown_hours ?? 24,
            'business_hours_only' => $request->boolean('business_hours_only'),
            'business_hours_start' => $request->business_hours_start,
            'business_hours_end' => $request->business_hours_end,
            'max_replies_per_day' => $request->max_replies_per_day ?? 3,
            'priority' => $request->priority ?? 5,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.whatsapp-auto-reply.index')
            ->with('success', 'Rule updated successfully! ✅');
    }

    /**
     * Delete a rule
     */
    public function destroy($id)
    {
        $rule = WhatsappAutoReplyRule::where('user_id', auth()->id())->findOrFail($id);
        $rule->delete();

        return redirect()->route('admin.whatsapp-auto-reply.index')
            ->with('success', 'Rule deleted successfully.');
    }

    /**
     * Toggle rule active/inactive (AJAX)
     */
    public function toggle($id)
    {
        $rule = WhatsappAutoReplyRule::where('user_id', auth()->id())->findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $rule->is_active,
            'message' => $rule->is_active ? 'Rule activated ✅' : 'Rule paused ⏸️',
        ]);
    }

    /**
     * Duplicate a rule
     */
    public function duplicate($id)
    {
        $rule = WhatsappAutoReplyRule::where('user_id', auth()->id())->findOrFail($id);

        $newRule = $rule->replicate();
        $newRule->name = $rule->name . ' (Copy)';
        $newRule->is_active = false;
        $newRule->total_triggered = 0;
        $newRule->total_sent = 0;
        $newRule->total_skipped = 0;
        $newRule->save();

        return redirect()->route('admin.whatsapp-auto-reply.index')
            ->with('success', 'Rule duplicated! New rule is paused by default.');
    }

    /**
     * Pause all rules for current user (AJAX)
     */
    public function pauseAll()
    {
        WhatsappAutoReplyRule::where('user_id', auth()->id())
            ->where('is_active', true)
            ->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'All rules paused ⏸️']);
    }

    /**
     * Analytics dashboard
     */
    public function analytics(Request $request)
    {
        $user = auth()->user();
        $instanceName = $this->getInstanceName();
        $period = $request->period ?? 'today';

        // Date range
        $startDate = match ($period) {
            'today' => today(),
            'week' => today()->subDays(7),
            'month' => today()->subDays(30),
            default => today(),
        };

        $logsQuery = WhatsappAutoReplyLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate);

        // Stats cards
        $stats = [
            'total_received' => (clone $logsQuery)->count(),
            'total_sent' => (clone $logsQuery)->where('status', 'sent')->count(),
            'total_skipped' => (clone $logsQuery)->where('status', 'skipped')->count(),
            'total_failed' => (clone $logsQuery)->where('status', 'failed')->count(),
        ];

        // Rule performance
        $rulePerformance = WhatsappAutoReplyRule::where('user_id', $user->id)
            ->select('id', 'name', 'total_triggered', 'total_sent', 'total_skipped', 'is_active')
            ->orderByDesc('total_triggered')
            ->get();

        // Recent logs (last 50)
        $recentLogs = WhatsappAutoReplyLog::where('user_id', $user->id)
            ->with('rule', 'template')
            ->latest()
            ->take(50)
            ->get();

        // ── Daily breakdown chart ──
        $dailyData = WhatsappAutoReplyLog::where('user_id', $user->id)
            ->where('status', 'sent')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();

        // Build daily chart with all dates in range
        $dailyChartData = [];
        $daysCursor = $startDate->copy();
        while ($daysCursor->lte(today())) {
            $key = $daysCursor->format('Y-m-d');
            $dailyChartData[$key] = $dailyData[$key] ?? 0;
            $daysCursor->addDay();
        }

        // ── Hourly breakdown chart ──
        $hourlyData = WhatsappAutoReplyLog::where('user_id', $user->id)
            ->where('status', 'sent')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupByRaw('HOUR(created_at)')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours with 0
        $chartData = [];
        for ($i = 0; $i < 24; $i++) {
            $chartData[$i] = $hourlyData[$i] ?? 0;
        }

        // ── Queue Jobs Section ──
        $queueQuery = \DB::table('jobs')->where('queue', 'default');

        // Apply queue filters
        if ($request->filled('queue_date')) {
            $queueQuery->whereRaw('DATE(FROM_UNIXTIME(created_at)) = ?', [$request->queue_date]);
        }
        if ($request->filled('queue_hour')) {
            $queueQuery->whereRaw('HOUR(FROM_UNIXTIME(created_at)) = ?', [(int)$request->queue_hour]);
        }

        $queueJobs = $queueQuery->orderByDesc('id')->limit(100)->get()->map(function ($job) {
            $payload = json_decode($job->payload, true);
            $command = isset($payload['data']['command']) ? unserialize($payload['data']['command']) : null;
            return (object)[
                'id' => $job->id,
                'queue' => $job->queue,
                'attempts' => $job->attempts,
                'created_at' => \Carbon\Carbon::createFromTimestamp($job->created_at),
                'available_at' => \Carbon\Carbon::createFromTimestamp($job->available_at),
                'reserved_at' => $job->reserved_at ? \Carbon\Carbon::createFromTimestamp($job->reserved_at) : null,
                'job_name' => $payload['displayName'] ?? class_basename($payload['data']['commandName'] ?? 'Unknown'),
            ];
        });

        $totalQueueCount = \DB::table('jobs')->count();

        return view('admin.whatsapp-auto-reply.analytics', compact(
            'stats', 'rulePerformance', 'recentLogs', 'chartData', 'dailyChartData', 'period',
            'queueJobs', 'totalQueueCount'
        ));
    }

    /**
     * Blacklist management page
     */
    public function blacklist()
    {
        $blacklist = WhatsappAutoReplyBlacklist::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('admin.whatsapp-auto-reply.blacklist', compact('blacklist'));
    }

    /**
     * Add number to blacklist
     */
    public function addToBlacklist(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $phone = preg_replace('/\D/', '', $request->phone_number);
        if (strlen($phone) == 12 && str_starts_with($phone, '91')) {
            $phone = substr($phone, 2);
        }

        WhatsappAutoReplyBlacklist::updateOrCreate(
            ['user_id' => auth()->id(), 'phone_number' => $phone],
            [
                'company_id' => auth()->user()->company_id ?? 1,
                'reason' => $request->reason,
            ]
        );

        return redirect()->back()->with('success', "Number {$phone} added to blacklist.");
    }

    /**
     * Remove from blacklist
     */
    public function removeFromBlacklist($id)
    {
        WhatsappAutoReplyBlacklist::where('user_id', auth()->id())
            ->where('id', $id)
            ->delete();

        return redirect()->back()->with('success', 'Number removed from blacklist.');
    }

    /**
     * Register webhook URL on Evolution API for this user's instance
     */
    private function ensureWebhookRegistered(string $instanceName)
    {
        $config = $this->getServerConfig();
        if (empty($config['api_url']) || empty($config['api_key'])) {
            return;
        }

        // Use webhook_base_url from settings (the server's public URL)
        // Falls back to APP_URL if not set, but that may be localhost
        $baseUrl = !empty($config['webhook_base_url']) ? $config['webhook_base_url'] : url('');
        $webhookUrl = rtrim($baseUrl, '/') . "/webhook/whatsapp/incoming/{$instanceName}";

        try {
            Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post("{$config['api_url']}/webhook/set/{$instanceName}", [
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'webhookByEvents' => false,
                    'events' => ['MESSAGES_UPSERT'],
                ],
            ]);

            Log::info("AutoReply: Webhook registered for {$instanceName} → {$webhookUrl}");
        } catch (\Exception $e) {
            Log::error("AutoReply: Failed to register webhook for {$instanceName}: " . $e->getMessage());
        }
    }
}
