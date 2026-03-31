<?php

declare(strict_types=1);

namespace Marwa\Event\Resolver;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Resolves various listener notations to callables with minimal overhead.
 * Supported:
 *  - callable
 *  - "Class@method"
 *  - ["Class", "method"]
 *  - "Class" (invokable)
 *
 * @phpstan-type ListenerDefinition callable|string|array<int, mixed>
 */
final class ListenerResolver
{
    public function __construct(
        private readonly ?ContainerInterface $container = null
    ) {}

    /**
     * @param ListenerDefinition $listener
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
                return $this->toCallable($instance, $method, $listener);
            }

            // "Class" (invokable)
            $instance = $this->make($listener);
            if (!is_callable($instance)) {
                throw new InvalidArgumentException("Listener '{$listener}' is not invokable.");
            }
            return $instance;
        }

        // ["Class", "method"]
        if (count($listener) === 2) {
            [$class, $method] = $listener;
            $instance = is_string($class) ? $this->make($class) : $class;

            if (!is_object($instance) || !is_string($method) || $method === '') {
                throw new InvalidArgumentException('Array listeners must be [object|string, non-empty-string].');
            }

            return $this->toCallable($instance, $method, sprintf('%s::%s', $instance::class, $method));
        }

        throw new InvalidArgumentException('Unsupported listener type.');
    }

    private function make(string $id): object
    {
        if ($this->container && $this->container->has($id)) {
            $resolved = $this->container->get($id);
            if (!is_object($resolved)) {
                throw new InvalidArgumentException("Container entry '{$id}' must resolve to an object.");
            }

            return $resolved;
        }

        if (!class_exists($id)) {
            throw new InvalidArgumentException("Listener class '{$id}' does not exist.");
        }

        try {
            // Fast path: avoid reflection-heavy DI; assume no-arg constructor.
            return new $id();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                "Listener class '{$id}' could not be instantiated without arguments.",
                0,
                $exception
            );
        }
    }

    private function toCallable(object $instance, string $method, string $label): callable
    {
        if (!is_callable([$instance, $method])) {
            throw new InvalidArgumentException("Listener '{$label}' is not callable.");
        }

        /** @var callable */
        return [$instance, $method];
    }
}
