<?php

namespace App\Enums;

enum SeasonalThemeEnum: string
{
    case None = 'none';
    case Halloween = 'halloween';
    case Christmas = 'christmas';
    case NewYear = 'newyear';

    public function label(): string
    {
        return config("seasonal-theme.themes.{$this->value}.label", $this->value);
    }

    public function description(): string
    {
        return config("seasonal-theme.themes.{$this->value}.description", '');
    }

    public function cssClass(): string
    {
        return $this === self::None ? '' : "theme-{$this->value}";
    }

    public static function resolveActive(): self
    {
        $active = config('seasonal-theme.active');

        if ($active === 'auto') {
            return self::resolveFromSchedule();
        }

        return self::tryFrom($active) ?? self::None;
    }

    public static function all(): array
    {
        return self::cases();
    }

    private static function resolveFromSchedule(): self
    {
        $today = now()->format('m-d');

        foreach (config('seasonal-theme.schedules', []) as $theme => $range) {
            $start = $range['start'];
            $end = $range['end'];

            if ($today >= $start && $today <= $end) {
                return self::tryFrom($theme) ?? self::None;
            }
        }

        return self::None;
    }
}
