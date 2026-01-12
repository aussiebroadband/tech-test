<?php

namespace App\Jobs;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNbnApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Application $application;

    /**
     * Create a new job instance.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $plan = $this->application->plan;
            $customer = $this->application->customer;

            // Simulate HTTP request
            $endpoint = env('NBN_B2B_ENDPOINT');

            // You can switch between success/fail stub for testing
            $response = Http::post($endpoint, [
                'address_1' => $customer->address_1,
                'address_2' => $customer->address_2,
                'city'      => $customer->city,
                'state'     => $customer->state,
                'postcode'  => $customer->postcode,
                'plan_name' => $plan->name,
            ]);

            // For testing: load stub
            // $response = Http::fake([
            //     '*' => Http::response(json_decode(file_get_contents(base_path('tests/stubs/nbn-successful-response.json')), true), 200)
            // ]);

            if ($response->successful()) {
                // Assume response contains order_id
                $orderId = $response['order_id'] ?? rand(1000, 9999);

                $this->application->update([
                    'order_id' => $orderId,
                    'state'    => 'complete',
                ]);
            } else {
                $this->application->update([
                    'state' => 'order_failed',
                ]);
            }
        } catch (\Exception $e) {
            $this->application->update([
                'state' => 'order_failed',
            ]);
        }
    }
}
