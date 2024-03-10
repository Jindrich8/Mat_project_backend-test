<?php

namespace App\Helpers\Database {

    use App\Models\User;
    use App\Types\AuthUserAuthProvider;
    use App\Types\UserAuthProviderInterface;
    use Illuminate\Auth\AuthenticationException;

    class UserHelper
    {
        private static ?UserAuthProviderInterface $authProvider = null;

        private static function getAuthProvider():UserAuthProviderInterface{
           return (self::$authProvider ??= new AuthUserAuthProvider());
        }

        /**
         * @param UserAuthProviderInterface $provider
         * @param callable():void $action
         */
        public static function withAuthProvider(UserAuthProviderInterface $provider,callable $action):void{
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