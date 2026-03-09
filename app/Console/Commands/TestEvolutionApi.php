<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;

class TestEvolutionApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-evolution-api';

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
        $apiConfig = Setting::getValue('whatsapp', 'api_config', [], 1);
        $apiUrl = $apiConfig['api_url'] ?? '';
        $apiKey = $apiConfig['api_key'] ?? '';

        $response = Http::withHeaders([
            'apikey' => $apiKey,
            'Content-Type' => 'application/json',
        ])->get("{$apiUrl}/instance/fetchInstances");

        $this->info("Status: " . $response->status());
        $this->info("Response json:");
        print_r($response->json());
    }
}
