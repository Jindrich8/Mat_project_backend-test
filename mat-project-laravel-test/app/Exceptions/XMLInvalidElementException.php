<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Errors\XML\XMLInvalidElement;
    use App\Dtos\Defs\Errors\XML\XMLInvalidElementErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse;
    use App\Utils\Utils;


    class XMLInvalidElementException extends XMLParsingException
    {

      
        public function __construct(
            string $element,
            XMLInvalidElementErrorData $errorData,
            ?string $parent = null,
            string $message = "",
            string $description = "",

        ) {
            if(!$message){
                $message = $parent ?
                "Element '{$parent}' does not have child '{$element}'"
                : "Element '{$element}' does not exist";
                }

                $message = self::formatMessage($message,
                    column:$errorData->eColumn,
                line:$errorData->eLine
            );


            $correctElements = $errorData->expectedElements;
            if (!$description && $correctElements) {
                $description = "Expected one of: " . Utils::arrayToStr($correctElements);
            }

            parent::__construct(
                ErrorResponse::create()
                ->setUserInfo(
                    UserSpecificPartOfAnError::create()
                    ->setMessage($message)
                    ->setDescription($description)
                )
                    ->setDetails(
                        XMLInvalidElement::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
