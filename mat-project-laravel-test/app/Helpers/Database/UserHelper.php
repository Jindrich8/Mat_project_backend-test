<?php

namespace App\Helpers\Database {

    use Illuminate\Auth\AuthenticationException;
    use Illuminate\Support\Facades\Auth;

    class UserHelper
    {
        public static function getUserId():int{
           $userId = self::tryGetUserId()
            ?? throw new AuthenticationException();
            return $userId;
        }

        public static function tryGetUserId():?int{
            $user = Auth::user();
            return $user?->id;
        }
    }
}