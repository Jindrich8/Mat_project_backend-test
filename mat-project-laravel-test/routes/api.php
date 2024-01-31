<?php

use App\Dtos;
use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TakeAndSaveController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCreateInfoController;
use App\Utils\RouteUtils;

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

// RouteUtils::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


#region Teacher

RouteUtils::post('/task/create',fn(Request $request) =>
    TaskController::construct()
    ->store($request)
);

RouteUtils::put('/task/{id}/update',fn(Request $request,string $id) =>
    TaskController::construct()
    ->update($request,RequestHelper::translateId($id))
);

RouteUtils::delete('/task/{id}/delete',fn(Request $request,string $id)=>
    TaskController::construct()
    ->delete($request,RequestHelper::translateId($id))
);

RouteUtils::get('/task/create_info',fn(Request $request) =>
    TaskCreateInfoController::construct()
    ->getCreateInfo($request)
);

RouteUtils::get('/my_task/list',fn(Request $request) =>
    TaskController::construct()
    ->myList($request)
);


#endregion Teacher

#region User

RouteUtils::get('/review/list',fn(Request $request) =>
    ReviewController::construct()
    ->list($request)
);

RouteUtils::get('/review/{id}/detail',fn(Request $request,string $id) =>
    data:ReviewController::construct()
    ->detail($request,RequestHelper::translateId($id))
);

RouteUtils::get('/review/{id}/delete',fn(Request $request,string $id) =>
    ReviewController::construct()
    ->delete($request,RequestHelper::translateId($id))
);

#endregion User



RouteUtils::get('/task/{id}/detail',fn(Request $request,string $id) =>
    TaskController::construct()
    ->detail($request,RequestHelper::translateId($id))
);

RouteUtils::get('/task/{id}/take',fn(Request $request,string $id) =>
    TaskController::construct()
    ->take($request,RequestHelper::translateId($id))
);

RouteUtils::post('/task/{id}/evaluate',fn(Request $request,string $id)=>
    TaskController::construct()
    ->evaluate($request,RequestHelper::translateId($id))
);

RouteUtils::get('/task/list',fn(Request $request) =>
    TaskController::construct()
    ->list($request)
);
