<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappConnectController extends Controller
{
    /**
     * Get Evolution API server config from DB (URL + API Key only)
     */
    private function getServerConfig()
    {
        return Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], auth()->user()->company_id);
    }

    /**
     * Auto-generate instance name for the current user
     */
    private function getInstanceName()
    {
        $user = auth()->user();
        // Create a clean instance name: rvcrm_user_{id}
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $user->name));
        return 'rvcrm_' . $cleanName . '_' . $user->id;
    }

    /**
     * Check if server-level API is configured
     */
    private function isConfigured($config)
    {
        return !empty($config['api_url']) && !empty($config['api_key']);
    }

    /**
     * Show the WhatsApp Connect page (available to all users)
     */
    public function index()
    {
        $config = $this->getServerConfig();
        $isConfigured = $this->isConfigured($config);
        $instanceName = $this->getInstanceName();
        $userName = auth()->user()->name;

        return view('admin.whatsapp-connect.index', compact('isConfigured', 'instanceName', 'userName'));
    }

    /**
     * Show the WhatsApp Extension Download Page
     */
    public function extension()
    {
        return view('admin.whatsapp-connect.extension');
    }

    /**
     * Get QR code from Evolution API for this user's instance
     */
    public function getQrCode()
    {
        $config = $this->getServerConfig();

        if (!$this->isConfigured($config)) {
            return response()->json(['error' => 'WhatsApp API not configured.'], 400);
        }

        $instanceName = $this->getInstanceName();
        $apiUrl = rtrim($config['api_url'], '/');
        $headers = ['apikey' => $config['api_key'], 'Content-Type' => 'application/json'];

        try {
            // Step 1: Try connect endpoint to get QR
            $response = Http::withHeaders($headers)->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");

            Log::info('WhatsApp QR: connect response', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            // If 200 with QR, return it
            if ($response->successful()) {
                $data = $response->json();
                $qrcode = $this->extractQrCode($data);

                if ($qrcode) {
                    return response()->json(['success' => true, 'qrcode' => $qrcode, 'instance' => $instanceName]);
                }

                // Got {"count":0} or similar — instance is STUCK in connecting state
                // Fix: restart the instance, then retry
                Log::warning('WhatsApp QR: Instance stuck (no QR in 200 response). Restarting...', [
                    'response_data' => $data,
                ]);

                return $this->restartAndGetQr($config, $instanceName);
            }

            // Step 2: If 404, create instance
            if ($response->status() === 404) {
                Log::info('WhatsApp QR: Instance not found, creating new...', ['instance' => $instanceName]);
                return $this->createAndGetQr($config, $instanceName);
            }

            // Step 3: Other errors — try restart anyway
            Log::warning('WhatsApp QR: Unexpected status ' . $response->status() . ', attempting restart...');
            return $this->restartAndGetQr($config, $instanceName);

        } catch (\Exception $e) {
            Log::error('WhatsApp QR Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restart a stuck instance and get QR code
     */
    private function restartAndGetQr($config, $instanceName): \Illuminate\Http\JsonResponse
    {
        $apiUrl = rtrim($config['api_url'], '/');
        $headers = ['apikey' => $config['api_key'], 'Content-Type' => 'application/json'];

        try {
            // Try logout first (clears the connecting state)
            Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/logout/{$instanceName}");
            Log::info('WhatsApp QR: Logged out instance', ['instance' => $instanceName]);
            sleep(1);

            // Now restart the instance
            Http::withHeaders($headers)->timeout(10)->put("{$apiUrl}/instance/restart/{$instanceName}");
            Log::info('WhatsApp QR: Restarted instance', ['instance' => $instanceName]);
            sleep(2);

            // Now try connect again — should give fresh QR
            $response = Http::withHeaders($headers)->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");

            Log::info('WhatsApp QR: connect after restart', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $qrcode = $this->extractQrCode($data);
                if ($qrcode) {
                    return response()->json(['success' => true, 'qrcode' => $qrcode, 'instance' => $instanceName]);
                }
            }

            // Still no QR — try delete and recreate as last resort
            Log::warning('WhatsApp QR: Restart did not produce QR. Deleting and recreating...');
            return $this->createAndGetQr($config, $instanceName);

        } catch (\Exception $e) {
            Log::error('WhatsApp QR restart failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Restart failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete existing instance, create fresh one, and get QR
     */
    private function createAndGetQr($config, $instanceName): \Illuminate\Http\JsonResponse
    {
        $apiUrl = rtrim($config['api_url'], '/');
        $headers = ['apikey' => $config['api_key'], 'Content-Type' => 'application/json'];

        try {
            // Delete if exists (ignore errors)
            Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/delete/{$instanceName}");
            sleep(1);

            // Create fresh instance with QR
            $createResponse = Http::withHeaders($headers)->timeout(15)->post("{$apiUrl}/instance/create", [
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
            ]);

            Log::info('WhatsApp QR: create response', [
                'status' => $createResponse->status(),
                'body' => mb_substr($createResponse->body(), 0, 500),
            ]);

            if ($createResponse->successful()) {
                $data = $createResponse->json();
                // The CREATE response itself often contains the QR code
                $qrcode = $this->extractQrCode($data);
                if ($qrcode) {
                    // Register webhook
                    $this->registerWebhook($config, $instanceName);
                    return response()->json(['success' => true, 'qrcode' => $qrcode, 'instance' => $instanceName]);
                }

                // If create didn't return QR, try connect
                sleep(2);
                $connectResponse = Http::withHeaders($headers)->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");

                Log::info('WhatsApp QR: connect after create', [
                    'status' => $connectResponse->status(),
                    'body' => mb_substr($connectResponse->body(), 0, 500),
                ]);

                if ($connectResponse->successful()) {
                    $connectData = $connectResponse->json();
                    $qrcode = $this->extractQrCode($connectData);
                    if ($qrcode) {
                        $this->registerWebhook($config, $instanceName);
                        return response()->json(['success' => true, 'qrcode' => $qrcode, 'instance' => $instanceName]);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'Could not generate QR code. Please try again in a few seconds.',
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp QR create failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Create failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Register webhook after creating instance
     */
    private function registerWebhook($config, $instanceName)
    {
        $apiUrl = rtrim($config['api_url'], '/');
        $headers = ['apikey' => $config['api_key'], 'Content-Type' => 'application/json'];

        try {
            $baseUrl = !empty($config['webhook_base_url']) ? $config['webhook_base_url'] : secure_url('');
            $webhookUrl = rtrim($baseUrl, '/') . "/webhook/whatsapp/incoming/{$instanceName}";

            Http::withHeaders($headers)->post("{$apiUrl}/webhook/set/{$instanceName}", [
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'webhookByEvents' => false,
                    'events' => ['MESSAGES_UPSERT'],
                ],
            ]);
            Log::info('WhatsApp: Webhook registered', ['url' => $webhookUrl]);
        } catch (\Exception $e) {
            Log::error("Failed to set webhook: " . $e->getMessage());
        }
    }

    /**
     * Extract QR code base64 from various Evolution API response formats.
     * Different versions store QR in different locations.
     */
    private function extractQrCode($data): ?string
    {
        if (!is_array($data)) return null;

        // Format 1: { "base64": "data:image/png;base64,..." }
        if (!empty($data['base64'])) return $data['base64'];

        // Format 2: { "qrcode": { "base64": "..." } }
        if (!empty($data['qrcode']['base64'])) return $data['qrcode']['base64'];

        // Format 3: { "qrcode": "data:image/..." } (string directly)
        if (!empty($data['qrcode']) && is_string($data['qrcode'])) return $data['qrcode'];

        // Format 4: { "code": "raw-qr-text" } — need to generate image
        // Skip this, we need base64 image

        // Format 5: Nested in instance object
        if (!empty($data['instance']['qrcode'])) {
            $qr = $data['instance']['qrcode'];
            return is_array($qr) ? ($qr['base64'] ?? null) : $qr;
        }

        // Format 6: { "data": { "qrcode": "..." } }
        if (!empty($data['data']['qrcode'])) {
            $qr = $data['data']['qrcode'];
            return is_array($qr) ? ($qr['base64'] ?? null) : $qr;
        }

        // Deep search: look for any key containing 'base64' with image data
        $flat = json_encode($data);
        if (preg_match('/"base64"\s*:\s*"(data:image[^"]+)"/', $flat, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Get connection status for this user's instance
     */
    public function getStatus()
    {
        $config = $this->getServerConfig();

        if (!$this->isConfigured($config)) {
            return response()->json(['state' => 'not_configured']);
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
                
                // If connected, try to get the phone number
                $phoneNumber = null;
                if ($state === 'open') {
                    $phoneNumber = $this->getConnectedPhone($config, $instanceName);
                }
                
                return response()->json([
                    'success' => true,
                    'state' => $state,
                    'instance' => $instanceName,
                    'phone' => $phoneNumber,
                ]);
            }

            // If instance not found, create it
            if ($response->status() === 404) {
                $this->createInstance($config, $instanceName);
                return response()->json([
                    'success' => true,
                    'state' => 'close',
                    'instance' => $instanceName,
                ]);
            }

            return response()->json([
                'success' => false,
                'state' => 'error',
                'error' => $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('WhatsApp Status Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'state' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the connected phone number from Evolution API
     */
    private function getConnectedPhone($config, $instanceName)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->get("{$config['api_url']}/instance/fetchInstances", [
                'instanceName' => $instanceName,
            ]);

            if ($response->successful()) {
                $instances = $response->json();
                // Evolution API returns array of instances
                if (is_array($instances)) {
                    foreach ($instances as $inst) {
                        $name = $inst['instance']['instanceName'] ?? $inst['instanceName'] ?? '';
                        if ($name === $instanceName) {
                            // Try known paths for the phone number
                            return $inst['instance']['owner'] ?? $inst['owner'] ?? null;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("WhatsApp: Could not fetch phone for {$instanceName}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Create instance on Evolution API for this user
     */
    private function createInstance($config, $instanceName)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post("{$config['api_url']}/instance/create", [
                        'instanceName' => $instanceName,
                        'integration' => 'WHATSAPP-BAILEYS',
                        'qrcode' => true,
                    ]);
            $success = $response->successful();
            
            if ($success) {
                // Register webhook immediately
                $baseUrl = !empty($config['webhook_base_url']) ? $config['webhook_base_url'] : secure_url('');
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
                } catch (\Exception $e) {
                    Log::error("Failed to set webhook during instance creation: " . $e->getMessage());
                }
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('WhatsApp Create Instance Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect / logout this user's WhatsApp instance
     */
    public function disconnect()
    {
        $config = $this->getServerConfig();

        if (!$this->isConfigured($config)) {
            return response()->json(['error' => 'WhatsApp API not configured.'], 400);
        }

        $instanceName = $this->getInstanceName();
        $apiUrl = rtrim($config['api_url'], '/');
        $headers = ['apikey' => $config['api_key'], 'Content-Type' => 'application/json'];

        try {
            // Step 1: Logout first (cleanly closes WhatsApp session)
            try {
                Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/logout/{$instanceName}");
                Log::info('WhatsApp Disconnect: Logged out', ['instance' => $instanceName]);
            } catch (\Exception $e) {
                Log::warning('WhatsApp Disconnect: Logout failed (continuing): ' . $e->getMessage());
            }

            sleep(1);

            // Step 2: Delete instance completely
            $response = Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/delete/{$instanceName}");

            Log::info('WhatsApp Disconnect: Delete response', [
                'instance' => $instanceName,
                'status' => $response->status(),
            ]);

            // Success if deleted, or already gone
            if ($response->successful() || in_array($response->status(), [400, 404])) {
                return response()->json(['success' => true, 'message' => 'WhatsApp disconnected successfully.']);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to disconnect: ' . $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('WhatsApp Disconnect Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Force Reconnect — one-shot endpoint that guarantees QR code delivery.
     * Deletes old instance → Creates fresh → Resets state → Gets QR.
     */
    public function forceReconnect()
    {
        $config = $this->getServerConfig();

        if (!$this->isConfigured($config)) {
            return response()->json(['success' => false, 'error' => 'API not configured.'], 400);
        }

        $instanceName = $this->getInstanceName();
        $apiUrl = rtrim($config['api_url'], '/');
        $headers = ['apikey' => $config['api_key'], 'Content-Type' => 'application/json'];
        $steps = [];

        try {
            // ─── Step 1: Force logout (ignore errors) ───
            try {
                $r = Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/logout/{$instanceName}");
                $steps[] = "logout: " . $r->status();
            } catch (\Exception $e) {
                $steps[] = "logout: skip";
            }

            // ─── Step 2: Delete instance completely ───
            try {
                $r = Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/delete/{$instanceName}");
                $steps[] = "delete: " . $r->status();
            } catch (\Exception $e) {
                $steps[] = "delete: skip";
            }

            // Wait for API to fully process
            sleep(3);

            // ─── Step 3: Create FRESH instance with qrcode=true ───
            $createResponse = Http::withHeaders($headers)->timeout(30)->post("{$apiUrl}/instance/create", [
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
                'token' => '',
            ]);

            $createBody = $createResponse->body();
            $createData = $createResponse->json();
            $steps[] = "create: " . $createResponse->status();

            Log::info('WhatsApp ForceReconnect: FULL create response', [
                'status' => $createResponse->status(),
                'body' => $createBody,
            ]);

            // Check if CREATE response has QR
            if ($createResponse->successful() && $createData) {
                $qr = $this->extractQrCode($createData);
                if ($qr) {
                    $this->registerWebhook($config, $instanceName);
                    $steps[] = "qr_from: create";
                    return response()->json(['success' => true, 'qrcode' => $qr, 'instance' => $instanceName, 'steps' => $steps]);
                }
                // Log what keys the create response has
                $steps[] = "create_keys: " . implode(',', is_array($createData) ? array_keys($createData) : ['not_array']);
                
                // Check nested qrcode object
                if (isset($createData['qrcode'])) {
                    $steps[] = "create_qrcode_type: " . gettype($createData['qrcode']);
                    if (is_array($createData['qrcode'])) {
                        $steps[] = "create_qrcode_keys: " . implode(',', array_keys($createData['qrcode']));
                    }
                }
            }

            // ─── Step 4: Instance created but in 'connecting' state ───
            // Force logout to reset to 'close' state
            sleep(2);
            try {
                $r = Http::withHeaders($headers)->timeout(10)->delete("{$apiUrl}/instance/logout/{$instanceName}");
                $steps[] = "post_create_logout: " . $r->status();
            } catch (\Exception $e) {
                $steps[] = "post_create_logout: skip";
            }
            
            sleep(2);

            // ─── Step 5: Now connect — instance should be in 'close' state ───
            for ($i = 1; $i <= 3; $i++) {
                $connectResponse = Http::withHeaders($headers)->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");
                $connectBody = $connectResponse->body();
                $connectData = $connectResponse->json();

                Log::info("WhatsApp ForceReconnect: connect attempt {$i} after logout", [
                    'status' => $connectResponse->status(),
                    'body' => mb_substr($connectBody, 0, 1000),
                ]);

                $steps[] = "connect_{$i}: " . $connectResponse->status() . " → " . mb_substr($connectBody, 0, 150);

                if ($connectResponse->successful() && $connectData) {
                    $qr = $this->extractQrCode($connectData);
                    if ($qr) {
                        $this->registerWebhook($config, $instanceName);
                        $steps[] = "qr_from: connect_{$i}";
                        return response()->json(['success' => true, 'qrcode' => $qr, 'instance' => $instanceName, 'steps' => $steps]);
                    }
                }

                sleep(3);
            }

            // ─── Step 6: Try restart then connect ───
            try {
                $r = Http::withHeaders($headers)->timeout(10)->put("{$apiUrl}/instance/restart/{$instanceName}");
                $steps[] = "restart: " . $r->status();
            } catch (\Exception $e) {
                $steps[] = "restart: skip";
            }

            sleep(3);

            // Final connect attempt after restart
            $connectResponse = Http::withHeaders($headers)->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");
            $connectData = $connectResponse->json();
            $steps[] = "final_connect: " . $connectResponse->status() . " → " . mb_substr($connectResponse->body(), 0, 150);

            if ($connectResponse->successful() && $connectData) {
                $qr = $this->extractQrCode($connectData);
                if ($qr) {
                    $this->registerWebhook($config, $instanceName);
                    $steps[] = "qr_from: final_connect";
                    return response()->json(['success' => true, 'qrcode' => $qr, 'instance' => $instanceName, 'steps' => $steps]);
                }
            }

            // ─── FAILED ───
            $steps[] = "FAILED";
            Log::error('WhatsApp ForceReconnect: All attempts failed', ['steps' => $steps]);

            return response()->json([
                'success' => false,
                'error' => 'Evolution API is not generating QR codes. Please restart the Evolution API service from EasyPanel and try again.',
                'steps' => $steps,
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp ForceReconnect error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage(), 'steps' => $steps], 500);
        }
    }

    /**
     * Debug endpoint — shows raw Evolution API responses for troubleshooting
     */
    public function debugApi()
    {
        $config = $this->getServerConfig();

        if (!$this->isConfigured($config)) {
            return response()->json(['error' => 'API not configured']);
        }

        $instanceName = $this->getInstanceName();
        $apiUrl = rtrim($config['api_url'], '/');
        $results = [];

        // 1. Check all instances
        try {
            $r = Http::withHeaders(['apikey' => $config['api_key']])->timeout(10)->get("{$apiUrl}/instance/fetchInstances");
            $results['fetchInstances'] = ['status' => $r->status(), 'data' => $r->json()];
        } catch (\Exception $e) {
            $results['fetchInstances'] = ['error' => $e->getMessage()];
        }

        // 2. Connection state
        try {
            $r = Http::withHeaders(['apikey' => $config['api_key']])->timeout(10)->get("{$apiUrl}/instance/connectionState/{$instanceName}");
            $results['connectionState'] = ['status' => $r->status(), 'data' => $r->json()];
        } catch (\Exception $e) {
            $results['connectionState'] = ['error' => $e->getMessage()];
        }

        // 3. Connect (get QR)
        try {
            $r = Http::withHeaders(['apikey' => $config['api_key']])->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");
            $body = $r->body();
            $isJson = str_starts_with(trim($body), '{') || str_starts_with(trim($body), '[');
            $results['connect'] = [
                'status' => $r->status(),
                'is_json' => $isJson,
                'data' => $isJson ? $r->json() : mb_substr($body, 0, 300),
                'qr_extracted' => $isJson ? ($this->extractQrCode($r->json()) ? 'YES' : 'NO') : 'N/A',
            ];
        } catch (\Exception $e) {
            $results['connect'] = ['error' => $e->getMessage()];
        }

        $results['instance_name'] = $instanceName;
        $results['api_url'] = $apiUrl;

        return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
