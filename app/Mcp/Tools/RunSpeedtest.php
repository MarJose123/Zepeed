<?php

namespace App\Mcp\Tools;

use App\Enums\QueueWorkerName;
use App\Enums\TokenAbility;
use App\Jobs\RunSpeedtestJob;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\Provider;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Trigger an immediate manual speedtest run for a provider. Dispatches the run as a queued job; the job is skipped automatically if the provider is under a maintenance window. Requires the speedtest:run token ability.')]
class RunSpeedtest extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::SpeedtestRun);

        $validated = $request->validate([
            'provider_slug' => ['required', 'string'],
        ]);

        $provider = Provider::query()
            ->where('slug', $validated['provider_slug'])
            ->first();

        if ($provider === null) {
            return Response::error("Provider [{$validated['provider_slug']}] not found.");
        }

        if (! $provider->is_runnable) {
            return Response::error('Provider is disabled or not fully configured.');
        }

        dispatch(new RunSpeedtestJob(provider: $provider))->onQueue(QueueWorkerName::Speedtest->value);

        return Response::structured([
            'success'       => true,
            'message'       => "Manual speedtest run queued for {$provider->slug->label()}.",
            'provider_slug' => $provider->slug->value,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'provider_slug' => $schema->string()->description('Provider slug: ookla, librespeed, netflix, or cloudflare.')->required(),
        ];
    }
}
