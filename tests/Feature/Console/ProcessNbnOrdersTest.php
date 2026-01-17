<?php

namespace Tests\Feature\Console;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrdersJob;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessNbnOrdersTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_dispatches_jobs_for_nbn_applications_with_order_status()
    {
        Queue::fake();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        
        $nbnOrderApp = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $nbnPlan->id
        ]);

        $this->artisan('process:nbn-orders')
            ->assertSuccessful();

        Queue::assertPushed(ProcessNbnOrdersJob::class, function ($job) use ($nbnOrderApp) {
            return $job->application->id === $nbnOrderApp->id;
        });
    }

    /** @test */
    public function it_dispatches_multiple_jobs_for_multiple_applications()
    {
        Queue::fake();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        
        $app1 = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $nbnPlan->id
        ]);
        
        $app2 = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $nbnPlan->id
        ]);

        $this->artisan('process:nbn-orders')
            ->assertSuccessful();

        Queue::assertPushed(ProcessNbnOrdersJob::class, 2);
    }

    /** @test */
    public function it_ignores_non_nbn_applications()
    {
        Queue::fake();

        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);
        
        Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $mobilePlan->id
        ]);

        $this->artisan('process:nbn-orders')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_ignores_nbn_applications_with_non_order_status()
    {
        Queue::fake();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        
        // Create applications with various non-order statuses
        Application::factory()->create([
            'status' => ApplicationStatus::Prelim,
            'plan_id' => $nbnPlan->id
        ]);
        
        Application::factory()->create([
            'status' => ApplicationStatus::Complete,
            'plan_id' => $nbnPlan->id
        ]);
        
        Application::factory()->create([
            'status' => ApplicationStatus::OrderFailed,
            'plan_id' => $nbnPlan->id
        ]);

        $this->artisan('process:nbn-orders')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_outputs_processing_summary()
    {
        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        
        Application::factory()->count(3)->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $nbnPlan->id
        ]);

        $this->artisan('process:nbn-orders')
            ->expectsOutput('Processing 3 NBN applications...')
            ->expectsOutput('Dispatched 3 jobs to queue.')
            ->assertSuccessful();
    }

    /** @test */
    public function scope_ready_for_nbn_ordering_returns_correct_applications()
    {
        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);

        $shouldInclude = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $nbnPlan->id
        ]);

        $wrongStatus = Application::factory()->create([
            'status' => ApplicationStatus::Complete,
            'plan_id' => $nbnPlan->id
        ]);

        $wrongPlanType = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $mobilePlan->id
        ]);

        $results = Application::readyForNbnOrdering()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($shouldInclude->id, $results->first()->id);
    }
}
