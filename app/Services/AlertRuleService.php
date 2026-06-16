<?php

namespace App\Services;

use App\Enums\AlertRuleEvent;
use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use App\Models\SpeedResult;
use App\Services\Speedtest\Exceptions\SpeedtestFailureReason;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AlertRuleService
{
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    /**
     * Evaluate all active alert rules against a completed SpeedResult.
     * Called after every speedtest run.
     */
    public function evaluate(SpeedResult $result): void
    {
        $rules = AlertRule::query()
            ->where('is_active', true)
            ->with(['conditions', 'actions.mailProvider', 'actions.emailTemplate', 'actions.webhook'])
            ->get();

        foreach ($rules as $rule) {
            try {
                $this->evaluateRule($rule, $result);
            } catch (Throwable $e) {
                Log::error("AlertRuleService: failed evaluating rule [{$rule->id}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function evaluateRule(AlertRule $rule, SpeedResult $result): void
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

    private static function matchesEvent(AlertRule $rule, SpeedResult $result): bool
    {
        return match ($rule->event) {
            AlertRuleEvent::Any          => true,
            AlertRuleEvent::RunCompletes => in_array($result->status, ['success', 'failed']),
            AlertRuleEvent::RunFails     => $result->status === 'failed',
            AlertRuleEvent::RunSkipped   => $result->status === 'skipped',
        };
    }

    private function fire(AlertRule $rule, SpeedResult $result): void
    {
        $mergeData = self::buildMergeData($result);

        /** @var Collection<int, AlertRuleAction> $actions */
        $actions = $rule->actions;
        foreach ($actions as $action) {
            try {
                match ($action->type) {
                    'email'   => $this->fireEmail($action, $mergeData),
                    'webhook' => $this->fireWebhook($action, $result),
                };
            } catch (Throwable $e) {
                Log::error("AlertRuleService: action [{$action->id}] failed", [
                    'type'  => $action->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Record trigger time for cooldown
        $rule->update(['last_triggered_at' => now()]);
    }

    /**
     * @param array<string, mixed> $mergeData
     */
    private function fireEmail(AlertRuleAction $action, array $mergeData): void
    {
        if (! $action->emailTemplate || ! $action->recipient_email) {
            return;
        }

        $template = $action->emailTemplate;
        $subject = $template->renderSubject($mergeData);
        $body = $template->renderBody($mergeData);

        // Use the specific mail provider if set, otherwise use the failover chain
        $mailer = $action->mail_provider_id
            ? Mail::mailer($action->mail_provider_id)
            : Mail::mailer('zepeed_failover');

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

    private function fireWebhook(AlertRuleAction $action, SpeedResult $result): void
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
