<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Jobs\ProcessNbnApplication;

class QueueNbnApplications extends Command
{
    protected $signature = 'nbn:queue';
    protected $description = 'Queue all NBN applications with status order for processing';

    public function handle()
    {
        $applications = Application::where('plan_type', 'nbn')
            ->where('status', 'order')
            ->get();

        foreach ($applications as $app) {
            ProcessNbnApplication::dispatch($app);
        }

        $this->info("Queued {$applications->count()} NBN applications.");
    }
}
