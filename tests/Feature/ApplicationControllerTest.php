<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    private const URL = '/api/applications';

    public function test_must_be_authenticated(): void {
        $response = $this->getJson(self::URL);

        $response->assertStatus(403);
    }

    public function test_plan_type_should_exists(): void {
        Application::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL . '?plan_type=lorem');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'plan_type' => 'The selected plan type is invalid.'
        ]);
    }

    public function test_filter_by_plan_type(): void {
        $application = Application::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL. "?plan_type=" . $application->plan_type);

        $response->assertStatus(200);
        // assert it should return data
        $this->assertCount(1, $response->json()['data']);
    }

    public function test_must_be_paginated(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL);

        $response->assertStatus(200);
        $response->assertJsonStructure(['meta' => [
            'current_page',
            'from',
            'last_page',
            'links',
            'path',
            'per_page',
            'to',
            'total'
        ]]);
    }

    // this test the json structure we expected the api will return
    public function test_structure(): void
    {
        Application::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => ['*' => [
                'id',
                'customer',
                'address',
                'status',
                'plan' => [
                    'name',
                    'type',
                    'monthly_cost'
                ]
            ]]
        ]);

    }

    // show the order_id field when status is complete
    public function test_only_show_order_id_when_status_is_complete(): void {
        Application::factory()->complete()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['order_id']
            ]
        ]);
    }

    public function test_must_be_oldest_application_first(): void
    {
        // seed today and yesterday data
        Application::factory()->create();
        Carbon::setTestNow(Carbon::yesterday());
        $oldestApplication = Application::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL);

        $data = $response->json()['data'];
        $response->assertStatus(200);
        // assert that the yesterday data should be first on the list
        $this->assertEquals($data[0]['id'], $oldestApplication->id);
    }

    public function test_monthly_cost_should_be_in_human_readable_dollar_format(): void
    {
        Application::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->getJson(self::URL);

        $data = $response->json()['data'];

        $response->assertStatus(200);
        // https://regex101.com
        // assert regex should be "$9,612.32" ($, comma, decimals)
        $this->assertMatchesRegularExpression(
            '/^\$[\d,]+\.\d{2}$/',
            $data[0]['plan']['monthly_cost']
        );
    }
}
