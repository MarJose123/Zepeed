<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string            $id
 * @property bool              $is_enabled
 * @property array<int,string> $allowed_ips
 * @property int               $cache_ttl
 * @property bool              $include_speed
 * @property bool              $include_ping
 * @property bool              $include_system
 * @property array<int,string> $providers
 * @property CarbonImmutable   $created_at
 * @property CarbonImmutable   $updated_at
 */
class Prometheus extends Model
{
    use HasUuids;

    protected $table = 'prometheus';

    /** @var list<string> */
    protected $fillable = [
        'is_enabled',
        'allowed_ips',
        'cache_ttl',
        'include_speed',
        'include_ping',
        'include_system',
        'providers',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_enabled'     => 'boolean',
            'allowed_ips'    => 'array',
            'cache_ttl'      => 'integer',
            'include_speed'  => 'boolean',
            'include_ping'   => 'boolean',
            'include_system' => 'boolean',
            'providers'      => 'array',
        ];
    }

    /**
     * Singleton accessor — always returns the single config row.
     */
    public static function config(): static
    {
        return static::query()->firstOrCreate([], [
            'is_enabled'     => false,
            'allowed_ips'    => [],
            'cache_ttl'      => 60,
            'include_speed'  => true,
            'include_ping'   => true,
            'include_system' => true,
            'providers'      => ['ookla', 'librespeed', 'netflix', 'cloudflare'],
        ]);
    }
}
