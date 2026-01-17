<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ApplicationsListTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function guests_cannot_view_applications()
    {
        $response = $this->getJson('/api/applications');

        $response->assertStatus(403);
    }

    /** @test */
    public function authenticated_users_can_view_applications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200);
    }

    /** @test */
    public function applications_are_paginated()
    {
        $user = User::factory()->create();
        Application::factory()->count(25)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_full_name',
                        'address',
                        'plan_type',
                        'plan_name',
                        'state',
                        'plan_monthly_cost',
                    ]
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(15, 'data'); // Default pagination is 15
    }

    /** @test */
    public function oldest_applications_appear_first()
    {
        $user = User::factory()->create();

        $newest = Application::factory()->create(['created_at' => now()]);
        $oldest = Application::factory()->create(['created_at' => now()->subDays(5)]);
        $middle = Application::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertEquals([$oldest->id, $middle->id, $newest->id], $ids);
    }

    /** @test */
    public function order_id_only_shown_for_completed_applications()
    {
        $user = User::factory()->create();

        $completeApp = Application::factory()->create([
            'status'   => ApplicationStatus::Complete,
            'order_id' => 'NBN-12345'
        ]);

        $orderApp = Application::factory()->create([
            'status'   => ApplicationStatus::Order,
            'order_id' => null
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Complete application should have order_id
        $completeData = collect($data)->firstWhere('id', $completeApp->id);
        $this->assertArrayHasKey('order_id', $completeData);
        $this->assertEquals('NBN-12345', $completeData['order_id']);

        // Non-complete application should not have order_id
        $orderData = collect($data)->firstWhere('id', $orderApp->id);
        $this->assertArrayNotHasKey('order_id', $orderData);
    }

    /** @test */
    public function can_filter_by_plan_type()
    {
        $user = User::factory()->create();

        $nbnPlan      = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan   = Plan::factory()->create(['type' => 'mobile']);
        $opticommPlan = Plan::factory()->create(['type' => 'opticomm']);

        $nbnApp      = Application::factory()->create(['plan_id' => $nbnPlan->id]);
        $mobileApp   = Application::factory()->create(['plan_id' => $mobilePlan->id]);
        $opticommApp = Application::factory()->create(['plan_id' => $opticommPlan->id]);

        // Filter by nbn
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications?plan_type=nbn');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertEquals($nbnApp->id, $response->json('data.0.id'));
    }

    /** @test */
    public function shows_all_applications_without_filters()
    {
        $user = User::factory()->create();

        Application::factory()->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function rejects_invalid_plan_type_filters()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications?plan_type=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_type']);
    }

    /** @test */
    public function displays_cost_in_dollars_with_formatting()
    {
        $user = User::factory()->create();

        $plan = Plan::factory()->create([
            'monthly_cost' => 5999, // $59.99
        ]);

        $application = Application::factory()->create([
            'plan_id' => $plan->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200);

        $this->assertEquals('$59.99', $response->json('data.0.plan_monthly_cost'));
    }

    /** @test */
    public function handles_customers_with_missing_last_names()
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name'  => null
        ]);

        $application = Application::factory()->create([
            'customer_id' => $customer->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertEquals('John', $response->json('data.0.customer_full_name'));
    }

    /** @test */
    public function handles_applications_with_single_address_line()
    {
        $user = User::factory()->create();

        $application = Application::factory()->create([
            'address_1' => '123 Main St',
            'address_2' => null
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertEquals('123 Main St', $response->json('data.0.address'));
    }
}