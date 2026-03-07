<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Jobs\PullIndiaMartLeadsJob;
use Illuminate\Console\Command;

class PullIndiaMartLeadsCommand extends Command
{
    protected $signature = 'indiamart:pull-leads {companyId? : The company ID to pull leads for}';
    protected $description = 'Pull leads from IndiaMART for active integrations';

    public function handle(): int
    {
        $companyId = $this->argument('companyId');

        $query = Integration::where('provider', 'indiamart')
            ->where('status', 'active');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active IndiaMART integrations found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$integrations->count()} active IndiaMART integration(s).");

        foreach ($integrations as $integration) {
            $this->info("Dispatching job for company ID: {$integration->company_id}");
            PullIndiaMartLeadsJob::dispatch($integration->id);
        }

        $this->info('Jobs dispatched successfully.');
        return Command::SUCCESS;
    }
}
