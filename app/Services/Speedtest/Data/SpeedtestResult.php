<?php

namespace App\Services\Speedtest\Data;

use App\Enums\SpeedtestServer;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class SpeedtestResult extends Data
{
    #[Computed]
    public string $providerLabel;

    #[Computed]
    public string $providerWebsite;

    public function __construct(
        // Identity
        public readonly SpeedtestServer $server,

        // Core metrics — required, all CLIs must provide these
        public readonly float $downloadMbps,
        public readonly float $uploadMbps,
        public readonly float $pingMs,

        // Extended metrics — nullable, not all CLIs expose these
        public readonly ?float $jitterMs,
        public readonly ?float $packetLoss,
        public readonly ?int $downloadBytes,
        public readonly ?int $uploadBytes,

        // Connection metadata
        public readonly ?string $serverName,
        public readonly ?string $serverLocation,
        public readonly ?string $isp,
        public readonly ?string $clientIp,

        // Raw CLI output — stored verbatim for debugging
        public readonly string $rawJson,

        // share result url
        public readonly ?string $shareUrl,

        // Timestamp
        public readonly CarbonImmutable $measuredAt,
    ) {
        // Computed from enum — never passed in from outside
        $this->providerLabel = $server->label();
        $this->providerWebsite = $server->websiteLink();
    }

    /**
     * Named constructor used by each concrete service's normalise().
     * Accepts the intermediate normalised array + raw CLI string.
     */
    public static function fromNormalised(
        SpeedtestServer $server,
        array $data,
        string $rawJson,
    ): self {
        return new self(
            server: $server,
            downloadMbps: (float) $data['download_mbps'],
            uploadMbps: (float) $data['upload_mbps'],
            pingMs: (float) $data['ping_ms'],
            jitterMs: isset($data['jitter_ms']) ? (float) $data['jitter_ms'] : null,
            packetLoss: isset($data['packet_loss']) ? (float) $data['packet_loss'] : null,
            downloadBytes: isset($data['download_bytes']) ? (int) $data['download_bytes'] : null,
            uploadBytes: isset($data['upload_bytes']) ? (int) $data['upload_bytes'] : null,
            serverName: $data['server_name'] ?? null,
            serverLocation: $data['server_location'] ?? null,
            isp: $data['isp'] ?? null,
            clientIp: $data['client_ip'] ?? null,
            rawJson: $rawJson,
            shareUrl: $data['share_url'] ?? null,
            measuredAt: CarbonImmutable::now(),
        );
    }

    /**
     * Serialise to the shape expected by SpeedResult::create().
     * Uses spatie/laravel-data's own toArray() with explicit
     * column name mapping rather than the SnakeCaseMapper output
     * (which includes computed fields we don't want to store).
     */
    public function toStorageArray(): array
    {
        return [
            'provider_slug'   => $this->server->value,
            'download_mbps'   => $this->downloadMbps,
            'upload_mbps'     => $this->uploadMbps,
            'ping_ms'         => $this->pingMs,
            'jitter_ms'       => $this->jitterMs,
            'packet_loss'     => $this->packetLoss,
            'download_bytes'  => $this->downloadBytes,
            'upload_bytes'    => $this->uploadBytes,
            'server_name'     => $this->serverName,
            'server_location' => $this->serverLocation,
            'isp'             => $this->isp,
            'share_url'       => $this->shareUrl,
            'client_ip'       => $this->clientIp,
            'raw_json'        => $this->rawJson,
            'measured_at'     => $this->measuredAt,
            'status'          => 'success',
        ];
    }
}
