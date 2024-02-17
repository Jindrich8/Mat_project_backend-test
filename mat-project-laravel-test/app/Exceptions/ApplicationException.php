<?php

namespace App\Exceptions;

use App\Dtos\Errors\ErrorResponse as ErrorsErrorResponse;
use App\Utils\DtoUtils;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Swaggest\JsonSchema\Structure\ClassStructure;

class ApplicationException extends \Exception
{
    protected readonly ErrorsErrorResponse $userResponse;
    protected readonly int $userStatus;

    public function getUserStatus(){
        return $this->userStatus;
    }

    public function getErrorResponse(){
        return $this->userResponse;
    }

    public function __construct(int $userStatus,ErrorsErrorResponse $userResponse){
        $this->userStatus = $userStatus;
        $this->userResponse = $userResponse;
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

    public function render(Request $request): Response
    {
        return response(
         DtoUtils::dtoToJson($this->userResponse)
        , $this->userStatus);
    }
}
