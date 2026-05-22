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
            'plan_type' => ['nullable', 'in:nbn,opticomm,mobile'],
        ]);

        $planType = $request->input('plan_type');

        $applications = Application::query()
            ->with(['customer', 'plan'])
            ->when($planType, function ($query) use ($planType) {
                $query->whereHas('plan', function ($query) use ($planType) {
                    $query->where('type', $planType);
                });
            })
            ->oldest()
            ->paginate();

        return ApplicationResource::collection($applications);
    }
}
