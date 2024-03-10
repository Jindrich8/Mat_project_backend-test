<?php

namespace App\Types {

    use App\Models\User;

    interface UserAuthProviderInterface
    {
        function tryGetUser():?User;
    }
}