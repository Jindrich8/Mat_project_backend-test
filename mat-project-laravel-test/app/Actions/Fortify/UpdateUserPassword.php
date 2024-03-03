<?php

namespace App\Actions\Fortify;

use App\Dtos\Defs\Endpoints\User\Password\Errors\UserPasswordErrorDetails;
use App\Dtos\Defs\Endpoints\User\Password\Errors\UserPasswordErrorDetailsErrorData;
use App\Dtos\Defs\Types\Errors\FieldError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Exceptions\ApplicationException;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param User $user
     * @param array<string, string> $input
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        try {
            Validator::make($input, [
                'current_password' => ['required', 'string', 'current_password:web'],
                'password' => $this->passwordRules(),
            ], [
                'current_password.current_password' => __('The provided password does not match your current password.'),
            ])->validateWithBag('updatePassword');
        } catch (ValidationException $e) {
            $errorData = UserPasswordErrorDetailsErrorData::create();
            $errors = $e->validator->errors();
            if (($error = $errors->first('password'))) {
                $errorData->setPassword(
                    FieldError::create()
                        ->setMessage($error)
                );
            }
            if (($error = $errors->first('current_password'))) {
                $errorData->setCurrentPassword(
                    FieldError::create()
                        ->setMessage($error)
                );
            }

            throw new ApplicationException(
                Response::HTTP_BAD_REQUEST,
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage("Password update failed.")
                    )
                    ->setDetails(
                        UserPasswordErrorDetails::create()
                            ->setErrorData($errorData)
                    )
            );
        }

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
