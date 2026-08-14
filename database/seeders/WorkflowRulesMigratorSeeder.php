<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrates data related to the "Alert Rules" -> "Workflow Rules" rename.
 *
 * The table rename itself (alert_rules -> workflow_rules, etc.) is handled
 * data-preservingly by the migration, so this seeder only rewrites legacy
 * `alerts:*` abilities on existing personal access tokens to their renamed
 * `workflow-rules:*` equivalents, keeping existing API tokens and MCP access
 * working without any user action.
 */
class WorkflowRulesMigratorSeeder extends Seeder
{
    /**
     * Legacy ability values mapped to their renamed equivalents.
     *
     * @var array<string, string>
     */
    private const array ABILITY_MAP = [
        'alerts:view'   => 'workflow-rules:view',
        'alerts:create' => 'workflow-rules:create',
        'alerts:update' => 'workflow-rules:update',
        'alerts:delete' => 'workflow-rules:delete',
    ];

    public function run(): void
    {
        $this->migrateTokenAbilities();
    }

    private function migrateTokenAbilities(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $tokens = DB::table('personal_access_tokens')
            ->whereNotNull('abilities')
            ->get(['id', 'abilities']);

        foreach ($tokens as $token) {
            $abilities = json_decode((string) $token->abilities, true);

            if (! is_array($abilities)) {
                continue;
            }

            $updated = array_map(
                static fn (mixed $ability): string => self::ABILITY_MAP[(string) $ability] ?? (string) $ability,
                $abilities,
            );

            $updated = array_values(array_unique($updated));

            if ($updated !== $abilities) {
                DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->update(['abilities' => json_encode($updated)]);
            }
        }
    }
}
