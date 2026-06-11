<?php

namespace App\Services\Speedtest;

use App\Services\Speedtest\Exceptions\SpeedtestException;
use Override;

class FastcomService extends AbstractSpeedtestService
{
    protected function buildCommand(): array
    {
        // fast-cli binary is named `fast-cli`, not `fast`
        return ['fast-cli', ...$this->provider->resolvedFlags()];
    }

    protected function normalise(array $parsed, string $rawJson): array
    {
        // fast-cli --upload --json shape:
        // { "download_mbps": 131,   (float)
        //   "ping_ms":       20.8,  (float|null)
        //   "upload_mbps":   62,    (float|null)
        //   "error":         null   (string|null) }

        // Surface any tool-level error before field validation
        if (! empty($parsed['error'])) {
            throw SpeedtestException::nonZeroExit(
                server: $this->provider->slug,
                code  : 1,
                stderr: $parsed['error'],
            );
        }

        $this->requireField($parsed, 'download_mbps');
        $this->requireField($parsed, 'upload_mbps');
        $this->requireField($parsed, 'ping_ms');

        return [
            'download_mbps'   => (float) $parsed['download_mbps'],
            'upload_mbps'     => (float) ($parsed['upload_mbps'] ?? 0.0),
            'ping_ms'         => (float) ($parsed['ping_ms'] ?? 0.0),
            'jitter_ms'       => null, // fast-cli does not report jitter
            'packet_loss'     => null, // fast-cli does not report packet loss
            'download_bytes'  => null, // not exposed by mikkelam/fast-cli
            'upload_bytes'    => null, // not exposed by mikkelam/fast-cli
            'server_name'     => null, // fast.com does not expose server name
            'server_location' => null, // not exposed by mikkelam/fast-cli
            'isp'             => null,
            'client_ip'       => null,
        ];
    }
}
