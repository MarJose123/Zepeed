<?php

namespace App\Services\Speedtest\Exceptions;

use App\Enums\SpeedtestServer;

enum SpeedtestFailureReason: string
{
    case Timeout = 'timeout';
    case NonZeroExit = 'non_zero_exit';
    case InvalidJson = 'invalid_json';
    case MissingField = 'missing_field';

    public function describe(SpeedtestServer $server): string
    {
        return match ($this) {
            self::Timeout      => "{$server->label()} process timed out.",
            self::NonZeroExit  => "{$server->label()} process exited with an error.",
            self::InvalidJson  => "{$server->label()} returned invalid JSON output.",
            self::MissingField => "{$server->label()} output was missing a required field.",
        };
    }
}
