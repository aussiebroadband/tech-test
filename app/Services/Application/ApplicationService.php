<?php

namespace App\Services\Application;

use App\Repositories\ApplicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApplicationService
{
    public function __construct(
        private ApplicationRepository $applicationRepository
    ) {}

    public function listApplications(?string $planType): LengthAwarePaginator
    {
        $paginator = $this->applicationRepository->getApplications($planType);

        $paginator->getCollection()->transform(function ($application) {
            return [
                'application_id' => $application->id,
                'customer_full_name' => $application->first_name.' '.$application->last_name,

                'status' => $application->status,
                'address' => $application->address,
                'plan_type' => $application->plan_type,
                'plan_name' => $application->plan_name,
                'state' => $application->state,
                'plan_monthly_cost' => $this->formatDollars($application->plan_monthly_cost),
                'order_id' => $application->state === 'complete'
                    ? $application->order_id
                    : null,
            ];
        });

        return $paginator;
    }

    private function formatDollars(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }
}
