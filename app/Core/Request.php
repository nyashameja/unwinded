<?php
declare(strict_types=1);

namespace Unwinded\Core;

class Request
{
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private array $cookies;
    private ?string $body;

    public function __construct()
    {
        $this->get     = $_GET    ?? [];
        $this->post    = $_POST   ?? [];
        $this->files   = $_FILES  ?? [];
        $this->server  = $_SERVER ?? [];
        $this->cookies = $_COOKIE ?? [];
        $this->body    = null;
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        // Allow method override via _method POST field (for PUT/PATCH/DELETE from HTML forms)
        if ($method === 'POST' && isset($this->post['_method'])) {
            $override = strtoupper($this->post['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return $method;
    }

    public function isGet(): bool    { return $this->method() === 'GET'; }
    public function isPost(): bool   { return $this->method() === 'POST'; }
    public function isPut(): bool    { return $this->method() === 'PUT'; }
    public function isPatch(): bool  { return $this->method() === 'PATCH'; }
    public function isDelete(): bool { return $this->method() === 'DELETE'; }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    public function path(): string
    {
        return '/' . trim($this->uri(), '/');
    }

    public function query(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->get;
        return $this->get[$key] ?? $default;
    }

    public function input(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) return $this->post;
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        return ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) ? $f : null;
    }

    public function hasFile(string $key): bool
    {
        return $this->file($key) !== null;
    }

    public function ip(): string
    {
        // Trust X-Forwarded-For only if configured (set via config or env).
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', 'application/json');
    }

    public function body(): string
    {
        return $this->body ??= (string) file_get_contents('php://input');
    }

    public function json(): ?array
    {
        return json_decode($this->body(), true);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /** Trim and return a string input, or null if blank. */
    public function str(string $key): ?string
    {
        $val = trim((string) ($this->post[$key] ?? $this->get[$key] ?? ''));
        return $val !== '' ? $val : null;
    }

    /** Return an int input, or null if absent/non-numeric. */
    public function int(string $key): ?int
    {
        $val = $this->post[$key] ?? $this->get[$key] ?? null;
        return is_numeric($val) ? (int) $val : null;
    }
}
