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

    public function __construct(public int $applicationId) {}

    public function handle(): void
    {
        $application = Application::with('plan')->findOrFail($this->applicationId);

        if ($application->status !== ApplicationStatus::Order || $application->plan->type !== 'nbn') {
            return;
        }

        try {
            $response = Http::post(config('services.nbn.endpoint'), [
                'address_1' => $application->address_1,
                'address_2' => $application->address_2,
                'city' => $application->city,
                'state' => $application->state,
                'postcode' => $application->postcode,
                'plan_name' => $application->plan->name,
            ]);
        } catch (Throwable) {
            $this->markAsFailed($application);

            return;
        }

        if (! $response->successful() || $response->json('status') !== 'Successful' || blank($response->json('id'))) {
            $this->markAsFailed($application);

            return;
        }

        $application->forceFill([
            'order_id' => $response->json('id'),
            'status' => ApplicationStatus::Complete,
        ])->save();
    }

    private function markAsFailed(Application $application): void
    {
        $application->forceFill([
            'status' => ApplicationStatus::OrderFailed,
        ])->save();
    }
}
