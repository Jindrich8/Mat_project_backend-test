<?php

namespace App\Exceptions;

use App\Utils\DebugUtils;
use BackedEnum;
use phpDocumentor\Reflection\Types\ClassString;
use Throwable;
use UnitEnum;

class UnsupportedVariantException extends InternalException
{
 public function __construct(UnitEnum|BackedEnum $enum, int $code = 0, ?Throwable $previous = null)
 {
     $message ="Variant '{$enum->name}' of '".$enum::class."' is not supported.";
     $case = ['name'=>$enum->name];
     if($enum instanceof BackedEnum){
        $case['value'] = $enum->value;
     }
     parent::__construct(message:$message,context:[
        'class' => $enum::class,
        'case' => $case,
     ], code:$code, previous:$previous);
 }
}
