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

        // For column analysis, first few pages are sufficient to identify structure
        // Use fewer pages for very large PDFs to stay under Gemini 20MB limit
        $analysisPath = $pdfPath;
        $totalPages = $this->vertexAI->getPDFPageCount($pdfPath);
        $pdfSizeMB = filesize($pdfPath) / 1024 / 1024;

        // Determine max pages based on file size
        $maxAnalysisPages = 10;
        if ($pdfSizeMB > 15) $maxAnalysisPages = 5;  // Very large → only 5 pages
        if ($pdfSizeMB > 25) $maxAnalysisPages = 3;  // Huge → only 3 pages

        if ($totalPages > $maxAnalysisPages || $pdfSizeMB > 10) {
            $pagesToExtract = min($maxAnalysisPages, $totalPages);
            $reduced = $this->vertexAI->extractPDFPageRange($pdfPath, 1, $pagesToExtract);
            if ($reduced && file_exists($reduced)) {
                $analysisPath = $reduced;
                Log::info('CatalogueAI: Reduced PDF for analysis', [
                    'original_pages' => $totalPages,
                    'original_mb' => round($pdfSizeMB, 1),
                    'analysis_pages' => $pagesToExtract,
                    'reduced_mb' => round(filesize($reduced) / 1024 / 1024, 1),
                ]);
            } else {
                Log::warning('CatalogueAI: PDF reduction failed — attempting with full PDF');
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

YOUR MISSION: Analyze the catalogue and design the OPTIMAL database column structure. You MUST follow the 2-PHASE methodology below.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠️ GOLDEN RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚫 NEVER add columns for data NOT visible in the catalogue.
🚫 NEVER hallucinate fields like price, GST, HSN, description unless CLEARLY shown.
✅ ONLY include columns for attributes you can SEE in the catalogue.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🧠 PHASE 1: MENTAL RAW SCAN (do this FIRST before defining any columns)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Before defining columns, scan EVERY product/item in the catalogue and mentally build a raw list:

For EACH product block/card you see, note:
  • The FULL heading text exactly as shown (e.g., "HANDY CHOPPER 750ML", "NEO PUSH CHOPPER 500ML")
  • ALL visible attributes/specifications for that product
  • ALL pricing/measurement data shown

Do NOT output this list. Just build it mentally. You need it for Phase 2.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 PHASE 2: PATTERN DETECTION (this determines your column definitions)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Using your Phase 1 raw list, perform these pattern analyses IN ORDER:

─── STEP A: HEADING DECOMPOSITION ───

For every product heading, SPLIT IT into components:
  "HANDY CHOPPER 750ML" → "HANDY CHOPPER" + "750ML"
  "HANDY CHOPPER 500ML" → "HANDY CHOPPER" + "500ML"
  "NEO PUSH CHOPPER 900ML" → "NEO PUSH CHOPPER" + "900ML"
  "NEO PUSH CHOPPER COMBO CONTAINER" → "NEO PUSH CHOPPER COMBO CONTAINER" (no split — standalone)

─── STEP B: FIND THE CATEGORY (broadest grouping) ───

Look at ALL products. What is the BROADEST group they belong to?
  • "Handy Chopper", "Neo Push Chopper" → ALL are "Chopper" category
  • "Manual Juicer", "Electric Juicer" → ALL are "Juicer" category
  • If the catalogue has Choppers, Juicers, Lunch Boxes → Categories = ["Chopper", "Juicer", "Lunch Box"]

The category is the TOP-LEVEL group. Multiple product lines share the same category.
→ This becomes is_category=true, type="select"

─── STEP C: FIND THE PRODUCT NAME (product line identity) ───

Within a category, group products by their COMMON NAME:
  "Handy Chopper 750ML" + "Handy Chopper 500ML" + "Handy Chopper 900ML"
  → Common name = "Handy Chopper" (this appears 3 times with different suffixes)

  "Neo Push Chopper 900ML" + "Neo Push Chopper 500ML"
  → Common name = "Neo Push Chopper"

  "Neo Push Chopper Combo Container" → standalone, no split

⚠️ CRITICAL: The Product Name is the COMMON/SHARED part of the heading.
  • "Handy Chopper" is ONE product (not 3 products!)
  • "Neo Push Chopper" is ONE product (not 2 products!)
  • The varying suffix (750ML, 500ML) is NOT part of the product name

→ Product Name becomes is_title=true AND is_unique=true

─── STEP D: FIND COMBO/VARIATION FIELDS ───

Now look at what VARIES within the same product name:
  "Handy Chopper" → has 500ML, 750ML, 900ML → "Capacity" is a COMBO field
  "Neo Push Chopper" → has 500ML, 900ML → "Capacity" is a COMBO field

Also check traditional combos:
  • If a product shows sizes: 4", 6", 8" → Size is a COMBO field
  • If a product shows finishes: Black, Gold, Silver → Finish is a COMBO field
  • If a product shows colors: Red, Blue, Green → Color is a COMBO field

⚠️ COMBO means: ONE product has MULTIPLE variant options. Not every product needs a combo value.
  "Neo Push Chopper Combo Container" → has NO capacity variant → combo field will be empty for this product. That's fine!

→ Combo fields become is_combo=true, type="multiselect"
→ Collect ALL variant values across all products as the options array

─── STEP E: FIND REQUIRED PER-PRODUCT FIELDS ───

What data appears per EACH product variant (per item block)?
  • Price fields: "WITH GST: 168/-", "PRICE: 142/-", "MRP: 451/-" → REQUIRED
  • Tax: "GST: 18%" → REQUIRED
  • Codes: "HSN CODE: 392410" → REQUIRED
  • Pack info: "MASTER PACK: 144 NOS.", "INNER PACK: 12 NOS." → REQUIRED

→ These become is_required=true fields
→ If these field VALUES DIFFER per variant (e.g., 750ML has price 168, 500ML has price 146), also set is_variation_field=true
→ is_variation_field=true means: this field appears in the variation pricing table, each variant gets its own value

─── STEP F: FIND DESCRIPTIVE/OPTIONAL FIELDS ───

Any additional text like:
  • "CHOPPED, GREVY, CHUTNEY, PASTE" (usage/tagline)
  • "SHARPNESS STAINLESS STEEL 5 BLADES WITH CUTTING" (features/specs)
  • "UNBREAKABLE MATERIAL WITH TRANSPARENT CONTAINER" (description)

→ These become non-flagged columns (no special flags, is_required=false)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
COLUMN TYPE RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• Category → ALWAYS type="select" with options from Step B
• Product Name → type="text" (is_title=true, is_unique=true)
• Combo fields → type="multiselect", is_combo=true, with options array from Step D
• Fields with limited distinct values (< 30) → type="select" with options
• Free-text → type="text" (short) or type="textarea" (long)
• Numeric (prices, percentages, quantities) → type="number"
• Yes/No → type="boolean"

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FLAG ASSIGNMENT RULES:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• is_category=true → EXACTLY ONE column (the broadest group — Category Linked)
• is_title=true → EXACTLY ONE column (Quote/Lead Title — the product line name)
• is_unique=true → EXACTLY ONE column (Unique Identifier — usually same as title for named products)
• is_combo=true → Only for multiselect fields creating the Variation Matrix
• is_variation_field=true → Per-Variation Field. Fields whose VALUE changes per variant (e.g., different price per size). NEVER on combo columns.
• is_required=true → Required Field. Fields every product MUST have
• show_in_ai=true → Fields useful for WhatsApp chatbot matching

⚠️ is_title and is_unique CAN be on the same column
⚠️ is_combo and is_variation_field are MUTUALLY EXCLUSIVE (a column cannot be both)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
SORTING ORDER:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. Category (is_category)
2. Product Name (is_title)
3. Combo/Variation fields (is_combo)
4. Key specs/material
5. Required per-product fields (pricing, codes)
6. Optional descriptive fields

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXAMPLE — Homeware Catalogue (Choppers, Juicers, etc.):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Raw items seen:
  "Handy Chopper 750ML", "Handy Chopper 500ML", "Handy Chopper 900ML",
  "Neo Push Chopper 900ML", "Neo Push Chopper 500ML",
  "Neo Push Chopper Combo Container",
  "Manual Juicer", "Coffee Mug 2 Pcs Set"

Pattern analysis:
  → Categories: "Chopper", "Juicer", "Mug"
  → Product Names: "Handy Chopper", "Neo Push Chopper", "Neo Push Chopper Combo Container", "Manual Juicer", "Coffee Mug 2 Pcs Set"
  → Capacity COMBO: "500ML", "750ML", "900ML" (varies within Handy Chopper and Neo Push Chopper)
  → Some products have NO capacity variant (Combo Container, Juicer) — that's perfectly fine

Result:
{
  "columns": [
    {"name": "Category", "type": "select", "is_unique": false, "is_required": true, "is_category": true, "is_title": false, "is_combo": false, "is_variation_field": false, "options": ["Chopper", "Juicer", "Mug"], "show_in_ai": true, "sort_order": 1},
    {"name": "Product Name", "type": "text", "is_unique": true, "is_required": true, "is_category": false, "is_title": true, "is_combo": false, "is_variation_field": false, "options": [], "show_in_ai": true, "sort_order": 2},
    {"name": "Capacity", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "is_variation_field": false, "options": ["500ML", "750ML", "900ML", "900 & 500ML"], "show_in_ai": true, "sort_order": 3},
    {"name": "Sale Price (with GST)", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 4},
    {"name": "Base Price (without GST)", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 5},
    {"name": "GST Percentage", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 6},
    {"name": "HSN Code", "type": "text", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 7},
    {"name": "MRP", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": true, "sort_order": 8},
    {"name": "Price Unit Quantity", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 9},
    {"name": "Master Pack Quantity", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 10},
    {"name": "Inner Pack Quantity", "type": "number", "is_unique": false, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": true, "options": [], "show_in_ai": false, "sort_order": 11}
  ]
}

Notice:
  ✅ "Handy Chopper" is the Product Name — NOT "Handy Chopper 750ML"
  ✅ "750ML" goes into Capacity COMBO field — NOT into the product name
  ✅ "Neo Push Chopper Combo Container" has NO capacity variant — combo field will be empty for it
  ✅ Category is "Chopper" — the broadest group

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXAMPLE — Hardware/Fitting Catalogue (handles, hinges by Code No.):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Raw items seen:
  "Conceal Handle Code 9015", "Conceal Handle Code 9016",
  "Door Handle Code 8001"

Pattern analysis:
  → Categories: "Conceal Handle", "Door Handle"
  → No descriptive product names — only Code No. identifies each product
  → Sizes: 300mm, 450mm, 600mm (varies within some handles) — COMBO
  → Finishes: Black, Gold, Silver — COMBO

Result:
{
  "columns": [
    {"name": "Category", "type": "select", "is_unique": false, "is_required": true, "is_category": true, "is_title": true, "is_combo": false, "is_variation_field": false, "options": ["Conceal Handle", "Door Handle"], "show_in_ai": true, "sort_order": 1},
    {"name": "Code No.", "type": "text", "is_unique": true, "is_required": true, "is_category": false, "is_title": false, "is_combo": false, "is_variation_field": false, "options": [], "show_in_ai": true, "sort_order": 2},
    {"name": "Size", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "is_variation_field": false, "options": ["300mm", "450mm", "600mm"], "show_in_ai": true, "sort_order": 3},
    {"name": "Finish", "type": "multiselect", "is_unique": false, "is_required": false, "is_category": false, "is_title": false, "is_combo": true, "is_variation_field": false, "options": ["Black", "Rose Gold", "SS"], "show_in_ai": true, "sort_order": 4}
  ]
}

Notice:
  ✅ NO "Product Name" column — because products have no descriptive names
  ✅ Category gets BOTH is_category AND is_title (it's both the group AND the display name)
  ✅ "Code No." is the unique identifier

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
BUSINESS DETAILS EXTRACTION:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

While analyzing, ALSO look for BUSINESS INFORMATION:
• Company/Brand Name, Contact, Email, Website
• Address, GST Number, Social media
If found, include in "business_details". If not, set to null.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
OUTPUT FORMAT (STRICT JSON — NO MARKDOWN):
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

{
  "columns": [...],
  "source_summary": "description of catalogue",
  "confidence": 85,
  "business_details": "Company: XYZ\nPhone: +91-xxx\nWebsite: www.xyz.com" or null
}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
FINAL CHECKLIST — VERIFY BEFORE OUTPUT:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✗ Did you put "750ML" or any variant suffix INTO Product Name? → REMOVE it! Product Name = common part only!
✗ Did you create separate products for "X 500ML" and "X 750ML" instead of ONE product "X" with Capacity combo? → FIX!
✗ Did you add columns for data NOT visible in the catalogue? → REMOVE!
✗ Do you have more than ONE is_category? → FIX: only ONE allowed.
✗ Do you have more than ONE is_title? → FIX: only ONE allowed.
✗ Do you have more than ONE is_unique? → FIX: only ONE allowed.
✗ Is is_category column type NOT "select"? → FIX: must be "select".
✗ Did you miss a capacity/size/finish/color that varies within products? → ADD it as is_combo=true multiselect.
✗ Does a product have NO combo value? → That's OK! Not every product needs combo variants.
✗ Did you create "Product Name" when no descriptive names exist? → REMOVE, put is_title on Category.
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
                'is_variation_field' => (bool) ($col['is_variation_field'] ?? false),
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

        // Custom prompt override from Super Admin settings
        $customPrompt = Setting::getGlobalValue('setup_tour', 'product_extraction_prompt', '');
        $systemPrompt = !empty($customPrompt) ? $customPrompt : $this->getProductExtractionPrompt($columns);
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

2. ⚠️ CATEGORY COLUMN [CATEGORY flag] — VERY IMPORTANT:
   - The Category value is the PRODUCT GROUP/FAMILY name only
   - Multiple products MUST share the same category value
   - NEVER append model numbers, code numbers, or unique identifiers to the category
   - Think of it as: Category = A (group name), Unique Code = X (identifier)
   - ❌ WRONG: Category = "A X" (group name + code combined into one value)
   - ✅ CORRECT: Category = "A" (group name only, code goes in its own column)
   - If the Category and Unique Identifier appear on the SAME line in the catalogue, SEPARATE them into two different fields
   - Category should have FAR FEWER unique values than the number of products (e.g. 5 categories for 100 products)

3. ⚠️ PRODUCT NAME / TITLE COLUMN [TITLE flag] — EQUALLY IMPORTANT:
   - Product Name must NEVER contain the code/model number
   - If Category = "A" and Code = "X", then Product Name = "A", NOT "A X"
   - ❌ WRONG: Product Name = "A X" (group + code combined)
   - ✅ CORRECT: Product Name = "A" (group name only, code stays in UNIQUE column)
   - The UNIQUE IDENTIFIER column already captures the code — do NOT repeat it in Product Name
   - If the catalogue has a SPECIFIC descriptive name for the product, use that descriptive name
   - If no specific name exists, use the Category group name as the Product Name

4. For COMBO/VARIATION fields (like Finish, Color, Size):
   - Separate available options with " | " (pipe with spaces)
   - These represent the options available FOR THAT SPECIFIC MODEL

5. For PRICES: use numeric values only (no currency symbols, no commas)

6. If a field value is not visible/available for a product, use empty string ""

7. Do NOT include image URLs or file paths — text data only

8. For select-type fields, pick the closest matching option from the defined options

9. Extract ALL products from ALL visible pages of the catalogue
   - Even if products look similar, if they have different model numbers, sizes or specifications — they are separate products

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
EXAMPLE — Using abstract names A, B and codes X1, X2, Y1:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Let's say Category groups are "A" and "B", with unique codes "X1", "X2", "Y1":

❌ WRONG — code merged into Product Name:
{"products": [
  {"[CATEGORY col]": "A", "[TITLE col]": "A X1", "[UNIQUE col]": "X1"},
  {"[CATEGORY col]": "B", "[TITLE col]": "B Y1", "[UNIQUE col]": "Y1"}
]}

❌ WRONG — code merged into Category:
{"products": [
  {"[CATEGORY col]": "A X1", "[TITLE col]": "A X1", "[UNIQUE col]": "X1"}
]}

❌ WRONG — grouped by category, no individual models:
{"products": [
  {"[CATEGORY col]": "A", "[TITLE col]": "A", "[UNIQUE col]": ""}
]}

✅ CORRECT — each model is its own row, names are group-only, code is separate:
{"products": [
  {"[CATEGORY col]": "A", "[TITLE col]": "A", "[UNIQUE col]": "X1", ...},
  {"[CATEGORY col]": "A", "[TITLE col]": "A", "[UNIQUE col]": "X2", ...},
  {"[CATEGORY col]": "B", "[TITLE col]": "B", "[UNIQUE col]": "Y1", ...}
]}

↑ TITLE value = CATEGORY value = Group name. UNIQUE column has the code. They are ALWAYS SEPARATE.
↑ Replace [CATEGORY col], [TITLE col], [UNIQUE col] with the actual column names defined above.

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
- ⚠️ Count your product entries — if you have less than 10 products from a multi-page catalogue, you probably grouped incorrectly!
- ⚠️ Count your UNIQUE Category values — if you have as many categories as products, you are putting model numbers IN the category! Fix this!
- ⚠️ CATEGORY [CATEGORY flag] must be the GROUP NAME only — NEVER append code/model numbers!
- ⚠️ PRODUCT NAME [TITLE flag] must be the GROUP NAME only — NEVER append code/model numbers! Code goes ONLY in the UNIQUE IDENTIFIER column!
- ⚠️ If any value in CATEGORY or TITLE column ends with a number/code that matches the UNIQUE column value — that number does NOT belong there, REMOVE it!
PROMPT;
    }

    // ═══════════════════════════════════════════════════════════
    // PUBLIC PROMPT ACCESSORS (for Super Admin display)
    // ═══════════════════════════════════════════════════════════

    /**
     * Get the default column analysis prompt for display in Super Admin settings
     */
    public function getDefaultColumnAnalysisPromptPublic(): string
    {
        return $this->getDefaultColumnAnalysisPrompt();
    }

    /**
     * Get the default product extraction prompt for display in Super Admin settings
     * Uses sample columns since the actual prompt is dynamic
     */
    public function getDefaultProductExtractionPromptPublic(): string
    {
        $sampleColumns = [
            ['name' => 'Category', 'type' => 'select', 'is_category' => true, 'is_unique' => false, 'is_title' => true, 'is_combo' => false, 'options' => ['Sample A', 'Sample B']],
            ['name' => 'Code No.', 'type' => 'text', 'is_category' => false, 'is_unique' => true, 'is_title' => false, 'is_combo' => false, 'options' => []],
            ['name' => 'Material', 'type' => 'select', 'is_category' => false, 'is_unique' => false, 'is_title' => false, 'is_combo' => false, 'options' => ['Steel', 'Aluminium']],
        ];
        return $this->getProductExtractionPrompt($sampleColumns);
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
