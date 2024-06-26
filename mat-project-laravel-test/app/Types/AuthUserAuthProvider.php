<?php

namespace App\Types {

    use App\Models\User;
    use Illuminate\Support\Facades\Auth;

    class AuthUserAuthProvider implements UserAuthProviderInterface
    {
        public function tryGetUser(): ?User
        {
            return Auth::user();
        }
    }
}