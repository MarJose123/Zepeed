<?php

namespace App\Services\Speedtest;

use App\Services\Speedtest\Exceptions\SpeedtestException;

class CloudflareSpeedService extends AbstractSpeedtestService
{
    protected function buildCommand(): array
    {
        return ['cloudflare-speed-cli', ...$this->provider->resolvedFlags()];
    }

    /**
     * Normalise the Cloudflare speed CLI JSON output (RunResult shape).
     *
     * Cloudflare output top-level keys relevant here:
     *   download               ThroughputSummary  { bytes, duration_ms, mbps, mean_mbps, median_mbps, ... }
     *   upload                 ThroughputSummary  { bytes, duration_ms, mbps, mean_mbps, median_mbps, ... }
     *   idle_latency           LatencySummary     { sent, received, loss (0-1), min_ms, mean_ms, median_ms, jitter_ms, ... }
     *   loaded_latency_download LatencySummary
     *   loaded_latency_upload   LatencySummary
     *   ip                     ?string  — external IP (may be IPv4 or IPv6)
     *   external_ipv4          ?string  — explicit IPv4
     *   colo                   ?string  — Cloudflare PoP code, e.g. "SIN", "LAX"
     *   as_org                 ?string  — ASN org name, used as ISP
     *   connection_quality     ?object  — quality scoring
     *   experimental_udp       ?object  — UDP packet loss / jitter
     *
     * Latency decision: median_ms is preferred over mean_ms because it is
     * more robust against burst spikes that inflate the mean during a loaded
     * test; falls back to mean_ms when median is absent.
     *
     * Packet loss: Cloudflare expresses loss as a decimal fraction (0–1).
     * Multiplied by 100 here to align with the percentage-scale convention
     * used across all other providers (e.g. 0.05 → 5.0).
     *
     * Extra diagnostics: loaded latency, UDP stats, and connection quality
     * are stored verbatim in Provider.meta so they are available for future
     * dashboard widgets without requiring a schema change.
     *
     * @throws SpeedtestException
     */
    protected function normalise(array $parsed, string $rawJson): array
    {
        $download = $this->requireField($parsed, 'download');
        $upload = $this->requireField($parsed, 'upload');
        $idleLatency = $this->requireField($parsed, 'idle_latency');

        // Validate required nested fields
        if (! is_array($download) || ! isset($download['mbps'])) {
            throw SpeedtestException::missingField($this->provider->slug, 'download.mbps');
        }

        if (! is_array($upload) || ! isset($upload['mbps'])) {
            throw SpeedtestException::missingField($this->provider->slug, 'upload.mbps');
        }

        if (! is_array($idleLatency)) {
            throw SpeedtestException::missingField($this->provider->slug, 'idle_latency');
        }

        // Prefer median; fall back to mean — more stable under burst conditions
        $pingMs = $idleLatency['median_ms'] ?? $idleLatency['mean_ms'] ?? null;

        if ($pingMs === null) {
            throw SpeedtestException::missingField($this->provider->slug, 'idle_latency.median_ms');
        }

        // Cloudflare loss is 0-1 decimal fraction — convert to percentage scale
        $packetLoss = isset($idleLatency['loss'])
            ? round((float) $idleLatency['loss'] * 100, 2)
            : null;

        // Prefer explicit external_ipv4; fall back to generic ip field
        $clientIp = $parsed['external_ipv4'] ?? $parsed['ip'] ?? null;

        // Persist extended diagnostics into the provider's meta column so
        // future dashboard features can surface loaded latency, UDP quality,
        // connection scoring, and Cloudflare network metadata without a migration.
        $this->storeExtendedDiagnostics($parsed);

        return [
            'download_mbps'   => round((float) $download['mbps'], 2),
            'upload_mbps'     => round((float) $upload['mbps'], 2),
            'ping_ms'         => round((float) $pingMs, 2),
            'jitter_ms'       => isset($idleLatency['jitter_ms'])
                ? round((float) $idleLatency['jitter_ms'], 2)
                : null,
            'packet_loss'     => $packetLoss,
            'download_bytes'  => isset($download['bytes']) ? (int) $download['bytes'] : null,
            'upload_bytes'    => isset($upload['bytes']) ? (int) $upload['bytes'] : null,
            'server_name'     => $parsed['server'] ?? null,
            'server_location' => $parsed['colo'] ?? null,
            'isp'             => $parsed['as_org'] ?? null,
            'share_url'       => null, // Cloudflare speed test has no shareable result URL
            'client_ip'       => is_string($clientIp) ? $clientIp : null,
        ];
    }

    /**
     * Persist extended Cloudflare diagnostics into the Provider meta column.
     *
     * Stored keys:
     *   cloudflare_loaded_latency_download — LatencySummary during download phase
     *   cloudflare_loaded_latency_upload   — LatencySummary during upload phase
     *   cloudflare_udp                     — ExperimentalUdpSummary (packet loss, jitter, MOS)
     *   cloudflare_connection_quality      — quality scoring object
     *   cloudflare_network                 — ASN, colo, wireless flag, network name
     *   cloudflare_last_result_at          — ISO timestamp from the CLI output
     */
    private function storeExtendedDiagnostics(array $parsed): void
    {
        /** @var array<string, mixed> $existing */
        $existing = is_array($this->provider->meta) ? $this->provider->meta : [];

        $diagnostics = array_filter([
            'cloudflare_loaded_latency_download' => $parsed['loaded_latency_download'] ?? null,
            'cloudflare_loaded_latency_upload'   => $parsed['loaded_latency_upload'] ?? null,
            'cloudflare_udp'                     => $parsed['experimental_udp'] ?? null,
            'cloudflare_connection_quality'      => $parsed['connection_quality'] ?? null,
            'cloudflare_network'                 => array_filter([
                'asn'          => $parsed['asn'] ?? null,
                'colo'         => $parsed['colo'] ?? null,
                'is_wireless'  => $parsed['is_wireless'] ?? null,
                'network_name' => $parsed['network_name'] ?? null,
            ]),
            'cloudflare_last_result_at'          => $parsed['timestamp_utc'] ?? null,
        ], static fn (mixed $v): bool => $v !== null);

        $this->provider->update([
            'meta' => array_merge($existing, $diagnostics),
        ]);
    }
}
