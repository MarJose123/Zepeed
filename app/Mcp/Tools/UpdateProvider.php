<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\Provider;
use App\Models\ProviderSchedule;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Update a speedtest provider\'s configuration. When a previously enabled provider is disabled, all its active schedules are also disabled. Requires the providers:update token ability.')]
class UpdateProvider extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::ProvidersUpdate);

        $validated = $request->validate([
            'provider_slug'    => ['required', 'string'],
            'is_enabled'       => ['required', 'boolean'],
            'alert_on_failure' => ['required', 'boolean'],
            'server_url'       => ['nullable', 'url', 'max:500'],
            'server_id'        => ['nullable', 'string', 'regex:/^\d+$/', 'max:20'],
        ]);

        $provider = Provider::query()
            ->where('slug', $validated['provider_slug'])
            ->first();

        if ($provider === null) {
            return Response::error("Provider [{$validated['provider_slug']}] not found.");
        }

        $wasEnabled = $provider->is_enabled;
        $willDisable = $wasEnabled && ! $validated['is_enabled'];

        $provider->update([
            'is_enabled'       => $validated['is_enabled'],
            'alert_on_failure' => $validated['alert_on_failure'],
            'server_url'       => $validated['server_url'] ?? null,
            'server_id'        => $validated['server_id'] ?? null,
        ]);

        if ($willDisable) {
            ProviderSchedule::query()
                ->where('provider_slug', $provider->slug->value)
                ->where('is_enabled', true)
                ->update(['is_enabled' => false]);
        }

        $provider->load(['latestResult', 'latestSuccessfulResult']);

        return Response::structured([
            'success'  => true,
            'message'  => 'Provider updated successfully.',
            'provider' => $provider->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'provider_slug'    => $schema->string()->description('Provider slug: ookla, librespeed, netflix, or cloudflare.')->required(),
            'is_enabled'       => $schema->boolean()->description('Whether the provider is enabled.')->required(),
            'alert_on_failure' => $schema->boolean()->description('Whether to alert when a run fails.')->required(),
            'server_url'       => $schema->string()->description('Endpoint URL (required to enable LibreSpeed).'),
            'server_id'        => $schema->string()->description('Numeric Ookla server ID (optional).'),
        ];
    }
}
