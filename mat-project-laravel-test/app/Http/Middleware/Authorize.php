<?php

namespace App\Http\Middleware;

use App\Exceptions\AppUnathorizedException;
use App\Helpers\Database\UserHelper;
use App\Models\User;
use App\TableSpecificData\UserRole;
use App\Types\SimpleReadonlyEnumSet;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Ramsey\Collection\Set;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param value-of<UserRole> $role
     * @throws AuthenticationException
     * @throws AppUnathorizedException
     */
    public function handle(Request $request, Closure $next,int $role): Response
    {
        $r = UserHelper::getUser()->role;
        if($r !== $role){
            throw new AppUnathorizedException([$role->translate()]);
        }
        return $next($request);
    }
}
