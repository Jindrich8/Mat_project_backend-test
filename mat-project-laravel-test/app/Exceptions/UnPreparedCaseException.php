<?php

namespace App\Exceptions;

use Throwable;

class UnPreparedCaseException extends InternalException
{
    public function __construct(string $target,string $class,string $case, int $code = 0, ?Throwable $previous = null)
    {
        $message = "$target is not prepared for $class::$case";
        parent::__construct($message,[], $code, $previous);
    }
}
