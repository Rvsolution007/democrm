<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\CatalogueCustomColumn;
use App\Services\CatalogueAIService;
use App\Services\CatalogueColumnImportService;
use App\Services\ProductExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SetupWizardController extends Controller
{
    /**
     * Show the setup wizard page
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        // Check if wizard was already completed
        $isCompleted = Setting::getValue('setup_tour', 'completed', false, $companyId);

        // Check if columns already exist (not a truly fresh start)
        $existingColumns = CatalogueCustomColumn::where('company_id', $companyId)->count();

        // Get any cached AI analysis
        $cachedColumns = Setting::getValue('setup_tour', 'ai_columns_json', null, $companyId);
        if (is_string($cachedColumns)) $cachedColumns = json_decode($cachedColumns, true);

        // Get tour config from super admin
        $tourConfig = [
            'welcome_title' => Setting::getGlobalValue('setup_tour', 'welcome_title', 'Welcome to VyaparCRM! 🚀'),
            'welcome_subtitle' => Setting::getGlobalValue('setup_tour', 'welcome_subtitle', 'Let\'s set up your product catalogue in minutes using AI'),
            'intro_message' => Setting::getGlobalValue('setup_tour', 'intro_message', 'Upload your product catalogue PDF or share your website URL — our AI will automatically analyze your products and create the perfect database structure for you.'),
        ];

        return view('admin.setup-wizard.wizard', compact(
            'isCompleted', 'existingColumns', 'cachedColumns', 'tourConfig'
        ));
    }

    /**
     * Step 1: Analyze catalogue source (PDF upload or website URL)
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:pdf,website',
            'catalogue_pdf' => 'required_if:source_type,pdf|file|mimes:pdf|max:20480',
            'website_url' => 'required_if:source_type,website|nullable|url',
        ]);

        $companyId = auth()->user()->company_id;
        $service = new CatalogueAIService($companyId);

        // Boost memory for PDF processing (14MB PDF → ~19MB base64 → ~40MB+ in JSON)
        ini_set('memory_limit', '1G');

        try {
            $sourceType = $request->source_type;
            $content = '';
            $pdfPath = null;

            if ($sourceType === 'pdf') {
                $uploadedFile = $request->file('catalogue_pdf');
                $originalName = $uploadedFile->getClientOriginalName();

                Log::info('SetupWizard: PDF upload received', [
                    'name' => $originalName,
                    'size_mb' => round($uploadedFile->getSize() / 1024 / 1024, 2),
                ]);

                // Save PDF to temp location first (needed for multimodal fallback & product extraction)
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
                $tempName = 'catalogue_' . $companyId . '_' . time() . '.pdf';
                $uploadedFile->move($tempDir, $tempName);
                $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $tempName;

                // Try text extraction using the saved file
                $fakeUpload = new \Illuminate\Http\UploadedFile($pdfPath, $originalName, 'application/pdf', null, true);
                $content = $service->extractTextFromPDF($fakeUpload);

                if (empty(trim($content))) {
                    // Text extraction failed (image-based PDF) → use Gemini multimodal
                    Log::info('SetupWizard: Using multimodal PDF analysis (text extraction returned empty)');

                    // Cache the PDF path for product extraction in step 3
                    Setting::setValue('setup_tour', 'last_pdf_path', $pdfPath, $companyId);
                    Setting::setValue('setup_tour', 'last_source_text', '', $companyId);
                    Setting::setValue('setup_tour', 'source_mode', 'pdf_multimodal', $companyId);

                    // Analyze directly from PDF using Gemini vision
                    $analysis = $service->analyzeCatalogueFromPDF($pdfPath);

                    // Cache the analysis
                    Setting::setValue('setup_tour', 'ai_columns_json', json_encode($analysis['columns']), $companyId);

                    return response()->json([
                        'success' => true,
                        'columns' => $analysis['columns'],
                        'source_summary' => $analysis['source_summary'],
                        'confidence' => $analysis['confidence'],
                        'ai_tokens' => $analysis['ai_tokens'],
                        'message' => 'Catalogue analyzed successfully! ' . count($analysis['columns']) . ' columns identified. (Used AI vision for image-based PDF)',
                    ]);
                }
            } else {
                $content = $service->scrapeWebsite($request->website_url);
            }

            if (empty(trim($content))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not extract any text content from the provided source. Please try a different file or URL.'
                ], 422);
            }

            // Cache the source content for product extraction in step 3
            Setting::setValue('setup_tour', 'last_source_text', $content, $companyId);
            Setting::setValue('setup_tour', 'source_mode', 'text', $companyId);
            if ($pdfPath) {
                Setting::setValue('setup_tour', 'last_pdf_path', $pdfPath, $companyId);
            }

            // AI Analysis: identify columns
            $analysis = $service->analyzeCatalogueSource($content, $sourceType);

            // Cache analysis for later use
            Setting::setValue('setup_tour', 'ai_columns_json', json_encode($analysis['columns']), $companyId);

            return response()->json([
                'success' => true,
                'columns' => $analysis['columns'],
                'source_summary' => $analysis['source_summary'],
                'confidence' => $analysis['confidence'],
                'ai_tokens' => $analysis['ai_tokens'],
                'message' => 'Catalogue analyzed successfully! ' . count($analysis['columns']) . ' columns identified.',
            ]);

        } catch (\RuntimeException $e) {
            Log::warning('SetupWizard: RuntimeException', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Error $e) {
            Log::error('SetupWizard: Fatal error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Server ran out of memory processing this PDF. Please try a smaller file (under 10MB).'], 500);
        } catch (\Exception $e) {
            Log::error('SetupWizard: Analysis failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Step 2a: Download AI-generated Columns Excel
     */
    public function downloadColumnsExcel()
    {
        $companyId = auth()->user()->company_id;
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $companyId);

        if (!$columnsJson) {
            return response()->json(['error' => 'No analysis found. Please run Step 1 first.'], 404);
        }

        $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;

        $service = new CatalogueAIService($companyId);
        $spreadsheet = $service->generateColumnsExcel($columns);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'catalogue_columns_' . date('Y-m-d_His') . '.xlsx';

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $tempPath = $tempDir . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Step 2b: Import columns (from cached AI analysis or uploaded Excel)
     */
    public function importColumns(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $importService = new CatalogueColumnImportService($companyId);

        $importType = $request->input('import_type', 'direct'); // 'direct' (from AI cache) or 'excel' (uploaded file)

        try {
            if ($importType === 'excel') {
                $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);

                $file = $request->file('file');
                $tempName = 'col_import_' . auth()->id() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
                $file->move($tempDir, $tempName);
                $filePath = $tempDir . '/' . $tempName;

                $result = $importService->importFromExcel($filePath);
                @unlink($filePath);

            } else {
                // Direct import from AI-cached columns
                $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $companyId);
                if (!$columnsJson) {
                    return response()->json(['success' => false, 'message' => 'No column analysis cached. Please run Step 1 first.'], 404);
                }
                $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;
                $result = $importService->importFromArray($columns);
            }

            // Clear AI bot product cache after new columns
            if (class_exists('\\App\\Services\\AIChatbotService')) {
                \App\Services\AIChatbotService::clearProductGroupCache($companyId);
            }

            return response()->json([
                'success' => true,
                'created' => $result['created'],
                'skipped' => $result['skipped'],
                'errors' => $result['errors'],
                'categories_created' => $result['categories_created'],
                'message' => "{$result['created']} columns created" .
                    (count($result['categories_created']) > 0 ? ", " . count($result['categories_created']) . " categories auto-created" : '') .
                    ($result['skipped'] > 0 ? ", {$result['skipped']} skipped (already exist)" : '') . '.',
            ]);

        } catch (\Exception $e) {
            Log::error('SetupWizard: Column import failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 3a: Extract product data using AI
     */
    public function extractProducts()
    {
        $companyId = auth()->user()->company_id;

        // Check which mode was used in step 1
        $sourceMode = Setting::getValue('setup_tour', 'source_mode', 'text', $companyId);

        // Get column definitions (either from system or AI cache)
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $companyId);
        $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : ($columnsJson ?? []);

        if (empty($columns)) {
            return response()->json(['success' => false, 'message' => 'No columns defined. Please complete Step 2 first.'], 422);
        }

        try {
            $service = new CatalogueAIService($companyId);

            if ($sourceMode === 'pdf_multimodal') {
                // Image-based PDF → use Gemini multimodal for product extraction
                $pdfPath = Setting::getValue('setup_tour', 'last_pdf_path', null, $companyId);
                if (!$pdfPath || !file_exists($pdfPath)) {
                    return response()->json(['success' => false, 'message' => 'PDF file not found. Please re-upload in Step 1.'], 404);
                }

                $result = $service->extractProductDataFromPDF($pdfPath, $columns);
            } else {
                // Text-based extraction
                $content = Setting::getValue('setup_tour', 'last_source_text', null, $companyId);
                if (!$content) {
                    return response()->json(['success' => false, 'message' => 'No catalogue source found. Please re-run Step 1.'], 404);
                }

                $result = $service->extractProductData($content, $columns);
            }

            // Cache extracted products
            Setting::setValue('setup_tour', 'ai_products_json', json_encode($result['products']), $companyId);

            return response()->json([
                'success' => true,
                'products' => array_slice($result['products'], 0, 10), // Preview first 10
                'total' => $result['total'],
                'ai_tokens' => $result['ai_tokens'],
                'message' => "{$result['total']} products extracted from your catalogue!",
            ]);

        } catch (\Exception $e) {
            Log::error('SetupWizard: Product extraction failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Step 3b: Download AI-extracted Products Excel
     */
    public function downloadProductsExcel()
    {
        $companyId = auth()->user()->company_id;

        $productsJson = Setting::getValue('setup_tour', 'ai_products_json', null, $companyId);
        $columnsJson = Setting::getValue('setup_tour', 'ai_columns_json', null, $companyId);

        if (!$productsJson || !$columnsJson) {
            return response()->json(['error' => 'No product data found. Please run Step 3 first.'], 404);
        }

        $products = is_string($productsJson) ? json_decode($productsJson, true) : $productsJson;
        $columns = is_string($columnsJson) ? json_decode($columnsJson, true) : $columnsJson;

        $service = new CatalogueAIService($companyId);
        $spreadsheet = $service->generateProductsExcel($products, $columns);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'products_import_' . date('Y-m-d_His') . '.xlsx';

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        $tempPath = $tempDir . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Mark setup wizard as complete
     */
    public function complete()
    {
        $companyId = auth()->user()->company_id;
        Setting::setValue('setup_tour', 'completed', true, $companyId);
        Setting::setValue('setup_tour', 'completed_at', now()->toISOString(), $companyId);
        Setting::setValue('setup_tour', 'completed_by', auth()->id(), $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Setup wizard completed! Your catalogue is ready.',
        ]);
    }

    /**
     * Reset wizard (allows re-run)
     */
    public function reset()
    {
        $companyId = auth()->user()->company_id;

        // Clean up temp PDF file if exists
        $pdfPath = Setting::getValue('setup_tour', 'last_pdf_path', null, $companyId);
        if ($pdfPath && file_exists($pdfPath)) {
            @unlink($pdfPath);
        }

        Setting::setValue('setup_tour', 'completed', false, $companyId);
        Setting::setValue('setup_tour', 'ai_columns_json', null, $companyId);
        Setting::setValue('setup_tour', 'ai_products_json', null, $companyId);
        Setting::setValue('setup_tour', 'last_source_text', null, $companyId);
        Setting::setValue('setup_tour', 'last_pdf_path', null, $companyId);
        Setting::setValue('setup_tour', 'source_mode', null, $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Setup wizard reset. You can run it again.',
        ]);
    }
}
