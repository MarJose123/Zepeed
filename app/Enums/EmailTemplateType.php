<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    case Speedtest = 'speedtest';
    case Ping = 'ping';

    public function label(): string
    {
        return match ($this) {
            self::Speedtest => 'Speedtest result',
            self::Ping      => 'Ping result',
        };
    }
}
