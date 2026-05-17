<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;

trait HasFilters
{
    /**
     * @return Builder|HigherOrderWhenProxy
     */
    #[Scope]
    protected function filter(Builder $query, array $filters)
    {
        return $query
            ->when($filters['id'] ?? null, fn ($query, $value) => $query->where('id', $value))
            ->when($filters['batch_id'] ?? null, fn ($query, $value) => $query->where('batch_id', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['channel'] ?? null, fn ($query, $value) => $query->where('channel', $value))
            ->when($filters['from'] ?? null, fn ($query, $value) => $query->where('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($query, $value) => $query->where('created_at', '<=', $value));
    }
}
