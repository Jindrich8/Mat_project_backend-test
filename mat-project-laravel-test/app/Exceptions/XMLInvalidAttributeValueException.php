<?php
namespace App\Exceptions{

    use App\Types\Coords;
    use App\Types\XMLParserOffest;
use DOMDocument;
use phpDocumentor\Reflection\Types\ClassString;
use Throwable;
use XMLReader;

class XMLInvalidAttributeValueException extends XMLParsingException{

    private string $attribute;
    /**
     * @param string[] $xpath
     * @param string $attribute
     * @param string $description
     * @param string $message
     * @param Coords $xmlCoords
     */
    public function __construct(
        array $xpath,
        string $attribute,
        string $description = "",
        string $message = "",
        Coords $xmlCoords = Coords::getInvalid()
        )
    {
        if(!$message){
        $message = "Attribute '$attribute' has invalid value.";
        }

        parent::__construct(
            xpath:$xpath,
            message:$message,
            description:$description,
            xmlCoords:$xmlCoords
        );
        $this->attribute = $attribute;
    }

    public function getUserErrorData(): array
    {
        return array_merge(
            ['attribute'=>$this->attribute],
            parent::getUserErrorData()
        );
    }
}
}
