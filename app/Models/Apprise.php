<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AppriseFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string                  $id
 * @property string                  $name
 * @property string                  $url
 * @property array<int, string>|null $tags
 * @property string|null             $username
 * @property string|null             $password
 * @property int                     $timeout
 * @property bool                    $verify_ssl
 * @property bool                    $is_active
 * @property CarbonImmutable|null    $last_fired_at
 * @property CarbonImmutable         $created_at
 * @property CarbonImmutable         $updated_at
 * @property-read bool                 $has_credentials
 */
#[UseFactory(AppriseFactory::class)]
class Apprise extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'url',
        'tags',
        'username',
        'password',
        'timeout',
        'verify_ssl',
        'is_active',
        'last_fired_at',
    ];

    /**
     * The Basic Auth password is encrypted and must never be serialized —
     * the REST API, MCP and UI expose only `has_credentials`.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Computed attributes included in serialization.
     *
     * @var list<string>
     */
    protected $appends = [
        'has_credentials',
    ];

    /**
     * Whether Basic Auth credentials are configured (the password itself is
     * never exposed — see $hidden).
     */
    protected function hasCredentials(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->username) && filled($this->password),
        );
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'tags'          => 'array',
            'password'      => 'encrypted',
            'verify_ssl'    => 'boolean',
            'is_active'     => 'boolean',
            'timeout'       => 'integer',
            'last_fired_at' => 'immutable_datetime',
        ];
    }

    /**
     * Check if this Apprise instance is referenced by any rules.
     */
    public function isUsedInRules(): bool
    {
        return WorkflowRuleAction::query()
            ->where('apprise_id', $this->id)
            ->exists()
            || PingAlertAction::query()
                ->where('apprise_id', $this->id)
                ->exists();
    }

    /**
     * Names of rules using this Apprise instance.
     *
     * @return array<string>
     */
    public function usedInRuleNames(): array
    {
        return WorkflowRuleAction::query()
            ->where('apprise_id', $this->id)
            ->with('rule')
            ->get()
            ->pluck('rule.name')
            ->merge(
                PingAlertAction::query()
                    ->where('apprise_id', $this->id)
                    ->with('rule')
                    ->get()
                    ->pluck('rule.name')
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
