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

        $queued = 0;

        $applications = Application::query()
            ->withStatus(ApplicationStatus::Order)
            ->ofPlanType(PlanType::Nbn)
            ->lazyById();

        foreach ($applications as $application) {
            OrderNbnApplication::dispatch($application);
            $queued++;
        }

        $this->info("Queued {$queued} nbn application(s) for ordering.");

        return self::SUCCESS;
    }
}
