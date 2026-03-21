<?php

namespace App\Enums;

enum MaintenanceWindowType: string
{
    case Indefinite = 'indefinite';
    case OneTime = 'one_time';
    case Recurring = 'recurring';

    public function label(): string
    {
        return match ($this) {
            self::Indefinite => 'Indefinite',
            self::OneTime    => 'One-time',
            self::Recurring  => 'Recurring',
        };
    }

    public function requiresDateRange(): bool
    {
        return $this === self::OneTime;
    }

    public function requiresCronExpression(): bool
    {
        return $this === self::Recurring;
    }
}
