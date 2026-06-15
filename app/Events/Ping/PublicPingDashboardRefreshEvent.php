<?php

namespace App\Events\Ping;

use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicPingDashboardRefreshEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PingTarget $target,
        public readonly PingResult $result,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('public.dashboard');
    }

    public function broadcastAs(): string
    {
        return 'ping.result.completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'result' => [
                'target_id'           => $this->target->id,
                'target_label'        => $this->target->label,
                'target_host'         => $this->target->host,
                'status'              => $this->result->status->value,
                'avg_ms'              => $this->result->avg_ms,
                'packet_loss_percent' => $this->result->packet_loss_percent,
                'measured_at'         => $this->result->measured_at->toIso8601String(),
            ],
        ];
    }
}
