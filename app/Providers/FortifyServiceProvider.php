<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\EmailVerificationNotificationSentResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Services\InertiaNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Override;

class FortifyServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(PasswordResetResponseContract::class, PasswordResetResponse::class);
        $this->app->singleton(EmailVerificationNotificationSentResponseContract::class, EmailVerificationNotificationSentResponse::class);
    }

    public function boot(): void
    {
        self::configureActions();
        self::configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private static function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private static function configureViews(): void
    {
        Fortify::loginView(static fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister'      => Features::enabled(Features::registration()),
        ]));

        Fortify::registerView(static fn () => Inertia::render('auth/Register'));

        Fortify::requestPasswordResetLinkView(static fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(static fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::verifyEmailView(static fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(static fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(static fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', static fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        RateLimiter::for('login', static function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey)
                ->response(static function () use ($throttleKey) {
                    $seconds = RateLimiter::availableIn($throttleKey);
                    InertiaNotification::make()
                        ->error()
                        ->title('Too many login attempts.')
                        ->message("Too many requests. Please try again in {$seconds} seconds.")
                        ->send();

                    return back()->withHeaders([
                        'Retry-After'   => $seconds,
                        'X-Status-Code' => 429,
                        'X-Status'      => 'Too Many Requests',
                    ]);
                });
        });

        RateLimiter::for('change-password', static function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower('change-password|' . $request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(6)->by($throttleKey)
                ->response(static function () use ($throttleKey) {
                    $seconds = RateLimiter::availableIn($throttleKey);
                    InertiaNotification::make()
                        ->error()
                        ->title('Too many changes password attempts.')
                        ->message("Too many requests. Please try again in {$seconds} seconds.")
                        ->send();

                    return back()->withHeaders([
                        'Retry-After'   => $seconds,
                        'X-Status-Code' => 429,
                        'X-Status'      => 'Too Many Requests',
                    ]);
                });
        });

        RateLimiter::for('verification', static function (Request $request) {
            $userName = Fortify::username();
            $throttleKey = Str::transliterate(Str::lower('verification|' . $request->user()->{$userName}) . '|' . $request->user()->id . '|' . $request->ip());

            return Limit::perMinute(2)->by($throttleKey)
                ->response(static function () use ($throttleKey) {
                    $seconds = RateLimiter::availableIn($throttleKey);
                    InertiaNotification::make()
                        ->error()
                        ->title('Too many email verification attempts.')
                        ->message('Too many requests. Please try again later.')
                        ->send();

                    return back()->withHeaders([
                        'Retry-After'   => $seconds,
                        'X-Status-Code' => 429,
                        'X-Status'      => 'Too Many Requests',
                    ]);
                });
        });
    }
}
