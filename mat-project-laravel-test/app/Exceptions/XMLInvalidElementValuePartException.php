<?php
namespace App\Exceptions{

    use App\Dtos\Defs\Errors\XML\XMLInvalidElementValuePart;
    use App\Dtos\Defs\Errors\XML\XMLInvalidElementValuePartErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ErrorResponse;

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
            ErrorResponse::create()
            ->setUserInfo(
                UserSpecificPartOfAnError::create()
                ->setMessage($message)
            ->setDescription($description)
            )
            ->setDetails(
                XMLInvalidElementValuePart::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
