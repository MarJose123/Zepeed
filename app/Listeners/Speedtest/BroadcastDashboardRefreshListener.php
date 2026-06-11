<?php

namespace App\Listeners\Speedtest;

use App\Events\Dashboard\DashboardRefreshEvent;
use App\Events\Dashboard\PublicDashboardRefreshEvent;
use App\Events\Speedtest\SpeedtestCompletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastDashboardRefreshListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * SpeedtestCompletedEvent is only ever dispatched for non-testOnly
     * runs (see RunSpeedtestJob line 172), so no extra guard is needed.
     */
    public function handle(SpeedtestCompletedEvent $event): void
    {
        event(new DashboardRefreshEvent($event->provider));
        event(new PublicDashboardRefreshEvent($event->provider, $event->result));
    }
}
