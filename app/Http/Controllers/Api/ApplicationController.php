<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Http\Requests\ListApplicationsRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    public function index(ListApplicationsRequest $request): AnonymousResourceCollection
    {
        $applications = Application::query()
            ->with(['customer', 'plan'])
            ->when($request->plan_type, function ($query, $planType) {
                $query->whereHas('plan', fn ($q) => $q->where('type', $planType));
            })
            ->orderBy('created_at', 'asc')
            ->paginate();

        return ApplicationResource::collection($applications);
    }
}
