<?php

namespace App\Http\Resources;

use App\Models\Apprise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class AppriseResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $apprise = $this->apprise();

        return [
            'id'                 => $apprise->id,
            'name'               => $apprise->name,
            'url'                => $apprise->url,
            'url_preview'        => self::buildUrlPreview($apprise->url),
            'tags'               => $apprise->tags ?? [],
            'has_credentials'    => $apprise->has_credentials,
            'username'           => $apprise->username,
            'timeout'            => $apprise->timeout,
            'verify_ssl'         => $apprise->verify_ssl,
            'is_active'          => $apprise->is_active,
            'last_fired_at'      => $apprise->last_fired_at?->toIso8601String(),
            'is_used_in_rules'   => $apprise->isUsedInRules(),
            'used_in_rule_names' => $apprise->usedInRuleNames(),
            'created_at'         => $apprise->created_at->toIso8601String(),
        ];
    }

    /**
     * Truncate URL for display — show host + first path segment.
     */
    private static function buildUrlPreview(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? $url;
        $path = $parsed['path'] ?? '';
        $short = rtrim($host . substr($path, 0, 20), '/');

        return strlen($url) > strlen($short) ? $short . '…' : $short;
    }

    private function apprise(): Apprise
    {
        /** @var Apprise */
        return $this->resource;
    }
}
