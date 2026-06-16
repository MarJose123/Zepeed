<?php

namespace App\Jobs;

use App\Models\PingTarget;
use App\Services\PingAlertService;
use App\Services\PingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunPingTestJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * No retries — if ping fails it has genuinely failed.
     * A retry would just re-ping the same host unnecessarily.
     */
    public int $tries = 1;

    /**
     * Ping runs are short (< timeout_seconds per packet × packets).
     * 120s is generous headroom for a 4-packet × 5s timeout test.
     */
    public int $timeout = 120;

    /**
     * Keep the unique lock for 2 minutes — prevents the scheduler
     * from queuing a second job for the same target while one is
     * still running or waiting in queue.
     */
    public int $uniqueFor = 120;

    public function __construct(
        public readonly PingTarget $target,
    ) {}

    public function uniqueId(): string
    {
        return "ping-target-{$this->target->id}";
    }

    public function handle(PingService $pingService, PingAlertService $alertService): void
    {
        if (! $this->target->is_enabled) {
            return;
        }

        try {
            $result = $pingService->run($this->target);
            $alertService->evaluate($this->target, $result);
        } catch (Throwable $e) {
            Log::error('RunPingTestJob: ping execution failed.', [
                'target_id' => $this->target->id,
                'host'      => $this->target->host,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('RunPingTestJob failed at queue level.', [
            'target_id' => $this->target->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
