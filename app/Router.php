<?php

declare(strict_types=1);

namespace App;

/**
 * CAL-05: router dedicado.
 *
 * Encapsula la resolución de rutas en vez de recorrer un arreglo dentro del
 * front controller. Además de devolver el handler y sus middlewares, detecta
 * cuando la ruta existe para otro método HTTP para responder 405 (antes
 * siempre devolvía 404).
 */
final class Router
{
    /**
     * @param array<string, array<string, array{0: array, 1: list<string>}>> $routes
     */
    public function __construct(private array $routes = [])
    {
    }

    /**
     * Resuelve la ruta para el método y URI dados.
     *
     * @return array{handler: array, middleware: list<string>, params: array<string,int|string>}|null
     */
    public function resolve(string $method, string $uri): ?array
    {
        $uri = rtrim($uri, '/') ?: '/';

        if (isset($this->routes[$method][$uri])) {
            [$handler, $middleware] = $this->routes[$method][$uri];

            return ['handler' => $handler, 'middleware' => $middleware, 'params' => []];
        }

        foreach ($this->routes[$method] ?? [] as $routePath => $route) {
            if (!str_contains($routePath, '{')) {
                continue;
            }

            $params = $this->matchPath($routePath, $uri);
            if ($params !== null) {
                [$handler, $middleware] = $route;

                return ['handler' => $handler, 'middleware' => $middleware, 'params' => $params];
            }
        }

        return null;
    }

    /**
     * Indica si la ruta existe para otro método HTTP (para responder 405).
     */
    public function methodNotAllowed(string $method, string $uri): bool
    {
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $routeMethod => $paths) {
            if ($routeMethod === $method) {
                continue;
            }

            if (isset($paths[$uri])) {
                return true;
            }

            foreach ($paths as $routePath => $_) {
                if (str_contains($routePath, '{') && $this->matchPath($routePath, $uri) !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string,int|string>|null
     */
    private function matchPath(string $routePath, string $uri): ?array
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return null;
        }

        $params = [];
        foreach ($routeParts as $i => $part) {
            if (preg_match('/^\{(\w+)\}$/', $part, $matches)) {
                $value = $uriParts[$i];
                $params[$matches[1]] = is_numeric($value) ? (int) $value : $value;
            } elseif ($part !== $uriParts[$i]) {
                return null;
            }
        }

        return $params;
    }
}
