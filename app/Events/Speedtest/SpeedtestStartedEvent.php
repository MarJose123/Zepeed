<?php

namespace App\Events\Speedtest;

namespace App\Events\Speedtest;

class SpeedtestStartedEvent extends Speedtest
{
    public function broadcastAs(): string
    {
        return 'speedtest.started';
    }
}
