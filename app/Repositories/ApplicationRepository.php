<?php

namespace App\Repositories;

use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApplicationRepository
{
    public function getApplications(?string $planType): LengthAwarePaginator
    {
        $query = Application::query()
            ->select([
                'applications.id',
                'applications.state',
                'applications.created_at',
                'applications.order_id',
                'customers.first_name',
                'customers.last_name',
                'applications.address_1',
                'plans.type as plan_type',
                'plans.name as plan_name',
                'plans.monthly_cost as plan_monthly_cost',
            ])
            ->join('customers', 'customers.id', '=', 'applications.customer_id')
            ->join('plans', 'plans.id', '=', 'applications.plan_id')
            ->orderBy('applications.created_at', 'asc');

        if ($planType) {
            $query->where('plans.type', $planType);
        }

        return $query->paginate(15);
    }
}
