<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;

class BackupCrmData extends Command
{
    protected $signature = 'backup:crm {--full : Force a full backup instead of incremental}';
    protected $description = 'Generate daily CRM data backup (full or incremental) as JSON';

    /**
     * All modules to back up: [key => [model_class, has_soft_deletes]]
     */
    private function modules(): array
    {
        return [
            'companies' => [\App\Models\Company::class, true],
            'roles' => [\App\Models\Role::class, true],
            'users' => [\App\Models\User::class, true],
            'categories' => [\App\Models\Category::class, true],
            'products' => [\App\Models\Product::class, true],
            'leads' => [\App\Models\Lead::class, true],
            'lead_followups' => [\App\Models\LeadFollowup::class, true],
            'clients' => [\App\Models\Client::class, true],
            'quotes' => [\App\Models\Quote::class, true],
            'quote_items' => [\App\Models\QuoteItem::class, false],
            'quote_payments' => [\App\Models\QuotePayment::class, false],
            'activities' => [\App\Models\Activity::class, true],
            'projects' => [\App\Models\Project::class, true],
            'tasks' => [\App\Models\Task::class, true],
            'task_activities' => [\App\Models\TaskActivity::class, false],
            'micro_tasks' => [\App\Models\MicroTask::class, false],
            'service_templates' => [\App\Models\ServiceTemplate::class, true],
            'settings' => [\App\Models\Setting::class, false],
        ];
    }

    public function handle()
    {
        $today = now()->toDateString();
        $lastBackupDate = Setting::getValue('system', 'last_backup_date', null);
        $forceFullBackup = $this->option('full');

        $isIncremental = !$forceFullBackup && $lastBackupDate;

        $this->info(
            $isIncremental
            ? "Running INCREMENTAL backup (changes since {$lastBackupDate})..."
            : "Running FULL backup..."
        );

        $backup = [
            'meta' => [
                'date' => $today,
                'type' => $isIncremental ? 'incremental' : 'full',
                'previous_date' => $lastBackupDate,
                'version' => '1.0',
                'generated_at' => now()->toISOString(),
            ],
            'data' => [],
        ];

        $totalRecords = 0;

        foreach ($this->modules() as $key => [$modelClass, $hasSoftDeletes]) {
            $this->info("  Backing up {$key}...");

            $upsertRecords = collect();
            $deletedIds = [];

            if ($isIncremental) {
                // Records created or updated since last backup
                $upsertRecords = $modelClass::where('updated_at', '>=', "{$lastBackupDate} 00:00:00")
                    ->get();

                // Soft-deleted records since last backup
                if ($hasSoftDeletes) {
                    $deletedIds = $modelClass::onlyTrashed()
                        ->where('deleted_at', '>=', "{$lastBackupDate} 00:00:00")
                        ->pluck('id')
                        ->toArray();
                }
            } else {
                // Full backup: all records (including trashed for reference)
                if ($hasSoftDeletes) {
                    $upsertRecords = $modelClass::withTrashed()->get();
                } else {
                    $upsertRecords = $modelClass::all();
                }
            }

            // For leads, include pivot data (lead_product)
            if ($key === 'leads' && $upsertRecords->count()) {
                $upsertRecords->load('products');
                $upsertRecords = $upsertRecords->map(function ($lead) {
                    $data = $lead->toArray();
                    $data['_pivot_products'] = $lead->products->map(function ($p) {
                        return [
                            'product_id' => $p->id,
                            'quantity' => $p->pivot->quantity,
                            'price' => $p->pivot->price,
                            'discount' => $p->pivot->discount,
                        ];
                    })->toArray();
                    return $data;
                });
            } else {
                $upsertRecords = $upsertRecords->map(fn($r) => $r->toArray());
            }

            $backup['data'][$key] = [
                'upsert' => $upsertRecords->values()->toArray(),
                'deleted_ids' => $deletedIds,
            ];

            $count = count($backup['data'][$key]['upsert']);
            $delCount = count($deletedIds);
            $totalRecords += $count + $delCount;
            $this->info("    → {$count} records, {$delCount} deletions");
        }

        // Save JSON to storage/app/backups/
        $fileName = "rvcrm_backup_{$today}.json";
        $path = "backups/{$fileName}";

        Storage::disk('local')->put($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $fullPath = storage_path("app/{$path}");
        $sizeMB = round(filesize($fullPath) / 1024 / 1024, 2);

        // Update last backup date
        Setting::setValue('system', 'last_backup_date', $today);

        $this->info("");
        $this->info("✅ Backup complete!");
        $this->info("   File: {$fullPath}");
        $this->info("   Size: {$sizeMB} MB");
        $this->info("   Records: {$totalRecords}");
        $this->info("   Type: " . ($isIncremental ? 'Incremental' : 'Full'));

        // Try Google Drive upload (if configured)
        try {
            $driveService = app(\App\Services\GoogleDriveService::class);
            if ($driveService->isConfigured()) {
                $this->info("   Uploading to Google Drive...");
                $driveService->upload($fullPath, $fileName);
                $this->info("   ✅ Google Drive upload complete!");
            }
        } catch (\Exception $e) {
            $this->warn("   ⚠ Google Drive upload skipped: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
