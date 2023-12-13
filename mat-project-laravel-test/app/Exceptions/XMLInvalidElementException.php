<?php
namespace App\Exceptions{

    use App\Types\Coords;
    use App\Types\XMLParserOffest;
    use App\Types\XMLParserPosition;
    use App\Types\XMLReadonlyParserPos;
    use App\Utils\Utils;
    use DOMDocument;
use phpDocumentor\Reflection\Types\ClassString;
use Throwable;
use XMLReader;

class XMLInvalidElementException extends XMLParsingException{

    /**
     * @param string $element
     * @param XMLReadonlyParserPos $postion
     * @param int $length
     * @param ?string $parent
     * @param string[]|null $correctElements
     * @param string $message
     * @param string $description
     * @param Coords $xmlCoords
     */
    public function __construct(
        string $element,
        XMLReadonlyParserPos $position,
        int $length,
        ?string $parent = null,
        ?array $correctElements = null,
        string $message = "",
        string $description = "",
        
        )
    {
        if(!$message){
        $message = $parent ? 
        "Element '{$parent}' does not have child '{$element}'"
        : "Element '{$element}' does not exist";
        }
        if(!$description && $correctElements){
            $description = "Expecte one of: ".Utils::arrayToStr($correctElements);
        }

        //$this->element = $element;
        // parent::__construct(
        //     xpath:$xpath,
        //     message:$message,
        //     description:$description,
        //     xmlCoords:$xmlCoords ?? Coords::getInvalid()
        // );
        
    }

    // public function getErrorData(): array
    // {
    //     return 
    //         [''=>$this->element,
    //         ...parent::getUserErrorData()
    // ];
        
    // }
}
}
