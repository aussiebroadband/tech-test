<?php

namespace Tests\Feature\Controllers\Application;

use App\Models\Application;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    public function test_it_can_list_all_records()
    {
        Application::factory(10)->create();

        $createUserToken = $this->call('POST', $this->baseUri . '/token/create', ['email' => '' /* email here */]);
        $user = $this->json($createUserToken);

        $response = $this->call('POST', $this->baseUri . '/application',
            [/* params */],
            [/* cookies */],
            [/* files */],
            ['HTTP_Authorization' => 'Bearer ' . $user->token]);


        $response->assertSuccessful();
        $response->assertJsonCount(10, 'data');
    }
}
