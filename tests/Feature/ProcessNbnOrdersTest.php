<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\Application;
use App\Jobs\ProcessNbnOrder;
use App\Enums\ApplicationStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class ProcessNbnOrdersTest extends TestCase
{
    public function test_command_dispatches_jobs_for_nbn_order_applications(): void
    {
        Queue::fake();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);

        $nbnOrderApp = Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $nbnPrelimApp = Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Prelim,
        ]);

        $mobileOrderApp = Application::factory()->create([
            'plan_id' => $mobilePlan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $this->artisan('orders:process-nbn')
            ->expectsOutput('Dispatched 1 NBN application(s) for processing.')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessNbnOrder::class, 1);
        Queue::assertPushed(ProcessNbnOrder::class, function ($job) use ($nbnOrderApp) {
            return $job->application->id === $nbnOrderApp->id;
        });
    }

    public function test_command_outputs_message_when_no_applications(): void
    {
        Queue::fake();

        $this->artisan('orders:process-nbn')
            ->expectsOutput('No NBN applications to process.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function test_job_updates_application_to_complete_on_success(): void
    {
        $successResponse = json_decode(
            file_get_contents(base_path('tests/stubs/nbn-successful-response.json')),
            true
        );

        Http::fake([
            '*' => Http::response($successResponse, 200),
        ]);

        $plan = Plan::factory()->create([
            'type' => 'nbn', 
            'name' => 'NBN Basic'
        ]);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $job = new ProcessNbnOrder($application);
        $job->handle();

        $application->refresh();

        $this->assertEquals(ApplicationStatus::Complete, $application->status);
        $this->assertEquals('ORD000000000000', $application->order_id);
    }

    public function test_job_updates_application_to_order_failed_on_failure(): void
    {
        $failResponse = json_decode(
            file_get_contents(base_path('tests/stubs/nbn-fail-response.json')),
            true
        );

        Http::fake([
            '*' => Http::response($failResponse, 200),
        ]);

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $job = new ProcessNbnOrder($application);
        $job->handle();

        $application->refresh();

        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
    }

    public function test_job_updates_application_to_order_failed_on_http_error(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
        ]);

        $job = new ProcessNbnOrder($application);
        $job->handle();

        $application->refresh();

        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
    }

    public function test_job_sends_correct_payload_to_b2b_endpoint(): void
    {
        Http::fake();

        $plan = Plan::factory()->create(['type' => 'nbn', 'name' => 'NBN Premium']);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
            'address_1' => '123 Test St',
            'address_2' => 'Unit 5',
            'city' => 'Sydney',
            'state' => 'NSW',
            'postcode' => '2000',
        ]);

        $job = new ProcessNbnOrder($application);
        $job->handle();

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request['address_1'] === '123 Test St'
                && $request['address_2'] === 'Unit 5'
                && $request['city'] === 'Sydney'
                && $request['state'] === 'NSW'
                && $request['postcode'] === '2000'
                && $request['plan_name'] === 'NBN Premium';
        });
    }

    public function test_command_is_scheduled_every_five_minutes(): void
    {
        $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);

        $events = collect($schedule->events())->filter(function ($event) {
            return str_contains($event->command, 'orders:process-nbn');
        });

        $this->assertTrue($events->count() > 0);
        $this->assertEquals('*/5 * * * *', $events->first()->expression);
    }
}
