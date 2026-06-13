<?php

namespace App\Enums;

enum PingStatus: string
{
    case Pending = 'pending';
    case Ok = 'ok';
    case Warn = 'warn';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Ok      => 'OK',
            self::Warn    => 'Warning',
            self::Failed  => 'Failed',
        };
    }
}
