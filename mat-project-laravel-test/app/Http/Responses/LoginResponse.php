<?php
namespace App\Http\Responses;

use Exception;
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
        report(new Exception("LoginResponse::toResponse"));
        throw new Exception("LoginResponse::toResponse");

dd('LoginResponse.toResponse');
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