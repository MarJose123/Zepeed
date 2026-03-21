<?php

namespace App\Services\Speedtest;

use Override;

class FastcomService extends AbstractSpeedtestService
{
    // fast-cli uses Puppeteer — give it more time
    // https://github.com/sindresorhus/fast-cli
    #[Override]
    public function timeout(): int
    {
        return 180;
    }

    protected function buildCommand(): array
    {
        // fast-cli is a Node.js binary — invoked via npx or global install
        return ['fast', ...$this->provider->resolvedFlags()];
    }

    protected function normalise(array $parsed, string $rawJson): array
    {
        // fast-cli --json shape:
        // { "downloadSpeed": <int>,  "downloadUnit": "Mbps",
        //   "uploadSpeed":   <int>,  "uploadUnit":   "Mbps",
        //   "downloaded":    <int>,  "uploaded":     <int>,
        //   "latency":       <int>,
        //   "bufferBloat":   <int>,
        //   "userLocation":  "",
        //   "userIp":        "" }

        $this->requireField($parsed, 'downloadSpeed');
        $this->requireField($parsed, 'uploadSpeed');
        $this->requireField($parsed, 'latency');

        return [
            'download_mbps'   => (float) $parsed['downloadSpeed'],
            'upload_mbps'     => (float) $parsed['uploadSpeed'],
            'ping_ms'         => (float) $parsed['latency'],
            'jitter_ms'       => null, // fast-cli does not report jitter
            'packet_loss'     => null, // fast-cli does not report packet loss
            'download_bytes'  => isset($parsed['downloaded']) ? (int) $parsed['downloaded'] : null,
            'upload_bytes'    => isset($parsed['uploaded']) ? (int) $parsed['uploaded'] : null,
            'server_name'     => null, // fast.com does not expose server name
            'server_location' => $parsed['userLocation'] ?? null,
            'isp'             => null,
            'client_ip'       => $parsed['userIp'] ?? null,
        ];
    }
}
