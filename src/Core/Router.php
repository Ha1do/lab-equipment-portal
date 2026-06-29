<?php

class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];

        // Получаем URI без query string
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Если скрипт лежит не в корне домена — отрезаем base path
        // Например: /student10/index.php → BASE_DIR = /student10
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptDir && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = rtrim($uri, '/') ?: '/';

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            if (is_callable($handler)) {
                call_user_func($handler);
            } elseif (is_array($handler)) {
                [$controller, $action] = $handler;
                (new $controller)->$action();
            }
            return;
        }

        http_response_code(404);
        require BASE_PATH . '/src/Views/pages/404.php';
    }
}