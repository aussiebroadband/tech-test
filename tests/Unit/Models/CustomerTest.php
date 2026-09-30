<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    public function test_the_full_name_joins_first_and_last_name(): void
    {
        $customer = (new Customer)->forceFill(['first_name' => 'Jane', 'last_name' => 'Citizen']);

        $this->assertSame('Jane Citizen', $customer->full_name);
    }

    public function test_the_full_name_is_the_first_name_when_there_is_no_last_name(): void
    {
        $customer = (new Customer)->forceFill(['first_name' => 'Cher', 'last_name' => null]);

        $this->assertSame('Cher', $customer->full_name);
    }
}
