<?php

namespace App\Helpers {

    use App\Utils\DebugUtils;
    use App\Utils\DtoUtils;
    use Illuminate\Contracts\Routing\ResponseFactory;
    use Illuminate\Http\Response;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class ResponseHelper
    {
        /**
         * @param ClassStructure|Response $data
         * @return Response|ResponseFactory
         * @throws \JsonException
         */
        public static function success(ClassStructure|Response $data): Response|\Illuminate\Contracts\Routing\ResponseFactory
        {
            if($data instanceof ClassStructure){
                $data = DtoUtils::exportedDtoToJson(['data' => DtoUtils::exportDto($data)]);
                $data = response(
                    content: $data
                );
            }
           return $data;
        }

        public static function translateIdForUser(int $id):string{
            return $id . '';
        }
    }
}
