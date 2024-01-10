<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\XMLMissingRequiredAttributes;
    use App\Dtos\Errors\ErrorResponse\XMLMissingRequiredAttributesErrorData;
    use App\Utils\Utils;

class XMLMissingRequiredAttributesException extends XMLParsingException{
   
    public function __construct(
        string $element,
        array $missingRequiredAttributes,
        XMLMissingRequiredAttributesErrorData $errorData,
        string $message = "",
        string $description = "",
        )
    {
        if(!$message){
            $message = "Element '$element' is missing required attributes";
        }

        $message = self::formatMessage($message,
        column:$errorData->eColumn,
    line:$errorData->eLine
);

        if(!$description){
            $description = "Missing required attributes: '"
            .Utils::arrayToStr($missingRequiredAttributes)
            .".";
        }
        parent::__construct(
           ApplicationErrorObject::create()
           ->setMessage($message)
           ->setDescription($description)
           ->setDetails(
            XMLMissingRequiredAttributes::create()
           ->setErrorData($errorData)
           )
        );
    }
}
}