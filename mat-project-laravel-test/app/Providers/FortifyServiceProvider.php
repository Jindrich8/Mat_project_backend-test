<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\FailedPasswordResetLinkRequestResponse;
use App\Http\Responses\FailedPasswordResetResponse;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;
use App\Models\User;
use App\Utils\DebugLogger;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LoginViewResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->instance(LogoutResponseContract::class, new class implements LogoutResponseContract
        // {
        //     public function toResponse($request)
        //     {
        //        return (new LogoutResponse)->toResponse($request);
        //     }
        // });

        // $this->app->instance(LoginResponseContract::class, new class implements LoginResponseContract
        // {
        //     public function toResponse($request)
        //     {
        //         return (new LoginResponse())->toResponse($request);
        //     }
        // });
        // $this->app->instance(LoginViewResponse::class, new class implements LoginViewResponse
        // {
        //     public function toResponse($request)
        //     {
        //         return (new LoginResponse())->toResponse($request);
        //     }
        // });

        // $this->app->singleton(
        //     \Laravel\Fortify\Http\Responses\LoginResponse::class,
        //     \App\Http\Responses\LoginResponse::class
        // );
        // $this->app->singleton(
        //     \Laravel\Fortify\Contracts\LogoutResponse::class,
        //     \App\Http\Responses\LogoutResponse::class
        // );
        // $this->app->singleton(
        //     \Laravel\Fortify\Contracts\RegisterResponse::class,
        //     \App\Http\Responses\RegisterResponse::class
        // );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
     {
    //     $this->app->instance(LogoutResponseContract::class, new class implements LogoutResponseContract
    //     {
    //         public function toResponse($request)
    //         {
    //             return (new LogoutResponse)->toResponse($request);
    //         }
    //     });

    //     $this->app->instance(LoginResponseContract::class, new class implements LoginResponseContract
    //     {
    //         public function toResponse($request)
    //         {
    //             return (new LoginResponse())->toResponse($request);
    //         }
    //     });
    //     $this->app->instance(LoginViewResponse::class, new class implements LoginViewResponse
    //     {
    //         public function toResponse($request)
    //         {
    //             return (new LoginResponse())->toResponse($request);
    //         }
    //     });

        $this->app->singleton(
            \Laravel\Fortify\Http\Responses\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
        $this->app->singleton(
            \Laravel\Fortify\Contracts\RegisterResponse::class,
            \App\Http\Responses\RegisterResponse::class
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse::class,
            SuccessfulPasswordResetLinkRequestResponse::class
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse::class,
            FailedPasswordResetLinkRequestResponse::class
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\PasswordResetResponse::class,
            PasswordResetResponse::class
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\FailedPasswordResetResponse::class,
            FailedPasswordResetResponse::class
        );

        ResetPassword::createUrlUsing(function(mixed $notifiable,string $token){
            return env("FRONTEND_URL","APP_URL")."/reset-passwor/$token?email=".$notifiable->getEmailForPasswordReset();
        });
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (
                $user &&
                Hash::check($request->password, $user->password)
            ) {
                return $user;
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
