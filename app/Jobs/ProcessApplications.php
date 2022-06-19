<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use App\Models\Application;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Queueable;
use App\Enums\ApplicationStatus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessApplications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private static $applications = [];

    private static $b2buri = false;

    private static $client = false;

    private static $planType = '';
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($planType = 'nbn')
    {
        $this->applications = Application::where(
            'status',
            ApplicationStatus::Order->value
        )->whereHas('plan', function($query) {
            $query->where('type', 'nbn');
        })->get();

        $this->client = new Client();

        $this->uri = new Uri(env('NBN_B2B_ENDPOINT'));

        $this->planType = $planType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->applications as $application) {
            $params =  [
                'address_1' => $application->address_1,
                'address_2' => $application->address_2,
                'city' => $application->city,
                'state' => $application->state,
                'postcode' => $application->postcode,
                'plan_name' => $application->plan && $application->plan->name ? $application->plan->name : ''
            ];

            $request = new Request(
                'POST',
                $this->uri,
                [],
                $params
            );

            $response = $this->client->send($request);

            if ($response && $response->getStatusCode() === 200) {
                $result = $request->getBody();

                $application->order_id = $result->id;
                $application->status = ApplicationStatus::Complete->value;
                $application->save();
            }

            if ($response && $response->getStatusCode() >= 400) {
                $application->status = ApplicationStatus::OrderFailed->value;
                $application->save();
            }
        }

        echo 'Completed processing NBN Applications';
    }
}
