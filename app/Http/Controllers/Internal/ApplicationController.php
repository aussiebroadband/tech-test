<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationListRequest;
use App\Http\Resources\ApplicationListResource;
use App\Models\Application;

class ApplicationController extends Controller
{
    public function index(ApplicationListRequest $request)
    {
        $planType = $request->validated('plan_type');
        
        $query = Application::query()
            ->with(['customer', 'plan'])
            ->orderBy('created_at', 'asc')
            ->when($planType, function($applicationQuery) use ($planType) {
                $applicationQuery->whereHas('plan', function($planQuery) use ($planType) {
                    $planQuery->where('type', $planType);
                });
            });

        $paginator = $query->paginate(config('app.default_page_size'));

        return ApplicationListResource::collection($paginator);

    }
}