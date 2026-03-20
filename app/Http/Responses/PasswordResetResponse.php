<?php

namespace App\Http\Responses;

use App\Services\InertiaNotification;
use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetResponse implements PasswordResetResponseContract
{
    /**
     * @throws Exception
     */
    public function toResponse($request): RedirectResponse|Response
    {

        InertiaNotification::make()
            ->success()
            ->title('Password reset')
            ->message('Your password has been reset.')
            ->send();

        return to_route('login');
    }
}
