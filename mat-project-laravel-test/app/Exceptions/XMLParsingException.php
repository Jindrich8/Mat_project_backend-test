<?php
namespace App\Exceptions;

use App\Dtos\Errors\ApplicationErrorInformation;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class XMLParsingException extends ApplicationException{
  
   function __construct(ApplicationErrorInformation $errorResponse,int $userStatus = Response::HTTP_BAD_REQUEST)
   {
     parent::__construct($userStatus,
     $errorResponse
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