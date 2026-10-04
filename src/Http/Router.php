<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    /** @var array<string, callable(Request): Response> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET ' . $path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $handler = $this->routes[$request->method . ' ' . $request->path] ?? null;
        if ($handler === null) {
            return Response::json(['error' => 'NOT_FOUND'], 404);
        }

        return $handler($request);
    }
}

