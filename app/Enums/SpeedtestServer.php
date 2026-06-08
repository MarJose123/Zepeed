<?php

namespace App\Enums;

use App\Services\Speedtest\FastcomService;
use App\Services\Speedtest\LibrespeedService;
use App\Services\Speedtest\OklaSpeedtestService;

enum SpeedtestServer: string
{
    case Ookla = 'ookla';
    case Librespeed = 'librespeed';
    case Netflix = 'netflix';

    public function label(): string
    {
        return match ($this) {
            SpeedtestServer::Ookla      => 'Ookla',
            SpeedtestServer::Librespeed => 'LibreSpeed',
            SpeedtestServer::Netflix    => 'Netflix',
        };
    }

    /**
     * Get the slug value (alias for the backing value).
     */
    public function slug(): string
    {
        return $this->value;
    }

    public function websiteLink(): string
    {
        return match ($this) {
            self::Ookla               => 'https://www.speedtest.net/',
            self::Librespeed          => 'https://librespeed.org/',
            self::Netflix             => 'https://fast.com/',
        };
    }

    /**
     * Returns the concrete service class for this provider.
     * Used by SpeedtestServiceProvider to resolve the correct
     * SpeedtestServiceInterface binding.
     */
    public function serviceClass(): string
    {
        return match ($this) {
            self::Ookla               => OklaSpeedtestService::class,
            self::Librespeed          => LibrespeedService::class,
            self::Netflix             => FastcomService::class,
        };
    }

    /**
     * The default CLI flags for this provider.
     *
     * @return string[]
     */
    public function defaultFlags(): array
    {
        return match ($this) {
            self::Ookla               => ['--format=json', '--accept-license', '--accept-gdpr'],
            self::Librespeed          => ['--json', '--share', '--no-icmp'],
            self::Netflix             => ['--upload', '--json'],
        };
    }

    public function requiresServerUrl(): bool
    {
        return match ($this) {
            self::Librespeed, self::Ookla, self::Netflix => false,
        };
    }

    public function supportServerUrl(): bool
    {
        return match ($this) {
            self::Librespeed                        => true,
            self::Ookla, self::Netflix              => false,
        };
    }

    /**
     * Whether this provider requires Puppeteer/Chromium to run.
     * Used to surface a warning in the Providers UI if Chromium
     * is not detected in the container.
     */
    public function requiresChromium(): bool
    {
        return match ($this) {
            self::Netflix , self::Ookla, self::Librespeed       => false,
        };
    }
}
