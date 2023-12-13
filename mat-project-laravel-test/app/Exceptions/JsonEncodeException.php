<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

class JsonEncodeException extends ApplicationException
{
    public function status(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function help(): string
    {
        return "";
    }

    public function error(): string
    {
        return "Json could not be encoded";
    }
}
