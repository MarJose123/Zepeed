<?php

namespace App\Models\Filters;

use App\Enums\PingResultStatus;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;

class StatusFilter extends EnumFilter
{
    protected string $field = 'status';
    protected string $enum = PingResultStatus::class;
}
