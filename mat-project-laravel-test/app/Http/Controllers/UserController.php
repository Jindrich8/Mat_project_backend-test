<?php

namespace App\Http\Controllers;

use App\Dtos\Defs\Endpoints\User\GetProfile\UserGetProfileResponse;
use App\Helpers\Database\UserHelper;
use App\TableSpecificData\UserRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class UserController extends Controller
{

    /**
     * @throws AuthenticationException
     */
    public static function getProfile(Request $request):UserGetProfileResponse{
        $user = UserHelper::getUser();
        $response = UserGetProfileResponse::create()
        ->setName($user->name)
        ->setEmail($user->email);

        if($user->role === UserRole::TEACHER->value){
        $response->setRole(UserGetProfileResponse::TEACHER);
        }
        return $response;
    }
}
