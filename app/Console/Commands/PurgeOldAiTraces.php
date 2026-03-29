<?php

namespace App\Console\Commands;

use App\Models\AiChatTrace;
use Illuminate\Console\Command;

class PurgeOldAiTraces extends Command
{
    protected $signature = 'ai:purge-traces {--days=2 : Days to keep}';
    protected $description = 'Delete AI chat trace logs older than N days (default 2)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleted = AiChatTrace::purgeOlderThan($days);

        $this->info("Purged {$deleted} AI trace records older than {$days} days.");
        return self::SUCCESS;
    }
}
