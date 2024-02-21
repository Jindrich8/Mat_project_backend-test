<?php
namespace App\Exceptions{

    use App\Dtos\Defs\Errors\XML\XMLInvalidElementValue;
    use App\Dtos\Defs\Errors\XML\XMLInvalidElementValueErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;

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
            ApplicationErrorInformation::create()
            ->setUserInfo(
                UserSpecificPartOfAnError::create()
                ->setMessage($message)
            ->setDescription($description)
            )
            ->setDetails(
                XMLInvalidElementValue::create()
            ->setErrorData($errorData)
            )
        );
    }
}
}
