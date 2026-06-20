<?php

namespace App\Models;

use App\Enums\SpeedtestServer;
use App\Services\Speedtest\Exceptions\SpeedtestException;
use App\Services\Speedtest\Exceptions\SpeedtestFailureReason;
use Database\Factories\SpeedResultFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string                      $id
 * @property SpeedtestServer             $provider_slug
 * @property string                      $status
 * @property string|null                 $download_mbps
 * @property string|null                 $upload_mbps
 * @property string|null                 $ping_ms
 * @property string|null                 $jitter_ms
 * @property string|null                 $packet_loss
 * @property int|null                    $download_bytes
 * @property int|null                    $upload_bytes
 * @property string|null                 $server_name
 * @property string|null                 $server_location
 * @property string|null                 $isp
 * @property string|null                 $share_url
 * @property string|null                 $client_ip
 * @property SpeedtestFailureReason|null $failure_reason
 * @property string|null                 $failure_message
 * @property array|null                  $raw_json
 * @property Carbon                      $measured_at
 * @property Carbon|null                 $created_at
 * @property Carbon|null                 $updated_at
 */
#[UseFactory(SpeedResultFactory::class)]
class SpeedResult extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'provider_slug',
        'status',
        'download_mbps',
        'upload_mbps',
        'ping_ms',
        'jitter_ms',
        'packet_loss',
        'download_bytes',
        'upload_bytes',
        'server_name',
        'server_location',
        'isp',
        'share_url',
        'client_ip',
        'failure_reason',
        'failure_message',
        'raw_json',
        'measured_at',
    ];

    protected function scopeSuccessful(Builder $query): void
    {
        $query->where('status', 'success');
    }

    protected function scopeFailed(Builder $query): void
    {
        $query->where('status', 'failed');
    }

    protected function scopeSkipped(Builder $query): void
    {
        $query->where('status', 'skipped');
    }

    protected function scopeForProvider(Builder $query, SpeedtestServer $server): void
    {
        $query->where('provider_slug', $server->value);
    }

    protected function scopeRecent(Builder $query, int $hours = 24): void
    {
        $query->where('measured_at', '>=', now()->subHours($hours));
    }

    /**
     * Create a skipped result record — no metrics, just the audit trail.
     */
    public static function recordSkipped(
        Provider $provider,
        string $reason = 'Maintenance window active.',
    ): self {
        return self::query()->create([
            'provider_slug'   => $provider->slug->value,
            'status'          => 'skipped',
            'failure_message' => $reason,
            'measured_at'     => now(),
        ]);
    }

    /**
     * Create a failed result record from a SpeedtestException.
     */
    public static function recordFailed(
        Provider $provider,
        SpeedtestException $e,
    ): self {
        return self::query()->create([
            'provider_slug'   => $provider->slug->value,
            'status'          => 'failed',
            'failure_reason'  => $e->reason->value,
            'failure_message' => $e->getMessage(),
            'measured_at'     => now(),
        ]);
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'slug');
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'provider_slug'   => SpeedtestServer::class,
            'failure_reason'  => SpeedtestFailureReason::class,
            'download_mbps'   => 'decimal:2',
            'upload_mbps'     => 'decimal:2',
            'ping_ms'         => 'decimal:2',
            'jitter_ms'       => 'decimal:2',
            'packet_loss'     => 'decimal:2',
            'measured_at'     => 'datetime',
        ];
    }
}
