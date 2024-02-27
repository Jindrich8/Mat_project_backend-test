<?php

namespace App\Exceptions;

use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Utils\DtoUtils;
use App\Utils\EndpointUtils;
use Illuminate\Http\JsonResponse;
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
        return array_map(fn($value)=>$value,$this->context);
    }

    

    public function render(Request $request)
    {
        $error = UserSpecificPartOfAnError::create()
        ->setMessage($this->getUserMessage())
        ->setDescription($this->getUserDescription());

        $response =  ApplicationErrorInformation::create()
        ->setUserInfo($error);
        return response(
            DtoUtils::dtoToJson($response)
           , $this->getUserStatus());
    }
}
