<?php

namespace App\Enums;

enum AppearanceEnum: string
{
    case Light = 'light';
    case Dark = 'dark';

    public function isDark(): bool
    {
        return $this === self::Dark;
    }

    public function isLight(): bool
    {
        return $this === self::Light;
    }

    public function label(): string
    {
        return $this->value;
    }
}
