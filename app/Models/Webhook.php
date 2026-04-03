<?php

namespace App\Models;

use App\Observers\WebhooksObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string                    $id
 * @property string                    $name
 * @property string                    $url
 * @property string                    $method
 * @property string|null               $secret
 * @property array<string,string>|null $headers
 * @property int                       $timeout
 * @property int                       $retry_attempts
 * @property bool                      $verify_ssl
 * @property bool                      $is_active
 * @property CarbonImmutable|null      $last_fired_at
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property-read HasMany<WebhookDelivery, self> $deliveries
 */
#[ObservedBy([WebhooksObserver::class])]
class Webhook extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'url',
        'method',
        'secret',
        'headers',
        'timeout',
        'retry_attempts',
        'verify_ssl',
        'is_active',
        'last_fired_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'headers'        => 'array',
            'secret'         => 'encrypted',
            'verify_ssl'     => 'boolean',
            'is_active'      => 'boolean',
            'last_fired_at'  => 'immutable_datetime',
            'retry_attempts' => 'integer',
            'timeout'        => 'integer',
        ];
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)->latest();
    }

    /**
     * Check if this webhook is referenced by any alert rules.
     */
    public function isUsedInRules(): bool
    {
        // Will be implemented when AlertRule model exists
        // For now returns false — safe to delete
        return false;
    }

    /**
     * Names of alert rules using this webhook.
     *
     * @return array<string>
     */
    public function usedInRuleNames(): array
    {
        return [];
    }
}
