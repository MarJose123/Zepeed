<?php

namespace App\Models;

use App\Enums\EmailTemplateType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string            $id
 * @property string            $name
 * @property string            $slug
 * @property string            $subject
 * @property string            $body
 * @property bool              $is_system
 * @property EmailTemplateType $template_type
 * @property CarbonImmutable   $created_at
 * @property CarbonImmutable   $updated_at
 */
#[Fillable([
    'name',
    'slug',
    'subject',
    'body',
    'is_system',
    'template_type',
])]
class EmailTemplate extends Model
{
    use HasUuids;

    #[Override]
    protected function casts(): array
    {
        return [
            'is_system'     => 'boolean',
            'template_type' => EmailTemplateType::class,
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
     * Render the subject string by replacing merge field placeholders
     * with safe string substitution. Does not compile Blade directives,
     * preventing Server-Side Template Injection (SSTI).
     *
     * @param array<string, mixed> $data
     */
    public function renderSubject(array $data): string
    {
        return $this->replaceMergeFields($this->subject, $data);
    }

    /**
     * Render the body string by replacing merge field placeholders
     * with safe string substitution. Does not compile Blade directives,
     * preventing Server-Side Template Injection (SSTI).
     *
     * @param array<string, mixed> $data
     */
    public function renderBody(array $data): string
    {
        $body = preg_replace_callback(
            '/<span[^>]+data-merge-field="([^"]+)"[^>]*>.*?<\/span>/s',
            static fn ($matches) => $matches[1],
            $this->body,
        ) ?? $this->body;

        return $this->replaceMergeFields($body, static::buildRenderData($data));
    }

    /**
     * Replace {{ field }} placeholders with values using safe string
     * replacement. Does not compile or execute Blade directives.
     *
     * @param array<string, mixed> $data
     */
    private function replaceMergeFields(string $template, array $data): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([\w.]+)\s*\}\}/',
            static function (array $matches) use ($data): string {
                $key = $matches[1];

                // Support dot notation for nested arrays
                $value = data_get($data, $key);

                return $value !== null
                    ? (is_string($value) ? $value : e($value))
                    : $matches[0];
            },
            $template,
        );
    }

    public function isUsedInRules(): bool
    {
        return WorkflowRuleAction::query()
            ->where('email_template_id', $this->id)
            ->exists();
    }

    /** @return array<string> */
    public function usedInRuleNames(): array
    {
        return WorkflowRuleAction::query()
            ->where('email_template_id', $this->id)
            ->with('rule')
            ->get()
            ->pluck('rule.name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function findBySlug(string $slug): self
    {
        return static::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }
}
