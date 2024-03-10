<?php

namespace App\Helpers\Database {

    use App\Models\User;
    use App\Types\AuthUserAuthProvider;
    use App\Types\UserAuthProvider;
    use Illuminate\Auth\AuthenticationException;

    class UserHelper
    {
        private static ?UserAuthProvider $authProvider = null;

        private static function getAuthProvider():UserAuthProvider{
           return (self::$authProvider ??= new AuthUserAuthProvider());
        }

        /**
         * @param UserAuthProvider $provider
         * @param callable():void $action
         */
        public static function withAuthProvider(UserAuthProvider $provider,callable $action):void{
            $prevProvider = self::$authProvider;
            self::$authProvider = $provider;
            $action();
            self::$authProvider = $prevProvider;
        }
          /**
         * @throws AuthenticationException
         */
        public static function getUserId():int{
           $userId = self::tryGetUserId()
            ?? throw new AuthenticationException();
            return $userId;
        }

        public static function tryGetUserId():?int{
            $user = self::getAuthProvider()->tryGetUser();
            return $user?->id;
        }

        public static function tryGetUser():?User{
            $user = self::getAuthProvider()->tryGetUser();
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