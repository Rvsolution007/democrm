<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateTestTrace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-test-trace';

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
        $sessions = \App\Models\AiChatSession::withCount(['messages', 'traces'])->get();
        foreach($sessions as $s) {
            $this->info("Session ID: {$s->id}, Phone: {$s->phone_number}, Messages: {$s->messages_count}, Traces: {$s->traces_count}");
        }
    }
}
