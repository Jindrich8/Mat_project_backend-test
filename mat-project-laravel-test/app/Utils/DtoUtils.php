<?php

namespace App\Utils {

    use App\Dtos\InternalTypes\FillInBlanksContent\FillInBlanksContent;
    use Swaggest\JsonSchema\Structure\ClassStructure;

    class DtoUtils
    {

        public static function exportDto(ClassStructure $dto):mixed{
            return $dto->export($dto);
        }

        public static function dtoToJson(ClassStructure $dto,string $field="",string $wrap="",int $otherJsonOptions = 0):string{
           $exported = self::exportDto($dto);
            if($field){
                $exported = self::accessExportedField($exported,$field);
            }
            if($wrap){
                $exported = [$wrap => $exported];
            }
            return self::exportedDtoToJson($exported,$otherJsonOptions);
        }

        public static function exportedDtoToJson(mixed $exportedDto,int $otherJsonOptions = 0):string{
            return json_encode($exportedDto,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR|$otherJsonOptions);
        }

        public static function accessExportedField(mixed $exported,string $field){
            return $exported->{$field};
        }
    }
}