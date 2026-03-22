<?php

namespace App\Http\Resources\Account\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use Override;

/**
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @param Request $request
     *
     * @return array
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'data'        => $this->data,
            'read_at'     => $this->read_at?->toIso8601String(),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
