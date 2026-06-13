<?php

namespace App\Enums;

enum PingAlertMetric: string
{
    case LatencyAvg = 'latency_avg';
    case LatencyMax = 'latency_max';
    case PacketLoss = 'packet_loss';
    case ConsecutiveFailures = 'consecutive_failures';

    public function label(): string
    {
        return match ($this) {
            self::LatencyAvg          => 'Latency (avg)',
            self::LatencyMax          => 'Latency (max)',
            self::PacketLoss          => 'Packet Loss (%)',
            self::ConsecutiveFailures => 'Consecutive Failures',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::LatencyAvg, self::LatencyMax => 'ms',
            self::PacketLoss                   => '%',
            self::ConsecutiveFailures          => 'failures',
        };
    }
}
