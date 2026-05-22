<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderNbnApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_jobs_only_for_order_ready_nbn_applications(): void
    {
        Bus::fake();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);

        $orderReadyNbnApplication = Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Order,
        ]);
        Application::factory()->create([
            'plan_id' => $mobilePlan->id,
            'status' => ApplicationStatus::Order,
        ]);
        Application::factory()->create([
            'plan_id' => $nbnPlan->id,
            'status' => ApplicationStatus::Prelim,
        ]);

        $this->artisan('applications:order-nbn')
            ->assertSuccessful();

        Bus::assertDispatchedTimes(OrderNbnApplication::class, 1);
        Bus::assertDispatched(OrderNbnApplication::class, function (OrderNbnApplication $job) use ($orderReadyNbnApplication) {
            return $job->applicationId === $orderReadyNbnApplication->id;
        });
    }

    public function test_job_sends_expected_payload_and_completes_successful_orders(): void
    {
        config(['services.nbn.endpoint' => 'https://nbn.example.test/orders']);

        Http::fake([
            'https://nbn.example.test/orders' => Http::response($this->stub('nbn-successful-response.json')),
        ]);

        $plan = Plan::factory()->create([
            'name' => 'NBN Home Fast',
            'type' => 'nbn',
        ]);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
            'address_1' => '1 Test Street',
            'address_2' => 'Unit 2',
            'city' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
        ]);

        (new OrderNbnApplication($application->id))->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://nbn.example.test/orders'
                && $request['address_1'] === '1 Test Street'
                && $request['address_2'] === 'Unit 2'
                && $request['city'] === 'Melbourne'
                && $request['state'] === 'VIC'
                && $request['postcode'] === '3000'
                && $request['plan_name'] === 'NBN Home Fast';
        });

        $application->refresh();

        $this->assertSame(ApplicationStatus::Complete, $application->status);
        $this->assertSame('ORD000000000000', $application->order_id);
    }

    public function test_job_marks_application_as_failed_when_b2b_response_fails(): void
    {
        config(['services.nbn.endpoint' => 'https://nbn.example.test/orders']);

        Http::fake([
            'https://nbn.example.test/orders' => Http::response($this->stub('nbn-fail-response.json')),
        ]);

        $application = $this->orderReadyNbnApplication();

        (new OrderNbnApplication($application->id))->handle();

        $application->refresh();

        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    public function test_job_marks_application_as_failed_when_b2b_request_errors(): void
    {
        config(['services.nbn.endpoint' => 'https://nbn.example.test/orders']);

        Http::fake(function () {
            throw new ConnectionException('B2B unavailable');
        });

        $application = $this->orderReadyNbnApplication();

        (new OrderNbnApplication($application->id))->handle();

        $application->refresh();

        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }


    private function stub(string $file): array
    {
        return json_decode(file_get_contents(base_path("tests/stubs/{$file}")), true);
    }

    private function orderReadyNbnApplication(): Application
    {
        $plan = Plan::factory()->create(['type' => 'nbn']);

        return Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
        ]);
    }
}
