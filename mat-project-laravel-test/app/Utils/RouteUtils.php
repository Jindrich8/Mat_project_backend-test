<?php

namespace App\Utils {

    use App\Helpers\ResponseHelper;
    use Illuminate\Support\Facades\Route;

    class RouteUtils
    {
        public static function post(string $uri,callable $action){
            return Route::post($uri,fn(...$args)=>ResponseHelper::success(
                $action(...$args)
            ));
        }

        public static function get(string $uri,callable $action){
            return Route::get($uri,fn(...$args)=>ResponseHelper::success(
                $action(...$args)
            ));
        }

        public static function put(string $uri,callable $action){
            return Route::put($uri,fn(...$args)=>ResponseHelper::success(
                $action(...$args)
            ));
        }

        public static function patch(string $uri,callable $action){
            return Route::patch($uri,fn(...$args)=>ResponseHelper::success(
                $action(...$args)
            ));
        }
    }
}