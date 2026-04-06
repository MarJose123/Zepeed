<?php

namespace App\Enums;

enum AlertRuleEvent: string
{
    case RunCompletes = 'run_completes';
    case RunFails = 'run_fails';
    case RunSkipped = 'run_skipped';
    case Any = 'any';

    public function label(): string
    {
        return match ($this) {
            self::RunCompletes => 'Run completes',
            self::RunFails     => 'Run fails',
            self::RunSkipped   => 'Run is skipped',
            self::Any          => 'Any run event',
        };
    }
}
