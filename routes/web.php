<?php

use App\Models\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiPlansController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test', function () {
    try {
        $apps = Application::factory()
        ->count(4)
        ->make();
    }
    catch(\Exception $e) {
        echo var_dump($e);
    }

    foreach ($apps as $app) {
        echo var_dump($app);
    }
});

Route::get('/plans/{plan?}', [ApiPlansController::class, 'index']);
