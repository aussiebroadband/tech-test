<?php

namespace Tests\Feature\Controllers\OrderQueue;

use App\Jobs\ProcessOrder;
use App\Models\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderQueueTest extends TestCase
{
    public function test_it_dispatches_the_applications_to_order_queue()
    {
        Queue::fake();

        Application::factory(1)->create();

        foreach (Application::with(['plan', 'customer'])->get()->toArray() as $app) {
            ProcessOrder::dispatch($app)->onQueue('order');
        }

        Queue::assertPushed(ProcessOrder::class, function ($job) {
            return is_array($job->data);
        });
    }

    public function test_it_consumes_data_from_order_queue_and_do_http_call_with_success_response()
    {
        Queue::fake();

        Application::factory(1)->create();

        $app = Application::with(['plan', 'customer'])->first()->toArray();
        $data = [
            'address_1' => $app['address_1'],
            'address_2' => $app['address_2'],
            'city'      => $app['city'],
            'state'     => $app['state'],
            'postcode'  => $app['postcode'],
            'plan_name' => $app['plan']['type'],
        ];

        ProcessOrder::dispatch($data)->onQueue('order');

        Http::fake([
            env('NBN_B2B_ENDPOINT') => Http::response(
                json_decode(file_get_contents('tests/stubs/nbn-successful-response.json'), true),
                200
            )
        ]);

        $response = Http::post(env('NBN_B2B_ENDPOINT'), $data)->json();

        if ($response['status'] == 'Successful') {
            ProcessOrder::dispatch($data)->onQueue('complete');
        }

        $this->assertEquals('Successful', $response['status']);
    }

    public function test_it_consumes_data_from_order_queue_and_do_http_call_with_failed_response()
    {
        Queue::fake();

        Application::factory(1)->create();

        $app = Application::with(['plan', 'customer'])->first()->toArray();
        $data = [
            'address_1' => $app['address_1'],
            'address_2' => $app['address_2'],
            'city'      => $app['city'],
            'state'     => $app['state'],
            'postcode'  => $app['postcode'],
            'plan_name' => $app['plan']['type'],
        ];

        ProcessOrder::dispatch($data)->onQueue('order');

        Http::fake([
            env('NBN_B2B_ENDPOINT') => Http::response(
                json_decode(file_get_contents('tests/stubs/nbn-fail-response.json'), true),
                200
            )
        ]);

        $response = Http::post(env('NBN_B2B_ENDPOINT'), $data)->json();

        if ($response['status'] == 'Failed') {
            ProcessOrder::dispatch($data)->onQueue('order_failed');
        }

        $this->assertEquals('Failed', $response['status']);

    }
}
