<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WorkflowRulesMigratorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRulesMigratorSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Legacy `alerts:*` abilities on existing tokens are rewritten to their
     * renamed `workflow-rules:*` equivalents so tokens keep working.
     *
     * (The table rename itself is handled data-preservingly by the migration;
     * the seeder only rewrites token abilities.)
     */
    public function testItMigratesLegacyTokenAbilities(): void
    {
        $user = User::factory()->create();
        $user->createToken('legacy-token', [
            'alerts:view',
            'alerts:update',
            'webhooks:view',
        ]);

        $this->seed(WorkflowRulesMigratorSeeder::class);

        $abilities = $user->tokens()->first()->abilities;

        $this->assertEqualsCanonicalizing([
            'workflow-rules:view',
            'workflow-rules:update',
            'webhooks:view',
        ], $abilities);
    }
}
