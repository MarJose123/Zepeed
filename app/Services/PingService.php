<?php

namespace App\Services;

use App\Enums\PingResultStatus;
use App\Models\PingResult;
use App\Models\PingTarget;
use Carbon\CarbonImmutable;
use Spatie\Ping\Enums\PingError;
use Spatie\Ping\Ping;

class PingService
{
    /**
     * Execute a ping test against the given target and persist the result.
     */
    public function run(PingTarget $target): PingResult
    {
        $now = CarbonImmutable::now();

        $pingResult = new Ping(
            hostname: $target->host,
            timeoutInSeconds: $target->timeout_seconds,
            count: $target->packets,
        )->run();

        $sent = $pingResult->packetsTransmitted() ?? $target->packets;
        $received = $pingResult->packetsReceived() ?? 0;
        $loss = $pingResult->packetLossPercentage();

        $status = match (true) {
            $received === 0   => PingResultStatus::Failed,
            $received < $sent => PingResultStatus::Partial,
            default           => PingResultStatus::Success,
        };

        $failureReason = null;
        if ($pingResult->hasError()) {
            $failureReason = match ($pingResult->error()) {
                PingError::HostnameNotFound => 'dns_failed',
                PingError::HostUnreachable  => 'unreachable',
                PingError::Timeout          => 'timeout',
                default                     => 'unknown',
            };
        }

        $result = PingResult::query()->create([
            'ping_target_id'      => $target->id,
            'status'              => $status,
            'packets_sent'        => $sent,
            'packets_received'    => $received,
            'packet_loss_percent' => (float) $loss,
            'min_ms'              => $pingResult->minimumTimeInMs(),
            'avg_ms'              => $pingResult->averageTimeInMs(),
            'max_ms'              => $pingResult->maximumTimeInMs(),
            'stddev_ms'           => $pingResult->standardDeviationTimeInMs(),
            'raw_output'          => $pingResult->rawOutput(),
            'failure_reason'      => $failureReason,
            'measured_at'         => $now,
            'created_at'          => $now,
        ]);

        $target->syncFromResult($result);

        return $result;
    }
}
