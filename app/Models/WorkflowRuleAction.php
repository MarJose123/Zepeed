<?php

namespace App\Models;

use Database\Factories\WorkflowRuleActionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string             $id
 * @property string             $workflow_rule_id
 * @property string             $type              email|webhook|apprise
 * @property string|null        $mail_provider_id
 * @property string|null        $email_template_id
 * @property string|null        $recipient_email
 * @property string|null        $webhook_id
 * @property string|null        $apprise_id
 * @property int                $sort_order
 * @property MailProvider|null  $mailProvider
 * @property EmailTemplate|null $emailTemplate
 * @property Webhook|null       $webhook
 * @property Apprise|null       $apprise
 */
#[UseFactory(WorkflowRuleActionFactory::class)]
class WorkflowRuleAction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workflow_rule_id',
        'type',
        'mail_provider_id',
        'email_template_id',
        'recipient_email',
        'webhook_id',
        'apprise_id',
        'sort_order',
    ];

    #[Override]
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /** @return BelongsTo<MailProvider, $this> */
    public function mailProvider(): BelongsTo
    {
        return $this->belongsTo(MailProvider::class);
    }

    /** @return BelongsTo<EmailTemplate, $this> */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    /** @return BelongsTo<Webhook, $this> */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /** @return BelongsTo<Apprise, $this> */
    public function apprise(): BelongsTo
    {
        return $this->belongsTo(Apprise::class);
    }

    /** @return BelongsTo<WorkflowRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(WorkflowRule::class, 'workflow_rule_id');
    }
}
