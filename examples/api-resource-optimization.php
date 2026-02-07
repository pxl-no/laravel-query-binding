<?php

declare(strict_types=1);

/**
 * API Resource Optimization Example
 *
 * This example demonstrates how to optimize API responses by combining
 * query binding with Laravel API Resources for efficient data loading.
 */

namespace App\Http\Controllers\Api;

use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Problem: N+1 Queries in API Resources
|--------------------------------------------------------------------------
|
| Without eager loading, API resources often cause N+1 query problems:
|
| // Bad: This causes N+1 queries for posts and comments
| Route::get('/users/{user}', fn (User $user) => new UserResource($user));
|
*/

/*
|--------------------------------------------------------------------------
| Solution: Eager Load in Route Binding
|--------------------------------------------------------------------------
|
| Use bindWith() to eager load exactly what the resource needs:
|
*/

Route::prefix('api/v1')->group(function (): void {

    Route::get('/users/{user}', fn (User $user) => new UserResource($user))
        ->bindWith('user', [
            'profile',
            'posts' => fn ($query) => $query->latest()->limit(5),
            'posts.tags',
        ]);

    Route::get('/users/{user}/posts', fn (User $user) => PostResource::collection($user->posts))
        ->bindWith('user', [
            'posts.author:id,name',
            'posts.tags:id,name',
            'posts.comments' => fn ($query) => $query->withCount('likes'),
        ]);

    Route::get('/posts/{post}', fn (Post $post) => new PostResource($post))
        ->bindQuery('post', fn ($query) => $query
            ->with([
                'author:id,name,avatar',
                'tags:id,name,slug',
                'comments' => fn ($q) => $q
                    ->latest()
                    ->limit(10)
                    ->with('user:id,name,avatar'),
            ])
            ->withCount(['comments', 'likes']));

});

/*
|--------------------------------------------------------------------------
| Combining with Column Selection
|--------------------------------------------------------------------------
|
| For even better optimization, combine eager loading with column selection:
|
*/

Route::get('/api/v1/posts/{post}/card', fn (Post $post) => [
    'id' => $post->id,
    'title' => $post->title,
    'author' => $post->author->name,
    'comments_count' => $post->comments_count,
])
    ->bindQuery('post', fn ($query) => $query
        ->select(['id', 'title', 'user_id'])
        ->with('author:id,name')
        ->withCount('comments'));

/*
|--------------------------------------------------------------------------
| Different Bindings for Different Endpoints
|--------------------------------------------------------------------------
|
| The same model can have different binding configurations per endpoint:
|
*/

Route::get('/api/posts/{post}', fn (Post $post) => new PostResource($post))
    ->bindWith('post', ['author', 'tags']);

Route::get('/api/posts/{post}/full', fn (Post $post) => new PostResource($post))
    ->bindWith('post', ['author', 'tags', 'comments.user', 'relatedPosts']);

Route::get('/api/posts/{post}/minimal', fn (Post $post) => [
    'id' => $post->id,
    'title' => $post->title,
])
    ->bindSelect('post', ['id', 'title']);
