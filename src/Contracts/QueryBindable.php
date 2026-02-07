<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for models that customize their route binding query.
 *
 * Implement this interface to define default query modifications
 * that apply whenever the model is resolved through route binding.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface QueryBindable
{
    /**
     * Apply default query modifications for route model binding.
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeForRouteBinding(Builder $query): Builder;
}
