<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Enums\ApplicationStatus;

class ProcessApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    private const SUCCESS = 'successful';
    private const FAIL    = 'failed';

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        // Get the data to be sent to B2B endpoint
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $postData = Http::post(NBN_B2B_ENDPOINT, $this->data);
        // $postData = file_get_contents(resource_path('../tests/stubs/nbn-successful-response.json'));
        // $postData = file_get_contents(resource_path('../tests/stubs/nbn-fail-response.json'));
        $response = json_decode($postData, true);
        $update = array();
        $responseId = $response['id'] ?? '';
        $responseStatus = strtolower($response['status']) ?? '';
        // Successful order
        if(!empty($responseId) && $responseStatus == self::SUCCESS) {
            $update['status'] = ApplicationStatus::Complete->value;
            $update['order_id'] = $responseId;
        } else if (empty($responseId) && $responseStatus == self::FAIL) {
            // Failed order
            $update['status'] = ApplicationStatus::OrderFailed->value;
        }
        $updateOrder = app('App\Http\Controllers\ApplicationController')->update($update, $this->data['id']);
        $updateOrder = json_decode($updateOrder, true);

    }
}
