<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Errors\XML\XMLMissingRequiredAttributes;
    use App\Dtos\Defs\Errors\XML\XMLMissingRequiredAttributesErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\Utils\Utils;

    class XMLMissingRequiredAttributesException extends XMLParsingException
    {

        public function __construct(
            string $element,
            XMLMissingRequiredAttributesErrorData $errorData,
            string $message = "",
            string $description = "",
        ) {
            if (!$message) {
                $message = "Element '$element' is missing required attributes";
            }

            $message = self::formatMessage(
                $message,
                column: $errorData->eColumn,
                line: $errorData->eLine
            );


            if (!$description) {
                $description = "Missing required attributes: '"
                    . Utils::arrayToStr($errorData->missingAttributes)
                    . ".";
            }
            parent::__construct(
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage($message)
                            ->setDescription($description)
                    )
                    ->setDetails(
                        XMLMissingRequiredAttributes::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
