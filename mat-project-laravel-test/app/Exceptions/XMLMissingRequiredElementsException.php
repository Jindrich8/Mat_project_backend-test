<?php
namespace App\Exceptions{

    use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
    use App\Dtos\Errors\ErrorResponse\ErrorResponse;
    use App\Dtos\Errors\ErrorResponse\XMLMissingRequiredElements;
    use App\Dtos\Errors\ErrorResponse\XMLMissingRequiredElementsErrorData;
    use App\Utils\Utils;

class XMLMissingRequiredElementsException extends XMLParsingException{
   
    /**
     * @param string $element
     * @param array<string|array<string>> $missingRequiredElements
     * if array element is array, then it means, that it should be one of element array elements
     * @param XMLMissingRequiredElementsErrorData $errorData,
     * @param string $message
     * @param string $description
     */
    public function __construct(
        string $element,
        array $missingRequiredElements,
        XMLMissingRequiredElementsErrorData $errorData,
        string $message = "",
        string $description = "",
        )
    {
        if(!$message){
            $message = "Element '$element' is missing required elements";
        }

        $message = self::formatMessage($message,
        column:$errorData->eColumn,
    line:$errorData->eLine
);

        if(!$description){
            $description = "Missing required elements: '"
            .Utils::arrayToStr(
                array_map(
                fn($missing)=>is_array($missing) ? Utils::wrapAndImplode("'",' or ',$missing) : $missing,
                $missingRequiredElements)
                )
            .".";
        }
        parent::__construct(
           ApplicationErrorObject::create()
           ->setMessage($message)
           ->setDescription($description)
           ->setDetails(
            XMLMissingRequiredElements::create()
           ->setErrorData($errorData)
           )
        );
    }
}
}
