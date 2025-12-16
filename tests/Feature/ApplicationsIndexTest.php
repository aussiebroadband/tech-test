<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationsIndexTest extends TestCase
{
    public function test_guest_cannot_list_applications(): void
    {
        $response = $this->getJson('/api/applications');

        $this->assertTrue(
            in_array($response->status(), [401, 403], true),
            "Expected 401 or 403, got {$response->status()}."
        );
    }

    public function test_it_returns_paginated_oldest_first_applications_with_expected_shape(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $planNbn = Plan::factory()->create([
            'type' => 'nbn',
            'name' => 'NBN 50',
            'monthly_cost' => 1234,
        ]);
        $customerAlice = Customer::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
        ]);
        $oldestComplete = Application::factory()->create([
            'status' => ApplicationStatus::Complete,
            'customer_id' => $customerAlice->id,
            'plan_id' => $planNbn->id,
            'address_1' => '1 Test St',
            'address_2' => 'Unit 2',
            'city' => 'Sydney',
            'state' => 'NSW',
            'postcode' => '2000',
            'order_id' => 'ORDER-123',
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $planMobile = Plan::factory()->create([
            'type' => 'mobile',
            'name' => 'Mobile 20',
            'monthly_cost' => 2500,
        ]);
        $customerBob = Customer::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
        ]);
        $newerOrder = Application::factory()->create([
            'status' => ApplicationStatus::Order,
            'customer_id' => $customerBob->id,
            'plan_id' => $planMobile->id,
            'address_1' => '2 Example Rd',
            'address_2' => null,
            'city' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'order_id' => 'ORDER-SHOULD-NOT-SHOW',
            'created_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->getJson('/api/applications');

        $response->assertOk()->assertJsonStructure([
            'data' => [
                '*' => [
                    'application_id',
                    'customer_full_name',
                    'address',
                    'plan_type',
                    'plan_name',
                    'state',
                    'plan_monthly_cost',
                ],
            ],
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
        ]);

        $response->assertJsonPath('meta.total', 2);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $this->assertSame($oldestComplete->id, $data[0]['application_id']);
        $this->assertSame($newerOrder->id, $data[1]['application_id']);

        $this->assertEqualsCanonicalizing([
            'application_id',
            'customer_full_name',
            'address',
            'plan_type',
            'plan_name',
            'state',
            'plan_monthly_cost',
            'order_id',
        ], array_keys($data[0]));
        $this->assertSame('Alice Smith', $data[0]['customer_full_name']);
        $this->assertSame('1 Test St, Unit 2, Sydney 2000', $data[0]['address']);
        $this->assertSame('nbn', $data[0]['plan_type']);
        $this->assertSame('NBN 50', $data[0]['plan_name']);
        $this->assertSame('NSW', $data[0]['state']);
        $this->assertSame('12.34', $data[0]['plan_monthly_cost']);
        $this->assertIsString($data[0]['plan_monthly_cost']);
        $this->assertSame('ORDER-123', $data[0]['order_id']);

        $this->assertEqualsCanonicalizing([
            'application_id',
            'customer_full_name',
            'address',
            'plan_type',
            'plan_name',
            'state',
            'plan_monthly_cost',
        ], array_keys($data[1]));
        $this->assertSame('Bob Jones', $data[1]['customer_full_name']);
        $this->assertSame('2 Example Rd, Melbourne 3000', $data[1]['address']);
        $this->assertSame('mobile', $data[1]['plan_type']);
        $this->assertSame('Mobile 20', $data[1]['plan_name']);
        $this->assertSame('VIC', $data[1]['state']);
        $this->assertSame('25.00', $data[1]['plan_monthly_cost']);
        $this->assertIsString($data[1]['plan_monthly_cost']);
        $this->assertArrayNotHasKey('order_id', $data[1]);
    }

    public function test_it_can_filter_by_each_plan_type(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $planNbn = Plan::factory()->create(['type' => 'nbn']);
        $planOpticomm = Plan::factory()->create(['type' => 'opticomm']);
        $planMobile = Plan::factory()->create(['type' => 'mobile']);

        Application::factory()->create(['plan_id' => $planNbn->id]);
        Application::factory()->create(['plan_id' => $planOpticomm->id]);
        Application::factory()->create(['plan_id' => $planMobile->id]);

        foreach (['nbn', 'opticomm', 'mobile'] as $planType) {
            $response = $this->getJson("/api/applications?plan_type={$planType}");

            $response->assertOk()->assertJsonPath('meta.total', 1);
            $response->assertJsonCount(1, 'data');

            foreach ($response->json('data') as $application) {
                $this->assertSame($planType, $application['plan_type']);
            }
        }
    }

    public function test_it_validates_plan_type_filter(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/applications?plan_type=foo')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan_type']);
    }
}
