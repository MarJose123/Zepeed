<?php

namespace App\Enums;

use App\Services\Speedtest\FastcomService;
use App\Services\Speedtest\LibrespeedService;
use App\Services\Speedtest\OklaSpeedtestService;

enum SpeedtestServer: string
{
    case Speedtest = 'speedtest';
    case Librespeed = 'librespeed';
    case NetflixSpeedtest = 'netflix-speedtest';

    public function label(): string
    {
        return match ($this) {
            self::Speedtest           => 'Speedtest Ookla',
            self::Librespeed          => 'LibreSpeed',
            self::NetflixSpeedtest    => 'Netflix Speedtest',
        };
    }

    public function websiteLink(): string
    {
        return match ($this) {
            self::Speedtest           => 'https://www.speedtest.net/',
            self::Librespeed          => 'https://librespeed.org/',
            self::NetflixSpeedtest    => 'https://fast.com/',
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
            self::Speedtest           => OklaSpeedtestService::class,
            self::Librespeed          => LibrespeedService::class,
            self::NetflixSpeedtest    => FastcomService::class,
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
            self::Speedtest           => ['--format=json', '--accept-license', '--accept-gdpr'],
            self::Librespeed          => ['--json', '--share', '--no-icmp'],
            self::NetflixSpeedtest    => ['--upload', '--json'],
        };
    }

    public function requiresServerUrl(): bool
    {
        return match ($this) {
            self::Librespeed, self::Speedtest, self::NetflixSpeedtest => false,
        };
    }

    public function supportServerUrl(): bool
    {
        return match ($this) {
            self::Librespeed => true,
            self::Speedtest, self::NetflixSpeedtest => false,
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
            self::NetflixSpeedtest => true,
            self::Speedtest, self::Librespeed       => false,
        };
    }
}
