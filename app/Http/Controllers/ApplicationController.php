<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
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
        return $orders;
    // Now $orders contains the results with the "product_price_in_dollars" attribute.

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
        return $orders;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
