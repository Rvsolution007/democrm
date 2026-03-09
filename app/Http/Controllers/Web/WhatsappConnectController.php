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
     * Get QR code from Evolution API for this user's instance
     */
    public function getQrCode()
    {
        $config = $this->getServerConfig();

        if (!$this->isConfigured($config)) {
            return response()->json(['error' => 'WhatsApp API not configured. Ask admin to configure in Settings → WhatsApp API.'], 400);
        }

        $instanceName = $this->getInstanceName();

        try {
            // First try to connect (get QR)
            $response = Http::withHeaders([
                'apikey' => $config['api_key'],
                'Content-Type' => 'application/json',
            ])->get("{$config['api_url']}/instance/connect/{$instanceName}");

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'qrcode' => $data['base64'] ?? $data['qrcode']['base64'] ?? null,
                    'pairingCode' => $data['pairingCode'] ?? null,
                    'code' => $data['code'] ?? null,
                    'instance' => $instanceName,
                ]);
            }

            // If instance doesn't exist (404), create it first
            if ($response->status() === 404) {
                $created = $this->createInstance($config, $instanceName);
                if ($created) {
                    // Retry getting QR after creation
                    $retryResponse = Http::withHeaders([
                        'apikey' => $config['api_key'],
                        'Content-Type' => 'application/json',
                    ])->get("{$config['api_url']}/instance/connect/{$instanceName}");

                    if ($retryResponse->successful()) {
                        $data = $retryResponse->json();
                        return response()->json([
                            'success' => true,
                            'qrcode' => $data['base64'] ?? $data['qrcode']['base64'] ?? null,
                            'pairingCode' => $data['pairingCode'] ?? null,
                            'instance' => $instanceName,
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to get QR code: ' . $response->body(),
            ], $response->status());
        } catch (\Exception $e) {
            Log::error('WhatsApp QR Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
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
                return response()->json([
                    'success' => true,
                    'state' => $data['instance']['state'] ?? $data['state'] ?? 'unknown',
                    'instance' => $instanceName,
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

            return $response->successful();
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
}
