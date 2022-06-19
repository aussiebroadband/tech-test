<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Enums\ApplicationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApiPlansController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Consistent naming with the above as I imagine the front end
     * will loop through the applications variable. Even though this 
     * will be only one application.
     * 
     * Added an applications key to the array incase we want to pass
     * additional data with the response.
     * 
     * Added default as false instead of null because its easier to check
     * if its empty. null === a value so if I checked for a plan
     * it would return true even when set to null.
     * 
     * @param String|boolean $planType
     * @return array
     */
    public function index($planType = false) {
        // $applicationsSQL = DB::raw(
        //     'SELECT
        //         applications.id,
        //         customer.first_name,
        //         customer.last_name,
        //         plan.type,
        //         plan.name,
        //         applications.state,
        //         plan.monthly_cost,
        //         applications.order_id
        //     FROM applications
        //     INNER JOIN plans plan ON plan_id = plan.id 
        //     INNER JOIN customers customer ON customer_id = customer.id
        //     WHERE applications.status = "'. ApplicationStatus::Complete->value .'"
        // ');

        // $plans = DB::select($applicationsSQL);

        $applicationFields = [
            'applications.id as application_id',
            DB::raw('customer.first_name || " " || customer.last_name as full_name'),
            DB::raw('applications.address_1 || " " || applications.address_2 || " " || applications.city || " " || applications.postcode as address'),
            'plan.type as plan_type',
            'plan.name as plan_name',
            'applications.state as state',
            'applications.status as status',
            DB::raw('plan.monthly_cost as monthly_cost'),
            DB::raw('(SELECT order_id FROM applications WHERE applications.status = "'.ApplicationStatus::Complete->value.'") as test')
        ];

        if (!$planType) {
            $applications = Application::select($applicationFields)
                ->join('customers as customer', 'customer.id', '=', 'customer_id')
                ->join('plans as plan', 'plan.id', '=', 'plan_id')
                ->get();

            $data = [
                'Applications' => $applications
            ];
    
            return response()->json($data);
        }

        $applications = Application::select($applicationFields)
            ->join('customers as customer', 'customer.id', '=', 'customer_id')
            ->join('plans as plan', 'plan.id', '=', 'plan_id')
            ->where('plan.type', $planType)
            ->first();

        $data = [
            'Applications' => $applications
        ];

        return response()->json($data);
    }
}
