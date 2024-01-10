<?php

namespace App\Exceptions {

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElement;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementErrorData;
    use App\Dtos\Errors\ErrorResponse\XMLUnsupportedConstruct;
    use App\Dtos\Errors\ErrorResponse\XMLUnsupportedConstructErrorData;
    use App\Utils\Utils;


    class XMLUnsupportedConstructException extends XMLParsingException
    {

      
        public function __construct(
            string $constructName,
            XMLUnsupportedConstructErrorData $errorData,
            string $message = "",
            string $description = ""
        ) {
            if (!$message) {
                $message = "Construct '{$constructName}' is not supported";
            }

            $message = self::formatMessage($message,
            column:$errorData->column,
        line:$errorData->line
    );

            $supportedConstructs = $errorData->supportedConstructs;
            if (!$description && $supportedConstructs) {
                $description = "Supported constructs: " . Utils::arrayToStr($supportedConstructs);
            }

            parent::__construct(
                ApplicationErrorObject::create()
                    ->setMessage($message)
                    ->setDescription($description)
                    ->setDetails(
                        XMLUnsupportedConstruct::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
