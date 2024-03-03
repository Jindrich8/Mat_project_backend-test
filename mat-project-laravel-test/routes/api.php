<?php

use App\Dtos;
use App\Helpers\RequestHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskCreateInfoController;
use App\Http\Controllers\TaskReviewController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\Authorize as MiddlewareAuthorize;
use App\TableSpecificData\UserRole;
use App\Utils\RouteUtils;
use Illuminate\Auth\Middleware\Authorize;

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

#region Teacher
Route::middleware(['auth:sanctum', MiddlewareAuthorize::class . ':' . UserRole::TEACHER->value])->group(function () {
    
    RouteUtils::post(
        '/task/create',
        fn (Request $request) =>
        TaskController::construct()
            ->store($request)
    );

    RouteUtils::put(
        '/task/{id}/update',
        fn (Request $request, string $id) =>
        TaskController::construct()
            ->update($request, RequestHelper::translateId($id))
    );

    RouteUtils::delete(
        '/task/{id}/delete',
        fn (Request $request, string $id) =>
        TaskController::construct()
            ->delete($request, RequestHelper::translateId($id))
    );

    RouteUtils::get(
        '/task/create_info',
        fn (Request $request) =>
        TaskCreateInfoController::construct()
            ->getCreateInfo($request)
    );

    RouteUtils::get(
        '/my_task/list',
        fn (Request $request) =>
        TaskController::construct()
            ->myList($request)
    );

    RouteUtils::get(
        '/my_task/{id}/detail',
        fn (Request $request, string $id) =>
        TaskController::construct()
            ->myDetail($request, RequestHelper::translateId($id))
    );
});
#endregion Teacher

#region User
Route::middleware('auth:sanctum')->group(function () {
    RouteUtils::get(
        '/user/get_profile',
        fn (Request $request) =>
        UserController::construct()
            ->getProfile($request)
    );

    RouteUtils::get(
        '/review/list',
        fn (Request $request) =>
        TaskReviewController::construct()
            ->list($request)
    );

    RouteUtils::get(
        '/review/{id}/detail',
        fn (Request $request, string $id) =>
        TaskReviewController::construct()
            ->detail($request, RequestHelper::translateId($id))
    );

    RouteUtils::get(
        '/review/{id}/get',
        fn (Request $request, string $id) =>
        TaskReviewController::construct()
            ->get($request, RequestHelper::translateId($id))
    );

    RouteUtils::get(
        '/review/{id}/delete',
        fn (Request $request, string $id) =>
        TaskReviewController::construct()
            ->delete($request, RequestHelper::translateId($id))
    );
});
#endregion User

RouteUtils::get(
    '/task/{id}/detail',
    fn (Request $request, string $id) =>
    TaskController::construct()
        ->detail($request, RequestHelper::translateId($id))
);

RouteUtils::get(
    '/task/{id}/take',
    fn (Request $request, string $id) =>
    TaskController::construct()
        ->take($request, RequestHelper::translateId($id))
);



RouteUtils::get(
    '/task/list',
    fn (Request $request) =>
    TaskController::construct()
        ->list($request)
);

RouteUtils::post(
    '/task/{id}/evaluate',
    fn (Request $request, string $id) =>
    TaskController::construct()
        ->evaluate($request, RequestHelper::translateId($id))
);
