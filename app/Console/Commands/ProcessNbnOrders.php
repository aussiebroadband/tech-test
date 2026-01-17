<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrdersJob;
use App\Models\Application;
use Illuminate\Console\Command;

class ProcessNbnOrders extends Command
{
    protected $signature = 'process:nbn-orders';

    protected $description = 'Process NBN applications with order status';

    public function handle(): int
    {
        $applications = Application::readyForNbnOrdering()
            ->with('plan')
            ->get();

        $count = $applications->count();

        if ($count === 0) {
            $this->info('No NBN applications to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$count} NBN applications...");

        $applications->each(function (Application $application) {
            ProcessNbnOrdersJob::dispatch($application);
        });

        $this->info("Dispatched {$count} jobs to queue.");

        return self::SUCCESS;
    }
}
