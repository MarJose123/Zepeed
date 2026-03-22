<?php

namespace App\Events\Speedtest\Test;

use App\Models\Provider;
use Override;

class SpeedtestTestSkippedEvent extends SpeedtestTest
{
    public function __construct(
        Provider $provider,
        public readonly string $reason = 'Maintenance window active.',
    ) {
        parent::__construct($provider);
    }

    public function broadcastAs(): string
    {
        return 'speedtest.test.skipped';
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'reason' => $this->reason,
        ]);
    }
}
