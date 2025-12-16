<?php

namespace Tests\Feature\Internal;

use Tests\TestCase;
use App\Models\Application;
use App\Models\Plan;
use App\Models\Customer;
use App\Models\User;
use App\Enums\ApplicationStatus;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;
    
    private string $url = '/api/applications';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_returns_paginated_structure(): void
    {
        Application::factory()->count(3)->create();

        $res = $this->getJson($this->url);

        $res->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
            ]);
    }

    public function test_returns_only_allowed_fields(): void
    {
        $plan = Plan::factory()->create(['type' => 'nbn', 'monthly_cost' => 9900]);
        $customer = Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $app = Application::factory()->create([
            'plan_id' => $plan->id,
            'customer_id' => $customer->id,
            'status' => ApplicationStatus::Prelim,
            'order_id' => 'NO_SHOW',
        ]);

        $res = $this->getJson($this->url)->assertOk();

        $row = collect($res->json('data'))->firstWhere('application_id', $app->id);

        $expectedKeys = [
            'application_id',
            'customer_full_name',
            'address',
            'plan_type',
            'plan_name',
            'state',
            'plan_monthly_cost',
        ];

        $this->assertSame($expectedKeys, array_values(array_intersect(array_keys($row), $expectedKeys)));
        $this->assertArrayNotHasKey('order_id', $row); //status is not complete

    }

    public function test_returns_by_application_created_date_asc(): void
    {
        
    }

    public function test_returns_human_readable_monthly_cost(): void
    {
        $plan = Plan::factory()->create(['monthly_cost' => 1234]);
        $app  = Application::factory()->create(['plan_id' => $plan->id]);

        $res = $this->getJson($this->url)->assertOk();

        $row = collect($res->json('data'))->firstWhere('application_id', $app->id);

        $this->assertSame('12.34', $row['plan_monthly_cost']);
    }

    public function test_returns_order_id_when_status_is_complete(): void
    {
        //should show order_id
        $completeApp = Application::factory()->create([
            'status' => ApplicationStatus::Complete,
            'order_id' => '11111',
        ]);

        //should not show order_id
        $notCompleteApp = Application::factory()->create([
            'status' => ApplicationStatus::Prelim,
            'order_id' => '22222',
        ]);

        $res = $this->getJson($this->url)->assertOk();

        $rowsByAppId = collect($res->json('data'))->keyBy('application_id');

        $this->assertSame('11111', $rowsByAppId[$completeApp->id]['order_id']);
        $this->assertArrayNotHasKey('order_id', $rowsByAppId[$notCompleteApp->id]);
    }

    public function test_can_filter_by_plan_type(): void
    {
        $nbnPlan = Plan::factory()->create(['type' => 'nbn']);
        $mobilePlan = Plan::factory()->create(['type' => 'mobile']);

        $nbnPlanApp = Application::factory()->create(['plan_id' => $nbnPlan->id]);
        $mobilePlanApp = Application::factory()->create(['plan_id' => $mobilePlan->id]);

        $res = $this->getJson($this->url . '?plan_type=nbn')->assertOk();

        foreach ($res->json('data') as $row) {
            $this->assertSame('nbn', $row['plan_type']);
        }
    }

    
}
