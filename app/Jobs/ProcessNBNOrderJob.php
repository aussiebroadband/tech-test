<?php

namespace App\Jobs;

use App\Models\Application;
use App\Enums\ApplicationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessNBNOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Application $application)
    {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Prepare the request payload with application and plan details
            $payload = [
                'address_1' => $this->application->address_1,
                'address_2' => $this->application->address_2,
                'city' => $this->application->city,
                'state' => $this->application->state,
                'postcode' => $this->application->postcode,
                'plan_name' => $this->application->plan->name,
            ];

            // Send the HTTP POST request to the B2B endpoint
            $response = Http::post(config('services.nbn.b2b_endpoint'), $payload);

            // Mark as failed if response is unsuccessful or order failed
            if (!$response->successful() || !$this->isSuccessfulOrder($response->json())) {
                $this->application->update(['status' => ApplicationStatus::OrderFailed]);
                return;
            }

            // Store the Order Id and update status to complete
            $data = $response->json();
            $this->application->update([
                'order_id' => $data['id'],
                'status' => ApplicationStatus::Complete,
            ]);
        } catch (\Exception $e) {
            // Handle any exceptions and mark as failed
            $this->application->update([
                'status' => ApplicationStatus::OrderFailed,
            ]);

            throw $e;
        }
    }

    /**
     * Check if the B2B response indicates a successful order.
     *
     * @param  array  $data
     * @return bool
     */
    private function isSuccessfulOrder(array $data): bool
    {
        return isset($data['status']) 
            && $data['status'] === 'Successful' 
            && isset($data['id']) 
            && !empty($data['id']);
    }
}
