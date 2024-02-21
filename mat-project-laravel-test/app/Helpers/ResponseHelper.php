<?php

namespace App\Helpers {

    use App\Dtos\SuccessResponse;
    use App\Utils\DtoUtils;
    use Illuminate\Http\Response;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        /**
         * @throws InvalidValue
         */
        public static function success(SuccessResponse|ClassStructure|Response $data): Response|\Illuminate\Contracts\Routing\ResponseFactory
        {
            if($data instanceof ClassStructure){
                if(!($data instanceof SuccessResponse)){
                $data = SuccessResponse::create()
                ->setData($data);
                }
                $data = response(
                    content: DtoUtils::dtoToJson($data)
                );
            }
           return $data;
        }

        public static function translateIdForUser(int $id):string{
            return $id . '';
        }
    }
}
