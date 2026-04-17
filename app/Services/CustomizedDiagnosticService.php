<?php

namespace App\Services;

use App\Models\CatalogueCustomColumn;
use App\Models\ChatflowStep;
use App\Models\Company;
use App\Models\Product;
use App\Models\Setting;

class CustomizedDiagnosticService
{
    public function run(int $companyId): array
    {
        $company = Company::find($companyId);
        $hasAiBot = $company->hasFeature('ai_bot');
        $hasChatflow = $company->hasFeature('chatflow');

        $results = [];

        // Modules for Botlist features
        if ($hasChatflow && !$hasAiBot) {
            $results['catalogue_columns'] = $this->checkCatalogueColumns($companyId);
            $results['chatflow_steps']    = $this->checkChatflowSteps($companyId);
        }
        
        // Modules for AI Bot
        if ($hasAiBot) {
            $results['ai_config'] = $this->checkAiConfig($companyId);
        }

        // Common
        $results['product_data'] = $this->checkProductData($companyId);

        return $results;
    }

    private function checkCatalogueColumns(int $companyId): array
    {
        $rows = [];
        $columns = CatalogueCustomColumn::where('company_id', $companyId)->where('is_active', true)->get();

        if ($columns->isEmpty()) {
            return [[
                'name' => 'No active columns found',
                'module' => 'Catalogue Columns',
                'admin_set' => false,
                'admin_detail' => 'No columns configured',
                'connected' => false,
                'connected_detail' => 'System cannot function without columns',
                'severity' => 'error'
            ]];
        }

        foreach ($columns as $col) {
            // Check Category Flag
            if ($col->is_category) {
                $hasStep = ChatflowStep::where('company_id', $companyId)->where('step_type', 'ask_category')->exists();
                $rows[] = [
                    'name' => $col->name,
                    'module' => 'Catalogue Columns',
                    'admin_set' => true,
                    'admin_detail' => 'is_category = ON',
                    'connected' => $hasStep,
                    'connected_detail' => $hasStep ? '✅ Linked to Chatflow (ask_category)' : '❌ Missing ask_category step in Chatflow',
                    'severity' => $hasStep ? 'success' : 'warning'
                ];
            }

            // Check Unique Flag
            if ($col->is_unique) {
                $hasStep = ChatflowStep::where('company_id', $companyId)->where('step_type', 'ask_unique_column')->exists();
                $rows[] = [
                    'name' => $col->name,
                    'module' => 'Catalogue Columns',
                    'admin_set' => true,
                    'admin_detail' => 'is_unique = ON',
                    'connected' => $hasStep,
                    'connected_detail' => $hasStep ? '✅ Linked to Chatflow (ask_unique_column)' : '❌ Missing ask_unique_column step in Chatflow',
                    'severity' => $hasStep ? 'success' : 'warning'
                ];
            }

            // Check Combo Flag
            if ($col->is_combo) {
                $hasStep = ChatflowStep::where('company_id', $companyId)->where('step_type', 'ask_combo')->where('linked_column_id', $col->id)->exists();
                $rows[] = [
                    'name' => $col->name,
                    'module' => 'Catalogue Columns',
                    'admin_set' => true,
                    'admin_detail' => 'is_combo = ON',
                    'connected' => $hasStep,
                    'connected_detail' => $hasStep ? '✅ Linked to Chatflow (ask_combo)' : '❌ No ask_combo step linked to this column',
                    'severity' => $hasStep ? 'success' : 'warning'
                ];
            }

            // Check Show in AI Flag
            if ($col->show_in_ai) {
                $rows[] = [
                    'name' => $col->name,
                    'module' => 'Catalogue Columns',
                    'admin_set' => true,
                    'admin_detail' => 'show_in_ai = ON',
                    'connected' => true,
                    'connected_detail' => '✅ Column data available to AI Bot',
                    'severity' => 'success'
                ];
            }
        }

        return $rows;
    }

    private function checkChatflowSteps(int $companyId): array
    {
        $rows = [];
        $steps = ChatflowStep::where('company_id', $companyId)->orderBy('sort_order')->get();

        if ($steps->isEmpty()) {
            return [[
                'name' => 'No Chatflow Steps',
                'module' => 'Chatflow',
                'admin_set' => false,
                'admin_detail' => 'Chatflow is empty',
                'connected' => false,
                'connected_detail' => 'Bot will not function without steps',
                'severity' => 'error'
            ]];
        }

        foreach ($steps as $index => $step) {
            $isConnected = true;
            $detail = '✅ Ready';
            $severity = 'success';
            $inputText = '';
            $processText = '';

            if ($step->step_type === 'ask_column' || $step->step_type === 'ask_combo') {
                if (!$step->linked_column_id) {
                    $isConnected = false;
                    $detail = '❌ No catalogue column linked';
                    $severity = 'error';
                } else if (!$step->linkedColumn) {
                    $isConnected = false;
                    $detail = '❌ Linked catalogue column deleted';
                    $severity = 'error';
                } else {
                    $detail = '✅ Linked to: ' . $step->linkedColumn->name;
                }
            }

            if (empty($step->question_text) && $step->step_type !== 'send_summary') {
                $isConnected = false;
                $detail = '❌ Missing question_text';
                $severity = 'error';
            }

            $outputText = '';

            // Define Flow Input / Outut
            if ($step->step_type === 'ask_category') {
                $inputText = 'User selects a category (or multiple categories).';
                $processText = 'Background progressive filter logic activates, filtering products (to next catalogue question) for the next steps.';
                $outputText = 'Sends currently filtered products or next question.';
            } else if ($step->step_type === 'ask_unique_column') {
                $inputText = 'User selects a specific product (or multiple).';
                $processText = 'Background identifies MATCH_ID and filters capacity/combos for next steps.';
                $outputText = 'Adds product to Quote/Lead and sends next related question (skips if data is null).';
            } else if ($step->step_type === 'ask_combo') {
                $inputText = 'User selects variation options.';
                $processText = 'Background searches ProductCombo variations and calculates dynamic pricing.';
                $outputText = 'Sends next related question and links options to Quote item.';
            } else if ($step->step_type === 'ask_column') {
                $inputText = 'User selects an option for ' . ($step->linkedColumn->name ?? 'column') . '.';
                $processText = 'Background narrows down filtered products based on option.';
                $outputText = 'Sends next related question.';
            } else if ($step->step_type === 'ask_text') {
                $inputText = 'User types a custom message.';
                $processText = 'Background saves this text directly to the quote payload metadata.';
                $outputText = 'Advances to next step.';
            } else if ($step->step_type === 'ask_phone') {
                $inputText = 'User enters contact number.';
                $processText = 'Background validates format, formats to E.164, and updates Lead profile.';
                $outputText = 'Advances to next step.';
            } else if ($step->step_type === 'ask_email') {
                $inputText = 'User enters email.';
                $processText = 'Background validates format and updates Lead profile.';
                $outputText = 'Advances to next step.';
            } else if ($step->step_type === 'send_summary') {
                $inputText = 'User confirms order.';
                $processText = 'Displays single product block.';
                $outputText = 'Requests continuation.';
            }

            $rows[] = [
                'name' => 'Step ' . ($index + 1) . ': ' . ($step->name ?? ucfirst(str_replace('_', ' ', $step->step_type))),
                'module' => 'Chatflow',
                'admin_set' => true,
                'admin_detail' => 'Configured',
                'connected' => $isConnected,
                'connected_detail' => $detail,
                'severity' => $severity,
                'bot_ask' => $step->question_text ?? 'Generated by System',
                'input_text' => $inputText,
                'process_text' => $processText,
                'output_text' => $outputText,
            ];
        }

        // Always append the System Default Final Question
        $rows[] = [
            'name' => 'System Default Final Step: Order Completion',
            'module' => 'Chatflow',
            'admin_set' => true,
            'admin_detail' => 'System Default',
            'connected' => true,
            'connected_detail' => '✅ Enforced',
            'severity' => 'success',
            'bot_ask' => 'All selected items, total amount, generates PDF (if configured), and requests final confirmation.',
            'input_text' => 'User hits final confirmation or completes queue.',
            'process_text' => 'Bot compiles all selected items, calculations, total amount, generates a PDF (if configured), and requests final confirmation.',
            'output_text' => 'Sends final Summary & Order PDF. Ends chatflow.',
        ];

        return $rows;
    }

    private function checkAiConfig(int $companyId): array
    {
        $rows = [];
        $company = Company::find($companyId);
        $subscription = $company->activeSubscription();
        $isAiBotEnabled = Setting::getValue('ai_bot', 'enabled', false, $companyId);
        
        $prompts = [
            'greeting_prompt' => 'Greeting Prompt (Tier 0)',
            'business_prompt' => 'Business Prompt (Tier 2)',
            'system_prompt'   => 'System Prompt (Tier 2 Base)',
            'tier3_prompt'    => 'Tier 3 Prompt (Analytics)',
            'spell_correction_prompt' => 'Spell Correction',
        ];

        foreach ($prompts as $key => $label) {
            $val = Setting::getValue('ai_bot', $key, '', $companyId);
            $isSet = !empty(trim($val));
            $rows[] = [
                'name' => $label,
                'module' => 'AI Configuration',
                'admin_set' => $isSet,
                'admin_detail' => $isSet ? '✅ ' . str_word_count($val) . ' words' : '❌ Empty',
                'connected' => $isSet && $isAiBotEnabled,
                'connected_detail' => $isAiBotEnabled ? ($isSet ? '✅ Active' : '❌ Fallback to default') : '❌ AI Bot disabled',
                'severity' => $isSet ? 'success' : 'warning',
            ];
        }

        // Check Vertex AI Config
        $vertexConfig = Setting::getValue('vertex_ai_config', 'settings', [], 0); // Global
        $hasVertex = !empty($vertexConfig['project_id']) && !empty($vertexConfig['service_account_json']);
        $rows[] = [
            'name' => 'Vertex AI Credentials',
            'module' => 'System Config',
            'admin_set' => $hasVertex,
            'admin_detail' => $hasVertex ? '✅ Configured' : '❌ Missing',
            'connected' => $hasVertex,
            'connected_detail' => $hasVertex ? '✅ System can connect to AI Models' : '❌ API calls will fail',
            'severity' => $hasVertex ? 'success' : 'error',
        ];

        return $rows;
    }

    private function checkProductData(int $companyId): array
    {
        $rows = [];
        $totalProducts = Product::where('company_id', $companyId)->count();
        $activeProducts = Product::where('company_id', $companyId)->where('status', 'active')->count();

        if ($totalProducts === 0) {
            return [[
                'name' => 'Total Products',
                'module' => 'Product Data',
                'admin_set' => false,
                'admin_detail' => '0 products',
                'connected' => false,
                'connected_detail' => 'Bot has no data to search',
                'severity' => 'error'
            ]];
        }

        $rows[] = [
            'name' => 'Active Products',
            'module' => 'Product Data',
            'admin_set' => true,
            'admin_detail' => "✅ {$activeProducts}/{$totalProducts} active",
            'connected' => true,
            'connected_detail' => '✅ Available for search',
            'severity' => ($activeProducts > 0) ? 'success' : 'error',
        ];

        // Categories Check
        $productsWithCat = Product::where('company_id', $companyId)->whereNotNull('category_id')->count();
        $rows[] = [
            'name' => 'Category Linked',
            'module' => 'Product Data',
            'admin_set' => ($productsWithCat > 0),
            'admin_detail' => $productsWithCat > 0 ? "✅ {$productsWithCat} products have category" : "❌ 0 products have category",
            'connected' => ($productsWithCat === $totalProducts),
            'connected_detail' => ($productsWithCat === $totalProducts) ? '✅ All good' : "⚠️ " . ($totalProducts - $productsWithCat) . " products have no category",
            'severity' => ($productsWithCat === $totalProducts) ? 'success' : 'warning',
        ];

        return $rows;
    }
}
