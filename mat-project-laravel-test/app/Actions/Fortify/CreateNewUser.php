<?php

namespace App\Actions\Fortify;

use App\Dtos\Defs\Endpoints\Register\Errors\RegisterErrorDetails;
use App\Dtos\Defs\Endpoints\Register\Errors\RegisterErrorDetailsErrorData;
use App\Dtos\Defs\Endpoints\Register\RegisterRequest;
use App\Dtos\Defs\Types\Errors\FieldError;
use App\Dtos\Defs\Types\Errors\UserSpecificPartOfAnError;
use App\Dtos\Errors\ApplicationErrorInformation;
use App\Exceptions\ApplicationException;
use App\Exceptions\InternalException;
use App\Exceptions\UnPreparedCaseException;
use App\Models\User;
use App\TableSpecificData\UserRole;
use App\Utils\DebugUtils;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     
     * @param array<string, string> $input
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Log::info("CreateNewUser - input: ",['input' => $input]);
        DebugUtils::log("CreateNewUser - input: ",['input' => $input]);
        try {
            Validator::make($input, [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class),
                ],
                'password' => $this->passwordRules(),
            ])->validate();
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            $data = RegisterErrorDetailsErrorData::create();
            if (($error = $errors->first('name'))) {
                $data->setName(
                    FieldError::create()
                        ->setMessage($error)
                );
            }

            if (($error = $errors->first('email'))) {
                $data->setEmail(
                    FieldError::create()
                        ->setMessage($error)
                );
            }
            $passwordError = $errors->first('password');
            if ($passwordError) {
                $data->setPassword(
                    FieldError::create()
                        ->setMessage($passwordError)
                );
            }

            throw new ApplicationException(
                Response::HTTP_BAD_REQUEST,
                ApplicationErrorInformation::create()
                    ->setUserInfo(
                        UserSpecificPartOfAnError::create()
                            ->setMessage("Register failed.")
                    )
                    ->setDetails(
                        RegisterErrorDetails::create()
                            ->setErrorData($data)
                    )
            );
        }
        $role = $input[RegisterRequest::ROLE] ?? null;
        Log::info("CreateNewUser - ROLE FROM INPUT!!! IS ",['ROLE' => $role]);
       $role = match($role){
        RegisterRequest::TEACHER => UserRole::TEACHER,
        null => UserRole::NONE,
        default => throw new UnPreparedCaseException(CreateNewUser::class,RegisterRequest::ROLE,$role)
        };

 Log::info("CreateNewUser: ",[
    'name' => $input['name'],
    'email' => $input['email'],
    'password' => Hash::make($input['password']),
    'role' => $role->value
]);
        DebugUtils::log("CreateNewUser: ",[
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => $role->value
        ]);
        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => $role->value
        ]);
    }
}
