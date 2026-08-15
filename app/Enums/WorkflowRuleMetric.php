<?php

namespace App\Enums;

enum WorkflowRuleMetric: string
{
    case Status = 'status';
    case DownloadMbps = 'download_mbps';
    case UploadMbps = 'upload_mbps';
    case PingMs = 'ping_ms';
    case JitterMs = 'jitter_ms';
    case PacketLoss = 'packet_loss';
    case LatencyAvg = 'latency_avg';
    case LatencyMax = 'latency_max';
    case ConsecutiveFailures = 'consecutive_failures';

    public function label(): string
    {
        return match ($this) {
            self::Status              => 'Status',
            self::DownloadMbps        => 'Download Mbps',
            self::UploadMbps          => 'Upload Mbps',
            self::PingMs              => 'Ping ms',
            self::JitterMs            => 'Jitter ms',
            self::PacketLoss          => 'Packet loss %',
            self::LatencyAvg          => 'Latency (avg)',
            self::LatencyMax          => 'Latency (max)',
            self::ConsecutiveFailures => 'Consecutive Failures',
        };
    }

    public function isNumeric(): bool
    {
        return match ($this) {
            self::Status => false,
            default      => true,
        };
    }

    public function isPingMetric(): bool
    {
        return match ($this) {
            self::LatencyAvg, self::LatencyMax, self::PacketLoss, self::ConsecutiveFailures => true,
            default                                                                         => false,
        };
    }

    /**
     * Whether this metric is exclusive to ping rules (packet_loss is valid
     * for both speedtest and ping rules).
     */
    public function isPingOnlyMetric(): bool
    {
        return match ($this) {
            self::LatencyAvg, self::LatencyMax, self::ConsecutiveFailures => true,
            default                                                       => false,
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::PingMs, self::JitterMs, self::LatencyAvg, self::LatencyMax => 'ms',
            self::PacketLoss                                                 => '%',
            self::ConsecutiveFailures                                        => 'failures',
            default                                                          => '',
        };
    }
}
