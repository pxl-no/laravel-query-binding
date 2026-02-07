<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Test user model with soft deletes for integration tests.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 */
class TestUserWithSoftDeletes extends Model
{
    use SoftDeletes;

    /** @var string */
    protected $table = 'test_users';

    /** @var array<int, string> */
    protected $guarded = [];
}
