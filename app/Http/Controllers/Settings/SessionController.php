<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Session\DeleteUserSession;
use App\Actions\Session\RetrieveWebUserSession;
use App\Http\Controllers\Controller;
use App\Services\InertiaNotification;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmPassword;
use Laravel\Fortify\Features;

class SessionController extends Controller implements HasMiddleware
{
    public function __construct(
        public readonly StatefulGuard $guard,
    ) {}

    /**
     * @return array|Middleware[]
     */
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [new Middleware('password.confirm', only: ['edit'])]
            : [];
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Sessions', [
            'userWebSession' => resolve(RetrieveWebUserSession::class)->handle($request),
        ]);
    }

    /**
     * Log out from other browser sessions by revoking all the session.
     *
     * @param Request $request
     *
     * @throws Exception
     * @throws AuthenticationException
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request): RedirectResponse
    {

        $confirmed = resolve(ConfirmPassword::class)(
            $this->guard, $request->user(), $request->input('password')
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        resolve(DeleteUserSession::class)->handle($request);

        InertiaNotification::make()
            ->success()
            ->title('Sessions revoked')
            ->message('The sessions have been revoke successfully.')
            ->send();

        return back();

    }

    /**
     * Revoke specific session and Log out from other browser sessions.
     *
     * @param Request $request
     *
     * @throws Exception
     * @throws AuthenticationException
     *
     * @return RedirectResponse
     */
    public function revokeSession(Request $request): RedirectResponse
    {

        if ($request->session()->getId() === $request->input('session_id')) {
            InertiaNotification::make()
                ->error()
                ->title('Session revocation failed.')
                ->message('You cannot revoke the current session.')
                ->send();
        }

        resolve(DeleteUserSession::class)->handle($request);

        InertiaNotification::make()
            ->success()
            ->title('Sessions revoked')
            ->message('The sessions have been revoke successfully.')
            ->send();

        return back();

    }
}
