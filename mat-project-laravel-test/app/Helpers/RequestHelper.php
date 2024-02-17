<?php

namespace App\Helpers {

    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ErrorResponse as ErrorsErrorResponse;
    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\ConversionException;
    use App\Exceptions\EnumConversionException;
    use App\Exceptions\InternalException;
    use App\Utils\DebugUtils;
    use App\Utils\StrUtils;
    use App\Utils\Utils;
    use Illuminate\Http\Request;
    use BackedEnum;
    use App\Types\BackedEnumTrait;
    use App\Utils\DtoUtils;
    use App\Utils\ValidateUtils;
    use Illuminate\Http\Client\Request as ClientRequest;
    use Illuminate\Http\Response;
    use Swaggest\JsonSchema\Structure\ClassStructure;
    use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
    use Validator;

    class RequestHelper
    {

        /**
         * @param class-string<BackedEnumTrait<int>> $enum
         * @param string $id
         * @throws EnumConversionException
         * @return int
         */
        public static function translateEnumId(string $enum, string $id): int
        {
            $translatedId = ValidateUtils::validateInt($id);
            if (is_int($translatedId)) {
                return $enum::fromThrow($translatedId)->value;
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

        public static function validateAndExtractRequestData(array|Request $data): array
        {
            $validatedData = $data;
            if (Utils::isArray($data)) {
                if (!Utils::arrayHasKey($data, 'data') || !Utils::isEmptyArray($data['data'])) {
                    $validatedData = Validator::validate($data, [
                        'data' => 'required|array'
                    ]);
                }
            } else if (!Utils::isEmptyArray($data->input('data'))) {
                $validatedData = $data->validate([
                    'data' => 'required|array'
                ]);
            }
            return $validatedData['data'];
        }

        /**
         * @template T of ClassStructure
         * @param class-string<T> $dtoClass
         * @param mixed $data
         * @return T
         */
        public static function requestDataToDto(string $dtoClass, mixed $data): ClassStructure
        {
            try {
                $dto = $dtoClass::import($data);
                return $dto;
            } catch (\Swaggest\JsonSchema\Exception | \Swaggest\JsonSchema\InvalidValue $e) {
                $message = "";
                $messagePosfix = "";
                if ($e instanceof \Swaggest\JsonSchema\InvalidValue) {
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
                if (preg_match(<<<'EOF'
            /(.*?)\s*,?(?:data:|at |#|\$|{|\[)/u
            EOF, $message, $matches)) {
                    $message = $matches[1];
                }
                $message = rtrim($message, ', ') . $messagePosfix . '.';
                throw new ApplicationException(
                    Response::HTTP_BAD_REQUEST,
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
         */
        public static function getDtoFromRequest(string $dtoClass, Request $request): ClassStructure
        {
            $requestData = $request->isMethod('GET') ?
                self::getQuery($request)
                : self::getData($request);

            return self::requestDataToDto($dtoClass, $requestData);
        }


        public static function getData(Request $request): mixed
        {
            $validated = self::validateAndExtractRequestData($request);
            return Utils::recursiveAssocArrayToStdClass($validated, canChange: true);
        }

        public static function getQuery(Request $request): mixed
        {
            $query = $request->query() ?:  ['data' => []];
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
