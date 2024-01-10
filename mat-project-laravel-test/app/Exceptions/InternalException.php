<?php

namespace App\Exceptions;

use App\Dtos\Errors\ErrorResponse\ErrorResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use LogicException;
use Throwable;

class InternalException extends LogicException
{
    /**
     * @param string $message — [optional] The Exception message to throw.
     * @param array<string, mixed> $context — [optional] The Exception additonal loggable informations.
     * @param int $code — [optional] The Exception code.
     * @param null|Throwable $previous
     * [optional] The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = "",private readonly array $context = [],?int $code = 0,?Throwable $previous = null){
        parent::__construct(
            message:$message,
            code:$code,
            previous:$previous
        );
    }

    public function getUsercode(): int
    {
        return 500;
    }

    public function getUserStatus(): int
    {
        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function getUserDescription(): string
    {
        return '';
    }

    public function getUserErrorData(): ?array
    {
        return null;
    }

    public function getUserMessage():string{
        return "Internal server error";
    }

    /**
     * Get the exception's context information.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function render(Request $request): Response
    {
        $error = ((object)(null))//ErrorResponse\Error::create()
        ->setCode($this->getUserCode())
        ->setMessage($this->getUserMessage())
        ->setDescription($this->getUserDescription());
        
        if(($errorData = $this->getUserErrorData()))$error->setErrorData($errorData);

        $response =  ErrorResponse::create()
        ->setError($error);
        return response(
            json_encode(ErrorResponse::export($response))
        , $this->getUserStatus());
    }
}
