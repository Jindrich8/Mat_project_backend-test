<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementValuePart;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidElementValuePartErrorData;

class XMLInvalidElementValuePartException extends XMLParsingException{

  
    public function __construct(
        string $element,
        XMLInvalidElementValuePartErrorData $errorData,
        string $message = "",
        string $description = ""
        )
    {

        if(!$message){
            $message = "Element '$element' has invalid part of it's value";
            }

            $message = self::formatMessage($message,
                column:$errorData->column,
            line:$errorData->line
        );

        parent::__construct(
            ApplicationErrorObject::create()
            ->setMessage($message)
            ->setDescription($description)
            ->setDetails(
                XMLInvalidElementValuePart::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
