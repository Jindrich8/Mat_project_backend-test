<?php

namespace App\Actions\Fortify;

use App\Dtos\Defs\Endpoints\User\ProfileInformation\Errors\UserProfileInformationErrorDetails;
use App\Dtos\Defs\Endpoints\User\ProfileInformation\Errors\UserProfileInformationErrorDetailsErrorData;
use App\Dtos\Defs\Types\Errors\FieldError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Exceptions\ApplicationException;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param User $user
     * @param array<string, string> $input
     * @throws ApplicationException
     */
    public function update(User $user, array $input): void
    {
        try{
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ])->validateWithBag('updateProfileInformation');
        }
        catch(ValidationException $e){
            $errorData = UserProfileInformationErrorDetailsErrorData::create();
            $errors = $e->validator->errors();
            if (($error = $errors->first('name'))) {
                $errorData->setName(
                    FieldError::create()
                        ->setMessage($error)
                );
            }
            if (($error = $errors->first('email'))) {
                $errorData->setEmail(
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
                        UserProfileInformationErrorDetails::create()
                            ->setErrorData($errorData)
                    )
            );
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
