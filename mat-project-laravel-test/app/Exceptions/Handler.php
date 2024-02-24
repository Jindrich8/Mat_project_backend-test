<?php

namespace App\Exceptions;

use App\Dtos\Defs\Errors\Access\UnathenticatedError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Utils\DebugUtils;
use App\Utils\ExceptionUtils;
use App\Utils\Utils;
use Exception;
use Http;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\Str;
use Request;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        $renderModelNotFoundException = function (ModelNotFoundException $e,HttpRequest $request){
            $model = $e->getModel();
            $model = Str::ucfirst(Str::afterLast($model,"\\"));
            $modelId = Utils::tryGetFirstArrayValue($e->getIds());
            return (new ApplicationException(
                userStatus:HttpFoundationResponse::HTTP_NOT_FOUND,
                userResponse:ApplicationErrorInformation::create()
               ->setUserInfo(
                UserSpecificPartOfAnError::create()
               ->setMessage($model.($modelId ?" with id '$modelId'" : "")." not found")
               ->setDescription($model." does not exist or you do not have permissions to access it.")
               )
               ))->render($request);
        };

        $this->renderable(function(Throwable $e,HttpRequest $request)use($renderModelNotFoundException){
            DebugUtils::log("Rendering  '".get_debug_type($e)."'",$e);
            if($e instanceof AuthenticationException){
                new ApplicationException(
                    HttpFoundationResponse::HTTP_UNAUTHORIZED,
                ApplicationErrorInformation::create()
                ->setUserInfo(
                    UserSpecificPartOfAnError::create()
                    ->setMessage("You are not authenticated.")
                    ->setDescription("You need to authenticate yourself to be able to do this action.")
                )
                ->setDetails(UnathenticatedError::create())
            );
            }
            
            $prevE = $e->getPrevious();
            if(($response = ExceptionUtils::tryRender($e,$request)) 
            || ($response = ExceptionUtils::tryRender($prevE,$request))){
                return $response;
            }
            if($e instanceof ModelNotFoundException){
                return $renderModelNotFoundException($e,$request);
            }
            if($prevE instanceof ModelNotFoundException){
                return $renderModelNotFoundException($prevE,$request);
            }
           
            if($e instanceof ItemNotFoundException){
                return (new ApplicationException(
                    userStatus:HttpFoundationResponse::HTTP_NOT_FOUND,
                    userResponse:ApplicationErrorInformation::create()
                   ->setUserInfo(
                    UserSpecificPartOfAnError::create()
                   ->setMessage($e->getMessage())
                   )
                   ))->render($request);
            }

            if($e instanceof HttpExceptionInterface){
                $status = $e->getStatusCode();
                 if($status !== HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR){
                return (new ApplicationException(
                 userStatus:$status,
                 userResponse:ApplicationErrorInformation::create()
                ->setUserInfo(
                 UserSpecificPartOfAnError::create()
                ->setMessage($e->getMessage())
                )
                ))->render($request);
                 }
             }
             
               return (new InternalException(
                message:$e->getMessage(),
                previous:$e
                ))
               ->render($request);
        });
    }
}
