<?php

namespace App\Enums;

enum QueueWorkerName: string
{
    case Speedtest = 'speedtest';
    case Mail = 'mail';
    case Realtime = 'realtime';
    case Ping = 'ping';
    case default = 'default';

    public function label(): string
    {
        return match ($this) {
            self::Speedtest => 'Speedtest',
            self::Mail      => 'Mail',
            self::Realtime  => 'Realtime',
            self::Ping      => 'Ping',
            self::default   => 'Default',
        };
    }
}
