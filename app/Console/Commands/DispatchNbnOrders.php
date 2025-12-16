<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use Illuminate\Console\Command;

class DispatchNbnOrders extends Command
{
    protected $signature = 'nbn:dispatch-orders';

    protected $description = 'Dispatch queue jobs for NBN applications ready to order.';

    public function handle(): int
    {
        Application::query()
            ->where('status', ApplicationStatus::Order)
            ->whereHas('plan', fn ($planQuery) => $planQuery->where('type', 'nbn'))
            ->orderBy('id')
            ->select('id')
            ->chunkById(500, function ($applications) {
                foreach ($applications as $application) {
                    OrderNbnApplication::dispatch($application->id);
                }
            });

        return self::SUCCESS;
    }
}

