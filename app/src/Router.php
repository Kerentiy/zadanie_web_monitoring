<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, AbstractController> */
    private array $routes = [];

    public function add(string $method, string $path, AbstractController $controller): self
    {
        $this->routes[strtoupper($method) . ' ' . $path] = $controller;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $controller = $this->routes[$request->method . ' ' . $request->path] ?? null;
        $pathRegistered = (bool) array_filter(
            array_keys($this->routes),
            fn (string $route): bool => str_ends_with($route, ' ' . $request->path),
        );
        // Keep HTTP metric labels bounded to declared paths; arbitrary unknown URLs
        // are all grouped under `other` to prevent cardinality attacks.
        $_SERVER['APP_METRIC_ROUTE'] = $pathRegistered ? $request->path : 'other';
        if ($controller !== null) {
            return $controller($request);
        }
        if ($pathRegistered) {
            return new Response('Method Not Allowed', 405, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
