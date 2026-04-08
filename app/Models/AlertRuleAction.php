<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string             $id
 * @property string             $alert_rule_id
 * @property string             $type              email|webhook
 * @property string|null        $mail_provider_id
 * @property string|null        $email_template_id
 * @property string|null        $recipient_email
 * @property string|null        $webhook_id
 * @property int                $sort_order
 * @property MailProvider|null  $mailProvider
 * @property EmailTemplate|null $emailTemplate
 * @property Webhook|null       $webhook
 */
class AlertRuleAction extends Model
{
    use HasUuids;

    protected $fillable = [
        'alert_rule_id',
        'type',
        'mail_provider_id',
        'email_template_id',
        'recipient_email',
        'webhook_id',
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

    /** @return BelongsTo<AlertRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }
}
