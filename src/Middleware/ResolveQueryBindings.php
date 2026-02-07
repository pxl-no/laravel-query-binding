<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Pxl\QueryBinding\Support\QueryBindingRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for resolving query bindings before controller execution.
 *
 * Intercepts requests and resolves route parameters using registered
 * query callbacks, enabling custom query logic for model binding.
 */
class ResolveQueryBindings
{
    public function __construct(
        protected QueryBindingRegistry $registry,
    ) {}

    /**
     * Handle an incoming request and resolve query bindings.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        ($route instanceof Route) && $this->resolveBindings($route);

        return $next($request);
    }

    /**
     * Resolve all parameters for the given route.
     */
    protected function resolveBindings(Route $route): void
    {
        collect($route->parameters())
            ->reject(fn (mixed $value): bool => $value instanceof Model)
            ->each(fn (mixed $value, string $parameter) => $this->resolveParameter($route, $parameter, $value));
    }

    /**
     * Resolve a single route parameter and update the route if necessary.
     */
    protected function resolveParameter(Route $route, string $parameter, mixed $value): void
    {
        $resolved = $this->registry->resolve($route, $parameter, $value);

        ($resolved !== null) && $route->setParameter($parameter, $resolved);
    }
}
