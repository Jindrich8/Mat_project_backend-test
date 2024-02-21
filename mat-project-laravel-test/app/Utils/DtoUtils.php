<?php

namespace App\Utils {

    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
    use App\Exceptions\InternalException;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\DBJsonHelper;
    use App\Helpers\EnumHelper;
    use Exception;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;
    use Throwable;
    use BackedEnum;

    class DtoUtils
    {

        /**
         * @template T of \BackedEnum
         * @param class-string<T> $enum
         */
        public static function accessAsOrderedEnumDto(mixed $record,string $prop,string $enum){
           $case = DBHelper::accessAsEnum($record,$prop,$enum);
           return self::createOrderedEnumDto($case);
        }

        /**
         * @template T of \BackedEnum
         * @param T $case
         * @return ResponseOrderedEnumElement
         */
        public static function createOrderedEnumDto(BackedEnum $case):ResponseOrderedEnumElement{
            return ResponseOrderedEnumElement::create()
            ->setName(EnumHelper::translate($case))
            ->setOrderedId($case->value);
        }

        public static function jsonEncodeOptionsForResponse():int{
            return JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR;
        }

        /**
         * @throws InvalidValue
         */
        public static function prepareDtoForJsonResponse(ClassStructure $dto, string $field="", string $wrap=""){
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
         * @throws InvalidValue
         * @throws Exception
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
         * @param string $table
         * @param string $column
         * @param mixed $id
         * @param string $wrapper
         * @param string $field
         * @return ClassStructure
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
         * @throws InvalidValue
         * @throws Exception
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

        /**
         * @throws \JsonException
         */
        public static function exportedDtoToJson(mixed $exportedDto, int $otherJsonOptions = 0):string{
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
