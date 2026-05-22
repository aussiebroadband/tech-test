<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_applications_with_required_fields_oldest_first(): void
    {
        $this->actingAsUser();

        $customer = Customer::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);
        $plan = Plan::factory()->create([
            'name' => 'NBN 100',
            'type' => 'nbn',
            'monthly_cost' => 7995,
        ]);

        $completeApplication = Application::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Complete,
            'order_id' => 'ORD-123',
            'address_1' => '1 Test Street',
            'address_2' => null,
            'city' => 'Melbourne',
            'state' => 'VIC',
            'postcode' => '3000',
            'created_at' => now()->subDays(2),
        ]);
        $incompleteApplication = Application::factory()->create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'status' => ApplicationStatus::Prelim,
            'order_id' => 'ORD-456',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/applications');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'customer_full_name',
                    'address',
                    'plan_type',
                    'plan_name',
                    'state',
                    'plan_monthly_cost',
                ]],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.id', $completeApplication->id)
            ->assertJsonPath('data.0.customer_full_name', 'Ada Lovelace')
            ->assertJsonPath('data.0.address', '1 Test Street, Melbourne, VIC, 3000')
            ->assertJsonPath('data.0.plan_type', 'nbn')
            ->assertJsonPath('data.0.plan_name', 'NBN 100')
            ->assertJsonPath('data.0.state', 'VIC')
            ->assertJsonPath('data.0.plan_monthly_cost', '79.95')
            ->assertJsonPath('data.0.order_id', 'ORD-123')
            ->assertJsonPath('data.1.id', $incompleteApplication->id);

        $this->assertArrayNotHasKey('order_id', $response->json('data.1'));
    }

    public function test_it_filters_applications_by_plan_type(): void
    {
        $this->actingAsUser();

        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);
        $nbnApplication = Application::factory()->create(['plan_id' => $nbnPlan->id]);
        Application::factory()->create(['plan_id' => $mobilePlan->id]);

        $this->getJson('/api/applications?plan_type=nbn')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nbnApplication->id)
            ->assertJsonPath('data.0.plan_type', 'nbn');
    }

    private function actingAsUser(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'password' => 'password',
        ]));
    }
}
