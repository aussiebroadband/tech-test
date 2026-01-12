<?php

namespace App\Console\Commands;
use App\Enums\ApplicationStatus;
use Illuminate\Console\Command;
use App\Models\Application;
use App\Jobs\ProcessNbnApplication;

class ProcessNbnApplications extends Command
{
    protected $signature = 'applications:process-nbn';
    protected $description = 'Dispatch NBN applications in order state to the queue';

    public function handle(): void
    {
        // Pick up only NBN applications in 'order' state
        $applications = Application::with(['plan', 'customer'])
            ->where('status', '=', ApplicationStatus::Order)
            ->whereHas('plan', fn($q) => $q->where('type', 'nbn'))
            ->get();

        foreach ($applications as $app) {
            //ProcessNbnApplication::dispatch($app);
            var_dump($app);
        }

        $this->info($applications->count() . ' NBN applications dispatched to queue.');
    }
}
