<?php

namespace App\Enums;

enum AlertRuleOperator: string
{
    case Is = 'is';
    case IsNot = 'is_not';
    case IsAbove = 'is_above';
    case IsBelow = 'is_below';

    public function label(): string
    {
        return match ($this) {
            self::Is      => 'is',
            self::IsNot   => 'is not',
            self::IsAbove => 'is above',
            self::IsBelow => 'is below',
        };
    }
}
