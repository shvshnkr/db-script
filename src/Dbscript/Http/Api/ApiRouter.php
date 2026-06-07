<?php
declare(strict_types=1);

namespace Dbscript\Http\Api;

use Symfony\Component\HttpFoundation\Request;

final class ApiRouter
{
    /** @var list<array{methods: list<string>, pattern: string, handler: callable, auth: bool}> */
    private array $routes = [];

    /** @param callable(Request): \Symfony\Component\HttpFoundation\Response $handler */
    public function add(string $method, string $pattern, callable $handler, bool $requiresAuth = true): self
    {
        $this->routes[] = [
            'methods' => [strtoupper($method)],
            'pattern' => $pattern,
            'handler' => $handler,
            'auth' => $requiresAuth,
        ];

        return $this;
    }

    /** @return array{handler: callable, auth: bool, params: array<string, string>}|null */
    public function match(Request $request): ?array
    {
        $path = $this->normalizePath($this->requestPath($request));
        $method = strtoupper($request->getMethod());

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            $params = $this->matchPattern($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            return [
                'handler' => $route['handler'],
                'auth' => $route['auth'],
                'params' => $params,
            ];
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = '/' . trim($path, '/');
        if (str_starts_with($path, '/api/index.php')) {
            $path = substr($path, strlen('/api/index.php')) ?: '/';
        }

        return $path === '' ? '/' : $path;
    }

    private function requestPath(Request $request): string
    {
        foreach (['REDIRECT_URL', 'REQUEST_URI'] as $key) {
            $value = $request->server->get($key);
            if (is_string($value) && str_starts_with($value, '/api/v1/')) {
                return $value;
            }
        }

        $pathInfo = $request->getPathInfo();
        if (is_string($pathInfo) && $pathInfo !== '') {
            return $pathInfo;
        }

        return $request->getRequestUri();
    }

    /** @return array<string, string>|null */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!is_string($regex)) {
            return null;
        }

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
