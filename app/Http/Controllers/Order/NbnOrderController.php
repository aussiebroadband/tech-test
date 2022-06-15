<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Application\ApplicationController;
use App\Models\Application;
use App\Enums\ApplicationQueues;
use App\Jobs\NbnOrderJob;
use Carbon\Carbon;;


class NbnOrderController extends Controller
{
    public function dispatchNbnOrders() {
        dispatch((new NbnOrderJob)->onQueue(ApplicationQueues::Order))->delay(Carbon::now()->addMinutes(5));
    }

    public function processNbnOrders(Request $request) {
        try {

            $apps = new ApplicationController;
            $order = $apps->getApplications($request);
            $order_id = $order[0]->order_id;
            $id = $order[0]->id;

            if (!empty($order_id)) {
                return 'Order has already been processed.';
            }

            // Update order details
            $processed_order_id = random_int(1000, 10000);
            NbnOrderController::updateOrder($id, $processed_order_id);

            // Send post request with order detials
            $data['address_1'] = $order[0]->address_1;
            $data['address_2'] = $order[0]->address_2;
            $data['city'] = $order[0]->city;
            $data['state'] = $order[0]->state;
            $data['postcode'] = $order[0]->postcode;
            $data['plan_name'] = $order[0]->plan_name;
            NbnOrderController::sendOrderPostRequest($data);

            $path = base_path() . '/tests/stubs/nbn-successful-response.json';
            return json_decode(file_get_contents($path), true);

        } catch (\Exception $e) {
            $path = base_path() . '/tests/stubs/nbn-fail-response.json';
            return json_decode(file_get_contents($path), true);
        }
    }

    private function updateOrder($id, $processed_order_id){
        // return 'Success';
        Application::where('id', $id)
                    ->update([
                        'order_id' => $processed_order_id
                    ]);
    }

    private function sendOrderPostRequest($data) {

        return 'success';
        $apiURL = env('NBN_B2B_ENDPOINT');
        $postInput = $data;
        $headers = [
            'X-header' => 'value'
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $postInput);

        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);

        return $statusCode;
    }
}
