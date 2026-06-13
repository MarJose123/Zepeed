<?php

namespace App\Jobs;

use App\Models\PingTarget;
use App\Services\PingAlertService;
use App\Services\PingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunPingTestJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 60;
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
            Log::error('RunPingTestJob: ping failed.', [
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
