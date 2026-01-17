<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'plan_type' => ['nullable', 'string', 'in:nbn,opticomm,mobile']
        ]);

        $applications = Application::withRelations()
            ->forPlanType($request->plan_type)
            ->oldest()
            ->paginate(15);

        return ApplicationResource::collection($applications);
    }
}