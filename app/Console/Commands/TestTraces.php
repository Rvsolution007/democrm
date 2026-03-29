<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestTraces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-traces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(\App\Models\AiChatTrace::count());
    }
}
