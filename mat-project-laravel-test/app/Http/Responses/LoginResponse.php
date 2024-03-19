<?php
namespace App\Http\Responses;

use App\Dtos\Defs\Endpoints\Login\LoginResponse as LoginLoginResponse;
use App\Helpers\Database\UserHelper;
use App\Helpers\ResponseHelper;
use App\Utils\DebugLogger;
use Exception;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{

    /**
     * @param  $request
     * @return mixed
     * @throws Exception
     */
    public function toResponse($request)
    {
       $user = UserHelper::getUser();
       return ResponseHelper::success(
        LoginLoginResponse::create()
       ->setName($user->name)
       ->setEmail($user->email)
    );

     }

}
