<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Override;

class PingTargetIdFilter extends StringFilter
{
    protected string $field = 'ping_target_id';

    /**
     * Override query name to keep API param as target_id.
     */
    #[Override]
    public function queryName(): string
    {
        return 'target_id';
    }

    /** @param Builder<Model> $query */
    #[Override]
    public function apply(Builder $query): Builder
    {
        return $query->where('ping_target_id', $this->values[$this->queryName()]);
    }
}
