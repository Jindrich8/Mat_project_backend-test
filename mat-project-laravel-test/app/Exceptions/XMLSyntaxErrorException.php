<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\XMLSyntaxError;
    use App\Dtos\Errors\ErrorResponse\XMLSyntaxErrorErrorData;

class XMLSyntaxErrorException extends XMLParsingException{
   
    public function __construct(
        XMLSyntaxErrorErrorData $errorData,
        string $message = "",
        string $description = "",
        )
    {
        if(!$message){
            $message = "XML syntax error";
        }

        $message = self::formatMessage($message,
        column:$errorData->column,
    line:$errorData->line
);

        if(!$description){
            $description = "XML does not have valid XML syntax.";
        }
        parent::__construct(
           ApplicationErrorObject::create()
           ->setMessage($message)
           ->setDescription($description)
           ->setDetails(
            XMLSyntaxError::create()
           ->setErrorData($errorData)
           )
        );
    }
}
}
