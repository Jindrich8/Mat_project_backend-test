<?php
namespace App\Exceptions{

    use App\Types\Coords;
    use App\Types\XMLParserOffest;
    use App\Types\XMLReadonlyParserPos;
    use DOMDocument;
use phpDocumentor\Reflection\Types\ClassString;
use Throwable;
use XMLReader;

class XMLInvalidElementValueException extends XMLParsingException{

    /**
     * @param XMLReadonlyParserPos $pos
     * @param string $element
     * @param string $message
     * @param string $description
     * @param bool $appendAt
     */
    public function __construct(
        XMLReadonlyParserPos $pos,
        string $element,
        string $message = "",
        string $description = "",
        bool $appendAt = true
        )
    {
        if(!$message){
        $message = "Element '$element' has invalid value";
        }

        parent::__construct(
            pos:$pos,
            message:$message,
            description:$description,
            appendAt:$appendAt
        );
    }
}
}
