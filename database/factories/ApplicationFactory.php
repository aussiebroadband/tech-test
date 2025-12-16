<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Customer;
use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
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

    public function complete()
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Complete,
            'order_id' => $this->faker->randomDigitNotNull()
        ]);
    }

    public function order()
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Order,
        ]);
    }

    public function withAddress2()
    {
        return $this->state(fn (array $attributes) => [
            'address_2' => $this->faker->sentence(1),
        ]);
    }
}
