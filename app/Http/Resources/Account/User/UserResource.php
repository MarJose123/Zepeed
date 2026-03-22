<?php

namespace App\Http\Resources\Account\User;

use App\Models\User;
use App\Services\UiAvatars;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param Request $request
     *
     * @return array
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'avatar'              => UiAvatars::make($this->name)->rounded()->get(),
            'email_verified_at'   => $this->email_verified_at,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
            'unread_count'        => $this->unreadNotifications()->count(),
            'notifications'       => NotificationResource::collection(
                $this->unreadNotifications()
                    ->latest()
                    ->take(20)
                    ->get()
            ),
        ];
    }
}
