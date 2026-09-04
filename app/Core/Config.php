<?php
declare(strict_types=1);

namespace Unwinded\Core;

class Config
{
    private array $data = [];
    private string $path;

    public function __construct(string $configPath)
    {
        $this->path = rtrim($configPath, '/');
        foreach (glob($this->path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->data[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $group = $this->data[$parts[0]] ?? null;
        if ($group === null) {
            return $default;
        }
        if (!isset($parts[1])) {
            return $group;
        }
        return $this->resolve($group, $parts[1], $default);
    }

    private function resolve(array $array, string $key, mixed $default): mixed
    {
        $parts = explode('.', $key, 2);
        if (!array_key_exists($parts[0], $array)) {
            return $default;
        }
        if (!isset($parts[1])) {
            return $array[$parts[0]];
        }
        if (!is_array($array[$parts[0]])) {
            return $default;
        }
        return $this->resolve($array[$parts[0]], $parts[1], $default);
    }

    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key, 2);
        if (!isset($parts[1])) {
            $this->data[$parts[0]] = $value;
        } else {
            $this->data[$parts[0]] ??= [];
            $this->setNested($this->data[$parts[0]], $parts[1], $value);
        }
    }

    private function setNested(array &$array, string $key, mixed $value): void
    {
        $parts = explode('.', $key, 2);
        if (!isset($parts[1])) {
            $array[$parts[0]] = $value;
        } else {
            $array[$parts[0]] ??= [];
            $this->setNested($array[$parts[0]], $parts[1], $value);
        }
    }
}
