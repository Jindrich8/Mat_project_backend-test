<?php

namespace App\Helpers {

    use App\Utils\DtoUtils;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        public static function success(ClassStructure $data)
        {
           return response(
                content: DtoUtils::dtoToJson($data,wrap:'data')
            );
        }
    }
}