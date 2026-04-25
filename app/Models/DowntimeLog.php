<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    /** @var array<string, string> */
    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
