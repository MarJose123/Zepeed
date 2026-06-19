<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Override;

/**
 * @property int                  $id
 * @property string               $tokenable_type
 * @property int                  $tokenable_id
 * @property string               $name
 * @property string               $token
 * @property array<string>        $abilities
 * @property string|null          $last_used_ip
 * @property string|null          $last_used_agent
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'last_used_ip',
        'last_used_agent',
        'last_used_at',
        'expires_at',
    ];

    /**
     * Record IP and user agent on each API request that uses this token.
     *
     * @param Request $request
     *
     * @return void
     */
    public function recordUsage(Request $request): void
    {
        $this->forceFill([
            'last_used_at'    => now(),
            'last_used_ip'    => $request->ip(),
            'last_used_agent' => $request->userAgent(),
        ])->save();
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'abilities'    => 'json',
            'last_used_at' => 'immutable_datetime',
            'expires_at'   => 'immutable_datetime',
        ];
    }
}
