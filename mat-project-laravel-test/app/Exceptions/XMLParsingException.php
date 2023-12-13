<?php
namespace App\Exceptions;

use App\Dtos\Task\Create\ErrorResponse\ApplicationErrorObject;
use App\Types\Coords;
use App\Types\XMLParserOffest;
use App\Types\XMLParserPosition;
use App\Types\XMLReadonlyParserPos;
use DOMDocument;
use OutOfRangeException;
use phpDocumentor\Reflection\Types\ClassString;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use XMLReader;

class XMLParsingException extends ApplicationException{

    private readonly string $description;
    private readonly XMLReadonlyParserPos $pos;
    private readonly int $length;
    private readonly ApplicationErrorObject $errorObj;

    public static function getDefaultMessage():string{
        return "XML parsing error occured";
    }
  
    /**
     * @param string $message
     * @param XMLReadonlyParserPos $pos
     * @param int $length
     * @param string $description
     * @param bool $appendAt
     */
    public function __construct(
        string $message,
        XMLReadonlyParserPos $pos,
        int $length,
        string $description = null,
        bool $appendAt = true
        ){
            if($length <= 0){
               throw new OutOfRangeException("Length '$length' must be greater than 0");
            }
            if($appendAt){
            $message .= " at: line: " . $pos->line . " column: " . $pos->column . ".";
            }
            $this->description = $description;
            $this->length = $length;
            $this->pos = $pos;
            parent::__construct(message:$message);
    }

    public function getXMLPosition():XMLReadonlyParserPos{
        return $this->pos;
    }

    public function getUserMessage(): string
    {
        return $this->getMessage();
    }

    public function getUserCode(): int
    {
        // TODO:  - change this to specific code
        return $this->code;
    }

    public function getUserStatus(): int
    {
        return \Illuminate\Http\Response::HTTP_BAD_REQUEST;
    }

    public function getUserDescription(): string
    {
        return $this->description;
    }

    public function getUserErrorData(): array
    {
        $ret = [
            'line'=>$this->pos->line,
            'column'=>$this->pos->column,
            'length'=>$this->length
        ];
        if($this->pos->byteIndexIsValid()){
            $ret['byteIndex'] = $this->pos->byteIndex;
        }
        return $ret;
    }
}