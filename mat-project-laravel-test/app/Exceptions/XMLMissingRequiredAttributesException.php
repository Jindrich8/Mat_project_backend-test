<?php
namespace App\Exceptions{

    use App\Types\Coords;
    use App\Types\XMLParserOffest;
    use App\Types\XMLReadonlyParserPos;
    use App\Utils\Utils;
    use DOMDocument;
use phpDocumentor\Reflection\Types\ClassString;
use Throwable;
use XMLReader;

class XMLMissingRequiredAttributesException extends XMLParsingException{
    /**
     * @param XMLReadonlyParserPos $pos
     * @param int $length,
     * @param string $element
     * @param string[] $missingRequiredAttributes
     * @param string $message
     * @param string $description
     * @param bool $appendAt
     */
    public function __construct(
        XMLReadonlyParserPos $pos,
        int $length,
        string $element,
        array $missingRequiredAttributes,
        string $message = "",
        string $description = "",
        bool $appendAt = true
        )
    {
        if(!$message){
            $message = "Element '$element' is missing required attributes";
        }
        if(!$description){
            $description = "Missing required attributes: '"
            .Utils::arrayToStr($missingRequiredAttributes)
            .".";
        }
        parent::__construct(
            length:$length,
            pos:$pos,
            message:$message,
            description:$description,
            appendAt:$appendAt
        );
    }
}
}
