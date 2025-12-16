<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Jobs\OrderNbnApplication;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderNbnApplicationTest extends TestCase
{
    public function test_it_orders_an_nbn_application_and_marks_complete_on_success(): void
    {
        $endpoint = 'https://nbn.example/orders';
        config(['services.nbn.endpoint' => $endpoint]);

        $payload = json_decode(file_get_contents(base_path('tests/stubs/nbn-successful-response.json')), true);

        Http::fake([
            $endpoint => Http::response($payload, 200),
        ]);

        $plan = Plan::factory()->create([
            'type' => 'nbn',
            'name' => 'NBN 50',
        ]);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
            'address_1' => '1 Test St',
            'address_2' => null,
            'city' => 'Sydney',
            'state' => 'NSW',
            'postcode' => '2000',
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Complete, $application->status);
        $this->assertSame($payload['id'], $application->order_id);

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($endpoint, $application, $plan) {
            if ($request->url() !== $endpoint || $request->method() !== 'POST') {
                return false;
            }

            $data = $request->data();

            return ($data['address_1'] ?? null) === $application->address_1
                && array_key_exists('address_2', $data) && ($data['address_2'] ?? null) === $application->address_2
                && ($data['city'] ?? null) === $application->city
                && ($data['state'] ?? null) === $application->state
                && ($data['postcode'] ?? null) === $application->postcode
                && ($data['plan_name'] ?? null) === $plan->name;
        });
    }

    public function test_it_marks_order_failed_on_failure_response(): void
    {
        $endpoint = 'https://nbn.example/orders';
        config(['services.nbn.endpoint' => $endpoint]);

        $payload = json_decode(file_get_contents(base_path('tests/stubs/nbn-fail-response.json')), true);

        Http::fake([
            $endpoint => Http::response($payload, 200),
        ]);

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    public function test_it_marks_order_failed_when_the_http_client_throws(): void
    {
        $endpoint = 'https://nbn.example/orders';
        config(['services.nbn.endpoint' => $endpoint]);

        Http::fake(function () {
            throw new \Exception('boom');
        });

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
    }

    public function test_it_does_not_process_non_nbn_or_non_order_applications(): void
    {
        Http::fake();

        $plan = Plan::factory()->create(['type' => 'mobile']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Order, $application->status);
        $this->assertNull($application->order_id);
        Http::assertNothingSent();
    }

    public function test_it_does_not_process_when_application_is_not_in_order_status(): void
    {
        Http::fake();

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Prelim,
            'plan_id' => $plan->id,
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::Prelim, $application->status);
        $this->assertNull($application->order_id);
        Http::assertNothingSent();
    }

    public function test_it_marks_order_failed_when_endpoint_is_missing(): void
    {
        config(['services.nbn.endpoint' => null]);
        Http::fake();

        $plan = Plan::factory()->create(['type' => 'nbn']);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
        Http::assertNothingSent();
    }

    public function test_it_marks_order_failed_when_plan_name_is_missing(): void
    {
        $endpoint = 'https://nbn.example/orders';
        config(['services.nbn.endpoint' => $endpoint]);
        Http::fake();

        $plan = Plan::factory()->create([
            'type' => 'nbn',
            'name' => '',
        ]);
        $application = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'plan_id' => $plan->id,
            'order_id' => 'ORDER-SHOULD-BE-CLEARED',
        ]);

        OrderNbnApplication::dispatchSync($application->id);

        $application->refresh();
        $this->assertSame(ApplicationStatus::OrderFailed, $application->status);
        $this->assertNull($application->order_id);
        Http::assertNothingSent();
    }
}
