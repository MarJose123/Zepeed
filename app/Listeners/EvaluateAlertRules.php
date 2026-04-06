<?php

namespace App\Listeners;

use App\Events\Speedtest\SpeedtestCompletedEvent as SpeedtestCompleted;
use App\Events\Speedtest\SpeedtestFailedEvent as SpeedtestFailed;
use App\Events\Speedtest\SpeedtestSkippedEvent as SpeedtestSkipped;
use App\Services\AlertRuleService;

class EvaluateAlertRules
{
    public function __construct(
        private readonly AlertRuleService $service,
    ) {}

    public function handle(
        SpeedtestCompleted|SpeedtestFailed|SpeedtestSkipped $event,
    ): void {
        if ($event->result) {
            $this->service->evaluate($event->result);
        }
    }
}
