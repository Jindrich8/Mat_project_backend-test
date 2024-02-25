<?php

namespace App\Utils {

    use Illuminate\Http\JsonResponse;
    use Illuminate\Support\Facades\Route;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class EndpointUtils
    {
        private static mixed $allowedOrigins = null;

        public static function getAllowOriginsHeader():array{
            return [
                'Access-Control-Allow-Origin' =>
                (self::$allowedOrigins ??= config('cors.allowed_origins'))
            ];
        }

        /**
         * @param int $status
         * @param ClassStructure[] $errors
         * @return JsonResponse
         * @throws InvalidValue
         */
        public static function stdErrorJsonResponse(int $status,array $errors){
            DebugUtils::log("Middlewares",fn()=>Route::getCurrentRoute()?->gatherMiddleware() ?? [null]);
            return self::stdJsonResponse(
                status:$status,
                data:[
                    'errors'=>array_map(
                        fn(ClassStructure $error)=>DtoUtils::prepareDtoForJsonResponse($error),
                        $errors
                    )
                ]
            );
        }

        public static function stdSuccessJsonResponse(int $status,ClassStructure $response){
            return self::stdJsonResponse(
                status:$status,
                data:DtoUtils::prepareDtoForJsonResponse($response,wrap:'data')
        );
        }

        public static function stdJsonResponse(int $status,mixed $data){
            return response()->json(
                data:$data,
                status:$status,
                //headers:EndpointUtils::getAllowOriginsHeader(),
                options:DtoUtils::jsonEncodeOptionsForResponse()
            );
        }
    }
}
