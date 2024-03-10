<?php

namespace App\Exceptions;


use App\Utils\ExceptionUtils;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use \Illuminate\Http\Request;
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
