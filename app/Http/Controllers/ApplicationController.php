<?php

namespace App\Http\Controllers;
use App\Services\Application\ApplicationService;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
     public function __construct(
        private ApplicationService $applicationService
    ) {}

    public function index(Request $request)
    {
        $applications = $this->applicationService->listApplications(
            planType: $request->query('plan_type')
        );

        return response()->json($applications);
    }

}
