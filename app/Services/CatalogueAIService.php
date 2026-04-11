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

        $userMessage = "SOURCE TYPE: pdf\n\nPlease analyze this product catalogue PDF and identify the optimal database column structure. The PDF is attached. ONLY include columns for data that is ACTUALLY VISIBLE in the pages. Look carefully for Size tables, Available Finishes lists, Material labels, Code/Model numbers, and any other specifications shown per product.";

        // For column analysis, first 10 pages are sufficient to identify structure
        $analysisPath = $pdfPath;
        $totalPages = $this->vertexAI->getPDFPageCount($pdfPath);
        if ($totalPages > 10 || filesize($pdfPath) > 15 * 1024 * 1024) {
            $reduced = $this->vertexAI->extractPDFPageRange($pdfPath, 1, min(10, $totalPages));
            if ($reduced && file_exists($reduced)) {
                $analysisPath = $reduced;
                Log::info('CatalogueAI: Reduced PDF for analysis', [
                    'original_pages' => $totalPages,
                    'analysis_pages' => min(10, $totalPages),
                ]);
            }
        }

        $result = $this->vertexAI->generateContentWithPDF(
            $systemPrompt,
            $analysisPath,
            $userMessage,
            8192
        );

        // Cleanup temp analysis PDF
        if ($analysisPath !== $pdfPath && file_exists($analysisPath)) {
            @unlink($analysisPath);
        }

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
            'business_details' => $json['business_details'] ?? null,
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

        // Get total page count
        $totalPages = $this->vertexAI->getPDFPageCount($pdfPath);
        $fileSizeMB = round(filesize($pdfPath) / 1024 / 1024, 2);
        $pagesPerChunk = 10; // 10 pages per AI call

        Log::info('CatalogueAI: Starting chunked product extraction', [
            'total_pages' => $totalPages,
            'file_size_mb' => $fileSizeMB,
            'pages_per_chunk' => $pagesPerChunk,
        ]);

        $allProducts = [];
        $totalTokens = 0;
        $chunkCount = 0;
        $chunkPaths = []; // Track temp files for cleanup

        // Use chunking if: more than 15 pages OR file > 8MB
        // A 124-page PDF MUST be chunked regardless of file size
        if ($totalPages <= 15 && filesize($pdfPath) <= 8 * 1024 * 1024) {
            Log::info('CatalogueAI: PDF small enough for single-pass extraction', [
                'pages' => $totalPages,
                'size_mb' => $fileSizeMB,
            ]);
            return $this->extractProductsFromSinglePDF($systemPrompt, $pdfPath);
        }

        Log::info('CatalogueAI: Using chunked extraction', [
            'reason' => $totalPages > 15 ? "pages={$totalPages}>15" : "size={$fileSizeMB}MB>8MB",
        ]);

        // Chunked extraction: split PDF and process each chunk
        for ($startPage = 1; $startPage <= $totalPages; $startPage += $pagesPerChunk) {
            $endPage = min($startPage + $pagesPerChunk - 1, $totalPages);
            $chunkCount++;

            Log::info("CatalogueAI: Processing chunk {$chunkCount}", [
                'pages' => "{$startPage}-{$endPage}",
                'total_pages' => $totalPages,
            ]);

            // Extract page range into a chunk PDF
            $chunkPath = $this->vertexAI->extractPDFPageRange($pdfPath, $startPage, $endPage);

            if (!$chunkPath || !file_exists($chunkPath)) {
                Log::warning("CatalogueAI: Could not create chunk for pages {$startPage}-{$endPage}, skipping");
                continue;
            }

            $chunkPaths[] = $chunkPath;

            // Check chunk size — skip if too large
            $chunkSize = filesize($chunkPath);
            if ($chunkSize > 18 * 1024 * 1024) {
                Log::warning("CatalogueAI: Chunk too large", ['chunk_mb' => round($chunkSize / 1024 / 1024, 2)]);
                continue;
            }

            try {
                $userMessage = "Extract ALL individual product data from pages {$startPage} to {$endPage} of this catalogue PDF. Each model/SKU = one separate product row. Return ONLY the JSON.";

                $result = $this->vertexAI->generateContentWithPDF(
                    $systemPrompt,
                    $chunkPath,
                    $userMessage,
                    32768
                );

                $totalTokens += $result['total_tokens'] ?? 0;

                $json = $this->extractJSONFromResponse($result['text']);

                if ($json && isset($json['products']) && !empty($json['products'])) {
                    Log::info("CatalogueAI: Chunk {$chunkCount} extracted", ['products' => count($json['products'])]);
                    $allProducts = array_merge($allProducts, $json['products']);
                } else {
                    Log::warning("CatalogueAI: Chunk {$chunkCount} returned no products", [
                        'response_preview' => substr($result['text'], 0, 300),
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("CatalogueAI: Chunk {$chunkCount} failed", ['error' => $e->getMessage()]);
                // Continue processing other chunks
            }
        }

        // Cleanup temp chunk files
        foreach ($chunkPaths as $cp) {
            if (file_exists($cp)) @unlink($cp);
        }

        // Deduplicate products by model number / unique identifier
        $allProducts = $this->deduplicateProducts($allProducts, $columns);

        Log::info('CatalogueAI: Chunked extraction complete', [
            'total_products' => count($allProducts),
            'chunks_processed' => $chunkCount,
            'total_tokens' => $totalTokens,
        ]);

        if (empty($allProducts)) {
            throw new \RuntimeException('AI could not extract any products from the PDF across all pages. Please try again.');
        }

        return [
            'products' => $allProducts,
            'total' => count($allProducts),
            'ai_tokens' => $totalTokens,
        ];
    }

    /**
     * Single-pass extraction for smaller PDFs
     */
    private function extractProductsFromSinglePDF(string $systemPrompt, string $pdfPath): array
    {
        $userMessage = "Extract ALL individual product data from this catalogue PDF. Each model number / SKU = one separate product row. Return ONLY the JSON with the products array.";

        $result = $this->vertexAI->generateContentWithPDF(
            $systemPrompt,
            $pdfPath,
            $userMessage,
            32768
        );

        Log::info('CatalogueAI: Single-pass extraction raw response', [
            'text_length' => strlen($result['text']),
            'preview' => substr($result['text'], 0, 500),
            'tokens' => $result['total_tokens'] ?? 0,
        ]);

        $json = $this->extractJSONFromResponse($result['text']);

        if (!$json || !isset($json['products'])) {
            Log::error('CatalogueAI: Failed to parse product extraction from PDF', [
                'json_keys' => $json ? array_keys($json) : 'null',
                'response_preview' => substr($result['text'], 0, 1000),
            ]);
            throw new \RuntimeException('AI could not extract product data from the PDF. Please try again.');
        }

        if (empty($json['products'])) {
            Log::warning('CatalogueAI: Products array is empty in single-pass', [
                'response_length' => strlen($result['text']),
                'response_tail' => substr($result['text'], -200),
            ]);
        }

        Log::info('CatalogueAI: Single-pass products extracted', ['count' => count($json['products'])]);

        return [
            'products' => $json['products'] ?? [],
            'total' => count($json['products'] ?? []),
            'ai_tokens' => $result['total_tokens'] ?? 0,
        ];
    }

    /**
     * Deduplicate products by their unique/model column
     */
    private function deduplicateProducts(array $products, array $columns): array
    {
        if (empty($products)) return $products;

        // Find the unique/model column name
        $uniqueColName = null;
        foreach ($columns as $col) {
            if ($col['is_unique'] ?? false) {
                $uniqueColName = $col['name'];
                break;
            }
        }

        // Fallback: try to find a "Model Number" or "SKU" column
        if (!$uniqueColName) {
            foreach ($columns as $col) {
                $name = mb_strtolower($col['name']);
                if (in_array($name, ['model number', 'model', 'sku', 'model_number', 'item code'])) {
                    $uniqueColName = $col['name'];
                    break;
                }
            }
        }

        if (!$uniqueColName) {
            Log::info('CatalogueAI: No unique column for dedup, returning all products');
            return $products;
        }

        $seen = [];
        $unique = [];
        foreach ($products as $product) {
            $val = $product[$uniqueColName] ?? '';
            if (empty($val)) {
                $unique[] = $product; // Keep products without identifier
                continue;
            }
            $key = mb_strtolower(trim($val));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $product;
            }
        }

        $removed = count($products) - count($unique);
        if ($removed > 0) {
            Log::info("CatalogueAI: Dedup removed {$removed} duplicate products by '{$uniqueColName}'");
        }

        return $unique;
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
            'business_details' => $json['business_details'] ?? null,
        ];
    }

    /**
     * The default expert-level prompt for catalogue column analysis
     */
    private function getDefaultColumnAnalysisPrompt(): string
    {
        return <<<'PROMPT'
You are a world-class Product Catalogue Data Architect with 20+ years of experience in e-commerce, manufacturing, and wholesale catalogue digitization.

YOUR MISSION: Analyze the provided catalogue content and design the OPTIMAL database column structure based ONLY on data that is ACTUALLY PRESENT and VISIBLE in the catalogue.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠️ GOLDEN RULE — READ THIS FIRST:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚫 NEVER add columns for data that is NOT visible in the catalogue.
🚫 NEVER hallucinate or assume fields like price, GST, HSN, description, weight, etc. unless they are CLEARLY shown in the catalogue pages.
🚫 If the catalogue only shows model numbers, sizes, finishes, and materials — then ONLY create columns for those fields.

✅ ONLY include columns for attributes that you can SEE in the catalogue content/images.
✅ If you are not 100% sure a field exists in the catalogue, DO NOT include it.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ANALYSIS METHODOLOGY (follow in exact order):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. FIRST PASS — SCAN & CATEGORIZE
   • Read through ALL content to understand the product type, industry, and catalogue style
   • Identify product groupings (categories/families/series)
   • Note the hierarchical structure if present

2. SECOND PASS — IDENTIFY VISIBLE DATA FIELDS
   • Find ALL unique attributes/specifications that are ACTUALLY SHOWN for products
   • Note which attributes repeat across products (these become columns)
   • Look for TABLES showing Size, Color, Finish options — these are COMBO fields
   • Look for labels like "Code No.", "Model", "Available Finishes:", "Material:", "Size:" etc.

3. THIRD PASS — CLASSIFY EACH FIELD
   • Determine the optimal data type for each field
   • Identify which field uniquely identifies each product (model number, code no., part code)
   • Determine which field should serve as the display title
   • Identify category/grouping fields
   • Find variation/combo fields (fields where one product has MULTIPLE options like sizes, finishes)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
REQUIRED COLUMNS (always create these IF visible):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• ALWAYS include a Category column (is_category=true, type="select") if product groups/families exist
• ALWAYS include a unique identifier column (is_unique=true) using the EXACT term from the catalogue:
  - If catalogue says "Code No." → name it "Code No."
  - If catalogue says "Model Number" → name it "Model Number"
  - If catalogue says "SKU" → name it "SKU"
• ALWAYS include a product name/title column (is_title=true)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OPTIONAL COLUMNS (ONLY if they ACTUALLY EXIST in the catalogue):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• sale_price, mrp → ONLY if prices are actually shown in the catalogue
• gst_percent → ONLY if GST/tax rates are actually printed
• hsn_code → ONLY if HSN/HS codes are actually printed
• description → ONLY if product descriptions/paragraphs actually exist
• weight, dimensions → ONLY if measurements are actually shown
• Any other field → ONLY if data for it is VISIBLE in the catalogue

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COMBO FIELD DETECTION (VERY IMPORTANT):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Look carefully for fields where a SINGLE product offers MULTIPLE options:
• Size table showing: 300mm, 450mm, 600mm → is_combo=true, type="multiselect"
• "Available Finishes: Black, Rose Gold, Grey, Satin" → is_combo=true, type="multiselect"
• Color options → is_combo=true, type="multiselect"

These combo fields create product variation combinations. Extract the actual option values and put them in the "options" array.

A COMBO field means: for one product (e.g., Code No. 98), it comes in MULTIPLE sizes AND MULTIPLE finishes. Each combination (98 - 300mm - Black, 98 - 300mm - Gold, etc.) is a variation.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COLUMN TYPE RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• For fields with a LIMITED SET of distinct values (< 30 options), use "select" type with options array
• For fields where a product can have MULTIPLE values simultaneously, use "multiselect" type
• For combo fields that create variation combinations, use "multiselect" AND is_combo=true
• For YES/NO fields, use "boolean" type
• For numeric measurements (dimensions, weight, capacity), use "number" type
• For free-text fields, use "text" (short) or "textarea" (long/multi-line)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DEDUPLICATION RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• NEVER create two columns for the same concept
• "Model" and "Model Number" are the SAME → keep only ONE
• "Item Code" and "Part Number" and "SKU" are the SAME → pick the catalogue's term
• "Product Name" and "Product Title" are the SAME → keep only ONE

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FLAG ASSIGNMENT RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• is_unique = true → EXACTLY ONE column (the primary identifier)
• is_category = true → EXACTLY ONE column (type MUST be "select")
  ⚠ NEVER mark "Product Name" as is_category. Category is for GROUPING (e.g., "Door Handles", "Hinges").
• is_title = true → EXACTLY ONE column (display name)
• is_combo = true → Only for multiselect fields that create variation matrices (Size, Finish, Color)
• is_required = true → Fields that EVERY product must have
• show_in_ai = true → Fields useful for WhatsApp chatbot product matching

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SORTING ORDER:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Category (is_category)
2. Product Name/Title (is_title)
3. Unique Identifier (is_unique)
4. Key Specifications (material, type)
5. Combo/Variation fields (is_combo) — Size, Finish, Color
6. Other visible fields
7. Pricing (ONLY if visible)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXAMPLE — Hardware Catalogue with Code No., Size Table, Finishes, Material:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{
  "columns": [
    {"name": "Category", "type": "select", "is_unique": false, "is_required": true, "is_category": true, "is_title": false, "is_combo": false, "options": ["Conceal Handle", "Wardrobe Handle", "Profile Handle"], "show_in_ai": true, "sort_order": 1},
    {"name": "Product Name", "type": "text", "is_unique": false, "is_required": true, "is_category": false, "is_title": true, "is_combo": false, "options": [], "show_in_ai": true, "sort_order": 2},
    {"name": "Code No.", "type": "text", "is_unique": true, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "options": [], "show_in_ai": true, "sort_order": 3},
    {"name": "Material", "type": "select", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": false, "options": ["Aluminium", "Zinc Alloy", "Stainless Steel"], "show_in_ai": true, "sort_order": 4},
    {"name": "Size", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "options": ["300mm", "450mm", "600mm", "900mm"], "show_in_ai": true, "sort_order": 5},
    {"name": "Finish", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "options": ["Black", "Rose Gold", "Grey", "Satin", "SS", "Gold PVD"], "show_in_ai": true, "sort_order": 6},
    {"name": "Packing", "type": "number", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": false, "options": [], "show_in_ai": false, "sort_order": 7}
  ],
  "source_summary": "Hardware fittings catalogue with handle products across multiple categories. Each product has a Code No., available sizes, finishes, and material specifications.",
  "confidence": 90
}

Notice: NO sale_price, mrp, gst_percent, hsn_code, or description — because they were NOT visible in the catalogue.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
BUSINESS DETAILS EXTRACTION:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

While analyzing, ALSO look for any BUSINESS INFORMATION visible in the catalogue/website:
• Company/Brand Name
• Contact Number, Email, Website
• Address, City, State
• GST Number, Registration details
• Tagline, About the business
• Social media links
• Any other business identity details

If you find business details, include them in a "business_details" field as a well-formatted text block.
If NO business details are visible, set "business_details" to null.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OUTPUT FORMAT (STRICT JSON — NO MARKDOWN, NO EXPLANATION):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{
  "columns": [...],
  "source_summary": "description of what the catalogue contains",
  "confidence": 85,
  "business_details": "Company: XYZ Corp\nPhone: +91-9876543210\nEmail: info@xyz.com\nAddress: Mumbai, Maharashtra\nGST: 27XXXXX1234X1Z5\nWebsite: www.xyz.com" or null
}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FINAL CHECKLIST — COMMON MISTAKES TO AVOID:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Before outputting, verify ALL of these:
✗ Did you add sale_price/mrp/gst when NO prices are shown? → REMOVE them.
✗ Did you add description when no descriptions exist? → REMOVE it.
✗ Did you add hsn_code when no HSN codes are shown? → REMOVE it.
✗ Did you miss a Size/Finish combination table? → ADD it as is_combo=true multiselect.
✗ Did you create "Model" AND "Model Number" as separate columns? → MERGE into one.
✗ Did you mark Product Name as is_category=true? → FIX: only Category field gets is_category.
✗ Do you have more than ONE column with is_category=true? → FIX: only ONE is allowed.
✗ Do you have more than ONE column with is_title=true? → FIX: only ONE is allowed.
✗ Do you have more than ONE column with is_unique=true? → FIX: only ONE is allowed.
✗ Is the is_category column type something other than "select"? → FIX: must be "select" type.
✗ Did you add ANY column for data that is NOT visible in the catalogue? → REMOVE it immediately!
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
            $decoded = json_decode(trim($matches[1]), true);
            if ($decoded !== null) return $decoded;
        }

        // Try finding JSON object for columns OR products
        foreach (['columns', 'products'] as $searchKey) {
            if (preg_match('/\{[\s\S]*"' . $searchKey . '"[\s\S]*\}/s', $text, $matches)) {
                $json = $this->findBalancedJSON($matches[0]);
                if ($json) {
                    $decoded = json_decode($json, true);
                    if ($decoded !== null) return $decoded;
                }
            }
        }

        // Last resort: find any JSON object starting with {
        if (preg_match('/\{/', $text)) {
            $start = strpos($text, '{');
            $json = $this->findBalancedJSON(substr($text, $start));
            if ($json) {
                $decoded = json_decode($json, true);
                if ($decoded !== null) return $decoded;
            }
        }

        Log::warning('CatalogueAI: Could not extract JSON from response', ['text_preview' => substr($text, 0, 500)]);
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

YOUR MISSION: Extract EVERY SINGLE individual product/model from the provided catalogue and map each to the defined column structure.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DEFINED COLUMNS (extract data for these exact fields):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{$columnList}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
CRITICAL EXTRACTION RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. ⚠️ EACH MODEL NUMBER / SKU = ONE SEPARATE PRODUCT ROW
   - Do NOT group multiple models into one row
   - If a catalogue page shows 10 different models, you must create 10 separate product entries
   - Each model number, even within the same category, is a SEPARATE product

2. For CATEGORY column: use the product group/family name (e.g. "Conceal Handle", "Door Handle")
   - Multiple products CAN share the same category

3. For COMBO/VARIATION fields (like Finish, Color, Size):
   - Separate available options with " | " (pipe with spaces)
   - Example: "Matte Black | Brushed Gold | Silver"
   - These represent the options available FOR THAT SPECIFIC MODEL

4. For PRICES: use numeric values only (no currency symbols, no commas)

5. If a field value is not visible/available for a product, use empty string ""

6. Do NOT include image URLs or file paths — text data only

7. For select-type fields, pick the closest matching option from the defined options

8. Extract ALL products from ALL visible pages of the catalogue
   - Even if products look similar, if they have different model numbers, sizes or specifications — they are separate products

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXAMPLE — CORRECT vs WRONG:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

❌ WRONG (grouping by category):
{"products": [
  {"Category": "Conceal Handle", "Product Name": "Conceal Handle", "Model Number": "", ...},
  {"Category": "Wardrobe Handle", "Product Name": "Wardrobe Handle", "Model Number": "", ...}
]}

✅ CORRECT (each model = separate row):
{"products": [
  {"Category": "Conceal Handle", "Product Name": "Conceal Handle CH-101", "Model Number": "CH-101", "Material": "Aluminium", "Finish": "Black | Gold | Silver", ...},
  {"Category": "Conceal Handle", "Product Name": "Conceal Handle CH-102", "Model Number": "CH-102", "Material": "Zinc Alloy", "Finish": "Black | Rose Gold", ...},
  {"Category": "Conceal Handle", "Product Name": "Conceal Handle CH-103", "Model Number": "CH-103", "Material": "Stainless Steel", "Finish": "Silver", ...},
  {"Category": "Wardrobe Handle", "Product Name": "Wardrobe Handle WH-201", "Model Number": "WH-201", ...}
]}

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

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FINAL CHECKLIST:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- Output ONLY valid JSON. No markdown code fences. No explanation text.
- Use the EXACT column names as defined above (case-sensitive)
- Extract as many INDIVIDUAL products/models as possible (up to 500)
- Prices should be numbers only, no ₹ or Rs or $ symbols
- ⚠️ Did you create one row per MODEL? Not one row per CATEGORY! Double check!
- ⚠️ Count your product entries — if you have less than 10 products from a multi-page catalogue, you are probably grouping incorrectly!
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
