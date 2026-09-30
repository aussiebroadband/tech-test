<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use Illuminate\Console\Command;

class QueueNbnOrders extends Command
{
    protected $signature = 'applications:order-nbn';

    protected $description = 'Queue an nbn B2B order for every nbn application in the "order" status';

    public function handle(): int
    {
        // Without an endpoint every order would fail; stop before any
        // application is moved to "order failed" by a configuration error.
        if (blank(config('services.nbn.endpoint'))) {
            $this->error('NBN_B2B_ENDPOINT is not configured; no applications were queued.');

            return self::FAILURE;
        }

        $found = 0;

        $applications = Application::query()
            ->withStatus(ApplicationStatus::Order)
            ->ofPlanType(PlanType::Nbn)
            ->lazyById();

        // OrderNbnApplication is unique per application, so one that is still
        // waiting on the queue from an earlier run is skipped, not queued twice.
        foreach ($applications as $application) {
            OrderNbnApplication::dispatch($application);
            $found++;
        }

        $this->info("Found {$found} nbn application(s) awaiting order; queued each one not already on the queue.");

        return self::SUCCESS;
    }
}
