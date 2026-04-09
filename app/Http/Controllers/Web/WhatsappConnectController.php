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

        try {
            // Step 1: Try to get QR from connect endpoint
            $response = Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");

            Log::info('WhatsApp QR: connect response', [
                'instance' => $instanceName,
                'status' => $response->status(),
                'body_preview' => mb_substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $qrcode = $this->extractQrCode($data);

                if ($qrcode) {
                    return response()->json([
                        'success' => true,
                        'qrcode' => $qrcode,
                        'instance' => $instanceName,
                    ]);
                }

                // API returned 200 but no QR — instance might be already connected or connecting
                Log::info('WhatsApp QR: 200 but no QR found in response', [
                    'keys' => is_array($data) ? array_keys($data) : 'not_array',
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'QR not available yet. Instance may be connecting.',
                    'state' => $data['state'] ?? $data['instance']['state'] ?? 'unknown',
                ]);
            }

            // Step 2: If 404, create instance and retry
            if ($response->status() === 404) {
                Log::info('WhatsApp QR: Instance not found, creating...', ['instance' => $instanceName]);

                $created = $this->createInstance($config, $instanceName);

                if ($created) {
                    // Wait a moment for instance to initialize
                    sleep(2);

                    $retryResponse = Http::withHeaders([
                        'apikey' => $config['api_key'],
                        'Content-Type' => 'application/json',
                    ])->timeout(15)->get("{$apiUrl}/instance/connect/{$instanceName}");

                    Log::info('WhatsApp QR: retry response after create', [
                        'status' => $retryResponse->status(),
                        'body_preview' => mb_substr($retryResponse->body(), 0, 500),
                    ]);

                    if ($retryResponse->successful()) {
                        $data = $retryResponse->json();
                        $qrcode = $this->extractQrCode($data);

                        if ($qrcode) {
                            return response()->json([
                                'success' => true,
                                'qrcode' => $qrcode,
                                'instance' => $instanceName,
                            ]);
                        }
                    }
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Instance created. QR will be available shortly.',
                ]);
            }

            // Step 3: Other error
            return response()->json([
                'success' => false,
                'error' => 'API returned status ' . $response->status(),
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp QR Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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

        try {
            // First, try to remove the instance completely to ensure a fresh start next time
            $response = Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->delete("{$config['api_url']}/instance/delete/{$instanceName}");

            // If it was successful OR it failed because it doesn't exist/isn't connected, consider it a success locally
            if ($response->successful() || in_array($response->status(), [400, 404])) {
                return response()->json(['success' => true, 'message' => 'WhatsApp disconnected and cleared successfully.']);
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
