<?php
namespace App\Exceptions;

use App\Dtos\Errors\ErrorResponse\ApplicationErrorObject;
use App\Dtos\Errors\ErrorResponse\ErrorResponse;
use Dev\DtoGen\StrUtils;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class XMLParsingException extends ApplicationException{
  
   function __construct(ApplicationErrorObject $errorResponse,int $userStatus = Response::HTTP_BAD_REQUEST)
   {
     parent::__construct($userStatus,
     ErrorResponse::create()->setError($errorResponse)
    );
   }

   protected static function getDescriptionPositionPart(int $column,int $line){
    return "at line: $line and column: $column";
   }

   protected static function formatMessage(string $message,int $column,int $line){
    if(!Str::endsWith($message,' ')){
      $message.=' ';
    }
    return $message .
     self::getDescriptionPositionPart(
      column:$column,
      line:$line
      ) .
    '.';
   }
}