<?php

namespace App\Exceptions;

use phpDocumentor\Reflection\Types\ClassString;
use Throwable;

class UnsupportedVariantException extends InternalException
{
 public function __construct(string $class,string $case, int $code = 0, ?Throwable $previous = null)
 {
     $message = "Enum case is not supported!";
     parent::__construct(message:$message,context:[
        'class' => $class,
        'case' => $case,
     ], code:$code, previous:$previous);
 }
}
