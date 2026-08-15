<?php

namespace App\Enums;

enum WorkflowRuleEvent: string
{
    case RunCompletes = 'run_completes';
    case RunFails = 'run_fails';
    case RunSkipped = 'run_skipped';
    case Any = 'any';
    case Ping = 'ping';

    public function label(): string
    {
        return match ($this) {
            self::RunCompletes => 'Speedtest run completes',
            self::RunFails     => 'Speedtest run fails',
            self::RunSkipped   => 'Speedtest run is skipped',
            self::Any          => 'Any speedtest event',
            self::Ping         => 'Ping result recorded',
        };
    }

    public function isPingEvent(): bool
    {
        return $this === self::Ping;
    }
}
