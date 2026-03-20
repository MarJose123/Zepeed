<?php

namespace App\Actions\Session;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class DeleteUserSession
{
    public function __construct(private StatefulGuard $guard) {}

    /**
     * @throws AuthenticationException
     */
    public function handle(Request $request): void
    {

        if ($request->has('session_id')) {
            $this->revokeSession($request);
            event(new OtherDeviceLogout(auth()->getDefaultDriver(), $request->user()));
        } else {
            $this->guard->logoutOtherDevices($request->input('password'));

            $this->deleteOtherSessionRecords($request);
        }

    }

    /**
     * Delete the other browser session records from storage.
     *
     * @param Request $request
     *
     * @return void
     */
    private function deleteOtherSessionRecords(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }

    private function revokeSession(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '=', $request->input('session_id'))
            ->delete();
    }
}
