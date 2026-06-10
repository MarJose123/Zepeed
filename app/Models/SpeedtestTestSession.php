<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string               $id
 * @property string               $provider_id
 * @property string               $user_id
 * @property string               $status
 * @property string|null          $error_message
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 */
class SpeedtestTestSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'provider_id',
        'user_id',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'started_at'   => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function markRunning(): void
    {
        $this->update([
            'status'     => 'running',
            'started_at' => CarbonImmutable::now(),
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => CarbonImmutable::now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $message,
            'completed_at'  => CarbonImmutable::now(),
        ]);
    }

    public function markSkipped(): void
    {
        $this->update([
            'status'       => 'skipped',
            'completed_at' => CarbonImmutable::now(),
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'status'       => 'cancelled',
            'completed_at' => CarbonImmutable::now(),
        ]);
    }
}
