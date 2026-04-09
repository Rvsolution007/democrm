<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared WhatsApp messaging service — used by AIChatbotService, ListBotService, and AutoReplyService.
 * Provides methods for sending text, media, and interactive list messages via the Evolution API.
 */
class WhatsAppService
{
    private string $apiUrl;
    private string $apiKey;
    private bool $configured = false;

    public function __construct(int $companyId)
    {
        $config = Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], $companyId);

        $this->apiUrl = rtrim($config['api_url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->configured = !empty($this->apiUrl) && !empty($this->apiKey);
    }

    /**
     * Format phone number to international format (Indian default).
     */
    public static function formatPhone(string $phone): string
    {
        $formatted = preg_replace('/\D/', '', $phone);
        if (strlen($formatted) == 10) {
            $formatted = '91' . $formatted;
        }
        return $formatted;
    }

    /**
     * Check if WhatsApp API is configured.
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Send a plain text message via Evolution API.
     */
    public function sendText(string $instanceName, string $phone, string $text): bool
    {
        if (!$this->configured) {
            Log::error('WhatsAppService: API not configured');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/message/sendText/{$instanceName}", [
                'number' => self::formatPhone($phone),
                'text' => $text,
            ]);

            if (!$response->successful()) {
                Log::error('WhatsAppService: sendText failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsAppService: sendText exception - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send an Interactive List Message via Evolution API.
     *
     * @param string $instanceName  Evolution API instance name
     * @param string $phone         Recipient phone number
     * @param string $title         List title (max 60 chars) — shown at top
     * @param string $description   Body text — shown below title
     * @param string $buttonText    Button label (max 20 chars) — e.g., "☰ Menu"
     * @param array  $sections      Array of sections, each with:
     *                              [
     *                                  'title' => 'Section Title',
     *                                  'rows' => [
     *                                      ['title' => 'Row 1', 'description' => 'Desc', 'rowId' => 'cat_5'],
     *                                      ['title' => 'Row 2', 'description' => 'Desc', 'rowId' => 'prod_42'],
     *                                  ]
     *                              ]
     * @param string $footer        Footer text (optional, max 60 chars)
     * @return bool
     *
     * WhatsApp Limits:
     * - Max 10 sections
     * - Max 10 rows per section
     * - Max 100 total rows
     * - Title max 24 chars (row title)
     * - Description max 72 chars (row description)
     */
    public function sendList(
        string $instanceName,
        string $phone,
        string $title,
        string $description,
        string $buttonText,
        array $sections,
        string $footer = ''
    ): bool {
        if (!$this->configured) {
            Log::error('WhatsAppService: API not configured');
            return false;
        }

        try {
            // Enforce WhatsApp limits
            $sections = array_slice($sections, 0, 10); // Max 10 sections
            $totalRows = 0;

            foreach ($sections as &$section) {
                // Truncate section title to 24 chars
                $section['title'] = mb_substr($section['title'] ?? '', 0, 24);

                // Enforce row limits
                $section['rows'] = array_slice($section['rows'] ?? [], 0, 10); // Max 10 per section

                foreach ($section['rows'] as &$row) {
                    // Truncate row fields to WhatsApp limits
                    $row['title'] = mb_substr($row['title'] ?? '', 0, 24);
                    if (isset($row['description'])) {
                        $row['description'] = mb_substr($row['description'], 0, 72);
                    }
                    $totalRows++;
                }
                unset($row);

                // If we've hit 100 total rows, stop
                if ($totalRows >= 100) break;
            }
            unset($section);

            $listMessage = [
                'title' => mb_substr($title, 0, 60),
                'description' => $description,
                'buttonText' => mb_substr($buttonText, 0, 20),
                'sections' => $sections,
            ];

            if (!empty($footer)) {
                $listMessage['footerText'] = mb_substr($footer, 0, 60);
            }

            $payload = [
                'number' => self::formatPhone($phone),
                'listMessage' => $listMessage,
            ];

            Log::info('WhatsAppService: Sending interactive list', [
                'instance' => $instanceName,
                'phone' => $phone,
                'sections_count' => count($sections),
                'total_rows' => $totalRows,
            ]);

            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/message/sendList/{$instanceName}", $payload);

            if (!$response->successful()) {
                Log::error('WhatsAppService: sendList failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);
                return false;
            }

            Log::info('WhatsAppService: Interactive list sent successfully', [
                'phone' => $phone,
                'title' => $title,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsAppService: sendList exception - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build category sections for Interactive List.
     * Groups categories into sections of max 10 rows.
     *
     * @param \Illuminate\Database\Eloquent\Collection $categories
     * @return array Sections array ready for sendList()
     */
    public static function buildCategorySections($categories, string $sectionTitle = 'Categories'): array
    {
        $sections = [];
        $rows = [];

        foreach ($categories as $cat) {
            $productCount = $cat->products_count ?? $cat->products()->where('status', 'active')->count();
            $rows[] = [
                'title' => mb_substr($cat->name, 0, 24),
                'description' => $productCount . ' products available',
                'rowId' => 'cat_' . $cat->id,
            ];

            // WhatsApp allows max 10 rows per section
            if (count($rows) >= 10) {
                $sections[] = ['title' => mb_substr($sectionTitle, 0, 24), 'rows' => $rows];
                $rows = [];
                $sectionTitle = $sectionTitle . ' (cont.)';
            }
        }

        if (!empty($rows)) {
            $sections[] = ['title' => mb_substr($sectionTitle, 0, 24), 'rows' => $rows];
        }

        return $sections;
    }

    /**
     * Build product sections for Interactive List.
     *
     * @param \Illuminate\Database\Eloquent\Collection $products
     * @param callable|null $displayNameFn  Function to get display name: fn(Product) => string
     * @return array Sections array ready for sendList()
     */
    public static function buildProductSections($products, ?callable $displayNameFn = null, string $sectionTitle = 'Products'): array
    {
        $sections = [];
        $rows = [];

        foreach ($products as $product) {
            $name = $displayNameFn ? $displayNameFn($product) : $product->name;
            $desc = '';
            if ($product->sale_price > 0) {
                $desc = '₹' . number_format($product->sale_price / 100, 2);
            }
            if ($product->description) {
                $desc .= ($desc ? ' | ' : '') . mb_substr($product->description, 0, 50);
            }

            $rows[] = [
                'title' => mb_substr($name, 0, 24),
                'description' => mb_substr($desc, 0, 72) ?: 'Select this product',
                'rowId' => 'prod_' . $product->id,
            ];

            if (count($rows) >= 10) {
                $sections[] = ['title' => mb_substr($sectionTitle, 0, 24), 'rows' => $rows];
                $rows = [];
                $sectionTitle = $sectionTitle . ' (cont.)';
            }
        }

        if (!empty($rows)) {
            $sections[] = ['title' => mb_substr($sectionTitle, 0, 24), 'rows' => $rows];
        }

        return $sections;
    }

    /**
     * Build option sections for Interactive List (column values, combo values, etc.).
     *
     * @param array $values      Array of string values
     * @param string $columnName Name of the column (used as section title)
     * @param string $prefix     Prefix for rowId (e.g., 'col_7_' or 'combo_')
     * @return array Sections array ready for sendList()
     */
    public static function buildOptionSections(array $values, string $columnName, string $prefix): array
    {
        $sections = [];
        $rows = [];

        foreach ($values as $val) {
            $rows[] = [
                'title' => mb_substr($val, 0, 24),
                'description' => 'Select ' . mb_substr($val, 0, 60),
                'rowId' => $prefix . $val,
            ];

            if (count($rows) >= 10) {
                $sections[] = ['title' => mb_substr($columnName, 0, 24), 'rows' => $rows];
                $rows = [];
            }
        }

        if (!empty($rows)) {
            $sections[] = ['title' => mb_substr($columnName, 0, 24), 'rows' => $rows];
        }

        return $sections;
    }

    /**
     * Parse a rowId from a list response message.
     * Returns ['type' => 'category|product|column|combo', 'id' => ..., 'value' => ...] or null.
     */
    public static function parseRowId(string $rowId): ?array
    {
        // Category: cat_5
        if (preg_match('/^cat_(\d+)$/', $rowId, $m)) {
            return ['type' => 'category', 'id' => (int) $m[1], 'value' => null];
        }

        // Product: prod_42
        if (preg_match('/^prod_(\d+)$/', $rowId, $m)) {
            return ['type' => 'product', 'id' => (int) $m[1], 'value' => null];
        }

        // Column filter: col_7_225mm
        if (preg_match('/^col_(\d+)_(.+)$/', $rowId, $m)) {
            return ['type' => 'column', 'id' => (int) $m[1], 'value' => $m[2]];
        }

        // Combo: combo_<slug>_<value>
        if (preg_match('/^combo_([^_]+)_(.+)$/', $rowId, $m)) {
            return ['type' => 'combo', 'id' => $m[1], 'value' => $m[2]];
        }

        return null;
    }
}
