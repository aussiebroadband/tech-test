<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Application;
use App\Models\Plan;
use App\Enums\ApplicationStatus;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerValues = Customer::pluck('id')->toArray();
        $planValues = Plan::pluck('id')->toArray();

        foreach ($customerValues as $value1) {
            foreach ($planValues as $value2) {

                Application::factory()->create([
                    'customer_id' => $value1,
                    'plan_id'     => $value2,
                    'status'      => ApplicationStatus::Order
                ]);
            }
        }
    }
}
