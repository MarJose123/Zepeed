<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string          $id
 * @property string          $webhook_id
 * @property string          $event
 * @property int|null        $status_code
 * @property string|null     $status_text
 * @property int|null        $duration_ms
 * @property int             $attempt
 * @property int             $max_attempts
 * @property bool            $success
 * @property string|null     $response_body
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Webhook     $webhook
 */
class WebhookDelivery extends Model
{
    use HasUuids;

    protected $fillable = [
        'webhook_id',
        'event',
        'status_code',
        'status_text',
        'duration_ms',
        'attempt',
        'max_attempts',
        'success',
        'response_body',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'success'      => 'boolean',
            'status_code'  => 'integer',
            'duration_ms'  => 'integer',
            'attempt'      => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    /** @return BelongsTo<Webhook, $this> */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
