<?php
declare(strict_types=1);

namespace Unwinded\Middleware;

use Unwinded\Core\{Container, Request, Response};

class CsrfMiddleware
{
    // Routes that are exempt from CSRF (webhooks verify their own signatures).
    private const EXEMPT = ['/webhooks'];

    public function __construct(private Container $container) {}

    public function handle(Request $request): ?Response
    {
        if ($request->isGet()) return null;
        foreach (self::EXEMPT as $prefix) {
            if (str_starts_with($request->path(), $prefix)) return null;
        }
        $csrf = $this->container->make('csrf');
        if (!$csrf->verifyRequest($request)) {
            $response = new Response();
            if ($request->wantsJson() || $request->isAjax()) {
                return $response->json(['error' => 'CSRF token mismatch'], 419);
            }
            return $response->html('<h1>Session expired. Please go back and try again.</h1>', 419);
        }
        return null;
    }
}
