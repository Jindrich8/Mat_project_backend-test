<?php

namespace App\Helpers {

    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\Dtos\Request as DtosRequest;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\ConversionException;
    use App\Exceptions\EnumConversionException;
    use App\Utils\JsonSchemaUtils;
    use App\Utils\DebugLogger;
    use App\Utils\StrUtils;
    use App\Utils\Utils;
    use Illuminate\Http\Request;
    use App\Utils\ValidateUtils;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Validation\ValidationException;
    use stdClass;
    use Swaggest\JsonSchema\Exception;
    use Swaggest\JsonSchema\InvalidValue;
    use Swaggest\JsonSchema\Structure\ClassStructure;
    use Symfony\Component\HttpFoundation\Response as ResponseAlias;
    use Validator;

    class RequestHelper
    {

        /**
         * @param class-string<\IntBackedEnum> $enum
         * @param string $id
         * @throws EnumConversionException
         * @return int
         */
        public static function translateEnumId(string $enum, string $id): int
        {
            $translatedId = ValidateUtils::validateInt($id);
            if (is_int($translatedId)) {
                return EnumHelper::fromThrow($enum, $translatedId)->value;
            }
            throw new EnumConversionException($enum, $id);
        }

        public static function translateId(string $id): int
        {
            $translatedId = ValidateUtils::validateInt($id);
            if (is_int($translatedId)) {
                return $translatedId;
            }
            throw new ConversionException('int', $id);
        }

        public static function tryToTranslateId(string $id): int|null
        {
            $translatedId = ValidateUtils::validateInt($id);
            if (is_int($translatedId)) {
                return $translatedId;
            }
            return null;
        }

        /**
         * @throws ValidationException
         */
        public static function validateAndExtractRequestData(array|Request $data): array
        {
            $validatedData =  is_array($data) ?
                Validator::validate($data, [
                    DtosRequest::DATA => 'array'
                ])
                : $data->validate([
                    DtosRequest::DATA => 'array'
                ]);
            return $validatedData[DtosRequest::DATA] ?? [];
        }

        /**
         * @template T of ClassStructure
         * @param class-string<T> $dtoClass
         * @param mixed $data
         * @return T
         * @throws ApplicationException
         * @noinspection PhpRegExpRedundantModifierInspection
         */
        public static function requestDataToDto(string $dtoClass, mixed $data): ClassStructure
        {
            try {
                DebugLogger::debug("requestToDto: '$dtoClass'", ["data" => var_export($data, true)]);
                $dto = $dtoClass::import($data);
                return $dto;
            } catch (Exception | InvalidValue $e) {
                report($e);
                $message = "";
                $path =[];
                if ($e instanceof InvalidValue) {
                    $message = $e->error;
                    $path = JsonSchemaUtils::getPathProps($e->path);
                } else {
                    $message = $e->getMessage();
                }
                $message = JsonSchemaUtils::filterError($message);
                $message = JsonSchemaUtils::formatError($message,$path);
                throw new ApplicationException(
                    ResponseAlias::HTTP_BAD_REQUEST,
                    ApplicationErrorInformation::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage("Bad request")
                                ->setDescription($message)
                        )
                );
            }
        }

        /**
         * @template T of ClassStructure
         * @param class-string<T> $dtoClass
         * @param Request $request
         * @return T
         * @throws ApplicationException
         * @throws ValidationException
         */
        public static function getDtoFromRequest(string $dtoClass, Request $request): ClassStructure
        {
            $requestData = $request->isMethod('GET') ?
                self::getQuery($request)
                : self::getData($request);

            return self::requestDataToDto($dtoClass, $requestData);
        }


        /**
         * @throws ValidationException
         */
        public static function getData(Request $request): mixed
        {
            $validated = self::validateAndExtractRequestData($request);
            DebugLogger::debug("getData: ", ["validated" => var_export($validated, true)]);
            $res = Utils::recursiveAssocArrayToStdClass($validated, canChange: true);
            DebugLogger::debug("getData: (assoc => stdClass): ", ["res" => var_export($res, true)]);
            return $res ?: new stdClass;
        }

        /**
         * @throws ValidationException
         */
        public static function getQuery(Request $request): mixed
        {
            $query = $request->query() ?:  [];

            $validated = self::validateAndExtractRequestData($query);
            unset($query);
            Log::debug("getQuery: ", ["validated" => var_export($validated, true)]);
            $res = Utils::recursiveAssocArrayToStdClass(
                arr: $validated,
                canChange: true,
                parseValue: fn ($value) => is_string($value) ?
                    (
                        $value[0] === "'" ?
                        substr($value, 1) :
                        StrUtils::tryParseFloat($value, $value)
                    )
                    : $value
            );
            Log::debug("getQuery: (assoc => stdClass): ", ["res" => var_export($res, true)]);
            return $res ?: new stdClass;
        }
    }
}
