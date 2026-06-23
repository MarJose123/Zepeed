<?php

namespace App\Models\Filters;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\DateFilter;

class MeasuredAtFromFilter extends DateFilter
{
    protected string $field = 'measured_at';
    public FilterMode $mode = FilterMode::GREATER_OR_EQUAL;
}
