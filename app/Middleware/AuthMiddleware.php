<?php
declare(strict_types=1);

namespace Unwinded\Middleware;

use Unwinded\Core\{Container, Request, Response};

class AuthMiddleware
{
    public function __construct(private Container $container) {}

    public function handle(Request $request): ?Response
    {
        $auth = $this->container->make('auth');
        if (!$auth->check()) {
            $response = new Response();
            return $response->redirect('/admin/login');
        }
        return null;
    }
}
