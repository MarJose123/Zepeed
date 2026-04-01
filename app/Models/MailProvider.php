<?php

namespace App\Models;

use App\Enums\MailDriver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string               $id
 * @property MailDriver           $driver
 * @property string               $label
 * @property int                  $priority
 * @property bool                 $is_active
 * @property array<string, mixed> $config
 * @property string               $from_address
 * @property string               $from_name
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $last_failed_at
 * @property int                  $failure_count
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 *
 * @method static Builder<MailProvider> active()
 * @method static Builder<MailProvider> ordered()
 */
class MailProvider extends Model
{
    use HasUuids;
    protected $fillable = [
        'driver',
        'label',
        'priority',
        'is_active',
        'config',
        'from_address',
        'from_name',
        'last_used_at',
        'last_failed_at',
        'failure_count',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'driver'         => MailDriver::class,
            'is_active'      => 'boolean',
            'config'         => 'encrypted:array',
            'last_used_at'   => 'immutable_datetime',
            'last_failed_at' => 'immutable_datetime',
            'failure_count'  => 'integer',
            'priority'       => 'integer',
        ];
    }

    /** @param Builder<MailProvider> $query */
    protected function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<MailProvider> $query */
    protected function scopeOrdered(Builder $query): void
    {
        $query->orderBy('priority');
    }

    /**
     * Build a Laravel mailer config array from this provider's stored config.
     *
     * @return array<string, mixed>
     */
    public function toMailerConfig(): array
    {
        $config = $this->config;

        return match ($this->driver) {
            MailDriver::Smtp => [
                'transport'  => 'smtp',
                'host'       => $config['host'],
                'port'       => (int) $config['port'],
                'encryption' => $config['encryption'],
                'username'   => $config['username'],
                'password'   => $config['password'],
            ],
            MailDriver::Resend => [
                'transport' => 'resend',
                'key'       => $config['api_key'],
            ],
            MailDriver::Mailgun => [
                'transport' => 'mailgun',
                'domain'    => $config['domain'],
                'secret'    => $config['api_key'],
                'endpoint'  => $config['endpoint'] ?? 'api.mailgun.net',
                'scheme'    => 'https',
            ],
            MailDriver::Postmark => [
                'transport' => 'postmark',
                'token'     => $config['token'],
            ],
            MailDriver::Ses => [
                'transport' => 'ses',
                'key'       => $config['key'],
                'secret'    => $config['secret'],
                'region'    => $config['region'],
            ],
            MailDriver::Sendmail => [
                'transport' => 'sendmail',
                'path'      => $config['path'] ?? '/usr/sbin/sendmail -bs -i',
            ],
        };
    }

    /**
     * Record a successful send.
     */
    public function recordSuccess(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Record a failure — increments failure count.
     */
    public function recordFailure(): void
    {
        $this->increment('failure_count');
        $this->update(['last_failed_at' => now()]);
    }
}
