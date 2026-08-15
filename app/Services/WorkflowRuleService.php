<?php

namespace App\Services;

use App\Enums\WorkflowRuleEvent;
use App\Models\PingResult;
use App\Models\PingTarget;
use App\Models\SpeedResult;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Models\WorkflowRuleCondition;
use App\Services\Speedtest\Exceptions\SpeedtestFailureReason;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Throwable;

class WorkflowRuleService
{
    public function __construct(
        private readonly WebhookService $webhookService,
        private readonly AppriseService $appriseService,
        private readonly MailProviderService $mailProviderService,
    ) {}

    /**
     * Evaluate all active speedtest workflow rules against a completed
     * SpeedResult. Called after every speedtest run.
     */
    public function evaluate(SpeedResult $result): void
    {
        $rules = WorkflowRule::query()
            ->where('event', '!=', WorkflowRuleEvent::Ping->value)
            ->where('is_active', true)
            ->with(['conditions', 'actions.mailProvider', 'actions.emailTemplate', 'actions.webhook', 'actions.apprise'])
            ->get();

        foreach ($rules as $rule) {
            try {
                $this->evaluateRule($rule, $result);
            } catch (Throwable $e) {
                Log::error("WorkflowRuleService: failed evaluating rule [{$rule->id}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Evaluate all active ping workflow rules for a target after a ping
     * result is stored. Called after every ping test.
     */
    public function evaluatePing(PingTarget $target, PingResult $result): void
    {
        $rules = WorkflowRule::query()
            ->where('event', WorkflowRuleEvent::Ping->value)
            ->where('ping_target_id', $target->id)
            ->where('is_active', true)
            ->with(['conditions', 'actions.mailProvider', 'actions.emailTemplate', 'actions.webhook', 'actions.apprise'])
            ->get();

        foreach ($rules as $rule) {
            try {
                $this->evaluatePingRule($rule, $target, $result);
            } catch (Throwable $e) {
                Log::error("WorkflowRuleService: failed evaluating ping rule [{$rule->id}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function evaluateRule(WorkflowRule $rule, SpeedResult $result): void
    {
        // Check provider filter
        if ($rule->provider_slug && $rule->provider_slug !== $result->provider_slug) {
            return;
        }

        // Check event filter
        if (! self::matchesEvent($rule, $result)) {
            return;
        }

        // Check cooldown
        if ($rule->isInCooldown()) {
            return;
        }

        // Evaluate conditions
        /** @var Collection $conditions */
        $conditions = $rule->conditions;
        if ($conditions->isEmpty()) {
            // No conditions = always fires on matching event
            $this->fire($rule, $result);

            return;
        }

        $results = $conditions->map(
            static fn ($condition) => $condition->evaluate($result)
        );

        $passes = $rule->condition_operator === 'or'
            ? $results->contains(true)
            : $results->every(static fn ($r) => $r === true);

        if ($passes) {
            $this->fire($rule, $result);
        }
    }

    private function evaluatePingRule(WorkflowRule $rule, PingTarget $target, PingResult $result): void
    {
        if ($rule->isInCooldown()) {
            return;
        }

        /** @var Collection<int, WorkflowRuleCondition> $conditions */
        $conditions = $rule->conditions()->get();

        if ($conditions->isEmpty()) {
            $this->firePing($rule, $target, $result);

            return;
        }

        $metrics = self::resolvePingMetrics($target, $rule);
        $outcomes = $conditions->map(
            static fn ($condition) => $condition->evaluatePing($metrics)
        );

        $passes = $rule->condition_operator === 'or'
            ? $outcomes->contains(true)
            : $outcomes->every(static fn (bool $r) => $r === true);

        if ($passes) {
            $this->firePing($rule, $target, $result);
        }
    }

    private static function matchesEvent(WorkflowRule $rule, SpeedResult $result): bool
    {
        return match ($rule->event) {
            WorkflowRuleEvent::Any          => true,
            WorkflowRuleEvent::RunCompletes => in_array($result->status, ['success', 'failed']),
            WorkflowRuleEvent::RunFails     => $result->status === 'failed',
            WorkflowRuleEvent::RunSkipped   => $result->status === 'skipped',
            WorkflowRuleEvent::Ping         => false,
        };
    }

    private function fire(WorkflowRule $rule, SpeedResult $result): void
    {
        $mergeData = self::buildMergeData($result);

        /** @var Collection<int, WorkflowRuleAction> $actions */
        $actions = $rule->actions;
        foreach ($actions as $action) {
            try {
                match ($action->type) {
                    'email'   => $this->fireEmail($action, $mergeData),
                    'webhook' => $this->fireWebhook($action, $result),
                    'apprise' => $this->fireApprise($action, $result),
                };
            } catch (Throwable $e) {
                Log::error("WorkflowRuleService: action [{$action->id}] failed", [
                    'type'  => $action->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Record trigger time for cooldown
        $rule->update(['last_triggered_at' => now()]);
    }

    private function firePing(WorkflowRule $rule, PingTarget $target, PingResult $result): void
    {
        $rule->update(['last_triggered_at' => now()]);

        /** @var Collection<int, WorkflowRuleAction> $actions */
        $actions = $rule->actions()->get();

        foreach ($actions as $action) {
            try {
                match ($action->type) {
                    'webhook' => $this->firePingWebhook($action, $rule, $target, $result),
                    'email'   => $this->firePingEmail($action, $rule, $target, $result),
                    'apprise' => $this->firePingApprise($action, $rule, $target, $result),
                    default   => null,
                };
            } catch (Throwable $e) {
                Log::error("WorkflowRuleService: ping action [{$action->id}] failed", [
                    'type'  => $action->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("WorkflowRuleService: ping rule [{$rule->id}] fired for target [{$target->id}].");
    }

    /**
     * @param array<string, mixed> $mergeData
     */
    private function fireEmail(WorkflowRuleAction $action, array $mergeData): void
    {
        if (! $action->emailTemplate || ! $action->recipient_email) {
            return;
        }

        $template = $action->emailTemplate;
        $subject = $template->renderSubject($mergeData);
        $body = $template->renderBody($mergeData);

        // Use the action's specific provider when registered, otherwise fall
        // back to the failover chain (and skip when no mailer is available).
        $mailer = $this->mailProviderService->mailerFor($action->mail_provider_id);

        if ($mailer === null) {
            Log::warning("WorkflowRuleService: no mailer available for action [{$action->id}] on rule [{$action->workflow_rule_id}]; skipping email to [{$action->recipient_email}].");

            return;
        }

        $mailer
            ->html($body, static function (Message $message) use ($subject, $action) {
                /** @var string $fromAddress */
                $fromAddress = $action->mailProvider->from_address
                    ?? config('mail.from.address');

                /** @var string $fromName */
                $fromName = $action->mailProvider->from_name
                    ?? config('mail.from.name');

                $message
                    ->to((string) $action->recipient_email)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });
    }

    private function fireWebhook(WorkflowRuleAction $action, SpeedResult $result): void
    {
        if (! $action->webhook) {
            return;
        }

        $this->webhookService->dispatch(
            $action->webhook,
            'speedtest.' . $result->status,
            [
                'provider_slug'   => $result->provider_slug,
                'status'          => $result->status,
                'download_mbps'   => $result->download_mbps,
                'upload_mbps'     => $result->upload_mbps,
                'ping_ms'         => $result->ping_ms,
                'jitter_ms'       => $result->jitter_ms,
                'packet_loss'     => $result->packet_loss,
                'measured_at'     => $result->measured_at->toIso8601String(),
                'failure_reason'  => $result->failure_reason,
            ],
        );
    }

    private function fireApprise(WorkflowRuleAction $action, SpeedResult $result): void
    {
        if (! $action->apprise) {
            return;
        }

        $status = $result->status;
        $type = match ($status) {
            'success' => 'success',
            'failed'  => 'failure',
            default   => 'warning',
        };

        $this->appriseService->dispatch(
            $action->apprise,
            "Zepeed: Speedtest {$status}",
            self::buildAppriseBody($result),
            ['type' => $type],
        );
    }

    /**
     * Build merge data for a ping workflow rule email.
     *
     * @return array<string, mixed>
     */
    private static function buildPingMergeData(
        WorkflowRule $rule,
        PingTarget $target,
        PingResult $result,
    ): array {
        $tz = config('app.timezone');

        return [
            // Target & result
            'target_label'        => $target->label,
            'target_host'         => $target->host,
            'status'              => $result->status->value,
            'packets_sent'        => $result->packets_sent,
            'packets_received'    => $result->packets_received,
            'packet_loss_percent' => $result->packet_loss_percent,

            // Latency
            'min_ms'    => $result->min_ms ?? '—',
            'avg_ms'    => $result->avg_ms ?? '—',
            'max_ms'    => $result->max_ms ?? '—',
            'stddev_ms' => $result->stddev_ms ?? '—',

            // Failure
            'failure_reason' => $result->failure_reason ?? '',

            // Alert context
            'rule_name'    => $rule->name,
            'triggered_at' => now()->format('d M Y H:i') . " {$tz}",

            // Links
            'dashboard_url' => url(route('dashboard')),
        ];
    }

    private function firePingEmail(
        WorkflowRuleAction $action,
        WorkflowRule $rule,
        PingTarget $target,
        PingResult $result,
    ): void {
        if (! $action->emailTemplate || ! $action->recipient_email) {
            return;
        }

        $mergeData = self::buildPingMergeData($rule, $target, $result);
        $template = $action->emailTemplate;

        $subject = $template->renderSubject($mergeData);
        $body = $template->renderBody($mergeData);

        $mailer = $this->mailProviderService->mailerFor($action->mail_provider_id);

        if ($mailer === null) {
            Log::warning("WorkflowRuleService: no mailer available for action [{$action->id}] on rule [{$rule->id}]; skipping email to [{$action->recipient_email}].");

            return;
        }

        $mailer->html(
            $body,
            static function (Message $message) use ($subject, $action): void {
                $fromAddress = $action->mailProvider->from_address
                    ?? config('mail.from.address');
                $fromName = $action->mailProvider->from_name
                    ?? config('mail.from.name');

                $message
                    ->to((string) $action->recipient_email)
                    ->subject($subject)
                    ->from((string) $fromAddress, (string) $fromName);
            },
        );
    }

    private function firePingWebhook(
        WorkflowRuleAction $action,
        WorkflowRule $rule,
        PingTarget $target,
        PingResult $result,
    ): void {
        if (! $action->webhook) {
            return;
        }

        $this->webhookService->dispatch($action->webhook, 'ping.alert', [
            'rule'         => $rule->name,
            'rule_id'      => $rule->id,
            'target'       => $target->label,
            'target_id'    => $target->id,
            'host'         => $target->host,
            'status'       => $result->status->value,
            'avg_ms'       => $result->avg_ms,
            'max_ms'       => $result->max_ms,
            'packet_loss'  => $result->packet_loss_percent,
            'triggered_at' => now()->toIso8601String(),
        ]);
    }

    private function firePingApprise(
        WorkflowRuleAction $action,
        WorkflowRule $rule,
        PingTarget $target,
        PingResult $result,
    ): void {
        if (! $action->apprise) {
            return;
        }

        $status = $result->status->value;
        $type = match ($status) {
            'success' => 'success',
            'failed'  => 'failure',
            default   => 'warning',
        };

        $this->appriseService->dispatch(
            $action->apprise,
            "Zepeed: Ping alert — {$rule->name}",
            self::buildPingAppriseBody($rule, $target, $result),
            ['type' => $type],
        );
    }

    /**
     * Plain-text summary of a ping alert for Apprise notifications.
     */
    private static function buildPingAppriseBody(
        WorkflowRule $rule,
        PingTarget $target,
        PingResult $result,
    ): string {
        $data = self::buildPingMergeData($rule, $target, $result);

        return collect([
            "Rule: {$data['rule_name']}",
            "Target: {$data['target_label']} ({$data['target_host']})",
            "Status: {$data['status']}",
            "Avg latency: {$data['avg_ms']} ms",
            "Max latency: {$data['max_ms']} ms",
            "Packet loss: {$data['packet_loss_percent']}%",
            $data['failure_reason'] !== '' ? "Failure reason: {$data['failure_reason']}" : null,
            "Triggered at: {$data['triggered_at']}",
            "Dashboard: {$data['dashboard_url']}",
        ])
            ->filter()
            ->implode("\n");
    }

    /**
     * Compute metric values for a target over the condition lookback window.
     *
     * @return array{avg_ms: float|null, max_ms: float|null, packet_loss: float|null, consecutive_failures: int}
     */
    private static function resolvePingMetrics(PingTarget $target, WorkflowRule $rule): array
    {
        $maxLookback = $rule->conditions()->max('lookback_minutes') ?? 5;

        $recent = PingResult::query()
            ->where('ping_target_id', $target->id)
            ->where('measured_at', '>=', now()->subMinutes((int) $maxLookback))
            ->latest('measured_at')
            ->get();

        if ($recent->isEmpty()) {
            return [
                'avg_ms'               => null,
                'max_ms'               => null,
                'packet_loss'          => null,
                'consecutive_failures' => 0,
            ];
        }

        $consecutiveFailures = 0;
        foreach ($recent as $r) {
            if ($r->status->value === 'failed') {
                $consecutiveFailures++;
            } else {
                break;
            }
        }

        return [
            'avg_ms'               => $recent->avg('avg_ms'),
            'max_ms'               => $recent->max('max_ms'),
            'packet_loss'          => $recent->avg('packet_loss_percent'),
            'consecutive_failures' => $consecutiveFailures,
        ];
    }

    /**
     * Plain-text summary of a speedtest result for Apprise notifications.
     */
    private static function buildAppriseBody(SpeedResult $result): string
    {
        $data = self::buildMergeData($result);

        return collect([
            "Provider: {$data['provider_name']}",
            "Status: {$data['status']}",
            "Download: {$data['download_mbps']} Mbps",
            "Upload: {$data['upload_mbps']} Mbps",
            "Ping: {$data['ping_ms']} ms",
            "Jitter: {$data['jitter_ms']} ms",
            "Packet loss: {$data['packet_loss']}%",
            "Measured at: {$data['measured_at']}",
            $data['failure_reason'] !== '' ? "Failure reason: {$data['failure_reason']}" : null,
            "Dashboard: {$data['dashboard_url']}",
        ])
            ->filter()
            ->implode("\n");
    }

    /**
     * @param SpeedResult $result
     *
     * @return array<string, mixed>
     */
    private static function buildMergeData(SpeedResult $result): array
    {
        $providerName = $result->provider_slug->label();
        $tz = config('app.timezone');

        return [
            'provider_name'   => $providerName,
            'status'          => $result->status,
            'download_mbps'   => $result->download_mbps !== null
                ? number_format((float) $result->download_mbps, 2)
                : '—',
            'upload_mbps'     => $result->upload_mbps !== null
                ? number_format((float) $result->upload_mbps, 2)
                : '—',
            'ping_ms'         => $result->ping_ms ?? '—',
            'jitter_ms'       => $result->jitter_ms ?? '—',
            'packet_loss'     => $result->packet_loss ?? '0',
            'measured_at'     => $result->measured_at ? "{$result->measured_at->format('d M Y H:i')} {$tz}" : '—',
            'failure_reason'  => $result->failure_reason instanceof SpeedtestFailureReason
                ? $result->failure_reason->value
                : ($result->failure_reason ?? ''),
            'failure_message' => $result->failure_message ?? '',
            'isp'             => $result->isp ?? '',
            'client_ip'       => $result->client_ip ?? '',
            'dashboard_url'   => url(route('dashboard')),
            'share_url'       => $result->share_url ?? '',
        ];
    }
}
