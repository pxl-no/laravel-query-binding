<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Test user model for integration tests.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $slug
 * @property bool $is_active
 */
class TestUser extends Model
{
    /** @var string */
    protected $table = 'test_users';

    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * Get the posts for the user.
     *
     * @return HasMany<TestPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }

    /**
     * Scope to filter users with email.
     *
     * @param  Builder<TestUser>  $query
     * @return Builder<TestUser>
     */
    public function scopeWithEmail(Builder $query): Builder
    {
        return $query->whereNotNull('email');
    }

    /**
     * Scope to filter active users.
     *
     * @param  Builder<TestUser>  $query
     * @return Builder<TestUser>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
