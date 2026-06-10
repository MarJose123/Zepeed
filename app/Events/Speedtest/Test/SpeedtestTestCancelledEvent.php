<?php

namespace App\Events\Speedtest\Test;

use App\Models\Provider;
use Override;

class SpeedtestTestCancelledEvent extends SpeedtestTest
{
    public function __construct(
        Provider $provider,
        public readonly string $reason = 'Cancelled by user.',
    ) {
        parent::__construct($provider);
    }

    public function broadcastAs(): string
    {
        return 'speedtest.test.cancelled';
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'reason' => $this->reason,
        ]);
    }
}
