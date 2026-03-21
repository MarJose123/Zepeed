<?php

namespace App\Models;

use App\Enums\SpeedtestServer;
use App\Services\Speedtest\Exceptions\SpeedtestException;
use App\Services\Speedtest\Exceptions\SpeedtestFailureReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class SpeedResult extends Model
{
    use HasUuids;

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
