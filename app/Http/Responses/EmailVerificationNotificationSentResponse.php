<?php

namespace App\Http\Responses;

use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationNotificationSentResponse implements EmailVerificationNotificationSentResponseContract
{
    public function toResponse($request): Response|RedirectResponse
    {
        InertiaNotification::make()
            ->success()
            ->title('Email verification')
            ->message('A fresh verification link has been sent to your email address.')
            ->send();

        return back();
    }
}
