<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VertexAIService
{
    private string $projectId;
    private string $location;
    private string $model;
    private array $serviceAccount;

    public function __construct(int $companyId)
    {
        $config = Setting::getValue('ai_bot', 'vertex_config', [], $companyId);

        $this->projectId = $config['project_id'] ?? '';
        $this->location = $config['location'] ?? 'us-central1';
        $this->model = $config['model'] ?? 'gemini-2.0-flash';

        // Service account JSON (stored as parsed array)
        $this->serviceAccount = $config['service_account'] ?? [];
    }

    /**
     * Generate content using Vertex AI Gemini
     *
     * @param string $systemPrompt  System instructions for the AI
     * @param array  $messages      Chat history [{role:'user',text:'...'},{role:'model',text:'...'}]
     * @param string|null $imageUrl Image URL for multimodal (vision) processing
     * @return string  AI response text
     */
    public function generateContent(string $systemPrompt, array $messages, ?string $imageUrl = null): string
    {
        if (empty($this->projectId) || empty($this->serviceAccount)) {
            Log::error('VertexAI: Service not configured — missing project_id or service_account');
            return 'AI bot is not configured. Please set up Vertex AI in Settings.';
        }

        try {
            $accessToken = $this->getAccessToken();

            $endpoint = sprintf(
                'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
                $this->location,
                $this->projectId,
                $this->location,
                $this->model
            );

            // Build request body
            $requestBody = $this->buildRequestBody($systemPrompt, $messages, $imageUrl);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($endpoint, $requestBody);

            if (!$response->successful()) {
                Log::error('VertexAI: API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return 'Sorry, I am unable to process your request right now. Please try again later.';
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($text)) {
                Log::warning('VertexAI: Empty response from API', ['response' => $data]);
                return 'Sorry, I could not generate a response. Please try again.';
            }

            return trim($text);

        } catch (\Exception $e) {
            Log::error('VertexAI: Exception - ' . $e->getMessage());
            return 'Sorry, an error occurred. Please try again later.';
        }
    }

    /**
     * Build the Gemini API request body
     */
    private function buildRequestBody(string $systemPrompt, array $messages, ?string $imageUrl): array
    {
        $contents = [];

        // Convert chat history to Gemini format
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $parts = [];

            // Add text
            if (!empty($msg['text'])) {
                $parts[] = ['text' => $msg['text']];
            }

            // Add image for the last user message if provided
            if ($imageUrl && $role === 'user' && $msg === end($messages)) {
                $parts[] = [
                    'fileData' => [
                        'mimeType' => $this->guessImageMimeType($imageUrl),
                        'fileUri' => $imageUrl,
                    ],
                ];
            }

            if (!empty($parts)) {
                $contents[] = [
                    'role' => $role,
                    'parts' => $parts,
                ];
            }
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ],
        ];

        // Add system instruction
        if (!empty($systemPrompt)) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        return $body;
    }

    /**
     * Get OAuth2 access token from service account
     * Caches the token until it expires
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'vertex_ai_token_' . md5($this->projectId);

        return Cache::remember($cacheKey, 3300, function () { // Cache for ~55 min (tokens last 60 min)
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate a new OAuth2 access token using service account JWT
     */
    private function generateAccessToken(): string
    {
        $now = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $claimSet = [
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $payload = base64url_encode(json_encode($claimSet));
        $signingInput = "{$header}.{$payload}";

        // Sign with RSA private key
        $privateKey = openssl_pkey_get_private($this->serviceAccount['private_key']);
        if (!$privateKey) {
            throw new \RuntimeException('VertexAI: Invalid private key in service account');
        }

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $signingInput . '.' . base64url_encode($signature);

        // Exchange JWT for access token
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('VertexAI: Failed to get access token - ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Guess image MIME type from URL
     */
    private function guessImageMimeType(string $url): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }

    /**
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->projectId) && !empty($this->serviceAccount);
    }
}

/**
 * URL-safe base64 encoding (no padding)
 */
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
