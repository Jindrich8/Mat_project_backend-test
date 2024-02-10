<?php

namespace App\Helpers {

    use App\Utils\DtoUtils;
    use Illuminate\Http\Response;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        public static function success(ClassStructure|Response $data)
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