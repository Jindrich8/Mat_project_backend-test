<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidAttribute as ErrorResponseXMLInvalidAttribute;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidAttributeErrorData;

class XMLInvalidAttributeException extends XMLParsingException{

    
    public function __construct(
        string $element,
        XMLInvalidAttributeErrorData $errorData,
        string $message = '',
        string $description = ''
        )
    {
        $attribute = $errorData->invalidAttribute;
        if(!$message){
            $message = "Element '{$element}' has invalid attribute '{$attribute}'";
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
            ErrorResponseXMLInvalidAttribute::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
