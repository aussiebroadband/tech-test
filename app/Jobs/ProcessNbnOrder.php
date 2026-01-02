<?php

namespace App\Jobs;

use App\Models\Application;
use App\Enums\ApplicationStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessNbnOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function handle(): void
    {
        try {
            $response = Http::post(config('services.nbn.endpoint'), [
                'address_1' => $this->application->address_1,
                'address_2' => $this->application->address_2,
                'city' => $this->application->city,
                'state' => $this->application->state,
                'postcode' => $this->application->postcode,
                'plan_name' => $this->application->plan->name,
            ]);

            $data = $response->json();

            if ($response->successful() && ($data['status'] ?? null) === 'Successful') {
                $this->application->update([
                    'order_id' => $data['id'],
                    'status' => ApplicationStatus::Complete,
                ]);
            } else {
                $this->application->update([
                    'status' => ApplicationStatus::OrderFailed,
                ]);
            }
        } catch (\Exception $e) {
            $this->application->update([
                'status' => ApplicationStatus::OrderFailed,
            ]);
        }
    }
}
