<?php
/**
 * Application path registry.
 *
 * All filesystem paths are resolved once here. Nothing else in the codebase
 * concatenates __DIR__ or hardcodes a path; everything calls path().
 */

defined('APP_ROOT') || define('APP_ROOT', dirname(__DIR__));

return [
    'root'    => APP_ROOT,
    'app'     => APP_ROOT . '/app',
    'config'  => APP_ROOT . '/config',
    'storage' => APP_ROOT . '/storage',
    'public'  => APP_ROOT . '/public',
    'views'   => APP_ROOT . '/app/Views',
    'cache'   => APP_ROOT . '/storage/cache',
    'logs'    => APP_ROOT . '/storage/logs',
    'sessions'=> APP_ROOT . '/storage/sessions',
    'temp'    => APP_ROOT . '/storage/temp',
    'private' => APP_ROOT . '/storage/private',
    'database'=> APP_ROOT . '/database',
    'routes'  => APP_ROOT . '/routes',
    'vendor'  => APP_ROOT . '/vendor',
];
