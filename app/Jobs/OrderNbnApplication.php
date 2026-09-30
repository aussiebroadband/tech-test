<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderNbnApplication implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Placing an order is not idempotent, so a failed attempt is never retried
     * automatically: it moves to "order failed" for a person to review.
     */
    public int $tries = 1;

    /**
     * Release the uniqueness lock after an hour even if a worker dies mid-job.
     */
    public int $uniqueFor = 3600;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Application $application) {}

    /**
     * Only one order job per application may be queued at a time, so a backlog
     * across scheduler runs can never order the same application twice.
     */
    public function uniqueId(): string
    {
        return (string) $this->application->id;
    }

    public function handle(): void
    {
        // The model is re-read from the database when the job runs; skip it if
        // it has left the "order" status since it was queued.
        if ($this->application->status !== ApplicationStatus::Order) {
            return;
        }

        $response = Http::acceptJson()
            ->timeout(30)
            ->post(config('services.nbn.endpoint'), [
                'address_1' => $this->application->address_1,
                'address_2' => $this->application->address_2,
                'city' => $this->application->city,
                'state' => $this->application->state,
                'postcode' => $this->application->postcode,
                'plan_name' => $this->application->plan->name,
            ]);

        $orderId = $response->json('id');

        if ($response->successful() && $response->json('status') === 'Successful' && filled($orderId)) {
            $this->application->markAsOrdered($orderId);

            return;
        }

        Log::warning('nbn B2B order failed.', [
            'application_id' => $this->application->id,
            'http_status' => $response->status(),
            'order_status' => $response->json('status'),
        ]);

        $this->application->markAsOrderFailed();
    }

    /**
     * Any error while ordering (timeout, connection failure, ...) fails the order.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('nbn B2B order errored.', [
            'application_id' => $this->application->id,
            'exception' => $exception?->getMessage(),
        ]);

        $this->application->markAsOrderFailed();
    }
}
