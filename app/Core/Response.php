<?php
declare(strict_types=1);

namespace Unwinded\Core;

class Response
{
    private int    $status  = 200;
    private array  $headers = [];
    private string $body    = '';

    public static function make(): static
    {
        return new static();
    }

    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function html(string $content, int $status = 200): static
    {
        return $this->status($status)
                    ->header('Content-Type', 'text/html; charset=utf-8')
                    ->body($content);
    }

    public function json(mixed $data, int $status = 200): static
    {
        return $this->status($status)
                    ->header('Content-Type', 'application/json')
                    ->body(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function body(string $content): static
    {
        $this->body = $content;
        return $this;
    }

    public function redirect(string $url, int $status = 302): static
    {
        return $this->status($status)->header('Location', $url);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
