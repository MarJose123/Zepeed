<?php

namespace App\Listeners\Speedtest;

use App\Events\Dashboard\DashboardRefreshEvent;
use App\Events\Dashboard\PublicDashboardRefreshEvent;
use App\Events\Speedtest\SpeedtestCompletedEvent;

class BroadcastDashboardRefreshListener
{
    /**
     * Fires two in-memory broadcast events — no I/O, no queue needed.
     * SpeedtestCompletedEvent is only dispatched for non-testOnly runs
     * (RunSpeedtestJob line 173), so no extra guard is required here.
     */
    public function handle(SpeedtestCompletedEvent $event): void
    {
        event(new DashboardRefreshEvent($event->provider));
        event(new PublicDashboardRefreshEvent($event->provider, $event->result));
    }
}
