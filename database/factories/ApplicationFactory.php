<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'status' => ApplicationStatus::Prelim,
            'customer_id' => Customer::factory(),
            'plan_id' => Plan::factory(),
            'address_1' => $this->faker->sentence(1),
            'address_2' => rand(0, 1) > 0.8 ? $this->faker->sentence(1) : null,
            'city' => $this->faker->sentence(1),
            'state' => $this->faker->randomElement(['NSW', 'VIC', 'QLD', 'TAS', 'SA', 'WA', 'NT', 'ACT']),
            'postcode' => $this->faker->numerify('####'),
            'order_id' => null,
        ];
    }
}
