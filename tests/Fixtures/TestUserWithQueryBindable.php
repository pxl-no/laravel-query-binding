<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Pxl\QueryBinding\Contracts\QueryBindable;

/**
 * Test user model implementing QueryBindable for integration tests.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 *
 * @implements QueryBindable<TestUserWithQueryBindable>
 */
class TestUserWithQueryBindable extends Model implements QueryBindable
{
    /** @var string */
    protected $table = 'test_users';

    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * Apply default query modifications for route model binding.
     *
     * @param  Builder<TestUserWithQueryBindable>  $query
     * @return Builder<TestUserWithQueryBindable>
     */
    public function scopeForRouteBinding(Builder $query): Builder
    {
        return $query->select(['id', 'name']);
    }
}
