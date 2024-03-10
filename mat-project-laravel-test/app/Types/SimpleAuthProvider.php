<?php

namespace App\Types {

    use App\Models\User;

    class SimpleAuthProvider implements UserAuthProviderInterface
    {
        private ?User $user;
        public function __construct(?User $user)
        {
            $this->user = $user;
        }

        public function tryGetUser(): ?User
        {
            return $this->user;
        }
    }
}