<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
      /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plan_types =  [
            [
              'name' => 'None',
              'type' => 'None',
              'monthly_cost' => 0
            ],
            [
              'name' => 'Nbn',
              'type' => 'Nbn',
              'monthly_cost' => 18052.47
            ],
            [
               'name' => 'Opticomm',
               'type' => 'Opticomm',
               'monthly_cost' => 28883.96
            ],
            [
                'name' => 'Mobile',
                'type' => 'Mobile',
                'monthly_cost' => 36104.95
            ]
          ];
          foreach ($plan_types as $name) {
            if (!Plan::where('name', '=', $name['name'])->exists()) {
                Plan::create($name);
             }          
          }          
    }
}
