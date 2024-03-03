<?php

namespace App\Http\Middleware;

use App\Dtos\Defs\Errors\Access\AlreadyAuthenticatedError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Exceptions\ApplicationException;
use App\Http\Responses\LoginResponse;
use App\Providers\RouteServiceProvider;
use App\Utils\RouteUtils;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
              if ($request->expectsJson()) {
                throw new ApplicationException(
                  HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
                ApplicationErrorInformation::create()
                ->setUserInfo(
                  UserSpecificPartOfAnError::create()
                  ->setMessage("You are already authenticated.")
                  ->setDescription("Action is ignored, because you are already authenticated.")
                  )
                  ->setDetails(AlreadyAuthenticatedError::create())
                );
              }
              return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
