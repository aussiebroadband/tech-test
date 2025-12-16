<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationIndexRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    public function index(ApplicationIndexRequest $request): AnonymousResourceCollection
    {
        $query = Application::query()
            ->with(['customer', 'plan'])
            ->orderBy('created_at');

        $planType = $request->validated('plan_type');

        if ($planType !== null) {
            $query->whereHas('plan', function ($planQuery) use ($planType) {
                $planQuery->where('type', $planType);
            });
        }

        return ApplicationResource::collection($query->paginate());
    }
}
