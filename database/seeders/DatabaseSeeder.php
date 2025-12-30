<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
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
         Plan::factory(10)->create();
         Customer::factory(10)->create();
         Application::factory(10)->create();
    }
}
