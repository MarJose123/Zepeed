<?php

namespace App\Events\Speedtest\Test;

class SpeedtestTestStartedEvent extends SpeedtestTest
{
    public function broadcastAs(): string
    {
        return 'speedtest.test.started';
    }
}
