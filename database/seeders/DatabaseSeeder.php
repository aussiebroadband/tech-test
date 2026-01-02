<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Customer;
use App\Models\Application;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory(10)->create();
        
        $plans = Plan::factory(5)->create();
        $customers = Customer::factory(20)->create();
        
        Application::factory(30)->create([
            'customer_id' => fn () => $customers->random()->id,
            'plan_id' => fn () => $plans->random()->id,
        ]);
    }
}
