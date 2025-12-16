<?php

namespace Tests\Unit\Jobs;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Jobs\ProcessNBNOrderJob;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessNBNOrderJobTest extends TestCase
{
    /**
     * Test successful NBN order processing.
     */
    public function test_successful_order_stores_order_id_and_completes(): void
    {
        // Mock the HTTP response with successful stub
        $successResponse = json_decode(file_get_contents(base_path('tests/stubs/nbn-successful-response.json')), true);
        
        Http::fake([
            '*' => Http::response($successResponse, 200),
        ]);

        // Create test data
        $plan = Plan::factory()->create(['type' => PlanType::NBN->value]);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
            'order_id' => null,
        ]);

        // Dispatch the job
        ProcessNBNOrderJob::dispatch($application);

        // Verify the application was updated correctly
        $application->refresh();
        $this->assertEquals(ApplicationStatus::Complete, $application->status);
        $this->assertEquals('ORD000000000000', $application->order_id);
    }

    /**
     * Test failed NBN order processing.
     */
    public function test_failed_order_marks_as_order_failed(): void
    {
        // Mock the HTTP response with failed stub
        $failResponse = json_decode(file_get_contents(base_path('tests/stubs/nbn-fail-response.json')), true);
        
        Http::fake([
            '*' => Http::response($failResponse, 200),
        ]);

        // Create test data
        $plan = Plan::factory()->create(['type' => PlanType::NBN->value]);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
        ]);

        // Dispatch the job
        ProcessNBNOrderJob::dispatch($application);

        // Verify the application was marked as failed
        $application->refresh();
        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
    }

    /**
     * Test HTTP error marks application as failed.
     */
    public function test_http_error_marks_as_order_failed(): void
    {
        // Mock a failed HTTP response
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        // Create test data
        $plan = Plan::factory()->create(['type' => PlanType::NBN->value]);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
        ]);

        // Dispatch the job
        ProcessNBNOrderJob::dispatch($application);

        // Verify the application was marked as failed
        $application->refresh();
        $this->assertEquals(ApplicationStatus::OrderFailed, $application->status);
    }

    /**
     * Test that correct payload is sent to B2B endpoint.
     */
    public function test_correct_payload_is_sent(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'Successful', 'id' => 'ORD123'], 200),
        ]);

        // Create test data
        $plan = Plan::factory()->create([
            'type' => PlanType::NBN->value,
            'name' => 'NBN 25/5',
        ]);
        $application = Application::factory()->create([
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Order,
            'address_1' => '123 Main St',
            'address_2' => 'Apt 4B',
            'city' => 'Sydney',
            'state' => 'NSW',
            'postcode' => '2000',
        ]);

        // Dispatch the job
        ProcessNBNOrderJob::dispatch($application);

        // Verify the correct payload was sent
        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request['address_1'] === '123 Main St'
                && $request['address_2'] === 'Apt 4B'
                && $request['city'] === 'Sydney'
                && $request['state'] === 'NSW'
                && $request['postcode'] === '2000'
                && $request['plan_name'] === 'NBN 25/5';
        });
    }
}
