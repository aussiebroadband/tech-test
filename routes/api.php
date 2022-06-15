<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ApplicationApiController;
use App\Http\Controllers\Application\ApplicationController;
use App\Http\Controllers\Order\NbnOrderController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/applications', [ApplicationApiController::class, 'getApplications'])->middleware('auth:sanctum');

// Route::get('/process-nbn-orders', [NbnOrderController::class, 'dispatchNbnOrders']);
Route::get('/process-nbn-orders', [NbnOrderController::class, 'processNbnOrders']);
