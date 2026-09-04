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

    /**
     * Instantiate a class by auto-wiring its constructor from registered instances.
     * Resolves parameters by type-hint; looks up short class name in instances first.
     */
    public function build(string $class): object
    {
        $ref = new \ReflectionClass($class);
        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                // Try full class name first, then short name lowercased
                $short = lcfirst(basename(str_replace('\\', '/', $typeName)));
                if (isset($this->instances[$typeName])) {
                    $args[] = $this->instances[$typeName];
                } elseif (isset($this->instances[$short])) {
                    $args[] = $this->instances[$short];
                } elseif (isset($this->bindings[$typeName])) {
                    $args[] = ($this->bindings[$typeName])($this);
                } elseif ($param->isOptional()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \RuntimeException(
                        "Cannot auto-wire [{$typeName}] for [{$class}::\${$param->getName()}]"
                    );
                }
            } elseif ($param->isOptional()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(
                    "Cannot resolve non-typed parameter [{$param->getName()}] for [{$class}]"
                );
            }
        }
        return $ref->newInstanceArgs($args);
    }
}
