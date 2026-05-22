<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use Illuminate\Console\Command;

class OrderNbnApplications extends Command
{
    protected $signature = 'applications:order-nbn';

    protected $description = 'Dispatch order jobs for NBN applications ready to order';

    public function handle(): int
    {
        Application::query()
            ->where('status', ApplicationStatus::Order->value)
            ->whereHas('plan', function ($query) {
                $query->where('type', 'nbn');
            })
            ->select('id')
            ->chunkById(100, function ($applications) {
                foreach ($applications as $application) {
                    OrderNbnApplication::dispatch($application->id);
                }
            });

        return self::SUCCESS;
    }
}
