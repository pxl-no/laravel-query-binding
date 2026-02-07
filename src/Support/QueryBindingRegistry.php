<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Pxl\QueryBinding\Contracts\QueryBindable;
use ReflectionNamedType;

/**
 * Registry for managing query binding callbacks.
 *
 * Stores and retrieves query callbacks associated with route parameters,
 * handles model resolution, and supports custom route key configurations.
 */
class QueryBindingRegistry
{
    /**
     * Collection of registered query binding callbacks.
     *
     * @var Collection<string, callable>
     */
    protected Collection $bindings;

    public function __construct()
    {
        $this->bindings = collect();
    }

    /**
     * Register a query callback for a route parameter.
     */
    public function register(Route $route, string $parameter, callable $queryCallback): void
    {
        $this->bindings->put(
            $this->buildKey($route, $parameter),
            $queryCallback,
        );
    }

    /**
     * Retrieve a query callback as a Closure.
     */
    public function get(Route $route, string $parameter): ?Closure
    {
        $callback = $this->bindings->get($this->buildKey($route, $parameter));

        return $callback ? Closure::fromCallable($callback) : null;
    }

    /**
     * Check whether a binding exists for the given route and parameter.
     */
    public function has(Route $route, string $parameter): bool
    {
        return $this->bindings->has($this->buildKey($route, $parameter));
    }

    /**
     * Resolve a route parameter to its model using the registered query callback.
     *
     * @throws ModelNotFoundException
     */
    public function resolve(Route $route, string $parameter, mixed $value): ?Model
    {
        $bindingInfo = $this->getBindingInfo($route, $parameter);

        return match (true) {
            !$this->has($route, $parameter) => null,
            $bindingInfo === null => null,
            default => $this->executeQuery($route, $parameter, $value, $bindingInfo),
        };
    }

    /**
     * Extract model class and binding field from route signature.
     *
     * @return array{0: class-string<Model>, 1: string}|null
     */
    public function getBindingInfo(Route $route, string $parameter): ?array
    {
        return collect($route->signatureParameters())
            ->map(fn (\ReflectionParameter $param) => $this->extractBindingFromParameter($route, $parameter, $param))
            ->first(fn (?array $info): bool => $info !== null);
    }

    /**
     * Retrieve all registered bindings.
     *
     * @return Collection<string, callable>
     */
    public function all(): Collection
    {
        return $this->bindings;
    }

    /**
     * Clear all registered bindings.
     */
    public function clear(): void
    {
        $this->bindings = collect();
    }

    /**
     * Execute the query callback and resolve the model.
     *
     * @param  array{0: class-string<Model>, 1: string}  $bindingInfo
     *
     * @throws ModelNotFoundException
     */
    protected function executeQuery(Route $route, string $parameter, mixed $value, array $bindingInfo): Model
    {
        [$modelClass, $bindingField] = $bindingInfo;

        $query = $this->buildQuery($route, $parameter, $modelClass);

        /** @var int|string $valueId */
        $valueId = $value;

        return $query->where($bindingField, $value)->firstOr(
            fn () => throw (new ModelNotFoundException)->setModel($modelClass, [$valueId]),
        );
    }

    /**
     * Build the query with all applicable callbacks applied.
     *
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    protected function buildQuery(Route $route, string $parameter, string $modelClass): Builder
    {
        /** @var Builder<Model> $query */
        $query = $modelClass::query();

        $model = $query->getModel();
        ($model instanceof QueryBindable) && $model->scopeForRouteBinding($query);

        $callback = $this->get($route, $parameter);
        $callback && $callback($query, ...$this->getResolvedModels($route));

        return $query;
    }

    /**
     * Get only the models that have been resolved from route parameters.
     *
     * @return array<int, Model>
     */
    protected function getResolvedModels(Route $route): array
    {
        return collect($route->parameters())
            ->filter(fn (mixed $value): bool => $value instanceof Model)
            ->values()
            ->all();
    }

    /**
     * Extract binding information from a reflection parameter.
     *
     * @return array{0: class-string<Model>, 1: string}|null
     */
    protected function extractBindingFromParameter(Route $route, string $parameter, \ReflectionParameter $signatureParameter): ?array
    {
        $isMatchingParameter = $signatureParameter->getName() === $parameter;

        $type = $signatureParameter->getType();
        $isValidType = $type instanceof ReflectionNamedType;

        $className = $isValidType ? $type->getName() : '';
        $isEloquentModel = class_exists($className) && is_subclass_of($className, Model::class);

        return match (true) {
            !$isMatchingParameter => null,
            !$isValidType => null,
            !$isEloquentModel => null,
            default => [$className, $this->getBindingField($route, $parameter, $className)],
        };
    }

    /**
     * Build a unique key for storing binding callbacks.
     */
    protected function buildKey(Route $route, string $parameter): string
    {
        return sprintf(
            '%s:%s:%s',
            implode('|', $route->methods()),
            $route->uri(),
            $parameter,
        );
    }

    /**
     * Determine the database field used for model binding.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function getBindingField(Route $route, string $parameter, string $modelClass): string
    {
        $bindingFields = $route->bindingFields();

        return $bindingFields[$parameter] ?? (new $modelClass)->getRouteKeyName();
    }
}
