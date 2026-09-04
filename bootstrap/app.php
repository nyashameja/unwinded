<?php
/**
 * Application bootstrap.
 *
 * This file is the single entry point for the entire application.
 * It is required by public/index.php (web) and bin/console (CLI).
 * It must not produce output.
 */

declare(strict_types=1);

use Unwinded\Core\Container;
use Unwinded\Core\Config;
use Unwinded\Core\Database;
use Unwinded\Core\Logger;
use Unwinded\Core\Router;
use Unwinded\Core\Request;
use Unwinded\Core\Session;
use Unwinded\Core\Csrf;
use Unwinded\Core\Auth;
use Unwinded\Core\View;
use Unwinded\Core\RateLimiter;
use Unwinded\Core\EventBus;

// ── 1. Autoloader ──────────────────────────────────────────────────────────
defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

// ── 2. Paths ───────────────────────────────────────────────────────────────
$paths = require APP_ROOT . '/bootstrap/paths.php';

// ── 3. Environment ─────────────────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
try {
    $dotenv->load();
} catch (Dotenv\Exception\InvalidPathException) {
    // .env may not exist in tests; config falls back to defaults.
}

// ── 4. Container ───────────────────────────────────────────────────────────
$container = Container::getInstance();
$container->instance('paths', $paths);

// ── 5. Config ──────────────────────────────────────────────────────────────
$config = new Config(APP_ROOT . '/config');
$container->instance('config', $config);

// ── 6. Error handling ──────────────────────────────────────────────────────
$debug = (bool) ($config->get('app.debug') ?? false);

ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$logger = new Logger(
    $paths['logs'] . '/app.log',
    $config->get('app.env', 'production') === 'production' ? 'warning' : 'debug'
);
$container->instance('logger', $logger);

set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use ($logger): bool {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    $logger->error("PHP Error [{$errno}]: {$errstr}", ['file' => $errfile, 'line' => $errline]);
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    return true;
});

set_exception_handler(static function (Throwable $e) use ($logger, $debug, $container): void {
    $ref = 'ERR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $logger->critical("Unhandled exception [{$ref}]: " . $e->getMessage(), [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $debug ? $e->getTraceAsString() : '[hidden in production]',
    ]);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    if ($debug) {
        echo '<pre style="font:14px monospace;padding:2rem;">';
        echo e($e->getMessage()) . "\n\n" . e($e->getTraceAsString());
        echo '</pre>';
    } else {
        // Render a branded error page if possible; otherwise a plain fallback.
        $viewFile = APP_ROOT . '/app/Views/errors/500.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<!doctype html><html><head><title>Error — Unwinded</title></head>';
            echo '<body style="font-family:sans-serif;text-align:center;padding:4rem;">';
            echo '<h1>Something went wrong</h1>';
            echo '<p>Reference: <strong>' . e($ref) . '</strong></p>';
            echo '</body></html>';
        }
    }
    exit(1);
});

// ── 7. Timezone ────────────────────────────────────────────────────────────
date_default_timezone_set($config->get('app.timezone', 'Africa/Johannesburg'));

// ── 8. Database ────────────────────────────────────────────────────────────
$db = new Database($config->get('database', []));
$container->instance('db', $db);

// ── 9. Session (web only) ──────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    $session = new Session(
        savePath:  $paths['sessions'],
        secure:    $config->get('app.env') === 'production',
        cookieName: 'unwinded_session'
    );
    $session->start();
    $container->instance('session', $session);

    $csrf = new Csrf($session);
    $container->instance('csrf', $csrf);
}

// ── 10. Auth ───────────────────────────────────────────────────────────────
$auth = new Auth(
    db:      $db,
    session: PHP_SAPI !== 'cli' ? ($container->make('session')) : null,
    logger:  $logger
);
$container->instance('auth', $auth);

// ── 11. View ───────────────────────────────────────────────────────────────
$view = new View($paths['views'], $config->get('app', []));
$container->instance('view', $view);

// ── 12. Rate limiter ───────────────────────────────────────────────────────
$rateLimiter = new RateLimiter($db);
$container->instance('rateLimiter', $rateLimiter);

// ── 13. Event bus ──────────────────────────────────────────────────────────
$eventBus = new EventBus();
$container->instance('events', $eventBus);

// ── 14. Router ─────────────────────────────────────────────────────────────
$router = new Router($container);
$container->instance('router', $router);

return $container;
