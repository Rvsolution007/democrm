<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared WhatsApp messaging service — dual API support.
 * 
 * Supports TWO WhatsApp APIs simultaneously:
 * 1. Evolution API (Baileys/QR) — for bulk sending, follow-ups, and fallback bot replies
 * 2. Official WhatsApp Cloud API — for interactive lists, buttons, and premium bot replies
 * 
 * Routing Logic:
 * - Bot replies: Official API if ON, else Evolution API
 * - Bulk/Follow-up: Always Evolution API (free)
 * - If only one API configured: uses that for everything
 */
class WhatsAppService
{
    // Evolution API (Baileys)
    private string $apiUrl;
    private string $apiKey;
    private bool $evolutionConfigured = false;
    private bool $evolutionEnabled = false;

    // Evolution sub-feature toggles
    private bool $evoFollowupEnabled = true;
    private bool $evoBulkEnabled = true;
    private bool $evoTextmenuEnabled = true;

    // Official WhatsApp Cloud API
    private string $officialPhoneNumberId = '';
    private string $officialAccessToken = '';
    private bool $officialConfigured = false;
    private bool $officialEnabled = false;

    private int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;

        // Load Evolution API config
        $config = Setting::getValue('whatsapp', 'api_config', [
            'api_url' => '',
            'api_key' => '',
        ], $companyId);

        $this->apiUrl = rtrim($config['api_url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->evolutionConfigured = !empty($this->apiUrl) && !empty($this->apiKey);
        $this->evolutionEnabled = (bool) Setting::getValue('whatsapp', 'evolution_api_enabled', true, $companyId);

        // Evolution sub-feature toggles
        $this->evoFollowupEnabled = (bool) Setting::getValue('whatsapp', 'evolution_followup_enabled', true, $companyId);
        $this->evoBulkEnabled = (bool) Setting::getValue('whatsapp', 'evolution_bulk_enabled', true, $companyId);
        $this->evoTextmenuEnabled = (bool) Setting::getValue('whatsapp', 'evolution_textmenu_enabled', true, $companyId);

        // Load Official Cloud API config
        $officialConfig = Setting::getValue('whatsapp', 'official_api_config', [
            'phone_number_id' => '',
            'access_token' => '',
        ], $companyId);

        $this->officialPhoneNumberId = $officialConfig['phone_number_id'] ?? '';
        $this->officialAccessToken = $officialConfig['access_token'] ?? '';
        $this->officialConfigured = !empty($this->officialPhoneNumberId) && !empty($this->officialAccessToken);
        $this->officialEnabled = (bool) Setting::getValue('whatsapp', 'official_api_enabled', false, $companyId);

        // Backward compat: if evolution_api_enabled setting doesn't exist, default ON if configured
        if (!Setting::where('company_id', $companyId)->where('group', 'whatsapp')->where('key', 'evolution_api_enabled')->exists()) {
            $this->evolutionEnabled = $this->evolutionConfigured;
        }
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
     * Check if ANY WhatsApp API is configured.
     */
    public function isConfigured(): bool
    {
        return ($this->evolutionConfigured && $this->evolutionEnabled) 
            || ($this->officialConfigured && $this->officialEnabled);
    }

    /**
     * Check if Official Cloud API is active.
     */
    public function isOfficialApiActive(): bool
    {
        return $this->officialConfigured && $this->officialEnabled;
    }

    /**
     * Check if Evolution API is active.
     */
    public function isEvolutionApiActive(): bool
    {
        return $this->evolutionConfigured && $this->evolutionEnabled;
    }

    /**
     * Send a plain text message — routes to best available API.
     * For bot replies: Official API preferred (if ON), else Evolution API.
     */
    public function sendText(string $instanceName, string $phone, string $text): bool
    {
        // Try Official API first (for bot interactions — free user-initiated)
        if ($this->isOfficialApiActive()) {
            $sent = $this->sendTextViaOfficialApi($phone, $text);
            if ($sent) return true;
            // Fall through to Evolution API
        }

        // Evolution API fallback
        if ($this->isEvolutionApiActive()) {
            return $this->sendTextViaEvolution($instanceName, $phone, $text);
        }

        Log::error('WhatsAppService: No API configured or enabled');
        return false;
    }

    /**
     * Send text specifically via Evolution API (used for bulk sender, follow-ups).
     */
    public function sendTextViaEvolution(string $instanceName, string $phone, string $text): bool
    {
        if (!$this->evolutionConfigured) {
            Log::error('WhatsAppService: Evolution API not configured');
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
                Log::error('WhatsAppService: Evolution sendText failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsAppService: Evolution sendText exception - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send text via Official WhatsApp Cloud API.
     */
    public function sendTextViaOfficialApi(string $phone, string $text): bool
    {
        if (!$this->officialConfigured) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->officialAccessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$this->officialPhoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => self::formatPhone($phone),
                'type' => 'text',
                'text' => ['body' => $text],
            ]);

            if (!$response->successful()) {
                Log::warning('WhatsAppService: Official API sendText failed', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);
                return false;
            }

            Log::info('WhatsAppService: Sent text via Official API', ['phone' => $phone]);
            return true;
        } catch (\Exception $e) {
            Log::warning('WhatsAppService: Official API exception - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message for bulk sending — uses Evolution API (free) if sub-toggle ON, else Official.
     */
    public function sendForBulk(string $instanceName, string $phone, string $text): bool
    {
        if ($this->evolutionConfigured && $this->evolutionEnabled && $this->evoBulkEnabled) {
            return $this->sendTextViaEvolution($instanceName, $phone, $text);
        }
        // Fallback to Official API
        if ($this->officialConfigured && $this->officialEnabled) {
            return $this->sendTextViaOfficialApi($phone, $text);
        }
        // Last resort: try Evolution anyway
        return $this->sendTextViaEvolution($instanceName, $phone, $text);
    }

    /**
     * Send message for follow-up — uses Evolution API (free) if sub-toggle ON, else Official.
     */
    public function sendForFollowup(string $instanceName, string $phone, string $text): bool
    {
        if ($this->evolutionConfigured && $this->evolutionEnabled && $this->evoFollowupEnabled) {
            return $this->sendTextViaEvolution($instanceName, $phone, $text);
        }
        // Fallback to Official API
        if ($this->officialConfigured && $this->officialEnabled) {
            return $this->sendTextViaOfficialApi($phone, $text);
        }
        // Last resort: try Evolution anyway
        return $this->sendTextViaEvolution($instanceName, $phone, $text);
    }

    /**
     * Check if text menu fallback should use Evolution API.
     * When evoTextmenuEnabled is OFF, sendList() will prefer Official API native list.
     */
    public function shouldUseTextMenuFallback(): bool
    {
        return $this->evolutionEnabled && $this->evoTextmenuEnabled;
    }

    /**
     * Send an Interactive List Message — dual API routing.
     * 
     * Priority:
     * 1. Official Cloud API → native interactive list (≡ Menu popup) ✅
     * 2. Evolution API → native interactive list (Baileys, often blocked)
     * 3. Text fallback → numbered text menu
     *
     * @return bool|array  true = native list sent, array = text fallback with rowMap, false = failed
     */
    public function sendList(
        string $instanceName,
        string $phone,
        string $title,
        string $description,
        string $buttonText,
        array $sections,
        string $footer = ''
    ): bool|array {
        if (!$this->isConfigured()) {
            Log::error('WhatsAppService: No API configured');
            return false;
        }

        // Enforce WhatsApp limits on sections/rows before sending
        $sections = array_slice($sections, 0, 10);
        foreach ($sections as &$section) {
            $section['title'] = mb_substr($section['title'] ?? '', 0, 24);
            $section['rows'] = array_slice($section['rows'] ?? [], 0, 10);
            foreach ($section['rows'] as &$row) {
                $row['title'] = mb_substr($row['title'] ?? '', 0, 24);
                if (isset($row['description'])) {
                    $row['description'] = mb_substr($row['description'], 0, 72);
                }
            }
            unset($row);
        }
        unset($section);

        // ── Priority 1: Official Cloud API — native interactive list ──
        if ($this->isOfficialApiActive()) {
            $sent = $this->sendListViaOfficialApi($phone, $title, $description, $buttonText, $sections, $footer);
            if ($sent) {
                Log::info('WhatsAppService: Sent native list via Official API', ['phone' => $phone]);
                return true;
            }
        }

        // ── Priority 2: Evolution API — try native list (only if text menu is enabled for evo) ──
        if ($this->isEvolutionApiActive()) {
            try {
                $payload = [
                    'number' => self::formatPhone($phone),
                    'title' => mb_substr($title, 0, 60),
                    'description' => $description,
                    'buttonText' => mb_substr($buttonText, 0, 20),
                    'footerText' => $footer ?: '',
                    'sections' => $sections,
                ];

                $response = Http::withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post("{$this->apiUrl}/message/sendList/{$instanceName}", $payload);

                if ($response->successful()) {
                    Log::info('WhatsAppService: Sent native list via Evolution API', ['phone' => $phone]);
                    return true;
                }

                Log::warning('WhatsAppService: Evolution sendList failed, falling back to text', [
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);
            } catch (\Exception $e) {
                Log::warning('WhatsAppService: Evolution sendList exception', ['error' => $e->getMessage()]);
            }
        }

        // ── Priority 3: Text-based menu fallback ──
        // Only use text fallback if evoTextmenuEnabled is ON (or no Official API available)
        if ($this->shouldUseTextMenuFallback() || !$this->isOfficialApiActive()) {
            return $this->sendListAsText($instanceName, $phone, $title, $description, $sections, $footer);
        }

        // Text menu OFF + Official API active = native list is the only option (already tried above)
        Log::warning('WhatsAppService: Text menu disabled and Official API already tried. Cannot send list.', ['phone' => $phone]);
        return false;
    }

    /**
     * Send Interactive List via Official WhatsApp Cloud API.
     * This creates the native "≡ Menu" popup with selectable items.
     */
    private function sendListViaOfficialApi(
        string $phone,
        string $title,
        string $description,
        string $buttonText,
        array $sections,
        string $footer = ''
    ): bool {
        if (!$this->officialConfigured) {
            return false;
        }

        try {
            // Convert sections to Cloud API format
            $cloudSections = [];
            foreach ($sections as $section) {
                $cloudRows = [];
                foreach ($section['rows'] ?? [] as $row) {
                    $cloudRow = [
                        'id' => $row['rowId'] ?? uniqid(),
                        'title' => mb_substr($row['title'] ?? '', 0, 24),
                    ];
                    if (!empty($row['description'])) {
                        $cloudRow['description'] = mb_substr($row['description'], 0, 72);
                    }
                    $cloudRows[] = $cloudRow;
                }
                $cloudSections[] = [
                    'title' => mb_substr($section['title'] ?? 'Options', 0, 24),
                    'rows' => $cloudRows,
                ];
            }

            $interactive = [
                'type' => 'list',
                'body' => ['text' => $description],
                'action' => [
                    'button' => mb_substr($buttonText, 0, 20),
                    'sections' => $cloudSections,
                ],
            ];

            // Add header if title provided
            if (!empty($title)) {
                $interactive['header'] = ['type' => 'text', 'text' => mb_substr($title, 0, 60)];
            }

            // Add footer if provided
            if (!empty($footer)) {
                $interactive['footer'] = ['text' => mb_substr($footer, 0, 60)];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->officialAccessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$this->officialPhoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => self::formatPhone($phone),
                'type' => 'interactive',
                'interactive' => $interactive,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsAppService: Official API sendList failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::warning('WhatsAppService: Official API sendList exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send menu as formatted text message (fallback when interactive list API fails).
     * Returns array with row mapping so the bot can parse numbered replies.
     *
     * @return array|false  Returns ['sent' => true, 'rowMap' => [...]] on success
     */
    public function sendListAsText(
        string $instanceName,
        string $phone,
        string $title,
        string $description,
        array $sections,
        string $footer = ''
    ): array|false {
        $text = "*{$title}*\n{$description}\n\n";
        $rowMap = []; // number => rowId mapping
        $num = 1;

        foreach ($sections as $section) {
            if (!empty($section['title']) && count($sections) > 1) {
                $text .= "📂 *{$section['title']}*\n";
            }

            foreach ($section['rows'] ?? [] as $row) {
                $text .= "*{$num}.* {$row['title']}";
                if (!empty($row['description'])) {
                    $text .= " — _{$row['description']}_";
                }
                $text .= "\n";

                $rowMap[(string)$num] = [
                    'rowId' => $row['rowId'] ?? '',
                    'title' => $row['title'] ?? '',
                ];
                $num++;
            }
            $text .= "\n";
        }

        $text .= "👆 _Reply with the number or type the name to select_";

        if (!empty($footer)) {
            $text .= "\n\n_{$footer}_";
        }

        $sent = $this->sendText($instanceName, $phone, $text);

        if ($sent) {
            Log::info('WhatsAppService: List sent as text fallback', [
                'phone' => $phone,
                'options' => count($rowMap),
            ]);
            return ['sent' => true, 'rowMap' => $rowMap];
        }

        return false;
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
