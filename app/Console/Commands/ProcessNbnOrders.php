<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Jobs\ProcessNbnOrder;
use App\Enums\ApplicationStatus;
use Illuminate\Console\Command;

class ProcessNbnOrders extends Command
{
    protected $signature = 'orders:process-nbn';

    protected $description = 'Process all pending NBN applications with order status';

    public function handle(): int
    {
        $count = 0;

        Application::query()
            ->where('status', ApplicationStatus::Order)
            ->whereHas('plan', fn ($query) => $query->where('type', 'nbn'))
            ->orderBy('id')
            ->chunkById(500, function ($applications) use (&$count) {
                foreach ($applications as $application) {
                    ProcessNbnOrder::dispatch($application);
                    $count++;
                }
            });

        if ($count === 0) {
            $this->info('No NBN applications to process.');
        } else {
            $this->info("Dispatched {$count} NBN application(s) for processing.");
        }

        return Command::SUCCESS;
    }
}
