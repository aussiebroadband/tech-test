<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'plan_type' => ['nullable', Rule::in(['nbn', 'opticomm', 'mobile'])],
        ]);

        $query = Application::with('plan', 'customer')
            ->orderBy('created_at', 'ASC');

        if($planType = $request->get('plan_type')){
            $query = $query->whereHas('plan', function(Builder $query) use ($planType){
                $query->where('type', $planType);
            });
        }

        return ApplicationResource::collection($query->paginate());
    }
}
