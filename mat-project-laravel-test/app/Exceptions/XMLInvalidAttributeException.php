<?php
namespace App\Exceptions{

    use App\Dtos\Defs\Errors\XML\XMLInvalidAttribute;
    use App\Dtos\Defs\Errors\XML\XMLInvalidAttributeErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\Utils\Utils;

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
        if(!$description){
            $description = "Expected attributes: ".Utils::arrayToStr($errorData->expectedAttributes).".";
        }
        $message = self::formatMessage($message,
            column:$errorData->eColumn,
            line:$errorData->eLine
        );
       parent::__construct(
        ApplicationErrorInformation::create()
        ->setUserInfo(
            UserSpecificPartOfAnError::create()
            ->setMessage($message)
        ->setDescription($description)
            )
        ->setDetails(
            XMLInvalidAttribute::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
