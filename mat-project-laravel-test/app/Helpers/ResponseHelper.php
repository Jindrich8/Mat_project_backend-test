<?php

namespace App\Helpers {

    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        public static function success(ClassStructure $data)
        {
           return response(
                content: json_encode(
                    value: ['data' => ClassStructure::export($data)],
                    flags: JSON_UNESCAPED_UNICODE
                )
            );
        }
    }
}