<?php

namespace App\Utils {

    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
    use App\Dtos\InternalTypes\FillInBlanksContent\FillInBlanksContent;
    use App\Exceptions\InternalException;
    use App\Helpers\Database\DBJsonHelper;
    use BackedEnum;
    use Illuminate\Support\Facades\Response;
    use Swaggest\JsonSchema\Structure\ClassStructure;
    use Throwable;
    use App\Types\DBTranslationEnumTrait;

    class DtoUtils
    {

        /**
         * @param DBTranslationEnumTrait<int> $case
         * @return ResponseOrderedEnumElement
         */
        public static function createOrderedEnumDto(DBTranslationEnumTrait $case):ResponseOrderedEnumElement{
            return ResponseOrderedEnumElement::create()
            ->setName(DBTranslationEnumTrait::translate($case))
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
         * @template T of ClassStructure
         * @param class-string<T> $dto
         * @param string $json
         * @return T
         */
        public static function importDto(string $dto,string $json,string $table,string $column,mixed $id,string $wrapper = '',string $field = ''):ClassStructure{
            $decoded = null;
            try{
               $decoded = DBJsonHelper::decode(
                    json:$json,
                    table:$table,
                    column:$column,
                    id:$id
                );
                if($field){
                   $decoded =is_object($decoded) ? $decoded->{$field} : $decoded[$field];
                }
                if($wrapper){
                    $decoded = (object)[
                        $wrapper => $decoded
                    ];
                }
                return $dto::import($decoded);
                }
                catch(Throwable $e){
                    throw new InternalException(
                        message:"Failed to import '$dto'.",
                        context:[
                        'dto' => $dto,
                        'json' => $json,
                        'table' => $table,
                        'column' => $column,
                        'id' => $id,
                        'wrapper' => $wrapper,
                        'field' => $field,
                        'decoded' => $decoded
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

        public static function tryToExportDto(ClassStructure $dto,mixed $default):mixed{
            try{
            return $dto->export($dto);
            }
            catch(Throwable $e){
               return $default;
            }
        }
    }
}