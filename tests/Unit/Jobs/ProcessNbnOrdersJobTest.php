<?php

namespace Tests\Unit\Jobs;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrdersJob;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessNbnOrdersJobTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_sends_correct_payload_to_nbn_b2b_endpoint()
    {
        Http::fake([
            '*' => Http::response(json_decode(file_get_contents(base_path('tests/stubs/nbn-successful-response.json')), true))
        ]);

        $plan = Plan::factory()->create([
            'type' => 'nbn',
            'name' => 'NBN 100/20'
        ]);

        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
            'address_1' => '123 Main St',
            'address_2' => 'Unit 5',
            'city' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000'
        ]);

        $job = new ProcessNbnOrdersJob($application);
        $job->handle();

        Http::assertSent(function ($request) use ($plan, $application) {
            return $request->url() === config('services.nbn.b2b_endpoint') &&
                   $request['address_1'] === '123 Main St' &&
                   $request['address_2'] === 'Unit 5' &&
                   $request['city'] === 'Melbourne' &&
                   $request['state'] === 'VIC' &&
                   $request['postcode'] === '3000' &&
                   $request['plan name'] === 'NBN 100/20';
        });
    }

    /** @test */
    public function it_updates_application_to_complete_status_on_success()
    {
        Http::fake([
            '*' => Http::response(json_decode(file_get_contents(base_path('tests/stubs/nbn-successful-response.json')), true))
        ]);

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
            'order_id' => null
        ]);

        $job = new ProcessNbnOrdersJob($application);
        $job->handle();

        $application->refresh();

        $this->assertEquals(ApplicationStatus::Complete, $application->status);
        $this->assertEquals('ORD000000000000', $application->order_id);
    }

    /** @test */
    public function it_updates_application_to_order_failed_status_on_failure()
    {
        Http::fake([
            '*' => Http::response(json_decode(file_get_contents(base_path('tests/stubs/nbn-fail-response.json')), true))
        ]);

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id
        ]);

        $job = new ProcessNbnOrdersJob($application);
        $job->handle();

        $application->refresh();

        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    /** @test */
    public function it_updates_application_to_order_failed_on_http_exception()
    {
        Http::fake([
            '*' => Http::response('Service Unavailable', 503)
        ]);

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id
        ]);

        $job = new ProcessNbnOrdersJob($application);
        $job->handle();

        $application->refresh();

        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
    }
}
