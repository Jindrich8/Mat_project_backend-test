<?php

namespace App\Types {

    use App\Models\User;

    interface UserAuthProvider
    {
        function tryGetUser():?User;
    }
}