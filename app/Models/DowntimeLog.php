<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property string      $id
 * @property string      $event
 * @property string      $triggered_by
 * @property string|null $duration
 * @property string      $timestamp    — raw string from getRawOriginal()
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
#[Fillable([
    'event',
    'triggered_by',
    'duration',
    'timestamp',
])]
class DowntimeLog extends Model
{
    use HasUuids;

    /**
     * @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
        ];
    }
}
