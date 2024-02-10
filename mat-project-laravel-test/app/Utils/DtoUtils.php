<?php

namespace App\Utils {

    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
    use App\Dtos\InternalTypes\FillInBlanksContent\FillInBlanksContent;
    use App\Exceptions\InternalException;
    use BackedEnum;
    use Illuminate\Support\Facades\Response;
    use Swaggest\JsonSchema\Structure\ClassStructure;
    use Throwable;

    class DtoUtils
    {

        /**
         * @param BackedEnum<int> $case
         * @return ResponseOrderedEnumElement
         */
        public static function createOrderedEnumDto(BackedEnum $case):ResponseOrderedEnumElement{
            return ResponseOrderedEnumElement::create()
            ->setName($case->name)
            ->setOrderedId($case->value);
        }

        public static function jsonEncodeOptionsForResponse():int{
            return JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR;
        }

        public static function prepareDtoForJsonResponse(ClassStructure $dto,string $field="",string $wrap=""){
            $exported = self::exportDto($dto);
            if($field){
                $exported = self::accessExportedField($exported,$field);
            }
            if($wrap){
                $exported = [$wrap => $exported];
            }
            return $exported;
        }

        /**
         * @throws \Swaggest\JsonSchema\InvalidValue
         * @throws \Exception
         */
        public static function exportDto(ClassStructure $dto):mixed{
            try{
            return $dto->export($dto);
            }
            catch(Throwable $e){
                throw new InternalException(context:[
                    'dto' => $dto
                ],previous:$e);
            }
        }

          /**
         * @throws \Swaggest\JsonSchema\InvalidValue
         * @throws \Exception
         */
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