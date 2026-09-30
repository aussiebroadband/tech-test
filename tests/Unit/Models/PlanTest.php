<?php

namespace Tests\Unit\Models;

use App\Models\Plan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlanTest extends TestCase
{
    #[DataProvider('monthlyCosts')]
    public function test_the_monthly_cost_in_cents_is_formatted_as_dollars(int $cents, string $expected): void
    {
        $plan = (new Plan)->forceFill(['monthly_cost' => $cents]);

        $this->assertSame($expected, $plan->formatted_monthly_cost);
    }

    public static function monthlyCosts(): array
    {
        return [
            'whole dollars' => [5000, '$50.00'],
            'dollars and cents' => [8999, '$89.99'],
            'single cent' => [1, '$0.01'],
            'free' => [0, '$0.00'],
            'thousands separator' => [123456, '$1,234.56'],
        ];
    }
}
