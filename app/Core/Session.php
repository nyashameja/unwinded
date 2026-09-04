<?php
declare(strict_types=1);

namespace Unwinded\Core;

class Session
{
    private bool $started = false;

    public function __construct(
        private string $savePath,
        private bool   $secure = true,
        private string $cookieName = 'unwinded_session'
    ) {}

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0700, true);
        }
        session_save_path($this->savePath);
        session_name($this->cookieName);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        $this->started = true;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public function getFlash(string $type): array
    {
        $msgs = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $msgs;
    }

    /** Return all flash messages grouped by type and clear them. */
    public function getAll(): array
    {
        $all = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $all;
    }

    public function destroy(): void
    {
        session_unset();
        session_destroy();
        $this->started = false;
    }

    public function id(): string
    {
        return session_id();
    }
}
