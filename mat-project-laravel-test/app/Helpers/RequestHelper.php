<?php

namespace App\Helpers {

    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ErrorResponse as ErrorsErrorResponse;
    use App\Dtos\Request as DtosRequest;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\ConversionException;
    use App\Exceptions\EnumConversionException;
    use App\Exceptions\InternalException;
    use App\Utils\Utils;
    use Illuminate\Http\Request;
    use App\Types\BackedEnumTrait;
    use App\Utils\ValidateUtils;
    use Illuminate\Http\Response;
    use Illuminate\Validation\ValidationException;
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
                return EnumHelper::fromThrow($enum,$translatedId)->value;
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
            return $validatedData[DtosRequest::DATA];
        }

        /**
         * @template T of ClassStructure
         * @param class-string<T> $dtoClass
         * @param mixed $data
         * @return T
         * @throws ApplicationException
         */
        public static function requestDataToDto(string $dtoClass, mixed $data): ClassStructure
        {
            try {
                $dto = $dtoClass::import($data);
                return $dto;
            } catch (Exception | InvalidValue $e) {
                $message = "";
                $messagePosfix = "";
                if ($e instanceof InvalidValue) {
                    $message = $e->error;
                    $matches = [];
                    if (preg_match_all(<<<'EOF'
             /->properties:(.*?)(?:->|$)/u
             EOF, $e->path, $matches)) {
                        echo "Matches\n";
                        dump($matches);
                        array_splice($matches, 0, 1);
                        $segments = [];
                        foreach ($matches as $match) {
                            array_push($segments, ...$match);
                        }
                        $messagePosfix = ' at ' . implode('->', $segments);
                    }
                } else {
                    $message = $e->getMessage();
                }
                $regex = '/(.*?)\\s*,?(?:data:|at\\s|#|\\$|{|\\[)/u';
                if (preg_match(pattern: $regex, subject: $message, matches: $matches)) {
                    $message = $matches[1];
                }
                $message = rtrim($message, ', ') . $messagePosfix . '.';
                throw new ApplicationException(
                    ResponseAlias::HTTP_BAD_REQUEST,
                    ErrorsErrorResponse::create()
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
            return Utils::recursiveAssocArrayToStdClass($validated, canChange: true);
        }

        public static function getQuery(Request $request): mixed
        {
            $query = $request->query() ?:  [];
            report(new InternalException(
                "LOG: getQuery '" . var_export($query, true) . "'",
                context: [
                    'query' => $query
                ]
            ));

            $validated = self::validateAndExtractRequestData($query);
            report(new InternalException(
                "LOG: validated query '" . var_export($validated, true) . "'",
                context: [
                    'validatedQuery' => $validated
                ]
            ));
            unset($query);
            report(new InternalException("LOG: query VALIDATION SUCCEEDED"));
            return Utils::recursiveAssocArrayToStdClass($validated, canChange: true);
        }
    }
}
