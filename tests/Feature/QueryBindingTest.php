<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Pxl\QueryBinding\QueryBindingServiceProvider;
use Pxl\QueryBinding\Support\QueryBindingRegistry;
use Pxl\QueryBinding\Tests\Fixtures\TestPost;
use Pxl\QueryBinding\Tests\Fixtures\TestUser;
use Pxl\QueryBinding\Tests\Fixtures\TestUserWithQueryBindable;
use Pxl\QueryBinding\Tests\Fixtures\TestUserWithSlug;
use Pxl\QueryBinding\Tests\Fixtures\TestUserWithSoftDeletes;

beforeEach(function (): void {
    app(QueryBindingRegistry::class)->clear();
    QueryBindingServiceProvider::$boundParameters = [];

    Schema::create('test_users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('slug')->nullable();
        $table->boolean('is_active')->default(true);
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('test_posts', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('title');
        $table->string('slug')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_posts');
    Schema::dropIfExists('test_users');
});

describe('bindQuery', function (): void {
    it('resolves model with custom query selecting specific columns', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindQuery('user', fn ($query) => $query->select(['id', 'name']));

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json())->toHaveKeys(['id', 'name']);
        expect($response->json())->not->toHaveKey('email');
    });

    it('passes parent models to query callback', function (): void {
        $user = TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        TestPost::create(['id' => 1, 'user_id' => 1, 'title' => 'Post 1']);
        TestPost::create(['id' => 2, 'user_id' => 2, 'title' => 'Post 2']);

        Route::get('/users/{user}/posts/{post}', fn (TestUser $user, TestPost $post) => [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ])
            ->middleware(SubstituteBindings::class)
            ->bindQuery('user', fn ($query) => $query)
            ->bindQuery('post', fn ($query, TestUser $user) => $query->where('user_id', $user->id));

        $response = $this->get('/users/1/posts/1');

        $response->assertOk();
        expect($response->json('post_id'))->toBe(1);
    });

    it('throws 404 when model not found', function (): void {
        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindQuery('user', fn ($query) => $query);

        $response = $this->get('/users/999');

        $response->assertNotFound();
    });
});

describe('bindWith', function (): void {
    it('eager loads single relation', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        TestPost::create(['id' => 1, 'user_id' => 1, 'title' => 'Hello World']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWith('user', ['posts']);

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json('posts'))->toHaveCount(1);
    });

    it('eager loads multiple relations', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        TestPost::create(['id' => 1, 'user_id' => 1, 'title' => 'Hello World']);
        TestPost::create(['id' => 2, 'user_id' => 1, 'title' => 'Goodbye World']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWith('user', ['posts']);

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json('posts'))->toHaveCount(2);
    });

    it('accepts string relation name', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        TestPost::create(['id' => 1, 'user_id' => 1, 'title' => 'Hello World']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWith('user', 'posts');

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json('posts'))->toHaveCount(1);
    });
});

describe('bindWithCount', function (): void {
    it('adds relationship count to model', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        TestPost::create(['id' => 1, 'user_id' => 1, 'title' => 'Post 1']);
        TestPost::create(['id' => 2, 'user_id' => 1, 'title' => 'Post 2']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWithCount('user', ['posts']);

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json('posts_count'))->toBe(2);
    });
});

describe('bindSelect', function (): void {
    it('selects only specified columns', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindSelect('user', ['id', 'name']);

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json())->toHaveKeys(['id', 'name']);
        expect($response->json())->not->toHaveKey('email');
    });
});

describe('bindWithTrashed', function (): void {
    it('includes soft deleted models', function (): void {
        $user = TestUserWithSoftDeletes::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        $user->delete();

        Route::get('/admin/users/{user}', fn (TestUserWithSoftDeletes $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWithTrashed('user');

        $response = $this->get('/admin/users/1');

        $response->assertOk();
        expect($response->json('name'))->toBe('John');
    });
});

describe('bindOnlyTrashed', function (): void {
    it('returns only soft deleted models', function (): void {
        $user = TestUserWithSoftDeletes::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        $user->delete();

        Route::get('/trash/users/{user}', fn (TestUserWithSoftDeletes $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindOnlyTrashed('user');

        $response = $this->get('/trash/users/1');

        $response->assertOk();
        expect($response->json('name'))->toBe('John');
    });

    it('returns 404 for non-deleted models', function (): void {
        TestUserWithSoftDeletes::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/trash/users/{user}', fn (TestUserWithSoftDeletes $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindOnlyTrashed('user');

        $response = $this->get('/trash/users/1');

        $response->assertNotFound();
    });
});

describe('bindScoped', function (): void {
    it('applies named scope without arguments', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindScoped('user', 'withEmail');

        $response = $this->get('/users/1');

        $response->assertOk();
    });

    it('applies named scope with arguments', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'is_active' => true]);
        TestUser::create(['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'is_active' => false]);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindScoped('user', 'active');

        $response = $this->get('/users/1');
        $response->assertOk();

        $response = $this->get('/users/2');
        $response->assertNotFound();
    });
});

describe('bindWhere', function (): void {
    it('applies where condition with operator and value', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'is_active' => true]);
        TestUser::create(['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com', 'is_active' => false]);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWhere('user', 'is_active', '=', true);

        $response = $this->get('/users/1');
        $response->assertOk();

        $response = $this->get('/users/2');
        $response->assertNotFound();
    });
});

describe('custom route keys', function (): void {
    it('resolves model by custom route key from route definition', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'slug' => 'john-doe']);

        Route::get('/users/{user:slug}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindQuery('user', fn ($query) => $query);

        $response = $this->get('/users/john-doe');

        $response->assertOk();
        expect($response->json('name'))->toBe('John');
    });

    it('resolves model by getRouteKeyName method', function (): void {
        TestUserWithSlug::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'slug' => 'john-doe']);

        Route::get('/users/{user}', fn (TestUserWithSlug $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindQuery('user', fn ($query) => $query);

        $response = $this->get('/users/john-doe');

        $response->assertOk();
        expect($response->json('name'))->toBe('John');
    });
});

describe('QueryBindable interface', function (): void {
    it('applies scopeForRouteBinding from model', function (): void {
        TestUserWithQueryBindable::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/users/{user}', fn (TestUserWithQueryBindable $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindQuery('user', fn ($query) => $query);

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json())->toHaveKeys(['id', 'name']);
        expect($response->json())->not->toHaveKey('email');
    });
});

describe('chained bindings', function (): void {
    it('allows chaining multiple binding methods', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);
        TestPost::create(['id' => 1, 'user_id' => 1, 'title' => 'Hello World']);

        Route::get('/users/{user}/posts/{post}', fn (TestUser $user, TestPost $post) => [
            'user' => $user->toArray(),
            'post' => $post->toArray(),
        ])
            ->middleware(SubstituteBindings::class)
            ->bindWith('user', ['posts'])
            ->bindQuery('post', fn ($query) => $query->select(['id', 'title', 'user_id']));

        $response = $this->get('/users/1/posts/1');

        $response->assertOk();
        expect($response->json('user.posts'))->toHaveCount(1);
        expect($response->json('post'))->toHaveKeys(['id', 'title', 'user_id']);
    });
});

describe('bindWithoutGlobalScope', function (): void {
    it('removes specified global scope', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com', 'is_active' => false]);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWithoutGlobalScope('user', 'active');

        $response = $this->get('/users/1');

        $response->assertOk();
        expect($response->json('name'))->toBe('John');
    });
});

describe('bindWithoutGlobalScopes', function (): void {
    it('removes all global scopes when no arguments provided', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWithoutGlobalScopes('user');

        $response = $this->get('/users/1');

        $response->assertOk();
    });

    it('removes specified global scopes from array', function (): void {
        TestUser::create(['id' => 1, 'name' => 'John', 'email' => 'john@example.com']);

        Route::get('/users/{user}', fn (TestUser $user) => $user->toArray())
            ->middleware(SubstituteBindings::class)
            ->bindWithoutGlobalScopes('user', ['active']);

        $response = $this->get('/users/1');

        $response->assertOk();
    });
});

describe('middleware registration', function (): void {
    it('registers query-bindings middleware alias', function (): void {
        $router = app(\Illuminate\Routing\Router::class);
        $middleware = $router->getMiddleware();

        expect($middleware)->toHaveKey('query-bindings');
    });
});
