# Laravel Query Binding

Declarative route model binding with full query builder control.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pxl/laravel-query-binding.svg?style=flat-square)](https://packagist.org/packages/pxl/laravel-query-binding)
[![Total Downloads](https://img.shields.io/packagist/dt/pxl/laravel-query-binding.svg?style=flat-square)](https://packagist.org/packages/pxl/laravel-query-binding)
[![Tests](https://img.shields.io/github/actions/workflow/status/pxl-no/laravel-query-binding/tests.yml?label=tests&style=flat-square)](https://github.com/pxl-no/laravel-query-binding/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%209-brightgreen?style=flat-square)](https://phpstan.org/)

## The Problem

Laravel's route model binding is convenient but inflexible. You lose control over the query when using implicit binding:

```php
Route::get('/users/{user}', function (User $user) {
    return $user;
});
```

Common pain points:
- **N+1 queries**: No way to eager load relationships in the binding
- **Over-fetching**: Can't select specific columns
- **Soft deletes**: Must use `withTrashed()` in the controller
- **Scopes**: Can't apply query scopes declaratively

This package solves these problems with a clean, declarative API.

## Installation

```bash
composer require pxl/laravel-query-binding
```

The package auto-registers its service provider. No additional configuration required.

## Quick Start

```php
use App\Models\User;

Route::get('/users/{user}', fn (User $user) => $user)
    ->bindWith('user', ['posts', 'comments']);
```

## API Reference

### Core Method

#### `bindQuery(string $parameter, callable $callback): Route`

The foundation method that all other methods build upon. Accepts a query callback for complete control.

```php
Route::get('/users/{user}', fn (User $user) => $user)
    ->bindQuery('user', fn ($query) => $query
        ->select(['id', 'name', 'email'])
        ->with('profile')
        ->where('active', true));
```

**Parent Model Access**: Query callbacks receive previously resolved models as additional parameters:

```php
Route::get('/users/{user}/posts/{post}', fn (User $user, Post $post) => $post)
    ->bindQuery('post', fn ($query, User $user) => $query
        ->where('user_id', $user->id)
        ->with('tags'));
```

### Convenience Methods

#### `bindWith(string $parameter, array|string $relations): Route`

Eager load relationships to prevent N+1 queries.

```php
Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindWith('post', ['author', 'tags', 'comments.user']);

Route::get('/users/{user}', fn (User $user) => $user)
    ->bindWith('user', 'posts');
```

#### `bindWithCount(string $parameter, array|string $relations): Route`

Add relationship counts without loading the relationships.

```php
Route::get('/users/{user}', fn (User $user) => [
    'user' => $user,
    'posts_count' => $user->posts_count,
])
    ->bindWithCount('user', ['posts', 'comments']);
```

#### `bindSelect(string $parameter, array $columns): Route`

Select specific columns for optimized queries.

```php
Route::get('/users/{user}', fn (User $user) => $user)
    ->bindSelect('user', ['id', 'name', 'avatar']);
```

#### `bindWithTrashed(string $parameter): Route`

Include soft-deleted models in the query.

```php
Route::get('/admin/users/{user}', fn (User $user) => $user)
    ->bindWithTrashed('user');
```

#### `bindOnlyTrashed(string $parameter): Route`

Return only soft-deleted models.

```php
Route::get('/trash/users/{user}', fn (User $user) => $user)
    ->bindOnlyTrashed('user');
```

#### `bindScoped(string $parameter, string $scope, mixed ...$args): Route`

Apply a named model scope.

```php
Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindScoped('post', 'published');

Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindScoped('post', 'byCategory', 'technology');
```

#### `bindWhere(string $parameter, string $column, mixed $operator = null, mixed $value = null): Route`

Apply a simple where condition.

```php
Route::get('/users/{user}', fn (User $user) => $user)
    ->bindWhere('user', 'active', true);

Route::get('/users/{user}', fn (User $user) => $user)
    ->bindWhere('user', 'role', '!=', 'admin');
```

#### `bindWithoutGlobalScope(string $parameter, string|array $scopes): Route`

Remove specific global scopes.

```php
Route::get('/admin/posts/{post}', fn (Post $post) => $post)
    ->bindWithoutGlobalScope('post', 'published');
```

#### `bindWithoutGlobalScopes(string $parameter, ?array $scopes = null): Route`

Remove all or specified global scopes.

```php
Route::get('/admin/posts/{post}', fn (Post $post) => $post)
    ->bindWithoutGlobalScopes('post');

Route::get('/admin/posts/{post}', fn (Post $post) => $post)
    ->bindWithoutGlobalScopes('post', ['published', 'active']);
```

## Advanced Usage

### Custom Route Keys

Works seamlessly with Laravel's custom route key syntax:

```php
Route::get('/users/{user:slug}', fn (User $user) => $user)
    ->bindWith('user', ['posts']);

Route::get('/posts/{post:uuid}', fn (Post $post) => $post)
    ->bindQuery('post', fn ($query) => $query->with('author'));
```

Also respects the model's `getRouteKeyName()` method:

```php
class User extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

### QueryBindable Interface

Implement `QueryBindable` on your models to define default binding behavior:

```php
use Pxl\QueryBinding\Contracts\QueryBindable;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model implements QueryBindable
{
    public function scopeForRouteBinding(Builder $query): Builder
    {
        return $query
            ->with(['author:id,name', 'tags'])
            ->where('published', true);
    }
}
```

The `scopeForRouteBinding` is automatically applied, and you can add additional customizations:

```php
Route::get('/posts/{post}', fn (Post $post) => $post)
    ->bindQuery('post', fn ($query) => $query->withCount('comments'));
```

### Method Chaining

Chain multiple binding methods for complex requirements:

```php
Route::get('/users/{user}/posts/{post}', fn (User $user, Post $post) => [
    'user' => $user,
    'post' => $post,
])
    ->bindWith('user', ['profile'])
    ->bindWithCount('user', ['posts'])
    ->bindQuery('post', fn ($query, User $user) => $query
        ->where('user_id', $user->id)
        ->with('tags'));
```

### Nested Resource Scoping

Scope child resources to their parent models:

```php
Route::get('/teams/{team}/projects/{project}/tasks/{task}',
    fn (Team $team, Project $project, Task $task) => $task
)
    ->bindQuery('project', fn ($query, Team $team) => $query
        ->where('team_id', $team->id))
    ->bindQuery('task', fn ($query, Team $team, Project $project) => $query
        ->where('project_id', $project->id));
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=query-binding-config
```

```php
// config/query-binding.php
return [
    'global_middleware' => true,
];
```

### Middleware

The package registers a `query-bindings` middleware alias. Use it if you disable global middleware:

```php
Route::middleware('query-bindings')->group(function () {
    Route::get('/users/{user}', fn (User $user) => $user)
        ->bindSelect('user', ['id', 'name']);
});
```

## How It Works

1. Route macros register query callbacks in a singleton registry
2. When routes are resolved, the registered callback is retrieved
3. The model class is determined via reflection on the controller signature
4. A fresh query builder is created and the callback is applied
5. The model is resolved using the customized query
6. The resolved model replaces the route parameter value

Standard Laravel binding handles parameters without registered callbacks.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Testing

```bash
composer test
```

Run with coverage:

```bash
composer test:coverage
```

Static analysis:

```bash
composer analyse
```

Code formatting:

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please [contact us](https://pxl.no/en/contact)

## Credits

- [PXL AS](https://pxl.no)

## License

MIT License. See [LICENSE](LICENSE) for details.
