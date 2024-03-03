<?php

namespace App\Utils {

    use App\Helpers\ResponseHelper;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Route;
    use Throwable;

    class RouteUtils
    {
        private static function handle(Request $request,callable $action, ...$args){
            try{
                return ResponseHelper::success(
                    $action($request, ...$args)
                );
            }
            catch(Throwable $e){
                return ExceptionUtils::renderException($e,$request);
            }
        }

        public static function isLogin(\Illuminate\Routing\Route $route){
            return $route->getName() === 'login' || $route->getActionName() === 'login'
            || $route->uri() === $route->getPrefix().'/login';
        }

        public static function post(string $uri, callable $action)
        {
            return Route::post($uri, fn(Request $request, ...$args)=>self::handle(
                $request,$action,...$args
            ));
        }

        public static function get(string $uri,callable $action){
            return Route::get($uri,fn(Request $request,...$args)=>self::handle(
                $request,$action,...$args
            ));
        }

        public static function put(string $uri,callable $action){
            return Route::put($uri,fn(Request $request,...$args)=>self::handle(
                $request,$action,...$args
            ));
        }

        public static function delete(string $uri,callable $action){
            return Route::delete($uri,fn(Request $request,...$args)=>self::handle(
                $request,$action,...$args
            ));
        }

        public static function patch(string $uri,callable $action){
            return Route::patch($uri,fn(Request $request,...$args)=>self::handle(
                $request,$action,...$args
            ));
        }
    }
}