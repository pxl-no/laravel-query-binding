<?php

declare(strict_types=1);

namespace Pxl\QueryBinding;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Pxl\QueryBinding\Middleware\ResolveQueryBindings;
use Pxl\QueryBinding\Support\QueryBindingRegistry;

/**
 * Service provider for the Query Binding package.
 *
 * Registers route macros that enable declarative query customization
 * for Laravel's route model binding.
 */
class QueryBindingServiceProvider extends ServiceProvider
{
    /**
     * Tracks which route parameters have been bound to prevent duplicate bindings.
     *
     * @var array<string, bool>
     */
    public static array $boundParameters = [];

    /**
     * Register package services and configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/query-binding.php', 'query-binding');

        $this->app->singleton(QueryBindingRegistry::class);
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->resetStaticState();
        $this->registerRouteMacros();
        $this->registerMiddleware();
        $this->publishConfig();
    }

    /**
     * Reset static state to support fresh application instances.
     */
    protected function resetStaticState(): void
    {
        static::$boundParameters = [];
        $this->app->make(QueryBindingRegistry::class)->clear();
    }

    /**
     * Register route macros for query binding functionality.
     */
    protected function registerRouteMacros(): void
    {
        $registry = $this->app->make(QueryBindingRegistry::class);
        $router = $this->app->make(Router::class);

        $this->registerBindQueryMacro($registry, $router);
        $this->registerConvenienceMacros();
    }

    /**
     * Register the core bindQuery macro.
     */
    protected function registerBindQueryMacro(QueryBindingRegistry $registry, Router $router): void
    {
        Route::macro('bindQuery', function (string $parameter, callable $queryCallback) use ($registry, $router): Route {
            /** @var Route $this */
            $registry->register($this, $parameter, $queryCallback);

            $shouldRegisterBinding = !isset(QueryBindingServiceProvider::$boundParameters[$parameter]);

            QueryBindingServiceProvider::$boundParameters[$parameter] ??= true;

            $shouldRegisterBinding && $router->bind($parameter, static fn (mixed $value, Route $currentRoute) => $registry->resolve($currentRoute, $parameter, $value) ?? $value);

            return $this;
        });
    }

    /**
     * Register convenience macros that wrap bindQuery.
     */
    protected function registerConvenienceMacros(): void
    {
        Route::macro('bindWith', fn (string $parameter, array|string $relations): Route => $this->bindQuery($parameter, static fn ($query) => $query->with($relations)));

        Route::macro('bindWithCount', fn (string $parameter, array|string $relations): Route => $this->bindQuery($parameter, static fn ($query) => $query->withCount($relations)));

        Route::macro('bindSelect', fn (string $parameter, array $columns): Route => $this->bindQuery($parameter, static fn ($query) => $query->select($columns)));

        Route::macro('bindWithTrashed', fn (string $parameter): Route => $this->bindQuery($parameter, static fn ($query) => $query->withTrashed()));

        Route::macro('bindOnlyTrashed', fn (string $parameter): Route => $this->bindQuery($parameter, static fn ($query) => $query->onlyTrashed()));

        Route::macro('bindScoped', fn (string $parameter, string $scope, mixed ...$args): Route => $this->bindQuery($parameter, static fn ($query) => $query->{$scope}(...$args)));

        Route::macro('bindWhere', fn (string $parameter, string $column, mixed $operator = null, mixed $value = null): Route => $this->bindQuery($parameter, static fn ($query) => $query->where($column, $operator, $value)));

        Route::macro('bindWithoutGlobalScope', fn (string $parameter, string|array $scopes): Route => $this->bindQuery($parameter, static fn ($query) => $query->withoutGlobalScope($scopes)));

        Route::macro('bindWithoutGlobalScopes', fn (string $parameter, ?array $scopes = null): Route => $this->bindQuery($parameter, static fn ($query) => $query->withoutGlobalScopes($scopes)));
    }

    /**
     * Register the query binding middleware alias.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('query-bindings', ResolveQueryBindings::class);
    }

    /**
     * Publish the package configuration file.
     */
    protected function publishConfig(): void
    {
        $this->app->runningInConsole() && $this->publishes([
            __DIR__.'/../config/query-binding.php' => config_path('query-binding.php'),
        ], 'query-binding-config');
    }
}
