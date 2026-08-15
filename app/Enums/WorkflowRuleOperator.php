<?php

namespace App\Enums;

enum WorkflowRuleOperator: string
{
    case Is = 'is';
    case IsNot = 'is_not';
    case IsAbove = 'is_above';
    case IsBelow = 'is_below';
    case IsAboveOrEqual = 'is_above_or_equal';
    case IsBelowOrEqual = 'is_below_or_equal';

    public function label(): string
    {
        return match ($this) {
            self::Is             => 'is',
            self::IsNot          => 'is not',
            self::IsAbove        => 'is above',
            self::IsBelow        => 'is below',
            self::IsAboveOrEqual => 'is above or equal',
            self::IsBelowOrEqual => 'is below or equal',
        };
    }
}
