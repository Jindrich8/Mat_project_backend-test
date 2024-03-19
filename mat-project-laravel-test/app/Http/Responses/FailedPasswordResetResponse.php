<?php

namespace App\Http\Responses;

use App\Dtos\Defs\Endpoints\ResetPassword\Errors\ResetPasswordErrorDetails;
use App\Dtos\Defs\Endpoints\ResetPassword\Errors\ResetPasswordErrorDetailsErrorData;
use App\Dtos\Defs\Types\Errors\FieldError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Exceptions\ApplicationException;
use App\Utils\DebugLogger;
use Illuminate\Http\Response;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse as FailedPasswordResetResponseContract;

class FailedPasswordResetResponse implements FailedPasswordResetResponseContract
{
    /**
     * The response status language key.
     *
     * @var string
     */
    protected $status;

    /**
     * Create a new response instance.
     *
     * @param  string  $status
     * @return void
     */
    public function __construct(string $status)
    {
        $this->status = $status;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        DebugLogger::debug(self::class.'::toResponse', $request);
        $error = trans($this->status);
        if (!is_string($error)) {
            $error = "Invalid email address.";
        }
        return (new ApplicationException(
            Response::HTTP_BAD_REQUEST,
            ApplicationErrorInformation::create()
                ->setUserInfo(
                    UserSpecificPartOfAnError::create()
                        ->setMessage("Password reset failed.")
                )
                ->setDetails(
                    ResetPasswordErrorDetails::create()
                        ->setErrorData(
                            ResetPasswordErrorDetailsErrorData::create()
                                ->setEmail(
                                    FieldError::create()
                                        ->setMessage($error)
                                )
                        )
                )
        ))->render($request);
    }
}
