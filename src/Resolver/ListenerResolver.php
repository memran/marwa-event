<?php

namespace Marwa\Event\Resolver;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * Resolves various listener notations to callables with minimal overhead.
 * Supported:
 *  - callable
 *  - "Class@method"
 *  - ["Class", "method"]
 *  - "Class" (invokable)
 */
final class ListenerResolver
{
    public function __construct(
        private readonly ?ContainerInterface $container = null
    ) {}

    /**
     * @param callable|string|array $listener
     * @return callable
     */
    public function resolve(callable|string|array $listener): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        if (is_string($listener)) {
            if (str_contains($listener, '@')) {
                [$class, $method] = explode('@', $listener, 2);
                $instance = $this->make($class);
                return [$instance, $method];
            }

            // "Class" (invokable)
            $instance = $this->make($listener);
            if (!is_callable($instance)) {
                throw new InvalidArgumentException("Listener '{$listener}' is not invokable.");
            }
            return $instance;
        }

        // ["Class", "method"]
        if (is_array($listener) && count($listener) === 2) {
            [$class, $method] = $listener;
            $instance = is_string($class) ? $this->make($class) : $class;
            return [$instance, $method];
        }

        throw new InvalidArgumentException('Unsupported listener type.');
    }

    private function make(string $id): object
    {
        if ($this->container && $this->container->has($id)) {
            return $this->container->get($id);
        }

        // Fast path: avoid reflection-heavy DI; assume no-arg constructor.
        return new $id();
    }
}
