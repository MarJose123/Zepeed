<?php

namespace App\Models;

use App\Enums\PingResultStatus;
use App\Enums\PingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PingResultFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string           $id
 * @property string           $ping_target_id
 * @property PingResultStatus $status
 * @property int              $packets_sent
 * @property int              $packets_received
 * @property float            $packet_loss_percent
 * @property float|null       $min_ms
 * @property float|null       $avg_ms
 * @property float|null       $max_ms
 * @property float|null       $stddev_ms
 * @property string|null      $raw_output
 * @property string|null      $failure_reason
 * @property CarbonImmutable  $measured_at
 * @property CarbonImmutable  $created_at
 * @property-read PingTarget   $target
 */
#[UseFactory(PingResultFactory::class)]
class PingResult extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'ping_target_id',
        'status',
        'packets_sent',
        'packets_received',
        'packet_loss_percent',
        'min_ms',
        'avg_ms',
        'max_ms',
        'stddev_ms',
        'raw_output',
        'failure_reason',
        'measured_at',
        'created_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'status'               => PingResultStatus::class,
            'packets_sent'         => 'integer',
            'packets_received'     => 'integer',
            'packet_loss_percent'  => 'float',
            'min_ms'               => 'float',
            'avg_ms'               => 'float',
            'max_ms'               => 'float',
            'stddev_ms'            => 'float',
            'measured_at'          => 'immutable_datetime',
            'created_at'           => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PingTarget, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(PingTarget::class, 'ping_target_id');
    }

    protected function scopeForTarget(Builder $query, string $targetId): void
    {
        $query->where('ping_target_id', $targetId);
    }

    protected function scopeInDateRange(Builder $query, string $range): void
    {
        $hours = match ($range) {
            '7d'    => 168,
            '30d'   => 720,
            default => 24,
        };
        $query->where('measured_at', '>=', now()->subHours($hours));
    }

    /**
     * Derive the parent target's summary status from this result.
     */
    public function deriveTargetStatus(): PingStatus
    {
        return match ($this->status) {
            PingResultStatus::Success => PingStatus::Ok,
            PingResultStatus::Partial => PingStatus::Warn,
            PingResultStatus::Failed  => PingStatus::Failed,
        };
    }
}
