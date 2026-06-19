<?php

namespace App\Listeners;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Laravel\Sanctum\Events\TokenAuthenticated;

class RecordApiTokenUsageListener
{
    public function __construct(
        public readonly Request $request,
    ) {}

    /**
     * Record the IP address and user agent on every authenticated API request.
     *
     * @param TokenAuthenticated $event
     *
     * @return void
     */
    public function handle(TokenAuthenticated $event): void
    {
        if (! $event->token instanceof PersonalAccessToken) {
            return;
        }

        $event->token->recordUsage($this->request);
    }
}
