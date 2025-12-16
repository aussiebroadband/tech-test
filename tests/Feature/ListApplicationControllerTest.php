<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use App\Models\Customer;
use App\Models\Application;

class ListApplicationControllerTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user and token for authentication
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_list_applications_requires_authentication(): void
    {
        $response = $this->getJson('/api/applications');

        // Should return 403 (CSRF) or 401 (Unauthenticated)
        $this->assertThat(
            $response->getStatusCode(),
            $this->logicalOr(
                $this->equalTo(401),
                $this->equalTo(403)
            )
        );
    }

    public function test_list_applications_returns_all_applications(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_name',
                        'address',
                        'state',
                        'plan_type',
                        'plan_name',
                        'plan_monthly_cost',
                    ],
                ],
                'links',
                'meta',
            ]);

        // Should have 4 applications
        $this->assertCount(4, $response['data']);
    }

    public function test_list_applications_orders_by_created_at_oldest_first(): void
    {
        $app1 = Application::factory()->create();
        sleep(1); // Ensure different timestamp
        $app2 = Application::factory()->create();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertEquals($app1->id, $response['data'][0]['id']);
        $this->assertEquals($app2->id, $response['data'][1]['id']);
    }

    public function test_filter_applications_by_plan_type_nbn(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications?plan_type=nbn');

        $response->assertStatus(200);

        // Should have 2 NBN applications
        $this->assertCount(2, $response['data']);
        $this->assertEquals('nbn', $response['data'][0]['plan_type']);
        $this->assertEquals('nbn', $response['data'][1]['plan_type']);
    }

    public function test_filter_applications_by_plan_type_opticomm(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications?plan_type=opticomm');

        $response->assertStatus(200);

        // Should have 1 Opticomm application
        $this->assertCount(1, $response['data']);
        $this->assertEquals('opticomm', $response['data'][0]['plan_type']);
    }

    public function test_filter_applications_by_plan_type_mobile(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications?plan_type=mobile');

        $response->assertStatus(200);

        // Should have 1 Mobile application
        $this->assertCount(1, $response['data']);
        $this->assertEquals('mobile', $response['data'][0]['plan_type']);
    }

    public function test_invalid_plan_type_filter_returns_validation_error(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/applications?plan_type=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plan_type']);
    }

    public function test_plan_monthly_cost_is_formatted_in_dollars(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200);

        // Check that costs are formatted as dollar amounts
        foreach ($response['data'] as $app) {
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $app['plan_monthly_cost']);
        }
    }

    public function test_order_id_only_shown_for_complete_applications(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200);

        // Find applications with order_id (complete status)
        $completeApps = collect($response['data'])->filter(fn ($app) => isset($app['order_id']));
        $incompleteApps = collect($response['data'])->filter(fn ($app) => !isset($app['order_id']));

        // Should have at least one complete application with order_id
        $this->assertTrue($completeApps->count() > 0, 'No complete applications found');
        foreach ($completeApps as $app) {
            $this->assertNotNull($app['order_id']);
        }

        // Other applications should not have order_id
        foreach ($incompleteApps as $app) {
            $this->assertArrayNotHasKey('order_id', $app);
        }
    }

    public function test_customer_name_is_concatenated(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Application::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertEquals('John Doe', $response['data'][0]['customer_name']);
    }

    public function test_address_is_concatenated_with_address_2_when_present(): void
    {
        Application::factory()
            ->withAddress2()
            ->create([
                'address_1' => '123 Main St',
                'address_2' => 'Apt 4B',
            ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertStringContainsString('123 Main St', $response['data'][0]['address']);
        $this->assertStringContainsString('Apt 4B', $response['data'][0]['address']);
    }

    public function test_address_without_address_2(): void
    {
        Application::factory()->create([
            'address_1' => '456 Oak Ave',
            'address_2' => null,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertEquals('456 Oak Ave', $response['data'][0]['address']);
    }

    public function test_pagination_default_per_page(): void
    {
        $this->createApplications();

        $response = $this->withToken($this->token)
            ->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        // Should have pagination metadata
        $this->assertArrayHasKey('per_page', $response['meta']);
    }

    private function createApplications(): void
    {
        // Create 1 NBN application (prelim)
        Application::factory()
            ->create([
                'plan_id' => Plan::factory()->nbn()->create()->id,
            ]);

        // Create 1 Opticomm application (prelim)
        Application::factory()
            ->create([
                'plan_id' => Plan::factory()->opticomm()->create()->id,
            ]);

        // Create 1 Mobile application (order)
        Application::factory()
            ->order()
            ->create([
                'plan_id' => Plan::factory()->mobile()->create()->id,
            ]);

        // Create 1 NBN application (complete with order_id)
        Application::factory()
            ->complete()
            ->create([
                'plan_id' => Plan::factory()->nbn()->create()->id,
            ]);
    }
}

