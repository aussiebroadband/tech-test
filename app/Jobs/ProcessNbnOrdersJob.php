<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessNbnOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Application $application)
    {
        //
    }

    public function handle(): void
    {
        try {
            $response = Http::post(config('services.nbn.b2b_endpoint'), [
                'address_1' => $this->application->address_1,
                'address_2' => $this->application->address_2,
                'city' => $this->application->city,
                'state' => $this->application->state,
                'postcode' => $this->application->postcode,
                'plan name' => $this->application->plan->name,
            ]);

            if ($response->successful() && $response->json('status') === 'Successful') {
                $this->application->update([
                    'status' => ApplicationStatus::Complete,
                    'order_id' => $response->json('id'),
                ]);
            } else {
                $this->application->update([
                    'status' => ApplicationStatus::OrderFailed,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('NBN order processing failed', [
                'application_id' => $this->application->id,
                'error' => $e->getMessage()
            ]);

            $this->application->update([
                'status' => ApplicationStatus::OrderFailed,
            ]);
        }
    }
}
