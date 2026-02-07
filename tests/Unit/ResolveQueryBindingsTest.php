<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pxl\QueryBinding\Middleware\ResolveQueryBindings;
use Pxl\QueryBinding\Support\QueryBindingRegistry;

describe('ResolveQueryBindings Middleware', function (): void {
    it('passes request through when no route is set', function (): void {
        $registry = app(QueryBindingRegistry::class);
        $middleware = new ResolveQueryBindings($registry);

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, fn ($req) => new Response('ok', 200));

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('ok');
    });

    it('constructs with registry dependency', function (): void {
        $registry = app(QueryBindingRegistry::class);
        $middleware = new ResolveQueryBindings($registry);

        expect($middleware)->toBeInstanceOf(ResolveQueryBindings::class);
    });
});
