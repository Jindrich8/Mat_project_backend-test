<?php

namespace App\Exceptions;

use App\Dtos\ErrorResponse;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

abstract class ApplicationException extends \Exception
{
    abstract public function getUserCode():int;

    abstract public function getUserMessage():string;

    abstract public function getUserStatus(): int;

    abstract public function getUserDescription(): string;

    abstract public function getUserErrorData():?array;

    public function render(Request $request): Response
    {
        $error = null;//ErrorResponse\Error::create()
        ->setCode($this->getUserCode())
        ->setMessage($this->getUserMessage())
        ->setDescription($this->getUserDescription());
        
        if(($errorData = $this->getUserErrorData()))$error->setErrorData($errorData);

        $response =  ErrorResponse\ErrorResponse::create()
        ->setError($error);
        return response(
            json_encode(ErrorResponse\ErrorResponse::export($response))
        , $this->getUserStatus());
    }
}
