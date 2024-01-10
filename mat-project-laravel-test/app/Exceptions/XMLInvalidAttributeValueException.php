<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidAttributeValue;
    use App\Dtos\Errors\ErrorResponse\XMLInvalidAttributeValueErrorData;
    use App\Types\Coords;
    use App\Types\XMLParserOffest;
use DOMDocument;
use phpDocumentor\Reflection\Types\ClassString;
use Throwable;
use XMLReader;

class XMLInvalidAttributeValueException extends XMLParsingException{

 
    public function __construct(
        string $element,
        XMLInvalidAttributeValueErrorData $errorData,
        string $message = '',
        string $description = ''
        )
    {
        $attribute = $errorData->invalidAttribute;
        if(!$message){
        $message = "Element '$element' has attribute '$attribute' with invalid value ";
        }
        $message = self::formatMessage($message,
            column:$errorData->eColumn,
        line:$errorData->eLine
    );

        parent::__construct(
           errorResponse: ApplicationErrorObject::create()
           ->setMessage($message)
           ->setDescription($description)
            ->setDetails(
            XMLInvalidAttributeValue::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
