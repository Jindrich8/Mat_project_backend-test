<?php

namespace App\Exceptions;

use App\Dtos\Errors\ApplicationErrorInformation;
use App\Dtos\Errors\ErrorResponse;
use App\Utils\DtoUtils;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Swaggest\JsonSchema\InvalidValue;

class ApplicationException extends Exception
{
    protected readonly ErrorResponse $userResponse;
    protected readonly int $userStatus;

    public function getUserStatus(): int
    {
        return $this->userStatus;
    }

    public function getErrorResponse(): ErrorResponse
    {
        return $this->userResponse;
    }

    public function __construct(int $userStatus,ApplicationErrorInformation $userResponse){
        $this->userStatus = $userStatus;
        $this->userResponse = ErrorResponse::create()
        ->setError($userResponse);

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
