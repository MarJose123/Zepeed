<?php

namespace App\Concerns;

enum SpeedtestServer: string
{
    case Speedtest = 'Speedtest';
    case Librespeed = 'Librespeed';
    case Fast = 'Fast';

    public function websiteLink(): string
    {
        return match ($this) {
            self::Speedtest  => 'https://www.speedtest.net/',
            self::Librespeed => 'https://librespeed.org/',
            self::Fast       => 'https://fast.com/',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Speedtest  => 'Okla Speedtest',
            self::Librespeed => 'Librespeed',
            self::Fast       => 'Netflix Speedtest',
        };
    }
}
