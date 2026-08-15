<?php

namespace Tests\Feature;

use App\Enums\MailDriver;
use App\Http\Resources\MailProviderResource;
use App\Models\MailProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the mail provider edit UI contract: the resource must expose the
 * provider's config (with secrets masked) so the edit form can show the
 * current values, and the update endpoint must preserve stored credentials
 * when the UI submits the mask sentinel or an empty value.
 */
class MailProviderEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeProvider(array $overrides = []): MailProvider
    {
        return MailProvider::query()->create([
            'driver'       => MailDriver::Smtp,
            'label'        => 'Test SMTP',
            'priority'     => 1,
            'is_active'    => true,
            'config'       => [
                'host'       => 'smtp.example.com',
                'port'       => 587,
                'encryption' => 'tls',
                'username'   => 'user',
                'password'   => 's3cret-pass',
            ],
            'from_address' => 'sender@example.com',
            'from_name'    => 'Zepeed',
            ...$overrides,
        ]);
    }

    public function testResourceExposesConfigWithSecretsMasked(): void
    {
        $provider = $this->makeProvider();

        $payload = new MailProviderResource($provider)->resolve();

        // Non-secret values are exposed as-is so the edit fields can populate.
        $this->assertSame('smtp.example.com', $payload['config']['host']);
        $this->assertSame(587, $payload['config']['port']);
        $this->assertSame('user', $payload['config']['username']);

        // Credentials never leave the server in plaintext.
        $this->assertSame(MailProvider::SECRET_MASK, $payload['config']['password']);
        $this->assertNotContains('s3cret-pass', $payload['config']);
    }

    public function testUpdateWithMaskedSecretKeepsStoredCredential(): void
    {
        $user = User::factory()->create();
        $provider = $this->makeProvider();

        $this->actingAs($user)
            ->patch("/speedtest/integration/smtp/{$provider->id}", [
                'label'  => 'Renamed',
                'config' => [
                    'host'       => 'smtp.example.com',
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => 'user',
                    'password'   => MailProvider::SECRET_MASK,
                ],
            ])
            ->assertRedirect();

        $provider->refresh();

        $this->assertSame('Renamed', $provider->label);
        $this->assertSame('s3cret-pass', $provider->config['password']);
    }

    public function testUpdateWithEmptySecretKeepsStoredCredential(): void
    {
        $user = User::factory()->create();
        $provider = $this->makeProvider();

        $this->actingAs($user)
            ->patch("/speedtest/integration/smtp/{$provider->id}", [
                'label'  => 'Renamed',
                'config' => [
                    'host'       => 'smtp.example.com',
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => 'user',
                    'password'   => '',
                ],
            ])
            ->assertRedirect();

        $provider->refresh();

        $this->assertSame('Renamed', $provider->label);
        $this->assertSame('s3cret-pass', $provider->config['password']);
    }

    public function testUpdateWithNewSecretReplacesStoredCredential(): void
    {
        $user = User::factory()->create();
        $provider = $this->makeProvider();

        $this->actingAs($user)
            ->patch("/speedtest/integration/smtp/{$provider->id}", [
                'config' => [
                    'host'       => 'smtp.example.com',
                    'port'       => 587,
                    'encryption' => 'tls',
                    'username'   => 'user',
                    'password'   => 'new-pass',
                ],
            ])
            ->assertRedirect();

        $provider->refresh();

        $this->assertSame('new-pass', $provider->config['password']);
    }

    public function testUpdateWithoutConfigKeyKeepsStoredCredential(): void
    {
        $user = User::factory()->create();
        $provider = $this->makeProvider();

        $this->actingAs($user)
            ->patch("/speedtest/integration/smtp/{$provider->id}", [
                'label' => 'Only label changed',
            ])
            ->assertRedirect();

        $provider->refresh();

        $this->assertSame('Only label changed', $provider->label);
        $this->assertSame('s3cret-pass', $provider->config['password']);
    }
}
