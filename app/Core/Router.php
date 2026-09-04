<?php
declare(strict_types=1);

namespace Unwinded\Core;

/**
 * Regex-based router. Routes are defined in routes/*.php files.
 * Named parameters are captured as {name} and passed to the controller.
 */
class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $groupStack = [];

    public function __construct(private Container $container) {}

    // ── Registration ────────────────────────────────────────────────────────

    public function get(string $pattern, string|callable $handler, string $name = null): void
    {
        $this->addRoute('GET', $pattern, $handler, $name);
    }

    public function post(string $pattern, string|callable $handler, string $name = null): void
    {
        $this->addRoute('POST', $pattern, $handler, $name);
    }

    public function put(string $pattern, string|callable $handler, string $name = null): void
    {
        $this->addRoute('PUT', $pattern, $handler, $name);
    }

    public function patch(string $pattern, string|callable $handler, string $name = null): void
    {
        $this->addRoute('PATCH', $pattern, $handler, $name);
    }

    public function delete(string $pattern, string|callable $handler, string $name = null): void
    {
        $this->addRoute('DELETE', $pattern, $handler, $name);
    }

    public function match(array $methods, string $pattern, string|callable $handler, string $name = null): void
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $pattern, $handler, $name);
        }
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $pattern, string|callable $handler, ?string $name): void
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $prefix     .= $group['prefix']     ?? '';
            $middleware  = array_merge($middleware, $group['middleware'] ?? []);
        }
        $fullPattern = $prefix . $pattern;
        $route = [
            'method'     => $method,
            'pattern'    => $fullPattern,
            'regex'      => $this->compilePattern($fullPattern),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
        $this->routes[] = $route;
        if ($name !== null) {
            $this->namedRoutes[$name] = $fullPattern;
        }
    }

    private function compilePattern(string $pattern): string
    {
        $regex = preg_replace('/\{([a-z_][a-z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    // ── Dispatch ────────────────────────────────────────────────────────────

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return $this->runRoute($route, $request, $params);
        }

        // Check for wrong-method routes (405) before returning 404.
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path)) {
                return $this->methodNotAllowed();
            }
        }

        return $this->notFound($request);
    }

    private function runRoute(array $route, Request $request, array $params): Response
    {
        $handler = $route['handler'];

        // Resolve middleware (in order)
        foreach ($route['middleware'] as $mwClass) {
            $mw = new $mwClass($this->container);
            $response = $mw->handle($request);
            if ($response !== null) {
                return $response;
            }
        }

        if (is_callable($handler)) {
            return $handler($request, $params, $this->container);
        }

        // "ControllerClass@method" string
        [$controllerClass, $method] = explode('@', $handler, 2);
        $controller = new $controllerClass($this->container);
        return $controller->$method($request, $params);
    }

    private function notFound(Request $request): Response
    {
        // Check the redirects table before returning 404.
        try {
            $db = $this->container->make('db');
            $row = $db->fetchOne(
                "SELECT target_url, status_code FROM redirects WHERE source_url = ? AND is_active = 1 LIMIT 1",
                [$request->path()]
            );
            if ($row) {
                $response = new Response();
                return $response->redirect($row['target_url'], (int) $row['status_code']);
            }
        } catch (\Throwable) {
            // DB may not be available during install; fall through to 404.
        }

        $view = $this->container->make('view');
        $response = new Response();
        return $response->html($view->render('errors/404', ['path' => $request->path()]), 404);
    }

    private function methodNotAllowed(): Response
    {
        $view = $this->container->make('view');
        $response = new Response();
        return $response->html($view->render('errors/405'), 405);
    }

    // ── URL generation ──────────────────────────────────────────────────────

    public function route(string $name, array $params = []): string
    {
        $pattern = $this->namedRoutes[$name]
            ?? throw new \InvalidArgumentException("No route named [{$name}]");
        return preg_replace_callback('/\{([a-z_][a-z0-9_]*)\}/', function ($m) use (&$params) {
            if (!array_key_exists($m[1], $params)) {
                throw new \InvalidArgumentException("Missing route param [{$m[1]}]");
            }
            $val = $params[$m[1]];
            unset($params[$m[1]]);
            return urlencode((string) $val);
        }, $pattern);
    }

    public function load(string $file): void
    {
        $router = $this;
        require $file;
    }
}
