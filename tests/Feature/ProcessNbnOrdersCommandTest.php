<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\Application;
use App\Models\Plan;
use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrderJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class ProcessNbnOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_applications_for_nbn_order_only(): void
    {
        Queue::fake();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);

        //to be picked up 
        $app_1 = Application::factory()->create(['status' => ApplicationStatus::Order, 'plan_id' => $nbnPlan->id]);
        $app_2 = Application::factory()->create(['status' => ApplicationStatus::Order, 'plan_id' => $nbnPlan->id]);
        //not to be picked up
        $app_3 = Application::factory()->create(['status' => ApplicationStatus::Prelim, 'plan_id' => $nbnPlan->id]);
        $app_4 = Application::factory()->create(['status' => ApplicationStatus::Order, 'plan_id' => $mobilePlan->id]);

        $this->artisan('orders:process-nbn')->assertExitCode(0);

        Queue::assertPushed(ProcessNbnOrderJob::class, 2);

        Queue::assertPushed(ProcessNbnOrderJob::class, function ($job) use ($app_1, $app_2) {
            return in_array($job->application->id, [$app_1->id, $app_2->id], true);
        });
    }
}
