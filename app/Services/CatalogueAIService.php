<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\CatalogueCustomColumn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;

class CatalogueAIService
{
    private VertexAIService $vertexAI;
    private int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->vertexAI = new VertexAIService($companyId);
    }

    // ═══════════════════════════════════════════════════════════
    // PDF TEXT EXTRACTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Extract text content from uploaded PDF file.
     * Returns extracted text, or empty string if text extraction is not possible
     * (image-based PDFs). Caller should use multimodal fallback in that case.
     */
    public function extractTextFromPDF(UploadedFile $file): string
    {
        // Boost memory for PDF processing
        $originalMemory = ini_get('memory_limit');
        ini_set('memory_limit', '1G');

        try {
            return $this->doExtractTextFromPDF($file);
        } catch (\Error $e) {
            // Catch fatal errors like memory exhaustion
            Log::error('PDF extraction fatal error: ' . $e->getMessage());
            return '';
        } finally {
            ini_set('memory_limit', $originalMemory);
        }
    }

    /**
     * Internal PDF text extraction logic
     */
    private function doExtractTextFromPDF(UploadedFile $file): string
    {
        // Try smalot/pdfparser first
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file->getRealPath());
                $text = $pdf->getText();
                if ($this->isTextQualityGood($text)) {
                    Log::info('PDF text extracted via smalot/pdfparser', ['length' => strlen($text)]);
                    return $this->cleanExtractedText($text);
                } else {
                    Log::warning('smalot/pdfparser extracted low-quality text', [
                        'length' => strlen($text),
                        'preview' => substr(trim($text), 0, 200),
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('PDF Parser failed, trying fallback', ['error' => $e->getMessage()]);
            } catch (\Error $e) {
                Log::error('PDF Parser fatal error: ' . $e->getMessage());
            }
        } else {
            Log::info('smalot/pdfparser not installed, trying stream extraction');
        }

        // Fallback: Basic PHP stream-based extraction
        $content = @file_get_contents($file->getRealPath());
        if ($content !== false) {
            $text = $this->extractTextFromPDFStream($content);
            unset($content); // Free memory

            if ($this->isTextQualityGood($text)) {
                Log::info('PDF text extracted via stream parser', ['length' => strlen($text)]);
                return $this->cleanExtractedText($text);
            } else {
                Log::warning('Stream parser extracted low-quality text', [
                    'length' => strlen($text),
                    'preview' => substr(trim($text), 0, 200),
                ]);
            }
        }

        // Return empty — caller should use multimodal PDF analysis
        Log::info('No usable text extracted from PDF — will use multimodal AI analysis');
        return '';
    }

    /**
     * Check if extracted text is meaningful enough for AI analysis.
     * Returns false for empty text, garbled text, or text too short to be useful.
     */
    private function isTextQualityGood(string $text): bool
    {
        $text = trim($text);

        // Must have at least 200 characters of content
        if (strlen($text) < 200) {
            return false;
        }

        // Count printable, meaningful characters (letters, digits, common punctuation)
        $meaningful = preg_match_all('/[a-zA-Z0-9\p{L}]/u', $text);
        $total = strlen($text);

        // At least 30% of characters should be meaningful (letters/digits)
        // Image-based PDFs often extract as mostly special characters/gibberish
        if ($total > 0 && ($meaningful / $total) < 0.3) {
            return false;
        }

        // Must have at least some word-like sequences (3+ consecutive letters)
        $wordCount = preg_match_all('/[a-zA-Z\p{L}]{3,}/u', $text);
        if ($wordCount < 10) {
            return false;
        }

        return true;
    }

    /**
     * Analyze a catalogue PDF directly using Gemini multimodal (vision) capabilities.
     * Sends the entire PDF as base64 to Gemini, which can "read" both text and image content.
     *
     * @param string $pdfPath    Absolute path to the uploaded PDF file
     * @return array  {columns: [...], source_summary: string, confidence: int}
     */
    public function analyzeCatalogueFromPDF(string $pdfPath): array
    {
        if (!$this->vertexAI->isConfigured()) {
            throw new \RuntimeException('AI is not configured. Please ask your Super Admin to set up Vertex AI in Global Settings.');
        }

        $customPrompt = \App\Models\Setting::getGlobalValue('setup_tour', 'column_analysis_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getDefaultColumnAnalysisPrompt();

        $userMessage = "SOURCE TYPE: pdf\n\nPlease analyze this product catalogue PDF and identify the optimal database column structure. The PDF is attached as a file. Examine all pages including product tables, specifications, and pricing information.";

        $result = $this->vertexAI->generateContentWithPDF(
            $systemPrompt,
            $pdfPath,
            $userMessage,
            8192
        );

        $aiText = $result['text'];
        $json = $this->extractJSONFromResponse($aiText);

        if (!$json || !isset($json['columns'])) {
            Log::error('CatalogueAI: Failed to parse column analysis from multimodal PDF', ['response' => substr($aiText, 0, 500)]);
            throw new \RuntimeException('AI could not analyze the catalogue structure from the PDF. Please try again or use a website URL instead.');
        }

        $columns = $this->sanitizeColumns($json['columns']);

        return [
            'columns' => $columns,
            'source_summary' => $json['source_summary'] ?? 'Catalogue analyzed successfully from PDF',
            'confidence' => $json['confidence'] ?? 80,
            'ai_tokens' => $result['total_tokens'] ?? 0,
        ];
    }

    /**
     * Extract product data directly from a PDF file using Gemini multimodal.
     *
     * @param string $pdfPath   Absolute path to the PDF
     * @param array  $columns   Column definitions
     * @return array  {products: [...], total: int, ai_tokens: int}
     */
    public function extractProductDataFromPDF(string $pdfPath, array $columns): array
    {
        if (!$this->vertexAI->isConfigured()) {
            throw new \RuntimeException('AI is not configured.');
        }

        $systemPrompt = $this->getProductExtractionPrompt($columns);
        $userMessage = "Please extract ALL product data from this catalogue PDF. Map each product's attributes to the defined column structure. The PDF is attached as a file.";

        $result = $this->vertexAI->generateContentWithPDF(
            $systemPrompt,
            $pdfPath,
            $userMessage,
            8192
        );

        $json = $this->extractJSONFromResponse($result['text']);

        if (!$json || !isset($json['products'])) {
            Log::error('CatalogueAI: Failed to parse product extraction from PDF', ['response' => substr($result['text'], 0, 500)]);
            throw new \RuntimeException('AI could not extract product data from the PDF. Please try again.');
        }

        return [
            'products' => $json['products'] ?? [],
            'total' => count($json['products'] ?? []),
            'ai_tokens' => $result['total_tokens'] ?? 0,
        ];
    }

    /**
     * Basic PDF text extraction using stream processing
     */
    private function extractTextFromPDFStream(string $content): string
    {
        $text = '';
        // Extract text between BT and ET (text blocks)
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
            foreach ($matches[1] as $block) {
                // Extract Tj and TJ text operators
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $texts)) {
                    $text .= implode(' ', $texts[1]) . "\n";
                }
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $arrays)) {
                    foreach ($arrays[1] as $arr) {
                        if (preg_match_all('/\((.*?)\)/s', $arr, $parts)) {
                            $text .= implode('', $parts[1]) . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }
        return $text;
    }

    /**
     * Clean and normalize extracted text
     */
    private function cleanExtractedText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s{3,}/', "\n", $text);
        // Remove non-printable characters except newlines
        $text = preg_replace('/[^\P{C}\n\t]/u', '', $text);
        // Collapse blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        // Limit to prevent token overflow (Gemini context)
        if (mb_strlen($text) > 30000) {
            $text = mb_substr($text, 0, 30000) . "\n\n[... content truncated for analysis ...]";
        }
        return trim($text);
    }

    // ═══════════════════════════════════════════════════════════
    // WEBSITE SCRAPING
    // ═══════════════════════════════════════════════════════════

    /**
     * Scrape product-related content from a website URL
     */
    public function scrapeWebsite(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                throw new \RuntimeException("Could not access the website (HTTP {$response->status()}). Please check the URL and try again.");
            }

            $html = $response->body();
            return $this->extractTextFromHTML($html);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Could not connect to the website. Please check the URL and ensure the site is accessible.');
        }
    }

    /**
     * Extract relevant text content from HTML, stripping scripts/styles/nav
     */
    private function extractTextFromHTML(string $html): string
    {
        // Remove scripts, styles, nav, footer, header
        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/si', '', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/si', '', $html);
        $html = preg_replace('/<header[^>]*>.*?<\/header>/si', '', $html);
        $html = preg_replace('/<noscript[^>]*>.*?<\/noscript>/si', '', $html);

        // Convert tables to structured text
        $html = preg_replace('/<\/td>\s*<td/i', ' | <td', $html);
        $html = preg_replace('/<\/tr>/i', "\n", $html);

        // Convert list items to lines
        $html = preg_replace('/<li[^>]*>/i', "• ", $html);

        // Convert headings to uppercase markers
        $html = preg_replace_callback('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/si', function ($m) {
            return "\n## " . strip_tags($m[1]) . "\n";
        }, $html);

        // Convert <br> and <p> to newlines
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>/i', "\n", $html);

        // Strip remaining HTML
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return $this->cleanExtractedText($text);
    }

    // ═══════════════════════════════════════════════════════════
    // AI CATALOGUE COLUMN ANALYSIS
    // ═══════════════════════════════════════════════════════════

    /**
     * Analyze catalogue content and identify column structure
     *
     * @param string $content  Extracted text from PDF/website
     * @param string $sourceType  'pdf' or 'website'
     * @return array  {columns: [...], source_summary: string, confidence: int}
     */
    public function analyzeCatalogueSource(string $content, string $sourceType): array
    {
        if (!$this->vertexAI->isConfigured()) {
            throw new \RuntimeException('AI is not configured. Please ask your Super Admin to set up Vertex AI in Global Settings.');
        }

        // Custom prompt override from Super Admin settings
        $customPrompt = Setting::getGlobalValue('setup_tour', 'column_analysis_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getDefaultColumnAnalysisPrompt();

        $userMessage = "SOURCE TYPE: {$sourceType}\n\nCATALOGUE CONTENT:\n\n{$content}";

        $result = $this->vertexAI->generateContent(
            $systemPrompt,
            [['role' => 'user', 'text' => $userMessage]]
        );

        // Parse AI response as JSON
        $aiText = $result['text'];

        // Try to extract JSON from the response (AI sometimes wraps in markdown)
        $json = $this->extractJSONFromResponse($aiText);

        if (!$json || !isset($json['columns'])) {
            Log::error('CatalogueAI: Failed to parse column analysis', ['response' => $aiText]);
            throw new \RuntimeException('AI could not analyze the catalogue structure. Please try again or use a different source.');
        }

        // Validate and sanitize columns
        $columns = $this->sanitizeColumns($json['columns']);

        return [
            'columns' => $columns,
            'source_summary' => $json['source_summary'] ?? 'Catalogue analyzed successfully',
            'confidence' => $json['confidence'] ?? 80,
            'ai_tokens' => $result['total_tokens'] ?? 0,
        ];
    }

    /**
     * The default expert-level prompt for catalogue column analysis
     */
    private function getDefaultColumnAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are a world-class Product Catalogue Data Architect with 20+ years of experience in e-commerce, manufacturing, and wholesale catalogue digitization. You have analyzed 50,000+ catalogues across industries — from hardware and building materials to electronics, textiles, chemicals, and consumer goods.

YOUR MISSION: Analyze the provided catalogue content and design the OPTIMAL database column structure for managing this product catalogue in a CRM system.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ANALYSIS METHODOLOGY (follow in exact order):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. FIRST PASS — SCAN & CATEGORIZE
   • Read through ALL content to understand the product type, industry, and catalogue style
   • Identify product groupings (categories/families/series)
   • Note the hierarchical structure if present

2. SECOND PASS — IDENTIFY DATA FIELDS
   • Find ALL unique attributes/specifications mentioned for products
   • Note which attributes repeat across products (these are columns)
   • Identify pricing patterns, codes, units of measurement

3. THIRD PASS — CLASSIFY EACH FIELD
   • Determine the optimal data type for each field
   • Identify which field uniquely identifies each product (model number, part code, SKU)
   • Determine which field should serve as the display title
   • Identify category/grouping fields
   • Find variation/combo fields (fields where one product comes in multiple options)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COLUMN DESIGN RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• ALWAYS include a Category column if product groups/families exist
• ALWAYS include a unique identifier column (model number, SKU, part code)
• ALWAYS include a product name/title column
• ALWAYS include sale_price (number type) — extract from MRP/price/rate in catalogue
• Include mrp (number type) if both MRP and selling price exist
• Include gst_percent (number type) if tax information is present
• Include hsn_code (text type) if HSN/HS codes are mentioned
• Include description (textarea type) if detailed descriptions exist
• For fields with a LIMITED SET of distinct values (< 30 options), use "select" type with options array
• For fields where a product can have MULTIPLE values simultaneously, use "multiselect" type
• For fields that create PRODUCT VARIATIONS (e.g., Size, Color, Finish — where each combination needs separate pricing), mark as is_combo = true
• For YES/NO fields, use "boolean" type
• For numeric measurements (dimensions, weight, capacity), use "number" type
• For free-text fields, use "text" (short) or "textarea" (long/multi-line)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FLAG ASSIGNMENT RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• is_unique = true → EXACTLY ONE column (the primary identifier like Model Number, Part Code, Item Code)
• is_category = true → EXACTLY ONE column (the product family/group/category)
• is_title = true → EXACTLY ONE column (what shows as the product display name — usually the most descriptive unique field)
• is_combo = true → Only for select-type fields that create variation combinations (e.g., Size × Color matrix)
• is_required = true → Fields that EVERY product must have (category, identifier, name, price)
• show_in_ai = true → Fields useful for WhatsApp chatbot product matching (specs, features, not internal codes)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SORTING ORDER CONVENTION:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Category (is_category)
2. Product Name/Title (is_title)
3. Unique Identifier (is_unique) — SKU, Model, Part Code
4. Key Specifications (material, type, application)
5. Dimensions/Measurements
6. Combo/Variation fields (is_combo)
7. Description
8. Pricing (sale_price, mrp, gst_percent)
9. Additional metadata (HSN, unit, etc.)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OUTPUT FORMAT (STRICT JSON — NO MARKDOWN, NO EXPLANATION):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{
  "columns": [
    {
      "name": "Category",
      "type": "select",
      "is_unique": false,
      "is_required": true,
      "is_category": true,
      "is_title": false,
      "is_combo": false,
      "options": ["Category A", "Category B"],
      "show_in_ai": true,
      "sort_order": 1
    }
  ],
  "source_summary": "Hardware accessories catalogue with 150+ products across 8 categories including door handles, hinges, locks, and cabinet fittings.",
  "confidence": 85
}

CRITICAL:
- Output ONLY valid JSON. No markdown code fences. No explanatory text.
- Keep column count between 5 and 20 for usability
- Extract ACTUAL option values from the catalogue content, not generic examples
- confidence: 0-100 representing how confident you are in the analysis accuracy
PROMPT;
    }

    /**
     * Extract JSON from AI response (handles markdown code fences)
     */
    private function extractJSONFromResponse(string $text): ?array
    {
        // Try direct parse first
        $decoded = json_decode($text, true);
        if ($decoded !== null) return $decoded;

        // Try extracting from markdown code block
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) return $decoded;
        }

        // Try finding JSON object pattern
        if (preg_match('/\{[\s\S]*"columns"[\s\S]*\}/s', $text, $matches)) {
            // Find the balanced JSON
            $json = $this->findBalancedJSON($matches[0]);
            if ($json) {
                $decoded = json_decode($json, true);
                if ($decoded !== null) return $decoded;
            }
        }

        return null;
    }

    /**
     * Find balanced JSON object from start of string
     */
    private function findBalancedJSON(string $str): ?string
    {
        $depth = 0;
        $inString = false;
        $escape = false;

        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if (!$inString) {
                if ($char === '{') $depth++;
                if ($char === '}') {
                    $depth--;
                    if ($depth === 0) {
                        return substr($str, 0, $i + 1);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Sanitize and validate columns from AI output
     */
    private function sanitizeColumns(array $columns): array
    {
        $validTypes = ['text', 'textarea', 'number', 'select', 'multiselect', 'boolean'];
        $sanitized = [];

        foreach ($columns as $index => $col) {
            if (empty($col['name'])) continue;

            $type = $col['type'] ?? 'text';
            if (!in_array($type, $validTypes)) $type = 'text';

            // Ensure options is array for select types
            $options = [];
            if (in_array($type, ['select', 'multiselect']) && !empty($col['options'])) {
                $options = is_array($col['options']) ? array_values(array_filter($col['options'])) : [];
            }
            if (!empty($col['is_combo']) && !empty($col['options'])) {
                $options = is_array($col['options']) ? array_values(array_filter($col['options'])) : [];
                $type = 'select'; // Combos must be select type
            }

            $sanitized[] = [
                'name' => substr(trim($col['name']), 0, 100),
                'type' => $type,
                'is_unique' => (bool) ($col['is_unique'] ?? false),
                'is_required' => (bool) ($col['is_required'] ?? false),
                'is_category' => (bool) ($col['is_category'] ?? false),
                'is_title' => (bool) ($col['is_title'] ?? false),
                'is_combo' => (bool) ($col['is_combo'] ?? false),
                'options' => $options,
                'show_in_ai' => (bool) ($col['show_in_ai'] ?? true),
                'sort_order' => $col['sort_order'] ?? ($index + 1),
            ];
        }

        return $sanitized;
    }

    // ═══════════════════════════════════════════════════════════
    // COLUMNS EXCEL GENERATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate downloadable Excel for catalogue column import
     */
    public function generateColumnsExcel(array $columns): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Catalogue Columns');

        // Headers
        $headers = ['Name', 'Type', 'Options (comma-separated)', 'Is Required', 'Is Unique', 'Is Category', 'Is Title', 'Is Combo', 'Show In AI', 'Sort Order'];
        foreach ($headers as $i => $header) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($letter . '1', $header);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        // Style header
        $lastLetter = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$lastLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C4B5FD']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows
        foreach ($columns as $index => $col) {
            $row = $index + 2;
            $sheet->setCellValue("A{$row}", $col['name']);
            $sheet->setCellValue("B{$row}", $col['type']);
            $sheet->setCellValue("C{$row}", implode(', ', $col['options'] ?? []));
            $sheet->setCellValue("D{$row}", ($col['is_required'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("E{$row}", ($col['is_unique'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("F{$row}", ($col['is_category'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("G{$row}", ($col['is_title'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("H{$row}", ($col['is_combo'] ?? false) ? 'Yes' : 'No');
            $sheet->setCellValue("I{$row}", ($col['show_in_ai'] ?? true) ? 'Yes' : 'No');
            $sheet->setCellValue("J{$row}", $col['sort_order'] ?? ($index + 1));

            // Alternate row coloring
            if ($index % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastLetter}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F3FF']],
                ]);
            }
        }

        // Instructions sheet
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instructions');
        $instructions = [
            ['AI Catalogue Column Import — Instructions'],
            [''],
            ['This Excel was generated by AI analysis of your catalogue.'],
            ['You can modify the columns before importing into the system.'],
            [''],
            ['Column Descriptions:'],
            ['Name — The display label for this field (e.g., "Material", "Brand")'],
            ['Type — text, textarea, number, select, multiselect, boolean'],
            ['Options — Comma-separated values for select/multiselect types'],
            ['Is Required — Yes/No — Must be filled when adding products'],
            ['Is Unique — Yes/No — Values must be unique across all products (use for SKU/Model)'],
            ['Is Category — Yes/No — Exactly ONE column should be the product category'],
            ['Is Title — Yes/No — Exactly ONE column should be the display title'],
            ['Is Combo — Yes/No — Creates product variation matrix (size, color, finish)'],
            ['Show In AI — Yes/No — Visible to WhatsApp AI Bot for product matching'],
            ['Sort Order — Numeric order in which fields appear (1 = first)'],
            [''],
            ['IMPORT RULES:'],
            ['• Exactly ONE column must be marked as "Is Category = Yes"'],
            ['• Exactly ONE column must be marked as "Is Unique = Yes"'],
            ['• At most ONE column can be marked as "Is Title = Yes"'],
            ['• Categories will be auto-created from catalogue data in the next step'],
        ];
        foreach ($instructions as $i => $line) {
            $sheet2->setCellValue('A' . ($i + 1), $line[0] ?? '');
        }
        $sheet2->getColumnDimension('A')->setWidth(80);
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A6')->getFont()->setBold(true);
        $sheet2->getStyle('A18')->getFont()->setBold(true);

        $spreadsheet->setActiveSheetIndex(0);
        return $spreadsheet;
    }

    // ═══════════════════════════════════════════════════════════
    // AI PRODUCT DATA EXTRACTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Extract product data from catalogue content based on defined columns
     *
     * @param string $content  Catalogue text
     * @param array  $columns  Array of column definitions (from AI analysis or system)
     * @return array  {products: [[col_name => value, ...], ...], total: int}
     */
    public function extractProductData(string $content, array $columns): array
    {
        if (!$this->vertexAI->isConfigured()) {
            throw new \RuntimeException('AI is not configured.');
        }

        $systemPrompt = $this->getProductExtractionPrompt($columns);
        $userMessage = "CATALOGUE CONTENT:\n\n{$content}";

        $result = $this->vertexAI->generateContent(
            $systemPrompt,
            [['role' => 'user', 'text' => $userMessage]]
        );

        $json = $this->extractJSONFromResponse($result['text']);

        if (!$json || !isset($json['products'])) {
            Log::error('CatalogueAI: Failed to parse product extraction', ['response' => $result['text']]);
            throw new \RuntimeException('AI could not extract product data. The catalogue content may be too complex or insufficient.');
        }

        return [
            'products' => $json['products'] ?? [],
            'total' => count($json['products'] ?? []),
            'ai_tokens' => $result['total_tokens'] ?? 0,
        ];
    }

    /**
     * Build the product extraction prompt dynamically from column definitions
     */
    private function getProductExtractionPrompt(array $columns): string
    {
        $columnList = "";
        foreach ($columns as $col) {
            $name = $col['name'];
            $type = $col['type'] ?? 'text';
            $flags = [];
            if ($col['is_category'] ?? false) $flags[] = 'CATEGORY';
            if ($col['is_unique'] ?? false) $flags[] = 'UNIQUE IDENTIFIER';
            if ($col['is_title'] ?? false) $flags[] = 'DISPLAY TITLE';
            if ($col['is_combo'] ?? false) $flags[] = 'COMBO/VARIATION';
            $flagStr = count($flags) > 0 ? ' [' . implode(', ', $flags) . ']' : '';
            $optStr = '';
            if (!empty($col['options'])) {
                $optStr = ' Options: ' . implode(', ', array_slice($col['options'], 0, 20));
            }
            $columnList .= "  - \"{$name}\" (type: {$type}){$flagStr}{$optStr}\n";
        }

        return <<<PROMPT
You are a world-class Product Data Extraction Specialist with 20+ years of experience digitizing product catalogues into structured databases.

YOUR MISSION: Extract ALL product data from the provided catalogue content and map each product's attributes to the defined column structure.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DEFINED COLUMNS (extract data for these exact fields):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{$columnList}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXTRACTION RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Extract EVERY distinct product/item from the catalogue
2. Map each product's attributes to the column names EXACTLY as defined above
3. For COMBO fields, separate multiple values with " | " (pipe with spaces)
4. For CATEGORY, use the product group/family name from the catalogue
5. For PRICES, use numeric values only (no currency symbols, no commas)
6. If a field is not available for a product, use empty string ""
7. For select-type fields, pick the closest matching option from the defined options
8. Do NOT include image URLs or file paths — text data only
9. If the same product appears with different variations, create ONE entry with combo values separated by |

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OUTPUT FORMAT (STRICT JSON — NO MARKDOWN):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{
  "products": [
    {
      "Column Name 1": "value",
      "Column Name 2": "value",
      ...
    }
  ]
}

CRITICAL:
- Output ONLY valid JSON. No markdown. No explanation text.
- Use the EXACT column names as defined above (case-sensitive)
- Extract as many products as possible (up to 500)
- Prices should be numbers only, no ₹ or Rs symbols
PROMPT;
    }

    // ═══════════════════════════════════════════════════════════
    // PRODUCTS EXCEL GENERATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate Products Excel from AI-extracted data
     * Output matches ProductExcelService import format
     */
    public function generateProductsExcel(array $products, array $columns): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        // Build headers from columns
        $colIndex = 1;
        $headerMap = []; // colIndex => column_name

        foreach ($columns as $col) {
            $letter = Coordinate::stringFromColumnIndex($colIndex);
            $headerName = $col['name'];
            if ($col['is_combo'] ?? false) $headerName .= ' (combo)';

            $sheet->setCellValue($letter . '1', $headerName);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
            $headerMap[$colIndex] = $col['name'];
            $colIndex++;
        }

        // Style header
        $lastLetter = Coordinate::stringFromColumnIndex(max($colIndex - 1, 1));
        $sheet->getStyle("A1:{$lastLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Data rows
        foreach ($products as $rowIndex => $product) {
            $row = $rowIndex + 2;

            foreach ($headerMap as $ci => $colName) {
                $letter = Coordinate::stringFromColumnIndex($ci);
                $value = $product[$colName] ?? '';
                $sheet->setCellValue($letter . $row, $value);
            }

            // Alternate row coloring
            if ($rowIndex % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastLetter}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
                ]);
            }
        }

        $sheet->freezePane('A2');
        return $spreadsheet;
    }
}
