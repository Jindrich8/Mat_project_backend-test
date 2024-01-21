<?php

use App\Dtos;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TakeAndSaveController;
use App\Http\Controllers\TaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/task/create',fn(Request $request) =>
ResponseHelper::success(
    data:TaskController::construct()
    ->store($request)
));

Route::get('/task/{id}/take',fn(Request $request,string $id)=>
ResponseHelper::success(
    data:TaskController::construct()
    ->take($request,$id)
));

Route::get('/tags/all',fn(Request $request)=>
ResponseHelper::success(
    data:TagController::construct()
    ->getAll($request)
));


