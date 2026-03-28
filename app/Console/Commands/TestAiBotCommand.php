<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiBotDiagnosticService;

class TestAiBotCommand extends Command
{
    protected $signature = 'bot:test-diagnostic';
    protected $description = 'Run the AI Bot Diagnostic Tester';

    public function handle()
    {
        $this->info("Running Diagnostic...");
        $service = new AiBotDiagnosticService(1, 1);
        $service->run(function($type, $msg) {
            if ($type === 'error') {
                $this->error($msg);
            } elseif ($type === 'success') {
                $this->info($msg);
            } else {
                $this->line($msg);
            }
        });
        
        $this->info("Done");
    }
}
