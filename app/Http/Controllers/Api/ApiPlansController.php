<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ApiPlansController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Added an applications key to the data array incase we want to pass
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
        $whereCondition = '';

        if ($planType && $planType !== null) {
            $whereCondition = 'WHERE plan.type = "'.$planType.'"';
        }

        $applicationsSQL = DB::raw(
            'SELECT
                applications.id,
                customer.first_name || " " || customer.last_name as full_name,
                applications.address_1 || " " || applications.address_2 || " " || applications.city || " " || applications.postcode as "address",
                plan.type,
                plan.name,
                applications.state,
                plan.monthly_cost,
                (SELECT order_id FROM applications WHERE applications.status = "'. ApplicationStatus::Complete->value .'") as order_ids
            FROM applications
            INNER JOIN plans plan ON plan_id = plan.id 
            INNER JOIN customers customer ON customer_id = customer.id
            '.$whereCondition.'
        ');

        $applications = DB::select($applicationsSQL);

        $paginatedApplications = new Paginator($applications, count($applications), 10);

        $data = [
            'Applications' => $paginatedApplications
        ];

        return response()->json($data);
    }
}
