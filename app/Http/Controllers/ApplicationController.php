<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        // Optional plan type filter
        $planType = $request->query('plan_type');
	
       $query = Application::with(['customer', 'plan']);


        if ($planType) {
            $query->where('plan_type', $planType);
        }

        // Oldest first and paginate
        $applications = $query->orderBy('created_at', 'asc')->paginate(10);
	dd($applications->ToArray());
        // Transform the data manually
        $data = $applications->map(function ($app) {
    return [
        'id' => $app->id,
        'customer_full_name' => $app->customer->first_name.' '.$app->customer->last_name,
        'address' => trim($app->address_1.' '.$app->address_2),
        'plan_type' => $app->plan->type ?? null,
        'plan_name' => $app->plan->name ?? null,
        'state' => $app->state,
        'plan_monthly_cost' => $app->plan ? number_format($app->plan->monthly_cost / 100, 2) : null,
        'order_id' => $app->status === 'complete' ? $app->order_id : null,
    ];
});


        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }
}
