<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use App\Services\HttpClient;
use Illuminate\Support\Facades\Validator;
use App\Enums\ApplicationStatus;
use App\Jobs\ProcessApplication;

class ApplicationController extends Controller
{
    private const STATUS  = 'order';
    private const SUCCESS = 'successful';
    private const FAIL    = 'failed';
    private const SUCCESS_CODE = 200;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all the list of aaplications
        $orders = DB::table('applications')
                ->leftjoin('customers', 'applications.customer_id', '=', 'customers.id')
                ->leftjoin('plans', 'applications.plan_id', '=', 'plans.id')
                ->select('applications.id',DB::raw("customers.first_name || ' ' || customers.last_name as full_name, 
                          applications.address_1 || ' ' || applications.address_2 || ' ' || applications.city || ' ' || applications.state || ' ' || applications.postcode AS 'address',
                          CASE WHEN applications.status = 'complete' THEN applications.order_id ELSE '' END AS order_id"),
                          'plans.type', 'plans.name', 'applications.state', 'plans.monthly_cost')
                ->orderBy('applications.created_at', 'asc')
                ->paginate(20);

        // Convert cents to dollars
        $orders = $orders->map(function ($order) {
            $order->monthly_cost = $order->monthly_cost / 100;
            return $order;
        });
        return response()->json([
            'status' => 'applications_fetched',
            'data' => $orders
        ], self::SUCCESS_CODE);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Filter by plan type
        $orders = DB::table('applications')
                ->leftjoin('customers', 'applications.customer_id', '=', 'customers.id')
                ->leftjoin('plans', 'applications.plan_id', '=', 'plans.id')
                ->select('applications.id',DB::raw("customers.first_name || ' ' || customers.last_name as full_name, 
                         applications.address_1 || ' ' || applications.address_2 || ' ' || applications.city || ' ' || applications.state || ' ' || applications.postcode AS 'address',
                         CASE WHEN applications.status = 'complete' THEN applications.order_id ELSE '' END AS order_id"),
                         'plans.type', 'plans.name', 'applications.state', 'plans.monthly_cost')
                ->where('plans.type', '=', $id)
                ->orderBy('applications.created_at', 'asc')
                ->paginate(20);

        // Convert cents to dollars
        $orders = $orders->map(function ($order) {
            $order->monthly_cost = $order->monthly_cost / 100;
            return $order;
        });
        return response()->json([
            'status' => 'applications_fetched',
            'data' => $orders
        ], self::SUCCESS_CODE);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, int $id)
    {
        // Set Validation rules
        $validate = Validator::make($request, [
            'status'   => 'required'
        ]);
     
        if ($validate->fails())
        {
            return response()->json([
                'status' => 'update_failed',
                'data' => $request
            ], self::SUCCESS_CODE);
        } else {
            // Update only if application exists
            $application = Application::where('id', $id)->firstOrFail();
            $application->status = $request['status'] ?? self::STATUS;
            $application->order_id = $request['order_id'] ?? '';
            $application->update();
        }
    
        return response()->json([
            'status' => 'update_success',
            'data' => $request
        ], self::SUCCESS_CODE);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Process NBN applications
     */
    public function processNbnApplications()
    {
        // Get all NBN applications with status = order
        $filterByStatus = 'order';
        $applicationList = DB::table('applications')
                         ->leftjoin('plans', 'applications.plan_id', '=', 'plans.id')
                         ->select('applications.id','applications.address_1', 'applications.address_2', 'applications.city', 'applications.state', 'applications.postcode', 'plans.name')
                         ->where('applications.status', '=', self::STATUS)
                         ->get();
                    
        // Send post request to the B2B endpoint
        $orders = $applicationList->map(function ($order) {
            $sendData = array();
            $sendData['address_1'] = $order->address_1;
            $sendData['address_2'] = $order->address_2;
            $sendData['city']      = $order->city;
            $sendData['state']     = $order->state;
            $sendData['postcode']  = $order->postcode;
            $sendData['plan_name'] = $order->name;
            $sendData['id']        = $order->id;
            $queueJobs = ProcessApplication::dispatch($sendData);

        });

        return response()->json([
            'status' => 'applications_processed',
            'data' => $applicationList
        ], self::SUCCESS_CODE);
    }
}
