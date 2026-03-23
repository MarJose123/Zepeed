<?php

namespace App\Events\Speedtest;

use App\Models\Provider;
use App\Services\Speedtest\Data\SpeedtestResult;
use Override;

class SpeedtestCompletedEvent extends Speedtest
{
    public function __construct(
        Provider $provider,
        public readonly SpeedtestResult $result,
    ) {
        parent::__construct($provider);
    }

    public function broadcastAs(): string
    {
        return 'speedtest.completed';
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'download_mbps'   => $this->result->downloadMbps,
            'upload_mbps'     => $this->result->uploadMbps,
            'ping_ms'         => $this->result->pingMs,
            'jitter_ms'       => $this->result->jitterMs,
            'server_name'     => $this->result->serverName,
            'server_location' => $this->result->serverLocation,
            'isp'             => $this->result->isp,
            'share_url'       => $this->result->shareUrl,
            'measured_at'     => $this->result->measuredAt->toIso8601String(),
        ]);
    }
}
