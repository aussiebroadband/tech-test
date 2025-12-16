<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(rand(1, 3)),
            'type' => $this->faker->randomElement(['nbn', 'opticomm', 'mobile']),
            'monthly_cost' => $this->faker->numerify('####'),
        ];
    }

    public function nbn()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'nbn',
            'name' => 'NBN Standard',
        ]);
    }

    public function opticomm()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'opticomm',
            'name' => 'Opticomm Fibre',
        ]);
    }

    public function mobile()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'mobile',
            'name' => 'Mobile Unlimited',
        ]);
    }
}
