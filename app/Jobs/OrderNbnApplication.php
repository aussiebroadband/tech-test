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
use Throwable;

class OrderNbnApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $applicationId)
    {
    }

    public function handle(): void
    {
        $application = Application::query()->with('plan')->find($this->applicationId);

        if ($application === null) {
            return;
        }

        if ($application->status !== ApplicationStatus::Order) {
            return;
        }

        if ($application->plan?->type !== 'nbn') {
            return;
        }

        $endpoint = config('services.nbn.endpoint');

        if (! is_string($endpoint) || $endpoint === '') {
            $this->markOrderFailed($application);
            return;
        }

        $planName = $application->plan?->name;

        if (! is_string($planName) || $planName === '') {
            $this->markOrderFailed($application);
            return;
        }

        $payload = [
            'address_1' => $application->address_1,
            'address_2' => $application->address_2,
            'city' => $application->city,
            'state' => $application->state,
            'postcode' => $application->postcode,
            'plan_name' => $planName,
        ];

        try {
            $response = Http::post($endpoint, $payload);

            $responseStatus = $response->json('status');
            $orderId = $response->json('id');

            if ($response->successful() && $responseStatus === 'Successful' && is_string($orderId) && $orderId !== '') {
                $application->order_id = $orderId;
                $application->status = ApplicationStatus::Complete;
                $application->save();

                return;
            }
        } catch (Throwable) {
            // handled below
        }

        $this->markOrderFailed($application);
    }

    private function markOrderFailed(Application $application): void
    {
        $application->order_id = null;
        $application->status = ApplicationStatus::OrderFailed;
        $application->save();
    }
}
