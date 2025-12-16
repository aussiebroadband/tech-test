<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAppicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;

class ListApplicationController extends Controller
{
    public function __invoke(ListAppicationRequest $request)
    {
        $query = Application::query()
            ->with(['customer', 'plan'])
            ->orderBy('created_at')
            ->filterByPlanType($request->validated('plan_type'));

        return ApplicationResource::collection($query->paginate());
    }
}
