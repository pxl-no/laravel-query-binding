<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Test post model for integration tests.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 */
class TestPost extends Model
{
    /** @var string */
    protected $table = 'test_posts';

    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * Get the user that owns the post.
     *
     * @return BelongsTo<TestUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TestUser::class);
    }
}
