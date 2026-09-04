<?php
/**
 * Global helper functions.
 *
 * Keep this file small. Each function here must earn its place by being
 * genuinely needed from multiple unrelated call sites.
 */

use Unwinded\Core\Container;

/**
 * Resolve a service from the container, or return the container itself.
 */
function app(string $abstract = null): mixed
{
    $container = Container::getInstance();
    return $abstract === null ? $container : $container->make($abstract);
}

/**
 * Get a config value using dot notation. Returns $default if not found.
 */
function config(string $key, mixed $default = null): mixed
{
    return app('config')->get($key, $default);
}

/**
 * Get the absolute path for a named path key, optionally appending a suffix.
 */
function path(string $key, string $suffix = ''): string
{
    $paths = app('paths');
    $base  = $paths[$key] ?? throw new InvalidArgumentException("Unknown path key: {$key}");
    return $suffix === '' ? $base : $base . '/' . ltrim($suffix, '/');
}

/**
 * HTML-escape a value for safe output in an HTML context.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape for use inside an HTML attribute value (already inside quotes).
 */
function attr(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape for use inside a JavaScript string literal (JSON-encode is safest).
 */
function js(mixed $value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * Generate an absolute URL. Uses APP_URL as the base — never $_SERVER['HTTP_HOST'].
 */
function url(string $path = '', array $params = []): string
{
    $base = rtrim(config('app.url', ''), '/');
    $path = '/' . ltrim($path, '/');
    $url  = $base . $path;
    return $params ? $url . '?' . http_build_query($params) : $url;
}

/**
 * Generate a URL for a public asset (cache-busted via manifest hash).
 */
function asset(string $path): string
{
    static $manifest = null;
    if ($manifest === null) {
        $manifestFile = path('public', 'assets/dist/manifest.json');
        $manifest = file_exists($manifestFile)
            ? (json_decode(file_get_contents($manifestFile), true) ?? [])
            : [];
    }
    $path = ltrim($path, '/');
    $hashed = $manifest[$path] ?? $path;
    return url('assets/' . $hashed);
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url, int $status = 302): never
{
    // Prevent open-redirect: only allow same-origin or absolute URLs that match APP_URL
    $appUrl = rtrim(config('app.url', ''), '/');
    if (!str_starts_with($url, '/') && !str_starts_with($url, $appUrl)) {
        $url = '/';
    }
    http_response_code($status);
    header('Location: ' . $url);
    exit;
}

/**
 * Format a monetary value in South African rand.
 */
function money(int|float $cents, bool $showCents = true): string
{
    $rand = $cents / 100;
    return 'R\u{202F}' . number_format($rand, $showCents ? 2 : 0, '.', '\u{202F}');
}

/**
 * Generate a CSRF token field for use in forms.
 */
function csrf_field(): string
{
    $token = app('csrf')->token();
    return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
}

/**
 * Get the authenticated admin user, or null.
 */
function auth(): ?array
{
    return app('auth')->user();
}

/**
 * Check if the current user has a permission.
 */
function can(string $permission): bool
{
    return app('auth')->can($permission);
}

/**
 * Flash a message into the session, or return the session object when called
 * with no arguments (for e.g. flash()->getAll() in templates).
 */
function flash(?string $type = null, ?string $message = null): mixed
{
    $session = app('session');
    if ($type === null) {
        return $session;
    }
    $session->flash($type, $message ?? '');
    return null;
}

/**
 * Log a message to the application log.
 */
function logger(string $level, string $message, array $context = []): void
{
    app('logger')->log($level, $message, $context);
}

/**
 * Generate a cryptographically random public reference.
 * e.g. "UNW-B-7F3K9XQ2TB"
 */
function public_ref(string $prefix): string
{
    return \Unwinded\Support\Ref::generate($prefix);
}

/**
 * Get the current timestamp as a Carbon-less UTC datetime string.
 */
function now(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
        ->format('Y-m-d H:i:s');
}

/**
 * Return CSS class 'active' when the current request URI matches the given path.
 * Pass $exact = true to match only the exact path; false allows prefix matching.
 */
function active(string $path, bool $exact = false): string
{
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $current = rtrim($current ?? '/', '/') ?: '/';
    $path    = rtrim($path, '/') ?: '/';

    $match = $exact
        ? ($current === $path)
        : str_starts_with($current, $path);

    return $match ? 'is-active' : '';
}

/**
 * Dump and die (development only).
 */
function dd(mixed ...$values): never
{
    if (config('app.env') === 'production') {
        throw new RuntimeException('dd() called in production');
    }
    foreach ($values as $value) {
        echo '<pre>' . e(print_r($value, true)) . '</pre>';
    }
    exit;
}
