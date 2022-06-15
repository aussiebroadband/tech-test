<?php

namespace Tests\Unit;

use Tests\TestCase;
// use App\Http\Controllers\Api\ApplicationController;
use App\Helper;


class applicationTest extends TestCase
{

    public function test_to_dollars_is_float() {

        $value = Helper::to_dollars(1234);
        $this->assertIsFloat($value);
    }
}
