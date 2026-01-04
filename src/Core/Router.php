<?php
declare(strict_types=1);

namespace Erp2\Core;

final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    /**
     * Rutas dinámicas con parámetros tipo /terceros/{id}
     * {id} / {cid} se consideran numéricos.
     *
     * @var array<string, array<int, array{regex: string, handler: array{0: class-string, 1: string}, params: list<string>}>>
     */
    private array $dynamicRoutes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, array $handler): void
    {
        $this->register('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->register('POST', $path, $handler);
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalize($path);

        // 1) Match exacto (compatibilidad total con rutas existentes)
        $handler = $this->routes[$method][$path] ?? null;
        if ($handler) {
            $this->invoke($handler, []);
            return;
        }

        // 2) Match dinámico
        foreach ($this->dynamicRoutes[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $m) !== 1) {
                continue;
            }

            $args = [];
            foreach ($route['params'] as $name) {
                $val = $m[$name] ?? null;
                if (!is_string($val) || $val === '' || !ctype_digit($val)) {
                    // Por contrato de este hito: params son numéricos
                    http_response_code(404);
                    echo "404 Not Found";
                    return;
                }
                $args[] = (int) $val;
            }

            $this->invoke($route['handler'], $args);
            return;
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    /** @param array{0: class-string, 1: string} $handler */
    private function invoke(array $handler, array $args): void
    {
        [$class, $action] = $handler;

        if (!class_exists($class)) {
            http_response_code(500);
            echo "500 Controller not found";
            return;
        }

        $controller = new $class();

        if (!method_exists($controller, $action)) {
            http_response_code(500);
            echo "500 Action not found";
            return;
        }

        // Llamada con args (vacío para rutas exactas)
        $controller->$action(...$args);
    }

    /** @param array{0: class-string, 1: string} $handler */
    private function register(string $method, string $path, array $handler): void
    {
        $path = $this->normalize($path);

        if (strpos($path, '{') === false) {
            $this->routes[$method][$path] = $handler;
            return;
        }

        // Extraer {param} y generar regex numérico
        $params = [];
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $matches) === 0) {
            // Si tiene '{' pero no matchea patrón, tratar como ruta exacta
            $this->routes[$method][$path] = $handler;
            return;
        }

        foreach ($matches[1] as $p) {
            $params[] = $p;
        }

        // Reemplazar tokens por grupos nombrados numéricos
        $regex = preg_quote($path, '#');
        $regex = preg_replace('/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/', '(?P<$1>\\d+)', $regex);
        $regex = '#^' . $regex . '$#';

        $this->dynamicRoutes[$method][] = [
            'regex' => $regex,
            'handler' => $handler,
            'params' => $params,
        ];
    }

    private function normalize(string $path): string
    {
        if ($path === '') {
            return '/';
        }
        $path = '/' . ltrim($path, '/');
        return rtrim($path, '/') ?: '/';
    }
}