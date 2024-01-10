<?php

namespace App\Exceptions {

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElement;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementErrorData;
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
                ApplicationErrorObject::create()
                    ->setMessage($message)
                    ->setDescription($description)
                    ->setDetails(
                        XMLInvalidElement::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
