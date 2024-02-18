<?php

namespace App\Exceptions;

use App\Dtos\Errors\ErrorResponse as ErrorsErrorResponse;
use App\Utils\DtoUtils;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Structure\ClassStructure;

class ApplicationException extends Exception
{
    protected readonly ErrorsErrorResponse $userResponse;
    protected readonly int $userStatus;

    public function getUserStatus(): int
    {
        return $this->userStatus;
    }

    public function getErrorResponse(): ErrorsErrorResponse
    {
        return $this->userResponse;
    }

    public function __construct(int $userStatus,ErrorsErrorResponse $userResponse){
        $this->userStatus = $userStatus;
        $this->userResponse = $userResponse;
        parent::__construct($userResponse->userInfo->message);
    }

    /**
     * Get the exception's context information.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ['userResponse'=>$this->userResponse];
    }

    /**
     * @throws InvalidValue
     */
    public function render(Request $request): Response
    {
        return response(
         DtoUtils::dtoToJson($this->userResponse)
        , $this->userStatus);
    }
}
