<?php

namespace App\Helpers\Database {

    use App\Models\User;
    use Illuminate\Auth\AuthenticationException;
    use Illuminate\Support\Facades\Auth;

    class UserHelper
    {

          /**
         * @throws AuthenticationException
         */
        public static function getUserId():int{
           $userId = self::tryGetUserId()
            ?? throw new AuthenticationException();
            return $userId;
        }

        public static function tryGetUserId():?int{
            $user = Auth::user();
            return $user?->id;
        }

        public static function tryGetUser():?User{
            $user = Auth::user();
            return $user;
        }

        /**
         * @throws AuthenticationException
         */
        public static function getUser():User{
            $user = self::tryGetUser()
            ?? throw new AuthenticationException();
            return $user;
        }
    }
}