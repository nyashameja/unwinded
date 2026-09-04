<?php
/**
 * Unwinded CMS — Front Controller
 *
 * This is the ONLY PHP file in the public web root.
 * It bootstraps the application and dispatches the HTTP request.
 * Nothing else in public/ is PHP-executable.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Bootstrap: returns the configured container.
$container = require APP_ROOT . '/bootstrap/app.php';

// Load all route files.
$router = $container->make('router');
$router->load(APP_ROOT . '/routes/web.php');
$router->load(APP_ROOT . '/routes/admin.php');
$router->load(APP_ROOT . '/routes/api.php');
$router->load(APP_ROOT . '/routes/webhooks.php');

// Build the request from superglobals.
$request = new \Unwinded\Core\Request();
$container->instance('request', $request);
$container->instance(\Unwinded\Core\Request::class, $request);

// Apply security headers to every response.
$headers = new \Unwinded\Middleware\SecurityHeadersMiddleware($container);
$headers->handle($request);

// Dispatch and send.
$response = $router->dispatch($request);
$response->send();
