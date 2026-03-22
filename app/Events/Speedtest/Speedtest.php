<?php

namespace App\Events\Speedtest;

use App\Models\Provider;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class Speedtest implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Provider $provider,
    ) {}

    /**
     * Broadcast on a private channel per provider slug.
     * e.g. private-speedtest.speedtest, private-speedtest.librespeed
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("speedtest.{$this->provider->slug->value}");
    }

    /**
     * Data sent to the frontend with every event.
     * Each concrete event can override to add extra fields.
     */
    public function broadcastWith(): array
    {
        return [
            'provider_slug'    => $this->provider->slug->value,
            'provider_name'    => $this->provider->slug->label(),
            'last_run_at'      => $this->provider->last_run_at?->toIso8601String(),
            'last_run_status'  => $this->provider->last_run_status,
            'status_badge'     => $this->provider->status_badge,
            'is_healthy'       => $this->provider->is_healthy,
            'is_runnable'      => $this->provider->is_runnable,
        ];
    }
}
