<?php

namespace App\Enums;

enum AlertRuleMetric: string
{
    case Status = 'status';
    case DownloadMbps = 'download_mbps';
    case UploadMbps = 'upload_mbps';
    case PingMs = 'ping_ms';
    case JitterMs = 'jitter_ms';
    case PacketLoss = 'packet_loss';

    public function label(): string
    {
        return match ($this) {
            self::Status       => 'Status',
            self::DownloadMbps => 'Download Mbps',
            self::UploadMbps   => 'Upload Mbps',
            self::PingMs       => 'Ping ms',
            self::JitterMs     => 'Jitter ms',
            self::PacketLoss   => 'Packet loss %',
        };
    }

    public function isNumeric(): bool
    {
        return match ($this) {
            self::Status => false,
            default      => true,
        };
    }
}
