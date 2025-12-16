<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrderJob;
use App\Models\Application;
use Illuminate\Console\Command;

class ProcessNbnOrders extends Command
{
    protected $signature = 'orders:process-nbn {--limit=50}';
    protected $description = 'Filter NBN orders and process them';

    public function handle(): int
    {
        echo "starting ... ";
        $limit = (int) $this->option('limit');

        $applications = Application::query()
            ->where('status', ApplicationStatus::Order)
            ->whereHas('plan', fn ($q) => $q->where('type', 'nbn'))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        foreach ($applications as $application) {
            ProcessNbnOrderJob::dispatch($application);
        }

        echo "done!";

        return self::SUCCESS;
    }
}
