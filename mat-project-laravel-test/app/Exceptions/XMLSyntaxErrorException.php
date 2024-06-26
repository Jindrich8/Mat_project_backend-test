<?php
namespace App\Exceptions{

    use App\Dtos\Defs\Errors\XML\XMLSyntaxError as XMLXMLSyntaxError;
    use App\Dtos\Defs\Errors\XML\XMLSyntaxErrorErrorData as XMLXMLSyntaxErrorErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;

class XMLSyntaxErrorException extends XMLParsingException{
   
    public function __construct(
        XMLXMLSyntaxErrorErrorData $errorData,
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
           ApplicationErrorInformation::create()
           ->setUserInfo(
            UserSpecificPartOfAnError::create()
            ->setMessage($message)
            ->setDescription($description)
           )
           ->setDetails(
            XMLXMLSyntaxError::create()
           ->setErrorData($errorData)
           )
        );
    }
}
}
