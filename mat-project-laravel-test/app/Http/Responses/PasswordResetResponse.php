<?php

namespace App\Http\Responses {

    use Illuminate\Http\Response;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;

    class PasswordResetResponse implements PasswordResetResponseContract
    {
        public function toResponse($request)
        {
            return response(Response::HTTP_NO_CONTENT);
        }
    }
}