<?php

namespace App\Services;

use App\Models\PingAlertAction;
use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PingAlertService
{
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    /**
     * Evaluate all active alert rules for a target after a ping result is stored.
     */
    public function evaluate(PingTarget $target, PingResult $result): void
    {
        $rules = PingAlertRule::query()
            ->where('ping_target_id', $target->id)
            ->where('is_active', true)
            ->with([
                'conditions',
                'actions.mailProvider',
                'actions.emailTemplate',
                'actions.webhook',
            ])
            ->get();

        foreach ($rules as $rule) {
            try {
                $this->evaluateRule($rule, $target, $result);
            } catch (Throwable $e) {
                Log::error("PingAlertService: failed evaluating rule [{$rule->id}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function evaluateRule(PingAlertRule $rule, PingTarget $target, PingResult $result): void
    {
        if ($rule->isInCooldown()) {
            return;
        }

        /** @var Collection<int, PingAlertCondition> $conditions */
        $conditions = $rule->conditions()->get();

        if ($conditions->isEmpty()) {
            $this->fire($rule, $target, $result);

            return;
        }

        $metrics = self::resolveMetrics($target, $rule);
        $outcomes = $conditions->map(
            static fn (PingAlertCondition $c) => $c->evaluate($metrics)
        );

        $passes = $rule->condition_operator === 'or'
            ? $outcomes->contains(true)
            : $outcomes->every(static fn (bool $r) => $r === true);

        if ($passes) {
            $this->fire($rule, $target, $result);
        }
    }

    private function fire(PingAlertRule $rule, PingTarget $target, PingResult $result): void
    {
        $rule->update(['last_triggered_at' => now()]);

        /** @var Collection<int, PingAlertAction> $actions */
        $actions = $rule->actions()->get();

        foreach ($actions as $action) {
            try {
                match ($action->type) {
                    'webhook' => $this->fireWebhook($action, $rule, $target, $result),
                    'email'   => $this->fireEmail($action, $rule, $target, $result),
                    default   => null,
                };
            } catch (Throwable $e) {
                Log::error("PingAlertService: action [{$action->id}] failed", [
                    'type'  => $action->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("PingAlertService: rule [{$rule->id}] fired for target [{$target->id}].");
    }

    private function fireEmail(PingAlertAction $action, PingAlertRule $rule, PingTarget $target, PingResult $result): void
    {
        if (! $action->emailTemplate || ! $action->recipient_email) {
            return;
        }

        $mergeData = [
            'rule'         => $rule->name,
            'target'       => $target->label,
            'host'         => $target->host,
            'status'       => $result->status->label(),
            'avg_ms'       => $result->avg_ms,
            'packet_loss'  => $result->packet_loss_percent,
            'triggered_at' => now()->toIso8601String(),
        ];

        $template = $action->emailTemplate;
        $subject = $template->renderSubject($mergeData);
        $body = $template->renderBody($mergeData);

        $mailer = $action->mail_provider_id
            ? Mail::mailer($action->mail_provider_id)
            : Mail::mailer('zepeed_failover');

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

    private function fireWebhook(
        PingAlertAction $action,
        PingAlertRule $rule,
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

    /**
     * Compute metric values for a target over the condition lookback window.
     *
     * @return array{avg_ms: float|null, max_ms: float|null, packet_loss: float|null, consecutive_failures: int}
     */
    private static function resolveMetrics(PingTarget $target, PingAlertRule $rule): array
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
}
