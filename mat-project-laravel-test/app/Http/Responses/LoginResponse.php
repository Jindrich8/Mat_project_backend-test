<?php
namespace App\Http\Responses;

use App\Dtos\Defs\Endpoints\Login\LoginResponse as LoginLoginResponse;
use App\Helpers\Database\UserHelper;
use App\Helpers\ResponseHelper;
use App\Utils\DebugUtils;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\Console\Output\ConsoleOutput;

class LoginResponse implements LoginResponseContract
{

    /**
     * @param  $request
     * @return mixed
     * @throws Exception
     */
    public function toResponse($request)
    {
       $user = Auth::getUser();
       DebugUtils::log("LoginResponse");
        Log::info("LoginResponse");
       return ResponseHelper::success(
        LoginLoginResponse::create()
       ->setName($user->name)
       ->setEmail($user->email)
    );
       
        // replace this with your own code
        // the user can be located with Auth facade
//         $output = new ConsoleOutput(2);
//         report(new Exception("LoginResponse::toResponse"));
//         throw new Exception("LoginResponse::toResponse");

// dd('LoginResponse.toResponse');
// $output->writeln('LoginResponse.toResponse');
//             echo "LoginResponse::toResponse";
//         return response()->json([
//             'two_factor' => false,
//         'user'=> [
//             'name' => Auth::user()->name,
//             'email'=>Auth::user()->email
//             ]
//         ]
//         );
     }

}
