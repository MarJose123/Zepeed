<?php

namespace App\Services\Speedtest;

use App\Models\Provider;
use App\Services\Speedtest\Contracts\SpeedtestServiceInterface;
use App\Services\Speedtest\Data\SpeedtestResult;
use App\Services\Speedtest\Exceptions\SpeedtestException;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

abstract class AbstractSpeedtestService implements SpeedtestServiceInterface
{
    public function __construct(
        protected readonly Provider $provider,
    ) {}

    public function provider(): Provider
    {
        return $this->provider;
    }

    public function timeout(): int
    {
        return 120;
    }

    public function maxAttempts(): int
    {
        return 2;
    }

    /**
     * Build the CLI command array passed to Symfony Process.
     * e.g. ['speedtest', '--format=json', '--accept-license']
     */
    abstract protected function buildCommand(): array;

    /**
     * Normalise the raw CLI JSON into the intermediate array
     * shape that SpeedtestResult::fromNormalised() expects.
     *
     * Keys required:  download_mbps, upload_mbps, ping_ms
     * Keys optional:  jitter_ms, packet_loss, download_bytes,
     *                 upload_bytes, server_name, server_location,
     *                 isp, client_ip
     *
     * @throws SpeedtestException if required fields are absent
     */
    abstract protected function normalise(array $parsed, string $rawJson): array;

    /**
     * @throws Exception
     *
     * @return SpeedtestResult
     */
    public function run(): SpeedtestResult
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < $this->maxAttempts()) {
            $attempts++;

            try {
                return $this->attempt();
            } catch (SpeedtestException $e) {
                $lastError = $e;

                Log::warning('Speedtest service attempt failed', [
                    'provider' => $this->provider->slug->label(),
                    'attempt'  => $attempts,
                    'reason'   => $e->reason->value,
                    'message'  => $e->getMessage(),
                ]);

                if ($attempts < $this->maxAttempts()) {
                    Sleep::sleep(3);
                }
            }
        }

        throw $lastError;
    }

    /**
     *  Run the speedtest command and return the result for single attempt.
     *
     * @return SpeedtestResult
     */
    private function attempt(): SpeedtestResult
    {
        $process = new Process(
            command: $this->buildCommand(),
            timeout: $this->timeout(),
        );

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw SpeedtestException::timeout(
                server : $this->provider->slug,
                seconds: $this->timeout(),
            );
        }

        if (! $process->isSuccessful()) {
            throw SpeedtestException::nonZeroExit(
                server: $this->provider->slug,
                code  : $process->getExitCode(),
                stderr: trim($process->getErrorOutput()),
            );
        }

        $rawJson = trim($process->getOutput());
        $parsed = json_decode($rawJson, associative: true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
            throw SpeedtestException::invalidJson(
                server: $this->provider->slug,
                raw   : $rawJson,
            );
        }

        $normalised = $this->normalise($parsed, $rawJson);

        return SpeedtestResult::fromNormalised(
            server : $this->provider->slug,
            data   : $normalised,
            rawJson: $rawJson,
        );
    }

    /**
     * Assert a key exists in the parsed output.
     * Throws SpeedtestException::missingField() if absent.
     */
    protected function requireField(array $data, string $field): mixed
    {
        if (! array_key_exists($field, $data)) {
            throw SpeedtestException::missingField(
                server: $this->provider->slug,
                field : $field,
            );
        }

        return $data[$field];
    }
}
