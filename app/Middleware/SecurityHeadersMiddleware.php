<?php
declare(strict_types=1);

namespace Unwinded\Middleware;

use Unwinded\Core\{Container, Request, Response};

class SecurityHeadersMiddleware
{
    public function __construct(private Container $container) {}

    public function handle(Request $request): ?Response
    {
        $env = config('app.env');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        if ($env === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        // CSP — note: 'unsafe-inline' is NOT present. Any scripts must be loaded via src attributes.
        header("Content-Security-Policy: default-src 'self'; "
            . "script-src 'self'; "
            . "style-src 'self' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data:; "
            . "connect-src 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'none';");
        return null; // Continue pipeline
    }
}
