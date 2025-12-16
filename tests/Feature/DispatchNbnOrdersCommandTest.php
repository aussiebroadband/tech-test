<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchNbnOrdersCommandTest extends TestCase
{
    public function test_it_dispatches_jobs_for_order_status_nbn_applications_only(): void
    {
        Queue::fake();

        $planNbn = Plan::factory()->create(['type' => 'nbn']);
        $planMobile = Plan::factory()->create(['type' => 'mobile']);

        $eligibleOne = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $planNbn->id,
        ]);
        $eligibleTwo = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $planNbn->id,
        ]);

        $ineligibleWrongStatus = Application::factory()->create([
            'status' => ApplicationStatus::Prelim,
            'plan_id' => $planNbn->id,
        ]);
        $ineligibleWrongPlanType = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $planMobile->id,
        ]);

        $this->artisan('nbn:dispatch-orders')->assertExitCode(0);

        Queue::assertPushed(OrderNbnApplication::class, 2);
        Queue::assertPushed(OrderNbnApplication::class, fn (OrderNbnApplication $job) => $job->applicationId === $eligibleOne->id);
        Queue::assertPushed(OrderNbnApplication::class, fn (OrderNbnApplication $job) => $job->applicationId === $eligibleTwo->id);

        Queue::assertNotPushed(OrderNbnApplication::class, fn (OrderNbnApplication $job) => $job->applicationId === $ineligibleWrongStatus->id);
        Queue::assertNotPushed(OrderNbnApplication::class, fn (OrderNbnApplication $job) => $job->applicationId === $ineligibleWrongPlanType->id);
    }
}

