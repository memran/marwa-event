<?php

declare(strict_types=1);

namespace Marwa\Event\Bus;

use InvalidArgumentException;
use Marwa\Event\Contracts\Subscriber;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * A thin, fluent facade-like API resembling Laravel's Event facade.
 * Compose this in your container and bind as a singleton.
 *
 * @phpstan-type ListenerDefinition callable|string|array<int, mixed>
 */
final class EventBus
{
    public function __construct(
        private readonly ListenerProvider $provider,
        private readonly EventDispatcher $dispatcher,
        private readonly ?ContainerInterface $container = null
    ) {}

    /**
     * Register a listener.
     *
     * @param ListenerDefinition $listener
     */
    public function listen(string $eventClass, callable|string|array $listener, int $priority = 0): int
    {
        return $this->provider->addListener($eventClass, $listener, $priority);
    }

    /** Remove a listener by id. */
    public function forget(int $id): bool
    {
        return $this->provider->remove($id);
    }

    /** Register a subscriber object or FQCN. */
    public function subscribe(Subscriber|string $subscriber): void
    {
        $instance = is_string($subscriber) ? $this->instantiateSubscriber($subscriber) : $subscriber;
        $map = $instance::getSubscribedEvents();

        foreach ($map as $eventClass => $definition) {
            if (is_string($definition)) {
                $this->listen($eventClass, [$instance, $definition]);
                continue;
            }

            // Single handler with priority or array of handlers
            if (isset($definition[0]) && is_string($definition[0]) && $definition[0] !== '') {
                [$method, $priority] = [$definition[0], $definition[1] ?? 0];
                $this->listen($eventClass, [$instance, $method], (int)$priority);
                continue;
            }

            // Multiple handlers for same event
            foreach ($definition as $entry) {
                if (!is_array($entry) || !isset($entry[0]) || !is_string($entry[0]) || $entry[0] === '') {
                    throw new InvalidArgumentException(
                        "Subscriber definition for '{$eventClass}' must contain method/priority pairs."
                    );
                }

                [$method, $priority] = [$entry[0], $entry[1] ?? 0];
                $this->listen($eventClass, [$instance, $method], (int)$priority);
            }
        }
    }

    /** Dispatch an event object. */
    public function dispatch(object $event): object
    {
        return $this->dispatcher->dispatch($event);
    }

    private function instantiateSubscriber(string $subscriber): Subscriber
    {
        if ($this->container !== null && $this->container->has($subscriber)) {
            $resolved = $this->container->get($subscriber);

            if (!$resolved instanceof Subscriber) {
                throw new InvalidArgumentException("Subscriber class '{$subscriber}' must implement Subscriber.");
            }

            return $resolved;
        }

        if (!class_exists($subscriber)) {
            throw new InvalidArgumentException("Subscriber class '{$subscriber}' does not exist.");
        }

        try {
            $instance = new $subscriber();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                "Subscriber class '{$subscriber}' could not be instantiated without arguments.",
                0,
                $exception
            );
        }

        if (!$instance instanceof Subscriber) {
            throw new InvalidArgumentException("Subscriber class '{$subscriber}' must implement Subscriber.");
        }

        return $instance;
    }
}
