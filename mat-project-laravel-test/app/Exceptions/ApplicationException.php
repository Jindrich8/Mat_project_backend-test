<?php

namespace App\Exceptions;

use App\Dtos\Errors\ErrorResponse\ErrorResponse;
use App\Utils\DtoUtils;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Swaggest\JsonSchema\Structure\ClassStructure;

abstract class ApplicationException extends \Exception
{
    protected readonly ErrorResponse $userResponse;
    protected readonly int $userStatus;

    public function getUserStatus(){
        return $this->userStatus;
    }

    public function getErrorResponse(){
        return $this->userResponse;
    }

    public function __construct(int $userStatus,ErrorResponse $userResponse){
        $this->userStatus = $userStatus;
        $this->userResponse = $userResponse;
    }

    public function render(Request $request): Response
    {
        return response(
         DtoUtils::dtoToJson($this->userResponse)
        , $this->userStatus);
    }
}
