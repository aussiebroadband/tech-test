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
use Illuminate\Support\Facades\Log;
use Throwable;


class ProcessNbnOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Application $application) {}

    public function handle(): void
    {
        $endpoint = env('NBN_B2B_ENDPOINT');

        if (!$endpoint) {
            $this->error('Missing NBN_B2B_ENDPOINT (services.nbn.endpoint). No jobs dispatched.');
            return;
        }        
        
        $application = $this->application->load('plan');

        if (!$application) return;

        if ($application->status !== ApplicationStatus::Order) return;
        if (!$application->plan || $application->plan->type !== 'nbn') return;

        try {
            $payload = [
                'address_1' => $application->address_1,
                'address_2' => $application->address_2,
                'city'      => $application->city,
                'state'     => $application->state,
                'postcode'  => $application->postcode,
                'plan_name' => $application->plan->name,
            ];

            $response = Http::post($endpoint, $payload);
            $data = $response->json();

            $isSuccessful = ($data['status'] ?? null) === 'Successful' && !empty($data['id']);

            if ($isSuccessful) {
                $application->order_id = $data['id'];
                $application->status = ApplicationStatus::Complete;
                $application->save();

                Log::info('NBN order succeeded', [
                    'application_id' => $application->id,
                    'order_id' => $application->order_id,
                ]);

                return;
            }

            $application->status = ApplicationStatus::OrderFailed;
            $application->save();

            Log::warning('NBN order failed', [
                'application_id' => $application->id,
                'response_status' => $data['status'] ?? null,
                'response_id' => $data['id'] ?? null,
                'http_status' => $response->status(),
            ]);
        } catch (Throwable $e) {
            $application->status = ApplicationStatus::OrderFailed;
            $application->save();

            Log::error('NBN order exception', [
                'application_id' => $application->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
