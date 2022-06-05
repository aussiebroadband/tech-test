<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class ListController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $collection = QueryBuilder::for(Application::class)
            ->select(['id', 'customer_id', 'plan_id', 'address_1', 'address_2', 'state', 'applications.order_id'])
            ->allowedIncludes('plan')
            ->allowedFilters([
                AllowedFilter::exact('plan_type', 'plan.type')
            ])
            ->whereNotNull('applications.order_id')
            ->with([
                'customer' => function ($query) {
                    $query->select(['id', 'first_name', 'last_name']);
                },
                'plan' => function ($query) {
                    $query->select(['id', 'type', 'name', 'monthly_cost']);
                }
            ])
            ->orderBy('id', 'desc');

        return ApplicationResource::collection(custom_paginate($collection));
    }
}
