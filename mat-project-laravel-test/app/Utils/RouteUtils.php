<?php

namespace App\Utils {

    use App\Helpers\ResponseHelper;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;

    class RouteUtils
    {
        public static function post(string $uri,callable $action){
            return Route::post($uri,fn(Request $request,...$args)=>ResponseHelper::success(
                $action($request,...$args)
            ));
        }

        public static function get(string $uri,callable $action){
            return Route::get($uri,fn(Request $request,...$args)=>ResponseHelper::success(
                $action($request,...$args)
            ));
        }

        public static function put(string $uri,callable $action){
            return Route::put($uri,fn(Request $request,...$args)=>ResponseHelper::success(
                $action($request,...$args)
            ));
        }

        public static function delete(string $uri,callable $action){
            return Route::delete($uri,fn(Request $request,...$args)=>ResponseHelper::success(
                $action($request,...$args)
            ));
        }

        public static function patch(string $uri,callable $action){
            return Route::patch($uri,fn(Request $request,...$args)=>ResponseHelper::success(
                $action($request,...$args)
            ));
        }
    }
}