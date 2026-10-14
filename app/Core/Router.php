<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

final class Router
{
    /** @var list<array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(Request $request): void
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            $regex = $this->compilePattern($route['pattern']);
            if (!preg_match($regex, $request->path(), $matches)) {
                continue;
            }

            $pathMatched = true;
            if ($route['method'] !== $request->method()) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])($request, $params);

            return;
        }

        throw new HttpException($pathMatched ? 405 : 404, $pathMatched ? 'Metode tidak diizinkan.' : 'Halaman tidak ditemukan.');
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    private function compilePattern(string $pattern): string
    {
        $quoted = preg_quote($pattern, '#');
        $withParams = preg_replace('#\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\}#', '(?<$1>[^/]+)', $quoted);

        return '#^' . $withParams . '$#';
    }
}
