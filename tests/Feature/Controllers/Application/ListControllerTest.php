<?php

namespace Tests\Feature\Controllers\Application;

use App\Models\Application;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    public function test_it_can_insert_applications()
    {
        Application::factory(10)->create();

        $this->assertEquals(10, Application::all()->count());
    }

    public function test_it_can_throw_unauthorize_if_token_is_not_passed()
    {
        $response = $this->call('GET', $this->baseUri . '/application');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_it_can_list_applications_with_order_id()
    {
        $this->withoutMiddleware();

        Application::factory(10)->create(['order_id' => 1]);

        $response = $this->call('GET', $this->baseUri . '/application');

        $this->assertCount(10, $response->json()["data"]);
    }

    public function test_it_can_paginate()
    {
        $this->withoutMiddleware();

        Application::factory(10)->create(['order_id' => 1]);

        $response = $this->call('GET', $this->baseUri . '/application');


        $this->assertEquals(true, array_key_exists('data', $response->json())
            && array_key_exists('meta', $response->json())
            && array_key_exists('links', $response->json())
        );
    }
}
