<?php

namespace App;

class Router
{
    private $routes = [];
    private $notFoundHandler;

    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $this->normalizePath($path),
            'handler' => $handler,
        ];
    }

    public function setNotFound($handler)
    {
        $this->notFoundHandler = $handler;
    }

    public function dispatch($method, $uri)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPath($route['path'], $path);
            if ($params === false) {
                continue;
            }

            return $this->invoke($route['handler'], $params);
        }

        if ($this->notFoundHandler) {
            return $this->invoke($this->notFoundHandler, []);
        }

        http_response_code(404);
        echo '404 Not Found';
        return null;
    }

    private function normalizePath($path)
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function matchPath($pattern, $path)
    {
        if ($pattern === $path) {
            return [];
        }

        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return false;
        }

        array_shift($matches);
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $pattern, $names);

        $params = [];
        foreach ($names[1] as $i => $name) {
            $params[$name] = $matches[$i] ?? null;
        }

        return $params;
    }

    private function invoke($handler, $params)
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler, 2);
            $class = 'App\\Controllers\\' . $class;

            if (!class_exists($class)) {
                throw new \RuntimeException('Controller not found: ' . $class);
            }

            $controller = new $class();

            if (!method_exists($controller, $method)) {
                throw new \RuntimeException('Method not found: ' . $method);
            }

            return call_user_func_array([$controller, $method], $params);
        }

        throw new \RuntimeException('Invalid route handler');
    }
}
