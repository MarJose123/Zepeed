<?php

namespace App\Http\Resources;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class EmailTemplateResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $tpl = $this->template();

        return [
            'id'                 => $tpl->id,
            'name'               => $tpl->name,
            'slug'               => $tpl->slug,
            'subject'            => $tpl->subject,
            'body'               => $tpl->body,
            'is_system'          => $tpl->is_system,
            'is_used_in_rules'   => $tpl->isUsedInRules(),
            'used_in_rule_names' => $tpl->usedInRuleNames(),
            'updated_at'         => $tpl->updated_at->toIso8601String(),
            'created_at'         => $tpl->created_at->toIso8601String(),
        ];
    }

    private function template(): EmailTemplate
    {
        /** @var EmailTemplate */
        return $this->resource;
    }
}
