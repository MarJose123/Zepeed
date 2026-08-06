<?php

namespace Tests\Feature\Mcp;

use App\Enums\SpeedtestServer;
use App\Enums\TokenAbility;
use App\Jobs\RunSpeedtestJob;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\RunSpeedtest;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RunSpeedtestTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testRunSpeedtestDispatchesJobForRunnableProvider(): void
    {
        Queue::fake();

        $user = $this->mcpUser([TokenAbility::SpeedtestRun->value]);
        $provider = Provider::factory()->withSlug(SpeedtestServer::Ookla)->enabled()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(RunSpeedtest::class, ['provider_slug' => 'ookla']);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('provider_slug', 'ookla')
                    ->etc();
            });

        Queue::assertPushed(RunSpeedtestJob::class);
    }

    public function testRunSpeedtestRejectsDisabledProvider(): void
    {
        Queue::fake();

        $user = $this->mcpUser([TokenAbility::SpeedtestRun->value]);
        Provider::factory()->withSlug(SpeedtestServer::Ookla)->disabled()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(RunSpeedtest::class, ['provider_slug' => 'ookla']);

        $response->assertHasErrors();

        Queue::assertNotPushed(RunSpeedtestJob::class);
    }

    public function testRunSpeedtestRejectsUnknownProvider(): void
    {
        Queue::fake();

        $user = $this->mcpUser([TokenAbility::SpeedtestRun->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(RunSpeedtest::class, ['provider_slug' => 'unknown']);

        $response->assertHasErrors();

        Queue::assertNotPushed(RunSpeedtestJob::class);
    }
}
