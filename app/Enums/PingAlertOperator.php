<?php

namespace App\Enums;

enum PingAlertOperator: string
{
    case IsAbove = 'is_above';
    case IsBelow = 'is_below';
    case IsAboveOrEqual = 'is_above_or_equal';
    case IsBelowOrEqual = 'is_below_or_equal';
    case Is = 'is';
    case IsNot = 'is_not';

    public function label(): string
    {
        return match ($this) {
            self::IsAbove        => 'is above',
            self::IsBelow        => 'is below',
            self::IsAboveOrEqual => 'is above or equal',
            self::IsBelowOrEqual => 'is below or equal',
            self::Is             => 'is',
            self::IsNot          => 'is not',
        };
    }
}
