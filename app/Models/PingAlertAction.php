<?php

namespace App\Models;

use Database\Factories\PingAlertActionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string             $id
 * @property string             $ping_alert_rule_id
 * @property string             $type               email|webhook|apprise
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
#[UseFactory(PingAlertActionFactory::class)]
class PingAlertAction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ping_alert_rule_id',
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

    /** @return BelongsTo<PingAlertRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(PingAlertRule::class, 'ping_alert_rule_id');
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
}
