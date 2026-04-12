<?php

namespace App\Services;

use App\Models\MetaWhatsappTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaTemplateService
{
    private string $accessToken;
    private string $wabaId;
    private string $phoneNumberId;
    private bool $configured = false;

    public function __construct(int $companyId)
    {
        $config = Setting::getValue('whatsapp', 'official_api_config', [
            'phone_number_id' => '',
            'access_token' => '',
            'waba_id' => '',
        ], $companyId);

        $this->accessToken = $config['access_token'] ?? '';
        $this->wabaId = $config['waba_id'] ?? '';
        $this->phoneNumberId = $config['phone_number_id'] ?? '';
        $this->configured = !empty($this->accessToken) && !empty($this->wabaId);
    }

    /**
     * Check if Meta API is properly configured (needs WABA ID for template management)
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Create a template and submit it to Meta for review.
     * Returns: ['success' => bool, 'meta_id' => string|null, 'status' => string, 'error' => string|null]
     */
    public function createTemplate(MetaWhatsappTemplate $template): array
    {
        if (!$this->configured) {
            return ['success' => false, 'error' => 'Meta API not configured. Please add WABA ID in settings.'];
        }

        try {
            // Build components array for Meta API
            $components = [];

            // Header component
            if ($template->header_type === 'TEXT' && !empty($template->header_text)) {
                $components[] = [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => $template->header_text,
                ];
            }

            // Body component (required)
            $bodyComponent = [
                'type' => 'BODY',
                'text' => $template->body_text,
            ];

            // Add example values for variables if present
            $exampleValues = $template->example_values;
            if (!empty($exampleValues) && $template->variable_count > 0) {
                $bodyComponent['example'] = [
                    'body_text' => [$exampleValues],
                ];
            }
            $components[] = $bodyComponent;

            // Footer component
            if (!empty($template->footer_text)) {
                $components[] = [
                    'type' => 'FOOTER',
                    'text' => $template->footer_text,
                ];
            }

            // Buttons component
            if (!empty($template->buttons)) {
                $metaButtons = [];
                foreach ($template->buttons as $btn) {
                    $buttonData = ['type' => $btn['type'], 'text' => $btn['text']];
                    if ($btn['type'] === 'URL') {
                        $buttonData['url'] = $btn['url'];
                        if (!empty($btn['url_example'])) {
                            $buttonData['example'] = [$btn['url_example']];
                        }
                    } elseif ($btn['type'] === 'PHONE_NUMBER') {
                        $buttonData['phone_number'] = $btn['phone_number'];
                    }
                    $metaButtons[] = $buttonData;
                }
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => $metaButtons,
                ];
            }

            $payload = [
                'name' => $template->name,
                'language' => $template->language,
                'category' => $template->category,
                'components' => $components,
            ];

            Log::info('MetaTemplate: Creating template', ['name' => $template->name, 'payload' => $payload]);

            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v21.0/{$this->wabaId}/message_templates", $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('MetaTemplate: Template created successfully', $data);

                return [
                    'success' => true,
                    'meta_id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? 'PENDING',
                    'error' => null,
                ];
            }

            $error = $response->json('error.message') ?? $response->body();
            Log::error('MetaTemplate: Create failed', ['status' => $response->status(), 'error' => $error]);

            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            Log::error('MetaTemplate: Create exception - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync all templates from Meta API — update statuses in DB.
     * Returns count of updated templates.
     */
    public function syncAllTemplates(int $companyId): array
    {
        if (!$this->configured) {
            return ['success' => false, 'error' => 'Meta API not configured'];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v21.0/{$this->wabaId}/message_templates", [
                    'limit' => 100,
                ]);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? $response->body();
                return ['success' => false, 'error' => $error];
            }

            $metaTemplates = $response->json('data') ?? [];
            $updated = 0;
            $synced = 0;

            foreach ($metaTemplates as $metaTemplate) {
                $dbTemplate = MetaWhatsappTemplate::where('company_id', $companyId)
                    ->where('name', $metaTemplate['name'])
                    ->where('language', $metaTemplate['language'])
                    ->first();

                if ($dbTemplate) {
                    $oldStatus = $dbTemplate->status;
                    $newStatus = strtoupper($metaTemplate['status'] ?? 'PENDING');
                    
                    $dbTemplate->update([
                        'meta_template_id' => $metaTemplate['id'],
                        'status' => $newStatus,
                        'rejected_reason' => $metaTemplate['rejected_reason'] ?? ($metaTemplate['quality_score']['reasons'] ?? null),
                        'last_synced_at' => now(),
                    ]);

                    $synced++;
                    if ($oldStatus !== $newStatus) {
                        $updated++;
                    }
                }
            }

            Log::info("MetaTemplate: Synced {$synced} templates, {$updated} status changes");

            return [
                'success' => true,
                'synced' => $synced,
                'updated' => $updated,
                'total_meta' => count($metaTemplates),
            ];
        } catch (\Exception $e) {
            Log::error('MetaTemplate: Sync exception - ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Sync a single template status from Meta API.
     */
    public function syncSingleTemplate(MetaWhatsappTemplate $template): array
    {
        if (!$this->configured) {
            return ['success' => false, 'error' => 'Meta API not configured'];
        }

        if (empty($template->meta_template_id)) {
            // Try to find by name
            return $this->syncAllTemplates($template->company_id);
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->get("https://graph.facebook.com/v21.0/{$template->meta_template_id}");

            if ($response->successful()) {
                $data = $response->json();
                $template->update([
                    'status' => strtoupper($data['status'] ?? $template->status),
                    'rejected_reason' => $data['rejected_reason'] ?? null,
                    'last_synced_at' => now(),
                ]);

                return ['success' => true, 'status' => $template->status];
            }

            return ['success' => false, 'error' => $response->json('error.message') ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a template from Meta API.
     */
    public function deleteTemplate(MetaWhatsappTemplate $template): array
    {
        if (!$this->configured) {
            return ['success' => false, 'error' => 'Meta API not configured'];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->delete("https://graph.facebook.com/v21.0/{$this->wabaId}/message_templates", [
                    'name' => $template->name,
                ]);

            if ($response->successful()) {
                Log::info("MetaTemplate: Deleted template '{$template->name}' from Meta");
                return ['success' => true];
            }

            return ['success' => false, 'error' => $response->json('error.message') ?? 'Delete failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send an approved Meta template message to a phone number.
     * Used by auto-reply system when template_source = 'meta'.
     */
    public function sendTemplateMessage(MetaWhatsappTemplate $template, string $phone, array $variableValues = []): array
    {
        if (!$this->configured || empty($this->phoneNumberId)) {
            return ['success' => false, 'error' => 'Official API not configured'];
        }

        if (!$template->isApproved()) {
            return ['success' => false, 'error' => 'Template is not approved by Meta'];
        }

        try {
            $formatted = preg_replace('/\D/', '', $phone);
            if (strlen($formatted) == 10) {
                $formatted = '91' . $formatted;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $formatted,
                'type' => 'template',
                'template' => [
                    'name' => $template->name,
                    'language' => ['code' => $template->language],
                ],
            ];

            // Add variable parameters if any
            if (!empty($variableValues) && $template->variable_count > 0) {
                $params = [];
                foreach ($variableValues as $value) {
                    $params[] = ['type' => 'text', 'text' => (string) $value];
                }
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => $params,
                    ],
                ];
            }

            $response = Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v21.0/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info("MetaTemplate: Sent template '{$template->name}' to {$formatted}");
                return ['success' => true];
            }

            $error = $response->json('error.message') ?? $response->body();
            Log::error("MetaTemplate: Send failed for '{$template->name}': {$error}");
            return ['success' => false, 'error' => $error];
        } catch (\Exception $e) {
            Log::error("MetaTemplate: Send exception - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
