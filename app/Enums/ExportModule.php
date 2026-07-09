<?php

namespace App\Enums;

enum ExportModule: string
{
    case SpeedDownload = 'speed_download';
    case SpeedUpload = 'speed_upload';
    case SpeedLatency = 'speed_latency';
    case PingResult = 'ping_result';

    public function label(): string
    {
        return match ($this) {
            self::SpeedDownload => 'Download Results',
            self::SpeedUpload   => 'Upload Results',
            self::SpeedLatency  => 'Latency Results',
            self::PingResult    => 'Ping Results',
        };
    }
}
