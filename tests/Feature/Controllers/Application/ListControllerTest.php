<?php

namespace Tests\Feature\Controllers\Application;

use App\Models\Application;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    public function test_it_can_throw_unauthorize_if_email_is_wrong()
    {
//        Application::factory(10)->create();

        $createUserToken = $this->call('POST', $this->baseUri . '/token/create', ['email' => '' /* email here */]);

        $user = $createUserToken->getContent();

        $response = $this->call('POST', $this->baseUri . '/application',
            [/* params */],
            [/* cookies */],
            [/* files */],
            ['HTTP_Authorization' => 'Bearer ' . null]);


        $this->assertEquals(404, $createUserToken->getStatusCode());
    }
}
