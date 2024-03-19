<?php

namespace App\Utils {

    use App\Dtos\Defs\Types\Response\ResponseOrderedEnumElement;
    use App\Exceptions\InternalException;
    use App\Helpers\Database\DBHelper;
    use App\Helpers\Database\DBJsonHelper;
    use App\Helpers\EnumHelper;
    use App\Types\StopWatchTimer;
    use Exception;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;
    use Throwable;
    use BackedEnum;
    use Swaggest\JsonSchema\Context;

    class DtoUtils
    {
        private static ?Context $importDBDto = null;
        public static function getImportDBDtoContext(): Context
        {
            if (!self::$importDBDto) {
                self::$importDBDto = new Context();
                // Validation must be done, because if validation is skipped and union is importing, 
                // it is going to pick first type from union no matter of schema
                //self::$importDBDto->skipValidation = true;
            }
            return self::$importDBDto;
        }

        private static ?Context $exportDtoContext = null;

        public static function getExportDtoContext(): Context
        {
            if (!self::$exportDtoContext) {
                self::$exportDtoContext = new Context();
                self::$exportDtoContext->skipValidation = true;
            }
            return self::$exportDtoContext;
        }

        /**
         * @template T of \BackedEnum
         * @param class-string<T> $enum
         */
        public static function accessAsOrderedEnumDto(mixed $record, string $prop, string $enum)
        {
            $case = DBHelper::accessAsEnum($record, $prop, $enum);
            return self::createOrderedEnumDto($case);
        }

        /**
         * @template T of \BackedEnum
         * @param T $case
         * @return ResponseOrderedEnumElement
         */
        public static function createOrderedEnumDto(BackedEnum $case): ResponseOrderedEnumElement
        {
            return ResponseOrderedEnumElement::create()
                ->setName(EnumHelper::translate($case))
                ->setOrderedId($case->value);
        }

        public static function jsonEncodeOptionsForResponse(): int
        {
            return JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;
        }

        /**
         * @param ClassStructure $dto
         * @param string $field
         * @param string $wrap
         * @return array|mixed
         * @throws Exception
         */
        public static function prepareDtoForJsonResponse(ClassStructure $dto, string $field = "", string $wrap = "")
        {
            $exported = self::exportDto($dto);
            if ($field) {
                $exported = self::accessExportedField($exported, $field);
            }
            if ($wrap) {
                $exported = [$wrap => $exported];
            }
            return $exported;
        }


        /**
         * @param array{0:&array,1:ClassStructure|array}[] $stack
         */
        private static function transformValue(ClassStructure|array|string|int|bool|float|null $value, mixed &$dest, array &$stack)
        {
            if($value === null){
                $dest = $value;
            } else if (is_object($value)) {
                $stack[] = [&$dest, $value];
            } else if (is_array($value)) {
                $stack[] = [&$dest, $value];
            } else {
                $dest = $value;
            }
        }

        private static function export(ClassStructure $dtoParam)
        {
            /**
             * @var array{0:&array,1:ClassStructure|array}[] $stack
             */
            $stack = [];
            $transformed = [];
            $dest = &$transformed;
            $dto = $dtoParam;
            do {
                if (is_object($dto)) {
                    $mapping =  $dto->properties()->getDataKeyMap();
                    foreach ($mapping as $propName => $dataPropName) {
                        if (isset($dto->{$propName})) {
                            /**
                             * @var ClassStructure|array|string|int|bool|float $value
                             */
                            $value = $dto->{$propName};
                            $dest[$dataPropName] = [];
                            self::transformValue($value, $dest[$dataPropName], $stack);
                        }
                    }
                } else {
                    foreach ($dto as $value) {
                        $dest[] = [];
                        self::transformValue($value, $dest[array_key_last($dest)], $stack);
                    }
                }
                if (!$stack) {
                    break;
                }
                [&$dest, $dto] = $stack[array_key_last($stack)];
                array_pop($stack);
            } while (true);
            return $transformed;
        }

        /**
         * @throws Exception
         */
        public static function exportDto(ClassStructure $dto): mixed
        {
            try {
                $exported = self::export($dto);
                return $exported;
            } catch (Throwable $e) {
                throw new InternalException(context: [
                    'dto' => $dto
                ], previous: $e);
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
         * @return T
         */
        public static function importDto(string $dto, string $json, string $table, string $column, mixed $id, string $wrapper = '', string $field = ''): ClassStructure
        {
            $decoded = null;
            try {
                $decoded = DBJsonHelper::decode(
                    json: $json,
                    table: $table,
                    column: $column,
                    id: $id
                );
                if ($field) {
                    $decoded = is_object($decoded) ? $decoded->{$field} : $decoded[$field];
                }
                if ($wrapper) {
                    $decoded = (object)[
                        $wrapper => $decoded
                    ];
                }
                /**
                 * @var ClassStructure $dto
                 */
                $imported = $dto::import($decoded, self::getImportDBDtoContext());
                //DebugLogger::debug("importDto: ",var_export($imported,true));
                return $imported;
            } catch (Throwable $e) {
                throw new InternalException(
                    message: "Failed to import '$dto'.",
                    context: [
                        'dto' => $dto,
                        'json' => $json,
                        'table' => $table,
                        'column' => $column,
                        'id' => $id,
                        'wrapper' => $wrapper,
                        'field' => $field,
                        'decoded' => $decoded
                    ],
                    previous: $e
                );
            }
        }

        /**
         * @throws Exception
         */
        public static function dtoToJson(ClassStructure $dto, string $field = "", string $wrap = "", int $otherJsonOptions = 0): string
        {
            $exported = self::exportDto($dto);
            if ($field) {
                $exported = self::accessExportedField($exported, $field);
            }
            if ($wrap) {
                $exported = [$wrap => $exported];
            }
            return self::exportedDtoToJson($exported, $otherJsonOptions);
        }

        /**
         * @throws \JsonException
         */
        public static function exportedDtoToJson(mixed $exportedDto, int $otherJsonOptions = 0): string
        {
            return json_encode($exportedDto, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | $otherJsonOptions);
        }

        public static function accessExportedField(mixed $exported, string $field)
        {
            return is_object($exported) ? $exported->{$field} : $exported[$field];
        }

        public static function tryToExportDto(ClassStructure $dto, mixed $default): mixed
        {
            try {
                return $dto->export($dto);
            } catch (Throwable $e) {
                return $default;
            }
        }
    }
}
