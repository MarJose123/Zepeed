<?php

namespace App\Events\Dashboard;

use App\Models\Provider;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardRefreshEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Provider $provider,
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('dashboard');
    }

    public function broadcastAs(): string
    {
        return 'dashboard.refresh';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'provider_slug' => $this->provider->slug->value,
        ];
    }
}
