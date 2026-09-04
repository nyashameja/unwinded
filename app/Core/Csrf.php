<?php
declare(strict_types=1);

namespace Unwinded\Core;

class Csrf
{
    private const KEY = '_csrf_token';

    public function __construct(private Session $session) {}

    public function token(): string
    {
        if (!$this->session->has(self::KEY)) {
            $this->session->set(self::KEY, bin2hex(random_bytes(32)));
        }
        return $this->session->get(self::KEY);
    }

    public function verify(string $token): bool
    {
        return hash_equals($this->token(), $token);
    }

    public function verifyRequest(Request $request): bool
    {
        $token = $request->input('_csrf_token')
              ?? $request->header('X-CSRF-Token')
              ?? '';
        return $this->verify($token);
    }

    public function regenerate(): void
    {
        $this->session->set(self::KEY, bin2hex(random_bytes(32)));
    }
}
