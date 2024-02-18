<?php

namespace App\Helpers {

    use App\Utils\DtoUtils;
    use Illuminate\Http\Response;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        /**
         * @throws InvalidValue
         */
        public static function success(ClassStructure|Response $data): \Illuminate\Foundation\Application|Response|ClassStructure|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
        {
            if($data instanceof ClassStructure){
                $data = response(
                content: DtoUtils::dtoToJson($data,wrap:'data')
            );
            }
           return $data;
        }

        public static function translateIdForUser(int $id):string{
            return $id . '';
        }
    }
}
