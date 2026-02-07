<?php

declare(strict_types=1);

namespace Pxl\QueryBinding\Facades;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Pxl\QueryBinding\Support\QueryBindingRegistry;

/**
 * Facade for accessing the QueryBindingRegistry.
 *
 * @method static void register(Route $route, string $parameter, callable $queryCallback)
 * @method static Closure|null get(Route $route, string $parameter)
 * @method static bool has(Route $route, string $parameter)
 * @method static Collection<string, callable> all()
 * @method static void clear()
 * @method static array{0: class-string<Model>, 1: string}|null getBindingInfo(Route $route, string $parameter)
 *
 * @see QueryBindingRegistry
 */
class QueryBinding extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return QueryBindingRegistry::class;
    }
}
