<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use App\Models\Setting;

class BackupController extends Controller
{
    /**
     * Mapping: backup key → [Model class, has_soft_deletes]
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

    /**
     * Show the backup management page.
     */
    public function index()
    {
        $lastBackupDate = Setting::getValue('system', 'last_backup_date', 'Never');
        $driveConfigured = app(\App\Services\GoogleDriveService::class)->isConfigured();

        // List existing backup files
        $backupFiles = [];
        $files = Storage::disk('local')->files('backups');
        foreach ($files as $f) {
            if (str_ends_with($f, '.json')) {
                $backupFiles[] = [
                    'name' => basename($f),
                    'size' => round(Storage::disk('local')->size($f) / 1024, 1),
                    'date' => date('d M Y, h:i A', Storage::disk('local')->lastModified($f)),
                ];
            }
        }
        // Sort by name desc (newest first)
        usort($backupFiles, fn($a, $b) => strcmp($b['name'], $a['name']));

        return view('admin.backups.index', compact('lastBackupDate', 'driveConfigured', 'backupFiles'));
    }

    /**
     * Trigger a manual backup.
     */
    public function runBackup(Request $request)
    {
        $type = $request->input('type', 'auto'); // 'full' or 'auto'

        $exitCode = Artisan::call('backup:crm', $type === 'full' ? ['--full' => true] : []);

        if ($exitCode === 0) {
            return back()->with('success', 'Backup completed successfully!');
        }
        return back()->with('error', 'Backup failed. Check logs for details.');
    }

    /**
     * Download a backup file.
     */
    public function download(string $fileName)
    {
        $path = "backups/{$fileName}";
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Backup file not found.');
        }
        return Storage::disk('local')->download($path, $fileName);
    }

    /**
     * Import one or more backup JSON files.
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup_files' => 'required',
            'backup_files.*' => 'file|mimes:json,txt|max:51200', // 50MB max each
        ]);

        $files = $request->file('backup_files');
        if (!is_array($files)) {
            $files = [$files];
        }

        // Parse and sort by meta.date ASC
        $backups = [];
        foreach ($files as $file) {
            $content = json_decode(file_get_contents($file->getRealPath()), true);
            if (!$content || !isset($content['meta']['date'])) {
                return back()->with('error', 'Invalid backup file: ' . $file->getClientOriginalName());
            }
            $backups[] = $content;
        }

        usort($backups, fn($a, $b) => strcmp($a['meta']['date'], $b['meta']['date']));

        $stats = ['imported' => 0, 'updated' => 0, 'deleted' => 0, 'files' => count($backups)];

        foreach ($backups as $backup) {
            $this->processBackup($backup, $stats);
        }

        return back()->with(
            'success',
            "Import complete! {$stats['files']} files processed. " .
            "{$stats['imported']} records imported/updated, {$stats['deleted']} records deleted."
        );
    }

    /**
     * Process a single backup file — upsert + delete.
     */
    private function processBackup(array $backup, array &$stats): void
    {
        $modules = $this->modules();

        foreach ($backup['data'] as $key => $moduleData) {
            if (!isset($modules[$key]))
                continue;

            [$modelClass, $hasSoftDeletes] = $modules[$key];

            // UPSERT records
            foreach ($moduleData['upsert'] ?? [] as $record) {
                // Extract pivot data if present (for leads)
                $pivotProducts = $record['_pivot_products'] ?? null;
                unset($record['_pivot_products']);

                // Remove relationship data that might be included
                unset(
                    $record['products'],
                    $record['created_by'],
                    $record['assigned_to'],
                    $record['company'],
                    $record['role'],
                    $record['lead'],
                    $record['client'],
                    $record['quote'],
                    $record['task'],
                    $record['user'],
                    $record['project']
                );

                $id = $record['id'] ?? null;
                if (!$id)
                    continue;

                // Use updateOrCreate — if trashed, restore first
                if ($hasSoftDeletes) {
                    $existing = $modelClass::withTrashed()->find($id);
                    if ($existing) {
                        // Restore if trashed
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->fill($record);
                        $existing->save();
                        $stats['updated']++;
                    } else {
                        try {
                            $modelClass::create($record);
                            $stats['imported']++;
                        } catch (\Exception $e) {
                            // Skip records that fail (e.g. FK constraints)
                            continue;
                        }
                    }
                } else {
                    try {
                        $modelClass::updateOrCreate(['id' => $id], $record);
                        $stats['imported']++;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                // Sync pivot data for leads
                if ($key === 'leads' && $pivotProducts && is_array($pivotProducts)) {
                    $lead = \App\Models\Lead::find($id);
                    if ($lead) {
                        $syncData = [];
                        foreach ($pivotProducts as $pp) {
                            $syncData[$pp['product_id']] = [
                                'quantity' => $pp['quantity'],
                                'price' => $pp['price'],
                                'discount' => $pp['discount'],
                            ];
                        }
                        $lead->products()->sync($syncData);
                    }
                }
            }

            // DELETE records
            foreach ($moduleData['deleted_ids'] ?? [] as $deletedId) {
                if ($hasSoftDeletes) {
                    $record = $modelClass::find($deletedId);
                    if ($record) {
                        $record->delete();
                        $stats['deleted']++;
                    }
                }
            }
        }
    }
}
