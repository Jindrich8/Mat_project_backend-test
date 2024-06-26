<?php

namespace App\Utils {

    use App\Dtos\Defs\Endpoints\Login\Errors\LoginErrorDetails;
    use App\Dtos\Defs\Endpoints\Login\Errors\LoginErrorDetailsErrorData;
    use App\Dtos\Defs\Errors\Access\UnathenticatedError;
    use App\Dtos\Defs\Errors\CSRFTokenMismatchError;
    use App\Dtos\Defs\Types\Errors\FieldError;
    use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
    use App\Dtos\Errors\ApplicationErrorInformation;
    use App\Exceptions\ApplicationException;
    use App\Exceptions\AppUnathorizedException;
    use App\Exceptions\InternalException;
    use Illuminate\Auth\AuthenticationException;
    use Swaggest\JsonSchema\InvalidValue;
    use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
    use Illuminate\Database\Eloquent\ModelNotFoundException;
    use Illuminate\Http\Request as HttpRequest;
    use Illuminate\Http\Response;
    use \Illuminate\Http\Request;
    use Illuminate\Session\TokenMismatchException;
    use Illuminate\Support\ItemNotFoundException;
    use Illuminate\Validation\UnauthorizedException;
    use Throwable;
    use App\Utils\DebugLogger;
    use Illuminate\Support\Str;

    class ExceptionUtils
    {
        /**
         * @throws InvalidValue
         */
        public static function renderModelNotFoundException(ModelNotFoundException $e, HttpRequest $request): Response
        {
                $model = $e->getModel();
                $model = Str::ucfirst(Str::afterLast($model, "\\"));
                $modelId = Utils::tryGetFirstArrayValue($e->getIds());
                return (new ApplicationException(
                    userStatus: HttpFoundationResponse::HTTP_NOT_FOUND,
                    userResponse: ApplicationErrorInformation::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage($model . ($modelId ? " with id '$modelId'" : "") . " not found")
                                ->setDescription($model . " does not exist or you do not have permissions to access it.")
                        )
                ))->render($request);
        }

        /**
         * @throws InvalidValue
         */
        public static function renderException(Throwable $e, Request $request) {
            DebugLogger::log("Rendering  '" . get_debug_type($e) . "'", $e);
            if($e instanceof ApplicationException){
               // DebugLogger::debug("ApplicationException",[DtoUtils::exportDto($e->getErrorResponse())]);
                return $e->render($request);
            }
            if ($e instanceof AuthenticationException) {
                return (new ApplicationException(
                    HttpFoundationResponse::HTTP_UNAUTHORIZED,
                    ApplicationErrorInformation::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage("You are not authenticated.")
                                ->setDescription("You need to authenticate yourself to be able to do this action.")
                        )
                        ->setDetails(UnathenticatedError::create())
                ))->render($request);
            }
            if($e instanceof UnauthorizedException){
                return (new AppUnathorizedException())
                ->render($request);
            }
            if($e instanceof TokenMismatchException){
                $message = $e->getMessage();
                if(preg_match('/(?<!\\w)CSRF(?!\\w)/',$message)){
                    return (new ApplicationException(419,
                    ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                    ->setMessage($message)
                    )
                    ->setDetails(CSRFTokenMismatchError::create())
                    ))->render($request);
                }
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $errors = $e->validator->errors();
             //   DebugLogger::debug("Handler ValidationException: ", ['route' => $request->route(),'errors'=>$errors->all()]);
                $uri = $request->route()?->uri;
                if ($uri === 'api/login') {
                    $data = LoginErrorDetailsErrorData::create();
                    $emailError = $errors->first('email');
                    if ($emailError) {
                        $data->setEmail(
                            FieldError::create()
                                ->setMessage($emailError)
                        );
                    }
                    $passwordError = $errors->first('password');
                    if ($passwordError) {
                        $data->setPassword(
                            FieldError::create()
                                ->setMessage($passwordError)
                        );
                    }

                    return (new ApplicationException(
                        HttpFoundationResponse::HTTP_BAD_REQUEST,
                        ApplicationErrorInformation::create()
                            ->setUserInfo(
                                UserSpecificPartOfAnError::create()
                                    ->setMessage("Login failed.")
                            )
                            ->setDetails(
                                LoginErrorDetails::create()
                                    ->setErrorData($data)
                            )
                    ))->render($request);
                }

            }

            $prevE = $e->getPrevious();
            if ($e instanceof ModelNotFoundException) {
                return self::renderModelNotFoundException($e, $request);
            }
            if ($prevE instanceof ModelNotFoundException) {
                return self::renderModelNotFoundException($prevE, $request);
            }

            if ($e instanceof ItemNotFoundException) {
                return (new ApplicationException(
                    userStatus: HttpFoundationResponse::HTTP_NOT_FOUND,
                    userResponse: ApplicationErrorInformation::create()
                        ->setUserInfo(
                            UserSpecificPartOfAnError::create()
                                ->setMessage($e->getMessage())
                        )
                ))->render($request);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $e->getStatusCode();
                if ($status !== HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR) {
                    return (new ApplicationException(
                        userStatus: $status,
                        userResponse: ApplicationErrorInformation::create()
                            ->setUserInfo(
                                UserSpecificPartOfAnError::create()
                                    ->setMessage($e->getMessage())
                            )
                    ))->render($request);
                }
            }

            return (new InternalException(
                message: $e->getMessage(),
                previous: $e
            ))
                ->render($request);
        }

        public static function isRenderable(Throwable $e):bool{
            return method_exists($e,'render');
        }

        public static function tryRender(Throwable $e,Request $request):Response|null{
            if(self::isRenderable($e)){
                try{
                   $response = $e->{'render'}($request);
                   if($response instanceof Response){
                    return $response;
                   }
                }
                catch(Throwable $exc){

                }
            }
            return null;
        }
    }
}
