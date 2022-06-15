<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Helper;

class ApplicationController extends Controller
{

    public function getApplications(Request $request) {

        // Select fields
        $fields = [
            'applications.id',
            'applications.order_id',
            'customers.first_name',
            'customers.last_name',
            'applications.address_1',
            'applications.address_2',
            'applications.address_2',
            'applications.postcode',
            'applications.city',
            'applications.state',
            'plans.type',
            'plans.name',
            'plans.monthly_cost',
        ];

        // Run query
        $query = Application::join('plans', 'applications.plan_id', '=', 'plans.id')
                            ->join('customers', 'applications.customer_id', '=', 'customers.id')
                            ->select($fields)
                            ->orderBy('applications.created_at', 'desc');

        // Set plan type and request typw
        $plan_type = $request->get('plan_type');
        $request_type = $request->get('request_type');

        // Select only requested plan type
        if ($plan_type) {
            $query->where('plans.type', $plan_type);
        }

        // If request type is process, do not paginate results
        if (isset($request_type) && $request_type === 'process') {
            $result = $query->get();
        } else {
            $result = $query->paginate(3)
            ->appends(request()->query());
        }

        // Convert monthly cost to dollars
        foreach ($result as $key => $value) {
            $monthly_cost = $value->monthly_cost;
            $result[$key]->monthly_cost = '$' . Helper::to_dollars($monthly_cost);
        }

        // Return results
        return $result;
    }
}
