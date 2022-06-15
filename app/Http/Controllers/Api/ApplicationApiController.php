<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Application\ApplicationController;

class ApplicationApiController extends Controller
{
    public function getApplications(Request $request) {
        $apps = new ApplicationController;
        return $apps->getApplications($request); 
    }
}
