<?php

namespace Tests\Feature\Api;

use App\Enums\ApplicationStatus;
use App\Enums\PlanType;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListApplicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_applications(): void
    {
        $this->getJson(route('applications.index'))->assertUnauthorized();
    }

    public function test_it_returns_only_the_agreed_application_fields(): void
    {
        $application = Application::factory()
            ->for(Customer::factory()->state(['first_name' => 'Jane', 'last_name' => 'Citizen']))
            ->for(Plan::factory()->state(['name' => 'nbn 100/20', 'type' => PlanType::Nbn, 'monthly_cost' => 8999]))
            ->create([
                'status' => ApplicationStatus::Order,
                'address_1' => '1 Example Street',
                'address_2' => 'Unit 2',
                'city' => 'Morwell',
                'state' => 'VIC',
                'postcode' => '3840',
            ]);

        $this->listApplications()
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $application->id,
                'customer_full_name' => 'Jane Citizen',
                'address' => [
                    'address_1' => '1 Example Street',
                    'address_2' => 'Unit 2',
                    'city' => 'Morwell',
                    'postcode' => '3840',
                ],
                'state' => 'VIC',
                'plan_type' => 'nbn',
                'plan_name' => 'nbn 100/20',
                'plan_monthly_cost' => '$89.99',
            ]);
    }

    public function test_the_order_id_is_only_shown_for_complete_applications(): void
    {
        $complete = Application::factory()->create([
            'status' => ApplicationStatus::Complete,
            'order_id' => 'ORD000000000001',
        ]);

        // Give every other status an order id too, to prove it is hidden by status, not by being empty.
        foreach (ApplicationStatus::cases() as $status) {
            if ($status !== ApplicationStatus::Complete) {
                Application::factory()->create(['status' => $status, 'order_id' => 'ORD-HIDDEN']);
            }
        }

        $applications = collect($this->listApplications()->assertOk()->json('data'));

        $this->assertCount(count(ApplicationStatus::cases()), $applications);

        foreach ($applications as $application) {
            if ($application['id'] === $complete->id) {
                $this->assertSame('ORD000000000001', $application['order_id']);
            } else {
                $this->assertArrayNotHasKey('order_id', $application);
            }
        }
    }

    public function test_the_oldest_applications_are_at_the_top_of_the_list(): void
    {
        $newest = Application::factory()->create(['created_at' => now()->subDay()]);
        $oldest = Application::factory()->create(['created_at' => now()->subYear()]);
        $middle = Application::factory()->create(['created_at' => now()->subMonth()]);

        $this->listApplications()
            ->assertOk()
            ->assertJsonPath('data.*.id', [$oldest->id, $middle->id, $newest->id]);
    }

    public function test_applications_created_at_the_same_time_keep_a_stable_order(): void
    {
        $createdAt = now()->subDay();
        $applications = Application::factory()->count(3)->create(['created_at' => $createdAt]);

        $this->listApplications()
            ->assertOk()
            ->assertJsonPath('data.*.id', $applications->pluck('id')->all());
    }

    #[DataProvider('planTypes')]
    public function test_it_filters_applications_by_plan_type(PlanType $type): void
    {
        $matching = collect(PlanType::cases())
            ->mapWithKeys(fn (PlanType $planType) => [
                $planType->value => Application::factory()
                    ->for(Plan::factory()->state(['type' => $planType]))
                    ->create(),
            ])
            ->get($type->value);

        $this->listApplications(['plan_type' => $type->value])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.plan_type', $type->value);
    }

    public static function planTypes(): array
    {
        return array_map(fn (PlanType $type) => [$type], array_combine(
            array_column(PlanType::cases(), 'value'),
            PlanType::cases(),
        ));
    }

    public function test_without_a_plan_type_every_application_is_listed(): void
    {
        foreach (PlanType::cases() as $type) {
            Application::factory()->for(Plan::factory()->state(['type' => $type]))->create();
        }

        $this->listApplications()->assertOk()->assertJsonCount(count(PlanType::cases()), 'data');
        $this->listApplications(['plan_type' => ''])->assertOk()->assertJsonCount(count(PlanType::cases()), 'data');
    }

    public function test_an_unknown_plan_type_is_rejected(): void
    {
        $this->listApplications(['plan_type' => 'adsl'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plan_type');
    }

    public function test_applications_are_paginated(): void
    {
        Application::factory()->count(20)->create();

        $this->listApplications()
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonStructure(['data', 'links' => ['first', 'last', 'prev', 'next'], 'meta']);

        $this->listApplications(['page' => 2])
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_pagination_links_keep_the_plan_type_filter(): void
    {
        Application::factory()
            ->count(16)
            ->for(Plan::factory()->state(['type' => PlanType::Nbn]))
            ->create();

        $next = $this->listApplications(['plan_type' => 'nbn'])->assertOk()->json('links.next');

        $this->assertStringContainsString('plan_type=nbn', $next);
    }

    #[DataProvider('monthlyCosts')]
    public function test_the_plan_monthly_cost_is_shown_in_dollars(int $cents, string $expected): void
    {
        Application::factory()->for(Plan::factory()->state(['monthly_cost' => $cents]))->create();

        $this->listApplications()->assertOk()->assertJsonPath('data.0.plan_monthly_cost', $expected);
    }

    public static function monthlyCosts(): array
    {
        return [
            'whole dollars' => [5000, '$50.00'],
            'dollars and cents' => [8999, '$89.99'],
            'under a dollar' => [5, '$0.05'],
            'free' => [0, '$0.00'],
            'thousands separator' => [123456, '$1,234.56'],
        ];
    }

    private function listApplications(array $query = []): TestResponse
    {
        Sanctum::actingAs(User::factory()->create());

        return $this->getJson(route('applications.index', $query));
    }
}
