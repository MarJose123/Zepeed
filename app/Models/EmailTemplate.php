<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Override;

/**
 * @property string          $id
 * @property string          $name
 * @property string          $slug
 * @property string          $subject
 * @property string          $body
 * @property bool            $is_system
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class EmailTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'subject',
        'body',
        'is_system',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * Build render data — wraps URL fields in anchor tags so Blade outputs
     * clickable links rather than bare URL strings.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function buildRenderData(array $data): array
    {
        $urlFields = ['dashboard_url', 'share_url'];

        foreach ($urlFields as $field) {
            if (! empty($data[$field])) {
                $url = e($data[$field]);
                $data[$field] = "<a href=\"{$url}\" style=\"color:#6366f1;text-decoration:underline;\">{$url}</a>";
            }
        }

        return $data;
    }

    /**
     * Render the subject string with Blade merge data.
     *
     * @param array<string, mixed> $data
     */
    public function renderSubject(array $data): string
    {
        return Blade::render($this->subject, $data);
    }

    /**
     * Render the body string with Blade merge data.
     *
     * @param array<string, mixed> $data
     */
    public function renderBody(array $data): string
    {
        // Strip pill wrappers — extract the raw Blade tag from data-merge-field attribute.
        // Handles both {{ }} and {!! !!} tags.
        $body = preg_replace_callback(
            '/<span[^>]+data-merge-field="([^"]+)"[^>]*>.*?<\/span>/s',
            fn ($matches) => $matches[1],
            $this->body,
        ) ?? $this->body;

        return Blade::render($body, static::buildRenderData($data));
    }

    /**
     * Whether this template is referenced by any alert rules.
     * Placeholder until AlertRule model is built.
     */
    public function isUsedInRules(): bool
    {
        return false;
    }

    /**
     * @return array<string>
     */
    public function usedInRuleNames(): array
    {
        return [];
    }

    /**
     * Find a system template by slug, falling back to the default alert.
     */
    public static function findBySlug(string $slug): self
    {
        return static::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
