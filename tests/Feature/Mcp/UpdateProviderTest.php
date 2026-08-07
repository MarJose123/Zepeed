<?php

namespace Tests\Feature\Mcp;

use App\Enums\SpeedtestServer;
use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\UpdateProvider;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProviderTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testUpdateProviderUpdatesConfiguration(): void
    {
        $user = $this->mcpUser([TokenAbility::ProvidersUpdate->value]);
        $provider = Provider::factory()->withSlug(SpeedtestServer::Cloudflare)->disabled()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateProvider::class, [
                'provider_slug'    => 'cloudflare',
                'is_enabled'       => true,
                'alert_on_failure' => true,
                'server_id'        => '12345',
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('provider.slug', fn ($v) => $v === SpeedtestServer::Cloudflare)
                    ->where('provider.is_enabled', true)
                    ->where('provider.alert_on_failure', true)
                    ->etc();
            });

        $this->assertTrue($provider->refresh()->is_enabled);
        $this->assertSame('12345', $provider->refresh()->server_id);
    }

    public function testDisablingProviderDisablesItsSchedules(): void
    {
        $user = $this->mcpUser([TokenAbility::ProvidersUpdate->value]);
        $provider = Provider::factory()->withSlug(SpeedtestServer::Ookla)->enabled()->create();
        ProviderSchedule::factory()->create([
            'provider_slug' => $provider->slug->value,
            'is_enabled'    => true,
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateProvider::class, [
                'provider_slug'    => 'ookla',
                'is_enabled'       => false,
                'alert_on_failure' => false,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertFalse($provider->refresh()->is_enabled);
        $this->assertFalse(
            ProviderSchedule::query()->where('provider_slug', 'ookla')->first()->is_enabled
        );
    }

    public function testUpdateProviderRejectsUnknownProvider(): void
    {
        $user = $this->mcpUser([TokenAbility::ProvidersUpdate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateProvider::class, [
                'provider_slug'    => 'unknown',
                'is_enabled'       => true,
                'alert_on_failure' => true,
            ]);

        $response->assertHasErrors();
    }
}
