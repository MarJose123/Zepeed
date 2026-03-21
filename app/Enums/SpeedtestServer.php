<?php

namespace App\Enums;

use App\Services\Speedtest\FastcomService;
use App\Services\Speedtest\LibrespeedService;
use App\Services\Speedtest\OklaSpeedtestService;

enum SpeedtestServer: string
{
    case Speedtest = 'speedtest';
    case Librespeed = 'librespeed';
    case Fastcom = 'fastcom';

    public function label(): string
    {
        return match ($this) {
            self::Speedtest  => 'Speedtest Ookla',
            self::Librespeed => 'LibreSpeed',
            self::Fastcom    => 'Fast.com',
        };
    }

    public function websiteLink(): string
    {
        return match ($this) {
            self::Speedtest  => 'https://www.speedtest.net/',
            self::Librespeed => 'https://librespeed.org/',
            self::Fastcom    => 'https://fast.com/',
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
            self::Speedtest  => OklaSpeedtestService::class,
            self::Librespeed => LibrespeedService::class,
            self::Fastcom    => FastcomService::class,
        };
    }

    public function defaultFlags(): array
    {
        return match ($this) {
            self::Speedtest  => ['--format=json', '--accept-license', '--accept-gdpr'],
            self::Librespeed => ['--json'],
            self::Fastcom    => ['--upload', '--json'],
        };
    }

    public function requiresServerUrl(): bool
    {
        return $this === self::Librespeed;
    }

    /**
     * Whether this provider requires Puppeteer/Chromium to run.
     * Used to surface a warning in the Providers UI if Chromium
     * is not detected in the container.
     */
    public function requiresChromium(): bool
    {
        return $this === self::Fastcom;
    }
}
