<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

class DowntimeLog extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'event',
        'triggered_by',
        'duration',
        'timestamp',
    ];

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
