<?php

namespace App\Listeners\Speedtest;

use App\Events\Dashboard\DashboardRefreshEvent;
use App\Events\Dashboard\PublicDashboardRefreshEvent;
use App\Events\Speedtest\SpeedtestCompletedEvent;

class BroadcastDashboardRefreshListener
{
    public function handle(SpeedtestCompletedEvent $event): void
    {
        event(new DashboardRefreshEvent($event->provider));
        event(new PublicDashboardRefreshEvent($event->provider, $event->result));
    }
}
