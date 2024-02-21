<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Errors\XML\XMLInvalidAttributeValueErrorData;
    use App\Dtos\Defs\Errors\XML\XMLInvalidAttributeValue;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;

    class XMLInvalidAttributeValueException extends XMLParsingException
    {


        public function __construct(
            string $element,
            XMLInvalidAttributeValueErrorData $errorData,
            string $message = '',
            string $description = ''
        ) {
            $attribute = $errorData->invalidAttribute;
            if (!$message) {
                $message = "Element '$element' has attribute '$attribute' with invalid value ";
            }
            $message = self::formatMessage(
                $message,
                column: $errorData->eColumn,
                line: $errorData->eLine
            );

            parent::__construct(
                errorResponse: ApplicationErrorInformation::create()
                ->setUserInfo(
                    UserSpecificPartOfAnError::create()
                    ->setMessage($message)
                    ->setDescription($description)
                )
                    ->setDetails(
                        XMLInvalidAttributeValue::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
