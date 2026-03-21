<?php

namespace App\Services\Speedtest;

class LibrespeedService extends AbstractSpeedtestService
{
    protected function buildCommand(): array
    {
        $flags = $this->provider->resolvedFlags();

        // Self-hosted server URL — required field, validated before enabling
        if ($serverUrl = $this->provider->server_url) {
            $flags[] = "--server={$serverUrl}";
        }

        return ['librespeed-cli', ...$flags];
    }

    protected function normalise(array $parsed, string $rawJson): array
    {
        // librespeed-cli JSON shape (returns an array, take first element):
        // [{ "download": <Mbps float>,
        //    "upload":   <Mbps float>,
        //    "ping":     <ms float>,
        //    "jitter":   <ms float>,
        //    "server": { "name": "", "host": "" },
        //    "client": { "ip": "", "isp": "" } }]

        // CLI returns a JSON array — unwrap first result
        $result = is_array($parsed[0] ?? null) ? $parsed[0] : $parsed;

        $this->requireField($result, 'download');
        $this->requireField($result, 'upload');
        $this->requireField($result, 'ping');

        return [
            // librespeed reports directly in Mbps
            'download_mbps'   => round((float) $result['download'], 2),
            'upload_mbps'     => round((float) $result['upload'], 2),
            'ping_ms'         => (float) $result['ping'],
            'jitter_ms'       => isset($result['jitter']) ? (float) $result['jitter'] : null,
            'packet_loss'     => null, // librespeed-cli does not report packet loss
            'download_bytes'  => null,
            'upload_bytes'    => null,
            'server_name'     => $result['server']['name'] ?? null,
            'server_location' => $result['server']['host'] ?? null,
            'isp'             => $result['client']['isp'] ?? null,
            'client_ip'       => $result['client']['ip'] ?? null,
        ];
    }
}
