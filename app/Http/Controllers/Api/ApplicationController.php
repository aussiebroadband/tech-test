<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListApplicationsRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    /**
     * List applications oldest first, optionally filtered by plan type.
     */
    public function index(ListApplicationsRequest $request): AnonymousResourceCollection
    {
        $applications = Application::query()
            ->with(['customer', 'plan'])
            ->when($request->planType(), fn ($query, $type) => $query->ofPlanType($type))
            ->oldest()
            ->orderBy('id')
            ->paginate()
            ->withQueryString();

        return ApplicationResource::collection($applications);
    }
}
