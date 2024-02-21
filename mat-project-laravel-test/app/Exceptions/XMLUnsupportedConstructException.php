<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Errors\XML\XMLUnsupportedConstruct;
    use App\Dtos\Defs\Errors\XML\XMLUnsupportedConstructErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
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
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                        ->setMessage($message)
                    ->setDescription($description)
                    )
                    ->setDetails(
                        XMLUnsupportedConstruct::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
