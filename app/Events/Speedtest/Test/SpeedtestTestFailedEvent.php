<?php

namespace App\Events\Speedtest\Test;

class SpeedtestTestFailedEvent extends SpeedtestTest
{
    public function broadcastAs(): string
    {
        return 'speedtest.test.failed';
    }
}
