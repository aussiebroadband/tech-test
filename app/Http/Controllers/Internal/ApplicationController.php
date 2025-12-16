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
        $query = Application::query()
            ->with(['customer', 'plan'])
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate(30);

        return ApplicationListResource::collection($paginator);

    }
}