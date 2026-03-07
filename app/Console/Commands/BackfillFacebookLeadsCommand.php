<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\FacebookLeadAdsService;
use Illuminate\Console\Command;

class BackfillFacebookLeadsCommand extends Command
{
    protected $signature = 'facebook:backfill-leads 
                            {companyId? : The company ID to backfill leads for}
                            {--since= : Only fetch leads created after this date (Y-m-d)}';

    protected $description = 'Backfill Facebook Lead Ads that may have been missed';

    public function __construct(
        private FacebookLeadAdsService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $companyId = $this->argument('companyId');
        $since = $this->option('since');

        $query = Integration::where('provider', 'facebook')
            ->where('status', 'active');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active Facebook integrations found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$integrations->count()} active Facebook integration(s).");

        foreach ($integrations as $integration) {
            $this->info("Processing company ID: {$integration->company_id}");

            try {
                $result = $this->service->backfillLeads($integration, $since);
                $this->info("  Processed {$result['count']} leads.");
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                $integration->markError($e->getMessage());
            }
        }

        $this->info('Backfill completed.');
        return Command::SUCCESS;
    }
}
