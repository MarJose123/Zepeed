<?php

namespace App\Console\Commands;

use App\Enums\SpeedtestServer;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
use App\Models\SpeedResult;
use App\Services\Speedtest\Exceptions\SpeedtestException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SpeedtestRunCommand extends Command
{
    protected $signature = 'speedtest:run
                            {provider? : Provider slug (speedtest, librespeed, fastcom). Omit to run all.}
                            {--sync : Run synchronously and output results inline}
                            {--queue : Dispatch to the queue (default behaviour)}';

    protected $description = 'Trigger a speedtest run for one or all enabled providers';

    public function handle(): int
    {
        $providers = $this->resolveProviders();

        if ($providers->isEmpty()) {
            $this->components->warn('No enabled and runnable providers found.');

            return self::FAILURE;
        }

        $sync = $this->option('sync');

        foreach ($providers as $provider) {
            $sync
                ? $this->runSync($provider)
                : $this->runQueued($provider);
        }

        return self::SUCCESS;
    }

    private function resolveProviders(): Collection
    {
        $slug = $this->argument('provider');

        if ($slug !== null) {
            $server = SpeedtestServer::tryFrom($slug);

            if ($server === null) {
                $this->components->error(
                    "Invalid provider slug \"{$slug}\". Valid values: ".
                    implode(', ', array_column(SpeedtestServer::cases(), 'value'))
                );

                return collect();
            }

            $provider = Provider::query()
                ->forServer($server)
                ->first();

            if ($provider === null) {
                $this->components->error("Provider \"{$slug}\" not found in the database. Did you run the seeder?");

                return collect();
            }

            return collect([$provider]);
        }

        // No slug given — return all enabled providers
        return Provider::query()->enabled()->get();
    }

    private function runQueued(Provider $provider): void
    {
        if (! $provider->is_runnable) {
            $this->components->warn(
                "{$provider->slug->label()} is not runnable — skipping. ".
                ($provider->slug->requiresServerUrl() && empty($provider->server_url)
                    ? '(Server URL not configured)'
                    : '(Provider disabled)')
            );

            return;
        }

        dispatch(new RunSpeedtestJob($provider));

        $this->components->info("Dispatched {$provider->slug->label()} to the queue.");
    }

    private function runSync(Provider $provider): void
    {
        if (! $provider->is_runnable) {
            $this->components->warn(
                "{$provider->slug->label()} is not runnable — skipping. ".
                ($provider->slug->requiresServerUrl() && empty($provider->server_url)
                    ? '(Server URL not configured)'
                    : '(Provider disabled)')
            );

            return;
        }

        $this->components->task(
            "Running {$provider->slug->label()}",
            function () use ($provider): bool {
                try {
                    $result = $provider->service()->run();

                    SpeedResult::query()->create($result->toStorageArray());

                    $provider->markSuccessful();

                    // Output metrics table after successful sync run
                    $this->newLine();
                    $this->components->twoColumnDetail(
                        '<fg=green>Download</>',
                        "<fg=white>{$result->downloadMbps} Mbps</>"
                    );
                    $this->components->twoColumnDetail(
                        '<fg=green>Upload</>',
                        "<fg=white>{$result->uploadMbps} Mbps</>"
                    );
                    $this->components->twoColumnDetail(
                        '<fg=green>Ping</>',
                        "<fg=white>{$result->pingMs} ms</>"
                    );

                    if ($result->jitterMs !== null) {
                        $this->components->twoColumnDetail(
                            '<fg=green>Jitter</>',
                            "<fg=white>{$result->jitterMs} ms</>"
                        );
                    }

                    if ($result->isp !== null) {
                        $this->components->twoColumnDetail(
                            '<fg=green>ISP</>',
                            "<fg=white>{$result->isp}</>"
                        );
                    }

                    if ($result->serverLocation !== null) {
                        $this->components->twoColumnDetail(
                            '<fg=green>Server</>',
                            "<fg=white>{$result->serverName} ({$result->serverLocation})</>"
                        );
                    }

                    $this->newLine();

                    return true;

                } catch (SpeedtestException $e) {
                    SpeedResult::recordFailed(
                        provider: $provider,
                        e       : $e,
                    );

                    $provider->markFailed();

                    $this->newLine();
                    $this->components->error(
                        "Failed: [{$e->reason->value}] {$e->getMessage()}"
                    );

                    return false;
                }
            }
        );
    }
}
