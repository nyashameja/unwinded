<?php
declare(strict_types=1);

namespace Unwinded\Core;

use Closure;

class Container
{
    private static self $instance;
    private array $bindings  = [];
    private array $instances = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function instance(string $abstract, mixed $concrete): void
    {
        $this->instances[$abstract] = $concrete;
    }

    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bind($abstract, function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        });
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }
        throw new \RuntimeException("No binding registered for [{$abstract}]");
    }
}
