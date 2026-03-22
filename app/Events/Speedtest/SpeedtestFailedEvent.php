<?php

namespace App\Events\Speedtest;

class SpeedtestFailedEvent extends Speedtest
{
    public function broadcastAs(): string
    {
        return 'speedtest.failed';
    }
}
