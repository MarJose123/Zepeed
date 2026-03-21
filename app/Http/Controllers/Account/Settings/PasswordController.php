<?php

namespace App\Http\Controllers\Account\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\Settings\Password\PasswordUpdateRequest;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('account/settings/Password');
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        InertiaNotification::make()
            ->success()
            ->title('Password updated')
            ->message('Password updated successfully.')
            ->send();

        return back();
    }
}
