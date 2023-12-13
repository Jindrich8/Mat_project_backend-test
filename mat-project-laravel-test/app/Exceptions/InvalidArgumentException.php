<?php

namespace App\Exceptions;

use Illuminate\Http\Response;
use Throwable;

class InvalidArgumentException extends InternalException
{
    /**
     * @param string $argumentName
     * @param string $argumentValue
     * @param string $isNotValidBecause
     * @param int $code — [optional] The Exception code.
     * @param null|Throwable $previous
     * [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $argumentName,mixed $argumentValue,string $isNotValidBecause = "",?int $code = 0,?Throwable $previous = null){
        
        $message = "Argument '$argumentName' does not have a valid value"
        . ($isNotValidBecause ? ", because $isNotValidBecause":"")
        .".";
        parent::__construct(
            message:$message,
            context:['argumentValue'=>$argumentValue],
            code:$code,
            previous:$previous
        );
    }
}
