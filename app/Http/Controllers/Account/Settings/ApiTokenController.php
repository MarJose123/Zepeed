<?php

namespace App\Http\Controllers\Account\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\Settings\ApiToken\StoreApiTokenRequest;
use App\Models\PersonalAccessToken;
use App\Services\InertiaNotification;
use Exception;
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

class ApiTokenController extends Controller implements HasMiddleware
{
    public function __construct(
        public readonly StatefulGuard $guard,
    ) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [new Middleware('password.confirm', only: ['index'])]
            : [];
    }

    /**
     * Display the user's API tokens.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $tokens = $request->user()
            ->tokens()
            ->latest()
            ->get()
            ->map(static fn (PersonalAccessToken $token) => [
                'id'              => $token->id,
                'name'            => $token->name,
                'last_used_at'    => $token->last_used_at?->diffForHumans(),
                'last_used_ip'    => $token->last_used_ip,
                'last_used_agent' => $token->last_used_agent,
                'expires_at'      => $token->expires_at?->toDateTimeString(),
                'is_expired'      => $token->expires_at !== null && $token->expires_at->isPast(),
                'created_at'      => $token->created_at->toDateTimeString(),
            ]);

        return Inertia::render('account/settings/ApiTokens', [
            'tokens' => $tokens,
        ]);
    }

    /**
     * Create a new API token for the authenticated user.
     *
     * @param StoreApiTokenRequest $request
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        $newToken = $request->user()->createToken(
            $request->validated('name'),
            ['*'],
            $request->filled('expires_at')
                ? now()->parse($request->validated('expires_at'))
                : null,
        );

        Inertia::flash('new_token', $newToken->plainTextToken);

        InertiaNotification::make()
            ->success()
            ->title('API Token Created')
            ->message('Your new API token has been generated. Copy it now — it won\'t be shown again.')
            ->send();

        return back();
    }

    /**
     * Revoke a single API token belonging to the authenticated user.
     *
     * @param Request             $request
     * @param PersonalAccessToken $token
     *
     * @throws Exception
     * @throws ValidationException
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        $confirmed = resolve(ConfirmPassword::class)(
            $this->guard, $request->user(), $request->input('password'),
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        abort_if(
            (int) $token->tokenable_id !== $request->user()->id,
            403,
        );

        $tokenName = $token->name;
        $token->delete();

        InertiaNotification::make()
            ->success()
            ->title('Token Revoked')
            ->message("The token \"{$tokenName}\" has been revoked.")
            ->send();

        return back();
    }

    /**
     * Revoke all API tokens belonging to the authenticated user.
     *
     * @param Request $request
     *
     * @throws Exception
     * @throws ValidationException
     *
     * @return RedirectResponse
     */
    public function revokeAll(Request $request): RedirectResponse
    {
        $confirmed = resolve(ConfirmPassword::class)(
            $this->guard, $request->user(), $request->input('password'),
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }

        $count = $request->user()->tokens()->delete();

        InertiaNotification::make()
            ->success()
            ->title('All Tokens Revoked')
            ->message("Successfully revoked {$count} token(s).")
            ->send();

        return back();
    }
}
