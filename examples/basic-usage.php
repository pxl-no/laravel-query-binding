<?php

declare(strict_types=1);

/**
 * Basic Usage Examples for Laravel Query Binding
 *
 * These examples demonstrate how to use query binding macros
 * in your Laravel route definitions.
 */

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Basic Query Customization
|--------------------------------------------------------------------------
|
| Use bindQuery() to apply any query modifications when resolving models.
|
*/

Route::get('/users/{user}', fn (User $user) => $user)
    ->bindQuery('user', fn ($query) => $query->select(['id', 'name', 'email']));

Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindQuery('post', fn ($query) => $query->where('published', true));

/*
|--------------------------------------------------------------------------
| Eager Loading with bindWith()
|--------------------------------------------------------------------------
|
| Prevent N+1 queries by eager loading relationships.
|
*/

Route::get('/users/{user}/profile', fn (User $user) => $user)
    ->bindWith('user', ['posts', 'comments', 'profile']);

Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindWith('post', ['author', 'tags', 'comments.user']);

/*
|--------------------------------------------------------------------------
| Relationship Counts with bindWithCount()
|--------------------------------------------------------------------------
|
| Add relationship counts without loading the full relationships.
|
*/

Route::get('/users/{user}/stats', fn (User $user) => [
    'user' => $user,
    'posts_count' => $user->posts_count,
    'comments_count' => $user->comments_count,
])->bindWithCount('user', ['posts', 'comments']);

/*
|--------------------------------------------------------------------------
| Column Selection with bindSelect()
|--------------------------------------------------------------------------
|
| Optimize queries by selecting only the columns you need.
|
*/

Route::get('/users/{user}/card', fn (User $user) => $user)
    ->bindSelect('user', ['id', 'name', 'avatar']);

/*
|--------------------------------------------------------------------------
| Soft Deleted Models
|--------------------------------------------------------------------------
|
| Include or exclusively query soft deleted models.
|
*/

Route::get('/admin/users/{user}', fn (User $user) => $user)
    ->bindWithTrashed('user');

Route::get('/trash/users/{user}', fn (User $user) => $user)
    ->bindOnlyTrashed('user');

/*
|--------------------------------------------------------------------------
| Named Scopes with bindScoped()
|--------------------------------------------------------------------------
|
| Apply model scopes during binding resolution.
|
*/

Route::get('/active-users/{user}', fn (User $user) => $user)
    ->bindScoped('user', 'active');

Route::get('/featured-posts/{post}', fn (Post $post) => $post)
    ->bindScoped('post', 'featured');

/*
|--------------------------------------------------------------------------
| Conditional Filtering with bindWhere()
|--------------------------------------------------------------------------
|
| Apply simple where conditions during binding.
|
*/

Route::get('/verified-users/{user}', fn (User $user) => $user)
    ->bindWhere('user', 'email_verified_at', '!=', null);

/*
|--------------------------------------------------------------------------
| Custom Route Keys
|--------------------------------------------------------------------------
|
| Works with custom route key definitions (slug, uuid, etc.).
|
*/

Route::get('/users/{user:slug}', fn (User $user) => $user)
    ->bindWith('user', ['posts']);

Route::get('/posts/{post:uuid}', fn (Post $post) => $post)
    ->bindQuery('post', fn ($query) => $query->with('author'));

/*
|--------------------------------------------------------------------------
| Nested Resources with Parent Access
|--------------------------------------------------------------------------
|
| Access parent models in child query callbacks.
|
*/

Route::get('/users/{user}/posts/{post}', fn (User $user, Post $post) => [
    'user' => $user,
    'post' => $post,
])
    ->bindWith('user', ['profile'])
    ->bindQuery('post', fn ($query, User $user) => $query
        ->where('user_id', $user->id)
        ->with('tags'));

Route::get('/posts/{post}/comments/{comment}', fn (Post $post, Comment $comment) => [
    'post' => $post,
    'comment' => $comment,
])
    ->bindWith('post', ['author'])
    ->bindQuery('comment', fn ($query, Post $post) => $query
        ->where('post_id', $post->id)
        ->with('user'));

/*
|--------------------------------------------------------------------------
| Chaining Multiple Binding Methods
|--------------------------------------------------------------------------
|
| Combine multiple binding methods for complex requirements.
|
*/

Route::get('/dashboard/users/{user}/posts/{post}', fn (User $user, Post $post) => [
    'user' => $user,
    'post' => $post,
])
    ->bindWith('user', ['profile', 'settings'])
    ->bindWithCount('user', ['posts', 'comments'])
    ->bindQuery('post', fn ($query, User $user) => $query
        ->where('user_id', $user->id)
        ->with(['tags', 'comments' => fn ($q) => $q->latest()->limit(5)])
        ->select(['id', 'title', 'slug', 'created_at', 'user_id']));

/*
|--------------------------------------------------------------------------
| Global Scopes Bypass
|--------------------------------------------------------------------------
|
| Remove global scopes when needed.
|
*/

Route::get('/admin/all-posts/{post}', fn (Post $post) => $post)
    ->bindWithoutGlobalScopes('post');

Route::get('/admin/unfiltered-posts/{post}', fn (Post $post) => $post)
    ->bindWithoutGlobalScope('post', 'published');
