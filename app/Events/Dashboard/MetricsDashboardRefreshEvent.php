<?php

namespace App\Events\Dashboard;

use App\Models\Provider;
use App\Services\Speedtest\Data\SpeedtestResult;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MetricsDashboardRefreshEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Provider $provider,
        public readonly SpeedtestResult $result,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('public.metrics');
    }

    public function broadcastAs(): string
    {
        return 'metrics.refresh';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'result' => [
                'provider_slug' => $this->provider->slug->value,
                'provider_name' => $this->provider->slug->label(),
                'download_mbps' => $this->result->downloadMbps,
                'upload_mbps'   => $this->result->uploadMbps,
                'ping_ms'       => $this->result->pingMs,
                'jitter_ms'     => $this->result->jitterMs,
                'measured_at'   => $this->result->measuredAt->toIso8601String(),
            ],
        ];
    }
}
