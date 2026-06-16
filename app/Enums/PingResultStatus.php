<?php

namespace App\Enums;

enum PingResultStatus: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::Partial => 'Partial',
            self::Failed  => 'Failed',
        };
    }
}
