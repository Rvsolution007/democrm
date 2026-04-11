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
    public function generateContent(string $systemPrompt, array $messages, ?string $imageUrl = null): array
    {
        if (empty($this->projectId) || empty($this->serviceAccount)) {
            Log::error('VertexAI: Service not configured');
            return ['text' => 'AI bot is not configured. Please set up Vertex AI in Settings.', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        }

        try {
            $accessToken = $this->getAccessToken();
            $endpoint = $this->getEndpoint();
            $requestBody = $this->buildRequestBody($systemPrompt, $messages, $imageUrl);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($endpoint, $requestBody);

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
                'maxOutputTokens' => 512,
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

    /**
     * Generate content using Vertex AI Gemini with a PDF file (multimodal)
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

            // Check file size
            $fileSize = filesize($pdfPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            Log::info('VertexAI PDF: Processing file', ['size_mb' => $fileSizeMB, 'path' => basename($pdfPath)]);

            // Safety: if file is too large for inline, try to reduce pages
            $pdfToSend = $pdfPath;
            if ($fileSize > 15 * 1024 * 1024) {
                $reduced = $this->reducePDFPages($pdfPath, 10);
                if ($reduced !== $pdfPath && file_exists($reduced) && filesize($reduced) < $fileSize) {
                    $pdfToSend = $reduced;
                    Log::info('VertexAI PDF: Auto-reduced for API', [
                        'original_mb' => $fileSizeMB,
                        'reduced_mb' => round(filesize($reduced) / 1024 / 1024, 2),
                    ]);
                }
            }

            if (filesize($pdfToSend) > 20 * 1024 * 1024) {
                throw new \RuntimeException('PDF is too large for Gemini (max 20MB per request after reduction).');
            }

            // Read PDF and encode as base64
            $pdfContent = file_get_contents($pdfToSend);
            if ($pdfContent === false) {
                throw new \RuntimeException('Could not read PDF file.');
            }
            $pdfBase64 = base64_encode($pdfContent);
            unset($pdfContent); // Free memory immediately

            // Cleanup temp reduced PDF
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
                            [
                                'text' => $userMessage,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topP' => 0.8,
                    'maxOutputTokens' => $maxOutputTokens,
                ],
            ];

            unset($pdfBase64); // Free memory

            if (!empty($systemPrompt)) {
                $body['systemInstruction'] = [
                    'parts' => [['text' => $systemPrompt]],
                ];
            }

            Log::info('VertexAI PDF: Sending request to Gemini API');

            // Use cURL for large payloads — Laravel HTTP client may add Expect header
            $ch = curl_init($endpoint);
            $jsonBody = json_encode($body);
            unset($body);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                    'Expect:',  // Disable Expect: 100-continue
                ],
                CURLOPT_TIMEOUT => 300,
                CURLOPT_CONNECTTIMEOUT => 30,
            ]);

            unset($jsonBody);
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \RuntimeException('VertexAI PDF: cURL error - ' . $curlError);
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                Log::error('VertexAI PDF: API error', ['status' => $httpCode, 'body' => substr($responseBody, 0, 500)]);
                throw new \RuntimeException("VertexAI PDF: API error (HTTP {$httpCode})");
            }

            $data = json_decode($responseBody, true);
            unset($responseBody);

            if (!$data) {
                throw new \RuntimeException('VertexAI PDF: Invalid JSON response');
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $usage = $data['usageMetadata'] ?? [];

            Log::info('VertexAI PDF: Response received', [
                'text_length' => strlen($text),
                'tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ]);

            return [
                'text' => trim($text) ?: 'No response from AI.',
                'prompt_tokens' => $usage['promptTokenCount'] ?? 0,
                'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($usage['promptTokenCount'] ?? 0) + ($usage['candidatesTokenCount'] ?? 0),
            ];

        } catch (\Exception $e) {
            Log::error('VertexAI PDF: Exception', ['error' => $e->getMessage()]);
            throw $e;
        } finally {
            ini_set('memory_limit', $originalMemory);
        }
    }

    /**
     * Reduce a PDF to a specific number of pages using system tools
     */
    private function reducePDFPages(string $pdfPath, int $maxPages = 10): string
    {
        $tempDir = dirname($pdfPath);
        $reducedPath = $tempDir . DIRECTORY_SEPARATOR . 'reduced_' . basename($pdfPath);

        // Try Ghostscript
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
            Log::warning('VertexAI PDF: Ghostscript reduction failed', ['code' => $returnCode]);
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
                return $reducedPath;
            }
        }

        Log::warning('VertexAI PDF: No reduction tools available (gs, pdftk, qpdf). Sending full PDF.');
        return $pdfPath;
    }

    /**
     * Find a system command path
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
     * Extract a specific page range from a PDF
     */
    public function extractPDFPageRange(string $pdfPath, int $firstPage, int $lastPage): ?string
    {
        $tempDir = dirname($pdfPath);
        $chunkPath = $tempDir . DIRECTORY_SEPARATOR . "chunk_{$firstPage}_{$lastPage}_" . basename($pdfPath);

        $gsPath = $this->findCommand('gs');
        if ($gsPath) {
            $cmd = sprintf(
                '%s -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -sOutputFile=%s %s 2>&1',
                escapeshellarg($gsPath),
                $firstPage,
                $lastPage,
                escapeshellarg($chunkPath),
                escapeshellarg($pdfPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) {
                return $chunkPath;
            }
        }

        $pdftkPath = $this->findCommand('pdftk');
        if ($pdftkPath) {
            $cmd = sprintf(
                '%s %s cat %d-%d output %s 2>&1',
                escapeshellarg($pdftkPath),
                escapeshellarg($pdfPath),
                $firstPage,
                $lastPage,
                escapeshellarg($chunkPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) {
                return $chunkPath;
            }
        }

        $qpdfPath = $this->findCommand('qpdf');
        if ($qpdfPath) {
            $cmd = sprintf(
                '%s %s --pages . %d-%d -- %s 2>&1',
                escapeshellarg($qpdfPath),
                escapeshellarg($pdfPath),
                $firstPage,
                $lastPage,
                escapeshellarg($chunkPath)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && file_exists($chunkPath) && filesize($chunkPath) > 0) {
                return $chunkPath;
            }
        }

        Log::warning('VertexAI: Could not extract PDF page range', ['first' => $firstPage, 'last' => $lastPage]);
        return null;
    }

    /**
     * Get the total page count of a PDF
     */
    public function getPDFPageCount(string $pdfPath): int
    {
        // Try pdfinfo
        $pdfinfoPath = $this->findCommand('pdfinfo');
        if ($pdfinfoPath) {
            exec(escapeshellarg($pdfinfoPath) . ' ' . escapeshellarg($pdfPath) . ' 2>/dev/null', $output, $returnCode);
            if ($returnCode === 0) {
                foreach ($output as $line) {
                    if (preg_match('/^Pages:\s+(\d+)/i', $line, $m)) {
                        return (int)$m[1];
                    }
                }
            }
        }

        // Fallback: read PDF and count /Type /Page entries
        $content = file_get_contents($pdfPath);
        if ($content !== false) {
            $count = preg_match_all('/\/Type\s*\/Page[^s]/i', $content);
            if ($count > 0) return $count;
        }

        // Estimate from file size (~100KB per page for image-heavy PDFs)
        $sizeMB = filesize($pdfPath) / 1024 / 1024;
        return max(1, (int)ceil($sizeMB * 10));
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
