<?php

namespace App\Exceptions {

    use App\Dtos\Defs\Errors\XML\DefsOr;
    use App\Dtos\Defs\Errors\XML\XMLMissingRequiredElements;
    use App\Dtos\Defs\Errors\XML\XMLMissingRequiredElementsErrorData;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\Types\AndOrStringBuilder;
    use App\Utils\Utils;

    class XMLMissingRequiredElementsException extends XMLParsingException
    {

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
            XMLMissingRequiredElementsErrorData $errorData,
            string $message = "",
            string $description = "",
        ) {
            if (!$message) {
                $message = "Element '$element' is missing required elements";
            }

            $message = self::formatMessage(
                $message,
                column: $errorData->eColumn,
                line: $errorData->eLine
            );
            $missingRequiredElements = $errorData->missingElements;
            if (!$description) {
                $builder = new AndOrStringBuilder(
                    andDel: ', ',
                    orDel: ' / ',
                    andGroups: ['{', '}', '[', ']', '(', ')']
                );
                $description = "Missing required elements: " . $builder->transform($missingRequiredElements) . ".";
            }
            parent::__construct(
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage($message)
                            ->setDescription($description)
                    )
                    ->setDetails(
                        XMLMissingRequiredElements::create()
                            ->setErrorData($errorData)
                    )
            );
        }
    }
}
