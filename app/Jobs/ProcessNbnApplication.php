<?php

namespace App\Jobs;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessNbnApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function handle()
    {
        try {
            $response = Http::post(env('NBN_B2B_ENDPOINT'), [
                'address_1' => $this->application->address_1 ?? $this->application->address,
                'address_2' => $this->application->address_2 ?? '',
                'city' => $this->application->city ?? '',
                'state' => $this->application->state,
                'postcode' => $this->application->postcode ?? '',
                'plan_name' => $this->application->plan_name,
            ]);

            // Mock response handling for tests
            if ($response->successful() && isset($response['id']) && $response['id']!='' ) {
                $this->application->update([
                    'status' => 'complete',
                    'order_id' => $response['id'],
                ]);
            } else {
                $this->application->update(['status' => 'order failed']);
            }
        } catch (\Exception $e) {
            Log::error("NBN order failed for application {$this->application->id}: {$e->getMessage()}");
            $this->application->update(['status' => 'order failed']);
        }
    }
}
