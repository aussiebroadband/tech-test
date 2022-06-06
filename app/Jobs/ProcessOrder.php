<?php

namespace App\Jobs;

use App\Enums\ApplicationQueues;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->data['plan_name'] === 'nbn') {
            try {
                $response = Http::post(env('NBN_B2B_ENDPOINT'), [
                    'address_1' => $this->data['address_1'],
                    'address_2' => $this->data['address_2'],
                    'city' => $this->data['city'],
                    'state' => $this->data['state'],
                    'postcode' => $this->data['postcode'],
                    'plan_name' => $this->data['plan_name'],
                ])->body();

                if (($response["status"] ?? '') === 'complete') {
                    ProcessOrder::dispatch($this->data)->onQueue('complete');
                } else {
                    ProcessOrder::dispatch($this->data)->onQueue('order_failed');
                }

            } catch (\Exception $exception) {
                ProcessOrder::dispatch($this->data)->onQueue('order_failed');
            }
        }
    }
}
