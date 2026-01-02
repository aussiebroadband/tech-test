<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Plan;
use App\Models\User;
use App\Models\Customer;
use App\Models\Application;
use App\Enums\ApplicationStatus;
use Laravel\Sanctum\Sanctum;

class ApplicationApiTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_applications(): void
    {
        $response = $this->getJson('/api/applications');

        $this->assertContains($response->getStatusCode(), [401, 403]);
    }

    public function test_authenticated_user_can_list_applications(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Use actual Bearer token like Postman(as example)
        $this->withHeader('Authorization', 'Bearer ' . $token);

        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $plan = Plan::factory()->create([
            'name' => 'Basic Plan',
            'type' => 'nbn',
            'monthly_cost' => 4999,
        ]);
        $application = Application::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'address_1' => '123 Main St',
            'address_2' => 'Unit 1',
            'city' => 'Sydney',
            'state' => 'NSW',
            'postcode' => '2000',
            'status' => ApplicationStatus::Prelim,
        ]);

        $response = $this->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $application->id)
            ->assertJsonPath('data.0.customer_full_name', 'John Doe')
            ->assertJsonPath('data.0.address', '123 Main St, Unit 1, Sydney, NSW, 2000')
            ->assertJsonPath('data.0.plan_type', 'nbn')
            ->assertJsonPath('data.0.plan_name', 'Basic Plan')
            ->assertJsonPath('data.0.state', 'NSW')
            ->assertJsonPath('data.0.plan_monthly_cost', '$49.99')
            ->assertJsonMissing(['order_id']);
    }

    public function test_order_id_only_shown_for_complete_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $completeApp = Application::factory()->create([
            'status' => ApplicationStatus::Complete,
            'order_id' => 'ORD-12345',
        ]);

        $prelimApp = Application::factory()->create([
            'status' => ApplicationStatus::Prelim,
            'order_id' => 'ORD-99999',
        ]);

        $response = $this->getJson('/api/applications');

        $response->assertStatus(200);

        $data = $response->json('data');

        $completeData = collect($data)->firstWhere('id', $completeApp->id);
        $prelimData = collect($data)->firstWhere('id', $prelimApp->id);

        $this->assertEquals('ORD-12345', $completeData['order_id']);
        $this->assertArrayNotHasKey('order_id', $prelimData);
    }

    public function test_applications_can_be_filtered_by_plan_type(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);
        $opticommPlan = Plan::factory()->create(['type' => 'opticomm']);

        $nbnApp = Application::factory()->create(['plan_id' => $nbnPlan->id]);
        $mobileApp = Application::factory()->create(['plan_id' => $mobilePlan->id]);
        $opticommApp = Application::factory()->create(['plan_id' => $opticommPlan->id]);

        $response = $this->getJson('/api/applications?plan_type=opticomm');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($opticommApp->id, $ids);
        $this->assertNotContains($nbnApp->id, $ids);
        $this->assertNotContains($mobileApp->id, $ids);
    }

    public function test_applications_are_ordered_by_oldest_first(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $oldApp = Application::factory()->create([
            'created_at' => now()->subDays(10),
        ]);
        $newApp = Application::factory()->create([
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/applications');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals($oldApp->id, $ids[0]);
        $this->assertEquals($newApp->id, $ids[1]);
    }

    public function test_applications_are_paginated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Application::factory()->count(20)->create();

        $response = $this->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_invalid_plan_type_returns_validation_error(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/applications?plan_type=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_type']);
    }
}
