<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Test user model with custom route key name.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $slug
 */
class TestUserWithSlug extends Model
{
    /** @var string */
    protected $table = 'test_users';

    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
