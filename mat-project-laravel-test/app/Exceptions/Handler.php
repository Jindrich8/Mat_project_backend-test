<?php

namespace App\Exceptions;


use App\Dtos\Errors\ApplicationErrorInformation;
use App\Utils\ExceptionUtils;
use App\Utils\Utils;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Str;
use \Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**T
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function(Throwable $e,Request $request){
            return ExceptionUtils::renderException(
                e:$e, 
                request:$request
            );
        });
    }
}
