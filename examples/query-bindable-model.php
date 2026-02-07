<?php

declare(strict_types=1);

/**
 * QueryBindable Interface Example
 *
 * Implement the QueryBindable interface on your models to define
 * default query modifications that apply to all route bindings.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pxl\QueryBinding\Contracts\QueryBindable;

/**
 * Post model with default route binding query customization.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $user_id
 * @property bool $published
 *
 * @implements QueryBindable<Post>
 */
class Post extends Model implements QueryBindable
{
    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * Apply default query modifications for route model binding.
     *
     * This scope is automatically applied whenever the model is resolved
     * through route binding, ensuring consistent data loading.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeForRouteBinding(Builder $query): Builder
    {
        return $query
            ->where('published', true)
            ->with(['author:id,name,avatar', 'tags:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'user_id', 'created_at']);
    }

    /**
     * Get the author of the post.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the tags for the post.
     *
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }
}

/*
|--------------------------------------------------------------------------
| Usage in Routes
|--------------------------------------------------------------------------
|
| With the QueryBindable interface implemented, you can still use
| binding methods to add additional customizations.
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindQuery('post', fn ($query) => $query);

Route::get('/posts/{post}/comments', fn (Post $post) => $post->comments)
    ->bindQuery('post', fn ($query) => $query->with('comments.user'));
