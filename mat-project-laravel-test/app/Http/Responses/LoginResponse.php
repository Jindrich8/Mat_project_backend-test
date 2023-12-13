<?php
namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{

    /**
     * @param  $request
     * @return mixed
     */
    public function toResponse($request)
    {
        // replace this with your own code
        // the user can be located with Auth facade
        $output = new \Symfony\Component\Console\Output\ConsoleOutput(2);

$output->writeln('LoginResponse.toResponse');
            echo "LoginResponse::toResponse";
        return response()->json([
            'two_factor' => false,
        'user'=> [
            'name' => Auth::user()->name,
            'email'=>Auth::user()->email
            ]
        ]
    );
    }

}