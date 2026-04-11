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
     * Generate content using Vertex AI Gemini (Tier 2 — full conversational)
     *
     * @return array  ['text' => string, 'prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]
     */
    public function generateContent(string $systemPrompt, array $messages, ?string $imageUrl = null, int $maxOutputTokens = 8192): array
    {
        if (empty($this->projectId) || empty($this->serviceAccount)) {
            Log::error('VertexAI: Service not configured');
            return ['text' => 'AI bot is not configured. Please set up Vertex AI in Settings.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        try {
            $accessToken = $this->getAccessToken();
            $endpoint = $this->getEndpoint();
            $requestBody = $this->buildRequestBody($systemPrompt, $messages, $imageUrl, $maxOutputTokens);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($endpoint, $requestBody);

            if (!$response->successful()) {
                Log::error('VertexAI: API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['text' => 'Sorry, I am unable to process your request right now.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $data['usageMetadata'] ?? [];

            return [
                'text' => trim($text) ?: 'Sorry, I could not generate a response.',
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];

        } catch (\Exception $e) {
            Log::error('VertexAI: Exception - ' . $e->getMessage());
            return ['text' => 'Sorry, an error occurred. Please try again later.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }
    }

    /**
     * Generate content with a PDF file sent as inline base64 data (multimodal)
     * This allows Gemini to "see" the PDF pages — works for both text and image-based PDFs.
     *
     * @param string $systemPrompt  System instruction
     * @param string $pdfPath       Absolute path to the PDF file
     * @param string $userMessage   User prompt text
     * @param int    $maxOutputTokens  Max output tokens
     * @return array  ['text' => string, 'prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]
     */
    public function generateContentWithPDF(string $systemPrompt, string $pdfPath, string $userMessage, int $maxOutputTokens = 8192): array
    {
        if (empty($this->projectId) || empty($this->serviceAccount)) {
            Log::error('VertexAI: Service not configured');
            return ['text' => 'AI bot is not configured. Please set up Vertex AI in Settings.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        // Boost memory for large PDF base64 encoding
        $originalMemory = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        try {
            $accessToken = $this->getAccessToken();
            $endpoint = $this->getEndpoint();

            // Check file size — Gemini inline limit is ~20MB total request
            $fileSize = filesize($pdfPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            Log::info('VertexAI PDF: Processing file', ['size_mb' => $fileSizeMB, 'path' => basename($pdfPath)]);

            if ($fileSize > 20 * 1024 * 1024) {
                throw new \RuntimeException('PDF file is too large for AI analysis (max 20MB). Please compress the PDF and try again.');
            }

            // For large PDFs (>5MB), try to reduce to first 5 pages using Ghostscript
            $pdfToSend = $pdfPath;
            if ($fileSize > 5 * 1024 * 1024) {
                $pdfToSend = $this->reducePDFPages($pdfPath, 5);
                $newSize = filesize($pdfToSend);
                Log::info('VertexAI PDF: Reduced PDF for API', [
                    'original_mb' => $fileSizeMB,
                    'reduced_mb' => round($newSize / 1024 / 1024, 2),
                    'max_pages' => 5,
                ]);
            }

            // Read PDF and encode as base64
            $pdfContent = file_get_contents($pdfToSend);
            if ($pdfContent === false) {
                throw new \RuntimeException('Could not read PDF file.');
            }
            $pdfBase64 = base64_encode($pdfContent);
            unset($pdfContent); // Free memory immediately

            // Clean up temp reduced PDF
            if ($pdfToSend !== $pdfPath && file_exists($pdfToSend)) {
                @unlink($pdfToSend);
            }

            // Build multimodal request with PDF inline data
            $body = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'inlineData' => [
                                    'mimeType' => 'application/pdf',
                                    'data' => $pdfBase64,
                                ],
                            ],
                            ['text' => $userMessage],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topP' => 0.95,
                    'maxOutputTokens' => $maxOutputTokens,
                ],
            ];
            unset($pdfBase64); // Free base64 string

            if (!empty($systemPrompt)) {
                $body['systemInstruction'] = [
                    'parts' => [['text' => $systemPrompt]],
                ];
            }

            $jsonBody = json_encode($body);
            unset($body); // Free array

            Log::info('VertexAI PDF: Sending request to Gemini API', ['request_size_mb' => round(strlen($jsonBody) / 1024 / 1024, 2)]);

            // Use cURL directly to avoid Expect:100-continue header (causes HTTP 417)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$accessToken}",
                    'Content-Type: application/json',
                    'Expect:', // Explicitly disable Expect: 100-continue
                ],
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            unset($jsonBody); // Free JSON body

            if ($curlError) {
                Log::error('VertexAI PDF: cURL error', ['error' => $curlError]);
                throw new \RuntimeException('Network error while sending PDF to AI: ' . $curlError);
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                Log::error('VertexAI PDF: API error', ['status' => $httpCode, 'body' => substr($responseBody, 0, 500)]);
                throw new \RuntimeException('AI service returned an error while processing the PDF (HTTP ' . $httpCode . ').');
            }

            $data = json_decode($responseBody, true);
            unset($responseBody);

            // Check for blocked/empty candidates
            if (empty($data['candidates'])) {
                Log::error('VertexAI PDF: No candidates in response', ['data' => json_encode(array_keys($data))]);
                throw new \RuntimeException('AI could not process this PDF. The content may be too complex or blocked by safety filters.');
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $data['usageMetadata'] ?? [];

            Log::info('VertexAI PDF: Response received', ['text_length' => strlen($text), 'tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0)]);

            return [
                'text' => trim($text) ?: 'Could not extract content from PDF.',
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Error $e) {
            Log::error('VertexAI PDF: Fatal error - ' . $e->getMessage());
            throw new \RuntimeException('PDF processing ran out of memory. Please try a smaller PDF file (under 10MB).');
        } catch (\Exception $e) {
            Log::error('VertexAI PDF: Exception - ' . $e->getMessage());
            throw new \RuntimeException('An error occurred while processing the PDF with AI: ' . $e->getMessage());
        } finally {
            ini_set('memory_limit', $originalMemory);
        }
    }

    /**
     * Reduce a PDF to only the first N pages using available system tools.
     * Tries Ghostscript (gs), pdftk, and qpdf in order.
     * Returns path to reduced PDF, or original path if reduction fails.
     */
    private function reducePDFPages(string $pdfPath, int $maxPages = 5): string
    {
        $tempDir = dirname($pdfPath);
        $reducedPath = $tempDir . DIRECTORY_SEPARATOR . 'reduced_' . basename($pdfPath);

        // Try Ghostscript (most commonly available)
        $gsPath = $this->findCommand('gs');
        if ($gsPath) {
            $cmd = sprintf(
                '%s -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dFirstPage=1 -dLastPage=%d -sOutputFile=%s %s 2>&1',
                escapeshellarg($gsPath),
                $maxPages,
                escapeshellarg($reducedPath),
                escapeshellarg($pdfPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($reducedPath) && filesize($reducedPath) > 0) {
                Log::info('VertexAI PDF: Reduced using Ghostscript', ['pages' => $maxPages]);
                return $reducedPath;
            }
            Log::warning('VertexAI PDF: Ghostscript reduction failed', ['code' => $returnCode, 'output' => implode("\n", array_slice($output, -3))]);
        }

        // Try pdftk
        $pdftkPath = $this->findCommand('pdftk');
        if ($pdftkPath) {
            $cmd = sprintf(
                '%s %s cat 1-%d output %s 2>&1',
                escapeshellarg($pdftkPath),
                escapeshellarg($pdfPath),
                $maxPages,
                escapeshellarg($reducedPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($reducedPath) && filesize($reducedPath) > 0) {
                Log::info('VertexAI PDF: Reduced using pdftk', ['pages' => $maxPages]);
                return $reducedPath;
            }
        }

        // Try qpdf
        $qpdfPath = $this->findCommand('qpdf');
        if ($qpdfPath) {
            $cmd = sprintf(
                '%s %s --pages . 1-%d -- %s 2>&1',
                escapeshellarg($qpdfPath),
                escapeshellarg($pdfPath),
                $maxPages,
                escapeshellarg($reducedPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($reducedPath) && filesize($reducedPath) > 0) {
                Log::info('VertexAI PDF: Reduced using qpdf', ['pages' => $maxPages]);
                return $reducedPath;
            }
        }

        // No tools available — return original and hope it works
        Log::warning('VertexAI PDF: No PDF reduction tools available (gs, pdftk, qpdf). Sending full PDF.');
        return $pdfPath;
    }

    /**
     * Find a system command path.
     */
    private function findCommand(string $command): ?string
    {
        $cmd = PHP_OS_FAMILY === 'Windows' ? "where {$command}" : "which {$command}";
        exec($cmd . ' 2>/dev/null', $output, $returnCode);
        if ($returnCode === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        return null;
    }

    /**
     * Lightweight classification call (Tier 1 — small prompt, deterministic)
     *
     * @param string $prompt  Short classification prompt
     * @return array  ['text' => string, 'prompt_tokens' => int, 'completion_tokens' => int, 'total_tokens' => int]
     */
    public function classifyContent(string $prompt): array
    {
        if (empty($this->projectId) || empty($this->serviceAccount)) {
            return ['text' => 'NONE', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        try {
            $accessToken = $this->getAccessToken();
            $endpoint = $this->getEndpoint();

            $body = [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topP' => 0.8,
                    'maxOutputTokens' => 50,
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($endpoint, $body);

            if (!$response->successful()) {
                Log::error('VertexAI Classify: API error', ['status' => $response->status()]);
                return ['text' => 'NONE', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'NONE';
            $usage = $data['usageMetadata'] ?? [];

            return [
                'text' => trim($text),
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];

        } catch (\Exception $e) {
            Log::error('VertexAI Classify: Exception - ' . $e->getMessage());
            return ['text' => 'NONE', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }
    }

    /**
     * Get API endpoint URL
     */
    private function getEndpoint(): string
    {
        return sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:generateContent',
            $this->location,
            $this->projectId,
            $this->location,
            $this->model
        );
    }

    /**
     * Build the Gemini API request body
     */
    private function buildRequestBody(string $systemPrompt, array $messages, ?string $imageUrl, int $maxOutputTokens = 8192): array
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

            // Add inline data (for PDF/file parts passed in messages)
            if (!empty($msg['inline_data'])) {
                $parts[] = [
                    'inlineData' => $msg['inline_data'],
                ];
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
                'maxOutputTokens' => $maxOutputTokens,
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
        $privateKeyString = $this->serviceAccount['private_key'] ?? '';
        
        // Fix all possible newline escape variants from JSON/DB storage
        // Order matters: fix double-escaped first, then single-escaped
        $privateKeyString = str_replace(['\\n', '\\r'], ["\n", ""], $privateKeyString);
        $privateKeyString = str_replace("\r", "", $privateKeyString);
        
        // Ensure proper PEM format: header/footer on own lines
        $privateKeyString = trim($privateKeyString);
        
        $privateKey = openssl_pkey_get_private($privateKeyString);
        if (!$privateKey) {
            Log::error('VertexAI: Private key parse failed', [
                'key_length' => strlen($privateKeyString),
                'starts_with' => substr($privateKeyString, 0, 40),
                'ends_with' => substr($privateKeyString, -30),
                'has_begin' => str_contains($privateKeyString, '-----BEGIN'),
                'has_end' => str_contains($privateKeyString, '-----END'),
                'newline_count' => substr_count($privateKeyString, "\n"),
                'openssl_error' => openssl_error_string(),
            ]);
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
