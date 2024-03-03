<?php

namespace App\Helpers {

    use App\Dtos\SuccessResponse;
    use App\Utils\DtoUtils;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\Log;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        /**
         * @throws InvalidValue
         */
        public static function success(ClassStructure|Response $data): Response|\Illuminate\Contracts\Routing\ResponseFactory
        {
            if($data instanceof ClassStructure){
                Log::info(self::class."::success - ClassStructure");
                $data = DtoUtils::exportedDtoToJson(['data' => DtoUtils::exportDto($data)]);
                $data = response(
                    content: $data
                );
            }
            Log::info("ResponseHelper::success: ",['data' => $data->content()]);
           return $data;
        }

        public static function translateIdForUser(int $id):string{
            return $id . '';
        }
    }
}
