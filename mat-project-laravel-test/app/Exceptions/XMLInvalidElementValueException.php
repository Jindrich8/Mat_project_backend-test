<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementValue;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementValueErrorData;

class XMLInvalidElementValueException extends XMLParsingException{

  
    public function __construct(
        string $element,
        XMLInvalidElementValueErrorData $errorData,
        string $message = "",
        string $description = ""
        )
    {

        if(!$message){
            $message = "Element '$element' has invalid value";
            }

            $message = self::formatMessage($message,
                column:$errorData->eColumn,
            line:$errorData->eLine
        );

        parent::__construct(
            ApplicationErrorObject::create()
            ->setMessage($message)
            ->setDescription($description)
            ->setDetails(
                XMLInvalidElementValue::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
