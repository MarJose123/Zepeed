<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationController extends Controller
{
    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function dismiss(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_if($notification->notifiable_id !== $user->id, 403);

        $notification->delete();

        return back();
    }
}
