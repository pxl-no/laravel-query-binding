<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Pxl\QueryBinding\Support\QueryBindingRegistry;

beforeEach(function (): void {
    $this->registry = new QueryBindingRegistry;
});

describe('register', function (): void {
    it('stores callback for route parameter', function (): void {
        $route = createMockRoute('GET', '/users/{user}');
        $callback = fn ($query) => $query;

        $this->registry->register($route, 'user', $callback);

        expect($this->registry->has($route, 'user'))->toBeTrue();
    });

    it('stores multiple callbacks for different parameters', function (): void {
        $route = createMockRoute('GET', '/users/{user}/posts/{post}');

        $this->registry->register($route, 'user', fn ($q) => $q);
        $this->registry->register($route, 'post', fn ($q) => $q);

        expect($this->registry->has($route, 'user'))->toBeTrue();
        expect($this->registry->has($route, 'post'))->toBeTrue();
    });
});

describe('get', function (): void {
    it('returns closure for registered callback', function (): void {
        $route = createMockRoute('GET', '/users/{user}');
        $callback = fn ($query) => $query->select(['id']);

        $this->registry->register($route, 'user', $callback);

        expect($this->registry->get($route, 'user'))->toBeInstanceOf(Closure::class);
    });

    it('returns null for unregistered parameter', function (): void {
        $route = createMockRoute('GET', '/users/{user}');

        expect($this->registry->get($route, 'user'))->toBeNull();
    });
});

describe('has', function (): void {
    it('returns true for registered binding', function (): void {
        $route = createMockRoute('GET', '/users/{user}');
        $this->registry->register($route, 'user', fn ($q) => $q);

        expect($this->registry->has($route, 'user'))->toBeTrue();
    });

    it('returns false for unregistered binding', function (): void {
        $route = createMockRoute('GET', '/users/{user}');

        expect($this->registry->has($route, 'user'))->toBeFalse();
    });

    it('differentiates between routes with same uri but different methods', function (): void {
        $getRoute = createMockRoute('GET', '/users/{user}');
        $postRoute = createMockRoute('POST', '/users/{user}');

        $this->registry->register($getRoute, 'user', fn ($q) => $q);

        expect($this->registry->has($getRoute, 'user'))->toBeTrue();
        expect($this->registry->has($postRoute, 'user'))->toBeFalse();
    });
});

describe('all', function (): void {
    it('returns empty collection initially', function (): void {
        expect($this->registry->all())->toBeEmpty();
    });

    it('returns all registered bindings', function (): void {
        $route = createMockRoute('GET', '/users/{user}');
        $this->registry->register($route, 'user', fn ($q) => $q);

        expect($this->registry->all())->toHaveCount(1);
    });
});

describe('clear', function (): void {
    it('removes all registered bindings', function (): void {
        $route = createMockRoute('GET', '/users/{user}');
        $this->registry->register($route, 'user', fn ($q) => $q);

        $this->registry->clear();

        expect($this->registry->all())->toBeEmpty();
        expect($this->registry->has($route, 'user'))->toBeFalse();
    });
});

function createMockRoute(string $method, string $uri): Route
{
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('methods')->andReturn([$method]);
    $route->shouldReceive('uri')->andReturn($uri);

    return $route;
}
