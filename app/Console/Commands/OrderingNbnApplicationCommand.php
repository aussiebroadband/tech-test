<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Enums\OrderNbnApplicationStatus;
use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OrderingNbnApplicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ordering-nbn-application';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automate the ordering of all nbn applications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = Application::query()
            ->join('plans', 'plans.id', '=', 'applications.plan_id')
            ->where('applications.status', '=', ApplicationStatus::Order)
            ->where('plans.type', '=', 'nbn')
            ->get();

        foreach ($applications as $application) {
            $response = Http::post(config('app.nbn_b2b_endpoint'))->json();

            if($response['status'] === OrderNbnApplicationStatus::Successful->value) {
                $application->order_id = $response['id'];
                $application->status = ApplicationStatus::Complete;
                $application->save();
            } else if ($response['status'] === OrderNbnApplicationStatus::Failed->value) {
                $application->status = ApplicationStatus::OrderFailed;
                $application->save();
            }
        }

        return Command::SUCCESS;
    }
}
