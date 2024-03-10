<?php

namespace App\Http\Middleware;

use App\Exceptions\AppUnathorizedException;
use App\Helpers\Database\UserHelper;
use App\TableSpecificData\UserRole;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(Request): (Response) $next
     * @param value-of<UserRole> $role
     * @return Response
     * @throws AppUnathorizedException
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next,int $role): Response
    {
        $r = UserHelper::getUser()->role;
        if($r !== $role){
            throw new AppUnathorizedException([UserRole::translateFrom($role)]);
        }
        return $next($request);
    }
}
