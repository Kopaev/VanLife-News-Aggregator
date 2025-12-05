<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use InvalidArgumentException;

class Router
{
    /** @var array<int, array{method:string,path:string,pattern:string,params:array<int,string>,handler:callable|array}> */
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler): self
    {
        $method = strtoupper($method);
        [$pattern, $params] = $this->compilePath($path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'params' => $params,
            'handler' => $handler,
        ];

        return $this;
    }

    public function get(string $path, callable|array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri, array $context = []): Response
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = $this->extractParams($route['params'], $matches);
                $handler = $this->resolveHandler($route['handler'], $context);

                $result = $handler($params, $context);

                return $this->normalizeResponse($result);
            }
        }

        return Response::json(['error' => 'Not Found'], 404);
    }

    private function compilePath(string $path): array
    {
        $params = [];
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', function ($matches) use (&$params) {
            $params[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $path);

        return ['#^' . $regex . '$#', $params];
    }

    private function extractParams(array $names, array $matches): array
    {
        $params = [];
        foreach ($names as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }

        return $params;
    }

    private function resolveHandler(callable|array $handler, array $context): callable
    {
        if ($handler instanceof Closure) {
            return $handler;
        }

        if (is_array($handler) && count($handler) === 2 && is_string($handler[0]) && is_string($handler[1])) {
            $class = $handler[0];
            $method = $handler[1];

            if (!class_exists($class)) {
                throw new InvalidArgumentException("Handler class {$class} does not exist");
            }

            $instance = new $class($context);

            return [$instance, $method];
        }

        if (is_callable($handler)) {
            return $handler;
        }

        throw new InvalidArgumentException('Invalid route handler');
    }

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string)$result);
    }
}
