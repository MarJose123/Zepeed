<?php

namespace App\Services\Speedtest;

class OklaSpeedtestService extends AbstractSpeedtestService
{
    protected function buildCommand(): array
    {
        $flags = $this->provider->resolvedFlags();

        // Inject server ID if configured
        if ($serverId = $this->provider->server_id) {
            $flags[] = "--server-id={$serverId}";
        }

        return ['speedtest', ...$flags];
    }

    protected function normalise(array $parsed, string $rawJson): array
    {
        // Ookla JSON shape:
        // { "download": { "bandwidth": <bytes/s> },
        //   "upload":   { "bandwidth": <bytes/s> },
        //   "ping":     { "latency": <ms>, "jitter": <ms> },
        //   "packetLoss": <float>,
        //   "server":   { "name": "", "location": "" },
        //   "isp": "",
        //   "interface": { "externalIp": "" } }

        $download = $this->requireField($parsed, 'download');
        $upload = $this->requireField($parsed, 'upload');
        $ping = $this->requireField($parsed, 'ping');

        return [
            // Ookla reports bandwidth in bytes/s — convert to Mbps
            'download_mbps'   => round($download['bandwidth'] * 8 / 1_000_000, 2),
            'upload_mbps'     => round($upload['bandwidth'] * 8 / 1_000_000, 2),
            'ping_ms'         => $ping['latency'],
            'jitter_ms'       => $ping['jitter'] ?? null,
            'packet_loss'     => $parsed['packetLoss'] ?? null,
            'download_bytes'  => $download['bytes'] ?? null,
            'upload_bytes'    => $upload['bytes'] ?? null,
            'server_name'     => $parsed['server']['name'] ?? null,
            'server_location' => $parsed['server']['location'] ?? null,
            'isp'             => $parsed['isp'] ?? null,
            'share_url'       => $parsed['result']['url'] ?? null,
            'client_ip'       => $parsed['interface']['externalIp'] ?? null,
        ];
    }
}
