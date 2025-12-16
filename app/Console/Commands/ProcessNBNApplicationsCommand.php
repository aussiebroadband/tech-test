<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\ProcessNBNOrderJob;
use App\Models\Application;
use Illuminate\Console\Command;

class ProcessNBNApplicationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nbn:process-applications';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Process NBN applications with order status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Get all NBN applications with 'order' status
        $applications = Application::query()
            ->with('plan')
            ->where('status', ApplicationStatus::Order)
            ->whereHas('plan', fn ($query) => $query->where('type', PlanType::NBN->value))
            ->get();

        if ($applications->isEmpty()) {
            $this->info('No NBN applications with order status found.');
            return 0;
        }

        $this->info("Found {$applications->count()} NBN applications to process.");

        // Dispatch each application to the queue
        foreach ($applications as $application) {
            ProcessNBNOrderJob::dispatch($application);
            $this->line("Dispatched application {$application->id} to queue.");
        }

        $this->info('All applications dispatched to queue.');
        return 0;
    }
}
