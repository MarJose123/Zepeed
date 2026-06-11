<?php

namespace App\Jobs;

use App\Enums\MaintenanceWindowType;
use App\Enums\SpeedtestServer;
use App\Events\Speedtest\SpeedtestCompletedEvent;
use App\Events\Speedtest\SpeedtestExceptionEvent;
use App\Events\Speedtest\SpeedtestSkippedEvent;
use App\Events\Speedtest\SpeedtestStartedEvent;
use App\Events\Speedtest\Test\SpeedtestTestCompletedEvent;
use App\Events\Speedtest\Test\SpeedtestTestExceptionEvent;
use App\Events\Speedtest\Test\SpeedtestTestSkippedEvent;
use App\Events\Speedtest\Test\SpeedtestTestStartedEvent;
use App\Models\MaintenanceWindow;
use App\Models\Provider;
use App\Models\SpeedResult;
use App\Models\SpeedtestTestSession;
use App\Services\AlertRuleService;
use App\Services\Speedtest\Exceptions\SpeedtestException;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunSpeedtestJob implements ShouldQueue
{
    use Queueable;

    /**
     * No job-level retries — the service layer already retries once
     * internally before throwing SpeedtestException. If it throws,
     * the test has genuinely failed — retrying the Job would just
     * hammer the provider a third time unnecessarily.
     */
    public int $tries = 1;

    /**
     * Mark the job as failed if it runs longer than 5 minutes.
     * FastcomService has a 180s timeout internally — 300s here
     * gives it enough headroom including queue overhead.
     */
    public int $timeout = 300;

    /**
     * Prevent overlapping runs for the same provider.
     * If a previous job for this provider is still running,
     * the new one is released back onto the queue after 60s.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public readonly Provider $provider,
        public bool $testOnly = false,
        public bool $runFromConsole = false,
        public readonly ?string $testSessionId = null,
    ) {}

    public function uniqueId(): string
    {
        $slug = $this->provider->slug;

        if (! $slug instanceof SpeedtestServer) {
            return 'speedtest-provider-unknown';
        }

        return "speedtest-provider-{$slug->value}";
    }

    public function handle(AlertRuleService $alertRuleService): void
    {
        $slug = $this->provider->slug;

        if (! $slug instanceof SpeedtestServer) {
            Log::error('RunSpeedtestJob: provider slug is not a SpeedtestServer enum.', [
                'provider_id' => $this->provider->id,
            ]);

            return;
        }

        // For scheduled/manual runs, skip misconfigured or disabled providers.
        // For test-only runs the user explicitly requested the test regardless
        // of enabled state, so bypass this guard entirely.
        if (! $this->testOnly && ! $this->provider->is_runnable) {
            Log::info('Speedtest job skipped — provider not runnable.', [
                'provider' => $slug->value,
            ]);

            return;
        }

        // Bail early if the test session was cancelled while the job was queued.
        if ($this->testOnly && $this->testSessionId && $this->resolveSession()?->isCancelled()) {
            Log::info('Speedtest test job abandoned — session was cancelled before pickup.', [
                'provider'        => $slug->value,
                'test_session_id' => $this->testSessionId,
            ]);

            return;
        }

        if ($this->isUnderMaintenance($slug)) {
            $skipped = SpeedResult::recordSkipped(provider: $this->provider);
            $this->provider->markSkipped();

            if ($this->testOnly && $this->testSessionId) {
                $this->resolveSession()?->markSkipped();
            }

            if (! $this->runFromConsole && $this->testOnly) {
                event(new SpeedtestTestSkippedEvent($this->provider, 'Maintenance window active.'));
            }

            if (! $this->runFromConsole && ! $this->testOnly) {
                event(new SpeedtestSkippedEvent($this->provider, 'Maintenance window active.'));
            }

            Log::info('Speedtest job skipped — maintenance window active.', [
                'provider' => $slug->value,
            ]);

            $alertRuleService->evaluate($skipped);

            return;
        }

        // Mark session running and broadcast started.
        if ($this->testOnly && $this->testSessionId) {
            $this->resolveSession()?->markRunning();
        }

        if (! $this->runFromConsole && $this->testOnly) {
            event(new SpeedtestTestStartedEvent($this->provider));
        }

        if (! $this->runFromConsole && ! $this->testOnly) {
            event(new SpeedtestStartedEvent($this->provider));
        }

        try {
            $result = $this->provider->service()->run();

            // Re-fetch cancellation status from DB — the user may have cancelled
            // while service()->run() was blocking (30-120s). If so, suppress all
            // broadcasts and do not persist anything.
            if ($this->testOnly && $this->isCancelledFresh()) {
                Log::info('Speedtest test completed but session was cancelled mid-run — suppressing broadcast.', [
                    'provider'        => $slug->value,
                    'test_session_id' => $this->testSessionId,
                ]);

                return;
            }

            if (! $this->testOnly) {
                $speedResult = SpeedResult::query()->create($result->toStorageArray());
                $this->provider->markSuccessful();
                $alertRuleService->evaluate($speedResult);
            }

            if ($this->testOnly && $this->testSessionId) {
                $this->resolveSession()?->markCompleted();
            }

            if (! $this->runFromConsole && $this->testOnly) {
                event(new SpeedtestTestCompletedEvent($this->provider, $result));
            }

            if (! $this->runFromConsole && ! $this->testOnly) {
                event(new SpeedtestCompletedEvent($this->provider, $result));
            }

            Log::info('Speedtest completed successfully.', [
                'provider'      => $slug->value,
                'download_mbps' => $result->downloadMbps,
                'upload_mbps'   => $result->uploadMbps,
                'ping_ms'       => $result->pingMs,
            ]);

        } catch (SpeedtestException $e) {

            // Same cancellation check for the failure path.
            if ($this->testOnly && $this->isCancelledFresh()) {
                Log::info('Speedtest test failed but session was cancelled mid-run — suppressing broadcast.', [
                    'provider'        => $slug->value,
                    'test_session_id' => $this->testSessionId,
                ]);

                return;
            }

            if (! $this->testOnly) {
                $failed = SpeedResult::recordFailed(provider: $this->provider, e: $e);
                $this->provider->markFailed();
                $alertRuleService->evaluate($failed);
            }

            if ($this->testOnly && $this->testSessionId) {
                $this->resolveSession()?->markFailed($e->getMessage());
            }

            if (! $this->runFromConsole && $this->testOnly) {
                event(new SpeedtestTestExceptionEvent($this->provider, $e));
            }

            if (! $this->runFromConsole && ! $this->testOnly) {
                event(new SpeedtestExceptionEvent($this->provider, $e));
            }

            Log::error('Speedtest job failed.', [
                'provider' => $slug->value,
                'reason'   => $e->reason->value,
                'message'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the test session model once per call site.
     */
    private function resolveSession(): ?SpeedtestTestSession
    {
        if (! $this->testSessionId) {
            return null;
        }

        return SpeedtestTestSession::query()->find($this->testSessionId);
    }

    /**
     * Fresh DB read — do NOT use resolveSession() here since it may be
     * cached in memory from an earlier call. We need the current value
     * after service()->run() has been blocking for up to 120 seconds.
     */
    private function isCancelledFresh(): bool
    {
        if (! $this->testSessionId) {
            return false;
        }

        return SpeedtestTestSession::query()
            ->where('id', $this->testSessionId)
            ->value('status') === 'cancelled';
    }

    private function isUnderMaintenance(SpeedtestServer $slug): bool
    {
        return MaintenanceWindow::query()
            ->active()
            ->forProvider($slug)
            ->get()
            ->contains(fn (MaintenanceWindow $window): bool => match ($window->type) {
                MaintenanceWindowType::Indefinite => true,
                MaintenanceWindowType::OneTime    => $this->isWithinOneTimeWindow($window),
                MaintenanceWindowType::Recurring  => $this->isWithinRecurringWindow($window),
            });
    }

    private function isWithinOneTimeWindow(MaintenanceWindow $window): bool
    {
        if (! $window->starts_at || ! $window->ends_at) {
            return false;
        }

        return CarbonImmutable::now()->between($window->starts_at, $window->ends_at);
    }

    private function isWithinRecurringWindow(MaintenanceWindow $window): bool
    {
        $now = CarbonImmutable::now();
        $cron = new CronExpression($window->cron_expression ?? '* * * * *');

        $lastRun = CarbonImmutable::instance(
            $cron->getPreviousRunDate($now->toDateTime())
        );
        $windowEnd = $lastRun->addMinutes($window->duration_minutes ?? 60);

        return $now->lessThanOrEqualTo($windowEnd);
    }

    public function failed(Throwable $exception): void
    {
        $this->provider->markFailed();

        if ($this->testOnly && $this->testSessionId) {
            $this->resolveSession()?->markFailed($exception->getMessage());
        }

        $slug = $this->provider->slug;

        Log::critical('RunSpeedtestJob failed at queue level.', [
            'provider'  => $slug instanceof SpeedtestServer ? $slug->value : 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }
}
